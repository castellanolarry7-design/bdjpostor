<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Movement\StoreMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * InventoryMovementController
 *
 * Gestión de movimientos de inventario.
 *
 * Regla crítica: al crear un movimiento, el stock del producto
 * se actualiza en la MISMA transacción de base de datos.
 * Esto garantiza consistencia: no puede existir un movimiento
 * sin que el stock se haya actualizado, ni viceversa.
 *
 * Los movimientos son INMUTABLES: no hay update() ni destroy().
 * Para corregir un error, se crea un movimiento de ajuste.
 */
class InventoryMovementController extends Controller
{
    /**
     * GET /api/inventory-movements
     *
     * Lista movimientos del tenant autenticado.
     * (TenantScope filtra automáticamente)
     *
     * Parámetros:
     *   ?product_id=uuid
     *   ?type=entrada|salida|ajuste
     *   ?from=2024-01-01
     *   ?to=2024-12-31
     *   ?per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['product', 'user'])
                                  ->orderBy('moved_at', 'desc');

        // Filtro por producto
        if ($productId = $request->get('product_id')) {
            $query->where('product_id', $productId);
        }

        // Filtro por tipo de movimiento
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filtro por rango de fechas (scope en el modelo)
        $query->byDateRange($request->get('from'), $request->get('to'));

        $movements = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => InventoryMovementResource::collection($movements->items()),
            'meta' => [
                'total'        => $movements->total(),
                'per_page'     => $movements->perPage(),
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/inventory-movements
     *
     * Crea un movimiento de inventario y actualiza el stock del producto.
     *
     * ─── FLUJO ATÓMICO ────────────────────────────────────────────────────
     *
     * 1. Iniciar transacción DB
     * 2. Bloquear el producto con lockForUpdate() — evita race conditions
     *    si dos requests simultáneos intentan modificar el mismo producto
     * 3. Calcular stock_before y stock_after
     * 4. Validar que no quede stock negativo en salidas
     * 5. Crear el movimiento
     * 6. Actualizar products.stock_current
     * 7. Commit — ambas operaciones se consolidan juntas
     *
     * Si CUALQUIER paso falla → rollback automático.
     */
    public function store(StoreMovementRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verificar que el producto pertenece al tenant del usuario
        // (TenantScope en Product garantiza esto automáticamente)
        $product = Product::findOrFail($request->product_id);

        // Verificar que el producto está activo
        if (! $product->active) {
            return response()->json([
                'message' => 'No se pueden registrar movimientos de un producto inactivo.',
            ], 422);
        }

        // ─── Transacción atómica ──────────────────────────────────────────
        try {
        $movement = DB::transaction(function () use ($request, $user, $product) {

            // lockForUpdate(): bloquea el row del producto hasta que termine
            // la transacción. Evita que dos requests simultáneos lean el mismo
            // stock y ambos lo actualicen de forma incorrecta.
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $stockBefore = $product->stock_current;
            $quantity    = $request->quantity;

            // ─── Calcular el nuevo stock según el tipo de movimiento ──────

            $stockAfter = match($request->type) {
                'entrada' => $stockBefore + $quantity,
                'salida'  => $stockBefore - $quantity,
                'ajuste'  => $quantity, // En ajuste, quantity ES el nuevo stock total
            };

            // Validar que el stock no quede negativo
            if ($stockAfter < 0) {
                // Esta excepción hará rollback automático
                throw new \InvalidArgumentException(
                    "Stock insuficiente. Stock actual: {$stockBefore}, cantidad solicitada: {$quantity}."
                );
            }

            // ─── Crear el movimiento ──────────────────────────────────────

            $movement = InventoryMovement::create([
                'id'           => Str::uuid(),
                'tenant_id'    => $product->tenant_id,
                'product_id'   => $product->id,
                'user_id'      => $user->id,
                'type'         => $request->type,
                'quantity'     => $quantity,
                'stock_before' => $stockBefore,
                'stock_after'  => $stockAfter,
                'unit_cost'    => $request->unit_cost ?? $product->cost,
                'unit_price'   => $request->unit_price ?? $product->price,
                'note'         => $request->note,
                'reference'    => $request->reference,
                'moved_at'     => $request->moved_at ?? now(),
            ]);

            // ─── Actualizar el stock del producto ─────────────────────────
            // update() directo en lugar de save() para evitar disparar
            // otros eventos del modelo innecesariamente

            $product->update(['stock_current' => $stockAfter]);

            return $movement;
        });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Movimiento registrado correctamente.',
            'data'    => new InventoryMovementResource($movement->load(['product', 'user'])),
        ], 201);
    }

    /**
     * GET /api/inventory-movements/{id}
     *
     * Muestra el detalle de un movimiento.
     */
    public function show(string $id): JsonResponse
    {
        $movement = InventoryMovement::with(['product', 'user', 'tenant'])
                                     ->findOrFail($id);

        return response()->json([
            'data' => new InventoryMovementResource($movement),
        ]);
    }

    /**
     * GET /api/inventory-movements/export
     *
     * Devuelve hasta 1000 movimientos sin paginación para exportación CSV/PDF.
     */
    public function export(Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['product:id,name,sku', 'user:id,name'])
                                  ->orderBy('moved_at', 'desc');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $query->byDateRange($request->get('from'), $request->get('to'));

        $movements = $query->limit(1000)->get();

        return response()->json([
            'data' => $movements->map(fn($m) => [
                'date'         => $m->moved_at?->format('Y-m-d H:i'),
                'type'         => $m->type,
                'product_name' => $m->product?->name,
                'product_sku'  => $m->product?->sku,
                'quantity'     => $m->quantity,
                'stock_before' => $m->stock_before,
                'stock_after'  => $m->stock_after,
                'unit_cost'    => $m->unit_cost,
                'unit_price'   => $m->unit_price,
                'reference'    => $m->reference,
                'note'         => $m->note,
                'user_name'    => $m->user?->name,
            ]),
            'meta' => ['count' => $movements->count()],
        ]);
    }

    // ─── NOTA: No existe update() ni destroy() ───────────────────────────
    // Los movimientos de inventario son registros contables inmutables.
    // Para corregir un error:
    //   - Si fue una entrada incorrecta → crear una salida de ajuste
    //   - Si fue una salida incorrecta  → crear una entrada de ajuste
    //   - Para corrección directa de stock → crear un movimiento tipo 'ajuste'
}
