<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    /**
     * GET /api/v1/pos/scan/{code}
     *
     * Busca un producto por código de barras o SKU.
     */
    public function scanProduct(string $code): JsonResponse
    {
        $product = Product::active()
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)
                  ->orWhere('sku', $code);
            })
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        if ($product->stock_current <= 0) {
            return response()->json([
                'message' => "'{$product->name}' no tiene stock disponible.",
                'product' => $product,
            ], 422);
        }

        return response()->json([
            'data' => [
                'id'            => $product->id,
                'name'          => $product->name,
                'sku'           => $product->sku,
                'barcode'       => $product->barcode,
                'price'         => (float) $product->price,
                'stock_current' => $product->stock_current,
                'unit'          => $product->unit,
            ],
        ]);
    }

    /**
     * POST /api/v1/pos/sales
     *
     * Procesa una venta: descuenta stock y registra la transacción.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'string'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.unit_price'        => ['required', 'numeric', 'min:0'],
            'payments'                  => ['required', 'array', 'min:1'],
            'payments.*.method'         => ['required', 'string'],
            'payments.*.currency'       => ['required', 'string'],
            'payments.*.amount'         => ['required', 'numeric', 'min:0'],
            'payments.*.amount_usd'     => ['required', 'numeric', 'min:0'],
            'payments.*.exchange_rate'  => ['required', 'numeric', 'min:0'],
            'notes'                     => ['nullable', 'string', 'max:500'],
        ]);

        $user       = $request->user();
        $tenantId   = $user->tenant_id;
        $sale       = null;

        try {
            DB::transaction(function () use ($validated, $user, $tenantId, &$sale) {

                // Generar número de venta único
                $date       = now()->format('ymd');
                $count      = Sale::whereDate('sold_at', today())->count();
                $saleNumber = 'JS-' . $date . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

                // Procesar cada item
                $subtotal  = 0;
                $saleItems = [];

                foreach ($validated['items'] as $itemData) {
                    $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

                    if ($product->stock_current < $itemData['quantity']) {
                        throw new \Exception("Stock insuficiente para '{$product->name}'. Disponible: {$product->stock_current}");
                    }

                    $itemSubtotal = round($itemData['unit_price'] * $itemData['quantity'], 2);
                    $subtotal    += $itemSubtotal;

                    $stockBefore = $product->stock_current;
                    $stockAfter  = $stockBefore - $itemData['quantity'];
                    $product->update(['stock_current' => $stockAfter]);

                    // Movimiento de inventario
                    InventoryMovement::create([
                        'id'           => Str::uuid(),
                        'tenant_id'    => $tenantId,
                        'product_id'   => $product->id,
                        'user_id'      => $user->id,
                        'type'         => 'salida',
                        'quantity'     => $itemData['quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after'  => $stockAfter,
                        'unit_price'   => $itemData['unit_price'],
                        'note'         => "Venta POS #{$saleNumber}",
                        'reference'    => $saleNumber,
                        'moved_at'     => now(),
                    ]);

                    $saleItems[] = [
                        'product'    => $product,
                        'quantity'   => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal'   => $itemSubtotal,
                    ];
                }

                // Totales de pago
                $totalReceivedUsd = collect($validated['payments'])->sum('amount_usd');
                $changeAmount     = max(0, round($totalReceivedUsd - $subtotal, 2));

                // Crear venta
                $sale = Sale::create([
                    'id'                 => Str::uuid(),
                    'tenant_id'          => $tenantId,
                    'user_id'            => $user->id,
                    'sale_number'        => $saleNumber,
                    'subtotal'           => $subtotal,
                    'tax'                => 0,
                    'total'              => $subtotal,
                    'payments'           => $validated['payments'],
                    'total_received_usd' => $totalReceivedUsd,
                    'change_amount'      => $changeAmount,
                    'base_currency'      => 'USD',
                    'notes'              => $validated['notes'] ?? null,
                    'status'             => 'completed',
                    'sold_at'            => now(),
                ]);

                // Crear items de la venta
                foreach ($saleItems as $item) {
                    SaleItem::create([
                        'id'           => Str::uuid(),
                        'sale_id'      => $sale->id,
                        'product_id'   => $item['product']->id,
                        'product_name' => $item['product']->name,
                        'product_sku'  => $item['product']->sku,
                        'quantity'     => $item['quantity'],
                        'unit_price'   => $item['unit_price'],
                        'subtotal'     => $item['subtotal'],
                    ]);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Venta registrada correctamente.',
            'data'    => $sale->load('items'),
        ], 201);
    }

    /**
     * GET /api/v1/pos/sales/{id}
     *
     * Devuelve una venta con sus items (para recibo).
     */
    public function show(string $id): JsonResponse
    {
        $sale = Sale::with('items')->findOrFail($id);

        return response()->json(['data' => $sale]);
    }

    /**
     * GET /api/v1/pos/sales
     *
     * Lista de ventas con filtros (fecha, usuario). Para admin/owner.
     */
    public function salesList(Request $request): JsonResponse
    {
        $query = Sale::where('status', 'completed')
            ->with('user:id,name,role')
            ->with('items');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->from) {
            $query->where('sold_at', '>=', $request->from);
        }
        if ($request->to) {
            $query->where('sold_at', '<=', $request->to . ' 23:59:59');
        }

        $sales = $query->orderByDesc('sold_at')->paginate(20);

        return response()->json([
            'data' => $sales->map(fn($s) => [
                'id'          => $s->id,
                'sale_number' => $s->sale_number,
                'total'       => $s->total,
                'payments'    => $s->payments,
                'user_id'     => $s->user_id,
                'user_name'   => $s->user?->name,
                'user_role'   => $s->user?->role,
                'items_count' => $s->items->count(),
                'sold_at'     => $s->sold_at?->toISOString(),
            ]),
            'meta' => [
                'total'        => $sales->total(),
                'current_page' => $sales->currentPage(),
                'last_page'    => $sales->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/pos/sales/{id}/cancel
     *
     * Anula una venta completada:
     * - Cambia status a 'voided'
     * - Restaura el stock de cada item
     * - Crea movimientos de inventario de entrada (devolución)
     */
    public function cancel(string $id, Request $request): JsonResponse
    {
        $sale = Sale::with('items')->findOrFail($id);

        if ($sale->status !== 'completed') {
            return response()->json(['message' => 'Solo se pueden anular ventas completadas.'], 422);
        }

        $user = $request->user();

        try {
            DB::transaction(function () use ($sale, $user) {
                foreach ($sale->items as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);

                    if ($product) {
                        $stockBefore = $product->stock_current;
                        $stockAfter  = $stockBefore + $item->quantity;

                        $product->update(['stock_current' => $stockAfter]);

                        InventoryMovement::create([
                            'id'           => Str::uuid(),
                            'tenant_id'    => $sale->tenant_id,
                            'product_id'   => $item->product_id,
                            'user_id'      => $user->id,
                            'type'         => 'entrada',
                            'quantity'     => $item->quantity,
                            'stock_before' => $stockBefore,
                            'stock_after'  => $stockAfter,
                            'unit_price'   => $item->unit_price,
                            'note'         => "Devolución — Venta anulada #{$sale->sale_number}",
                            'reference'    => $sale->sale_number,
                            'moved_at'     => now(),
                        ]);
                    }
                }

                $sale->update(['status' => 'voided']);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Venta {$sale->sale_number} anulada correctamente. Stock restaurado.",
            'data'    => $sale->fresh()->load('items'),
        ]);
    }

    /**
     * GET /api/v1/pos/sales/export
     *
     * Exporta todas las ventas completadas del período (sin paginación).
     * Máximo 1000 registros para proteger el servidor.
     */
    public function exportSales(Request $request): JsonResponse
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $sales = Sale::where('status', 'completed')
            ->with(['user:id,name', 'items'])
            ->where('sold_at', '>=', $from)
            ->where('sold_at', '<=', $to . ' 23:59:59')
            ->orderByDesc('sold_at')
            ->limit(1000)
            ->get();

        return response()->json([
            'data' => $sales->map(fn($s) => [
                'sale_number'  => $s->sale_number,
                'total'        => $s->total,
                'subtotal'     => $s->subtotal,
                'payments'     => $s->payments,
                'user_name'    => $s->user?->name,
                'items_count'  => $s->items->count(),
                'items'        => $s->items->map(fn($i) => [
                    'product_name' => $i->product_name,
                    'product_sku'  => $i->product_sku,
                    'quantity'     => $i->quantity,
                    'unit_price'   => $i->unit_price,
                    'subtotal'     => $i->subtotal,
                ]),
                'sold_at'      => $s->sold_at?->toISOString(),
            ]),
            'meta' => [
                'from'  => $from,
                'to'    => $to,
                'count' => $sales->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/pos/stats/users
     *
     * Estadísticas de ventas agrupadas por usuario (para el panel del dueño).
     */
    public function statsByUser(Request $request): JsonResponse
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $stats = Sale::where('status', 'completed')
            ->where('sold_at', '>=', $from)
            ->where('sold_at', '<=', $to . ' 23:59:59')
            ->select('user_id', DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(total) as total_revenue'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return response()->json(['data' => $stats]);
    }
}
