<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * ProductController
 *
 * GRACIAS al TenantScope registrado en el modelo Product,
 * TODAS las queries aquí ya están filtradas por tenant_id automáticamente.
 *
 * No necesitas escribir ->where('tenant_id', $user->tenant_id) en cada método.
 * El GlobalScope lo hace por ti de forma transparente.
 *
 * Excepción: al CREAR un producto, sí debes asignar el tenant_id explícitamente.
 */
class ProductController extends Controller
{
    /**
     * GET /api/products
     *
     * Lista productos del tenant autenticado.
     * (TenantScope filtra automáticamente por tenant_id)
     *
     * Parámetros:
     *   ?search=nombre_o_sku
     *   ?category=electronica
     *   ?active=true
     *   ?low_stock=true          (solo productos con stock bajo)
     *   ?sort=name|stock_current|price|created_at
     *   ?direction=asc|desc
     *   ?per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        // Product::query() ya tiene el WHERE tenant_id = ? por TenantScope
        $query = Product::query();

        // ─── Filtros ─────────────────────────────────────────────────────

        if ($search = $request->get('search')) {
            $query->search($search); // scope definido en el modelo
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($request->has('active')) {
            $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock(); // scope: stock_current <= stock_minimum
        }

        // ─── Ordenamiento ────────────────────────────────────────────────

        $sortableColumns = ['name', 'sku', 'stock_current', 'price', 'cost', 'created_at'];
        $sort      = in_array($request->get('sort'), $sortableColumns) ? $request->get('sort') : 'name';
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $direction);

        // ─── Paginación ──────────────────────────────────────────────────

        $products = $query->paginate($request->get('per_page', 15));

        // En lugar de devolver la colección directamente, construimos una JsonResponse
        // para cumplir con el tipo de retorno del método y mantener la consistencia de la API.
        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/products
     *
     * Crea un nuevo producto en el tenant del usuario autenticado.
     *
     * IMPORTANTE: aunque TenantScope filtra las lecturas,
     * al CREAR debemos asignar tenant_id explícitamente.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $user = $request->user();

        // Para super_admin: puede crear producto en cualquier tenant
        // Para usuarios de empresa: su propio tenant_id
        $tenantId = $user->isSuperAdmin()
            ? $request->tenant_id
            : $user->tenant_id;

        $product = Product::create([
            'id'            => Str::uuid(),
            'tenant_id'     => $tenantId,
            'name'          => $request->name,
            'sku'           => $request->sku,
            'barcode'       => $request->barcode,
            'category'      => $request->category,
            'description'   => $request->description,
            'stock_current' => $request->stock_initial ?? 0, // Stock inicial al crear
            'stock_minimum' => $request->stock_minimum ?? 5,
            'unit'          => $request->unit ?? 'unidad',
            'cost'          => $request->cost ?? 0,
            'price'         => $request->price ?? 0,
            'supplier'      => $request->supplier,
            'active'        => true,
        ]);

        // Si se define un stock inicial > 0, crear el primer movimiento de entrada
        if (($request->stock_initial ?? 0) > 0) {
            $product->inventoryMovements()->create([
                'id'           => Str::uuid(),
                'product_id'   => $product->id, // <-- AÑADIR ESTA LÍNEA
                'tenant_id'    => $tenantId,
                'user_id'      => $user->id,
                'type'         => 'entrada',
                'quantity'     => $request->stock_initial,
                'stock_before' => 0,
                'stock_after'  => $request->stock_initial,
                'unit_cost'    => $request->cost ?? 0,
                'note'         => 'Stock inicial al crear el producto.',
                'moved_at'     => now(),
            ]);
        }

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'data'    => new ProductResource($product),
        ], 201);
    }

    /**
     * POST /api/products/bulk
     *
     * Alta masiva de productos (hoja de carga rápida e importación CSV).
     *
     * Valida y crea fila por fila: una fila inválida NO tumba el resto.
     * Devuelve un resultado por índice para que el frontend pueda marcar
     * exactamente qué filas quedaron pendientes de corregir.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = $request->validate([
            'items'             => ['required', 'array', 'min:1', 'max:500'],
            'items.*'           => ['array'],
            'tenant_id'         => [$user->isSuperAdmin() ? 'required' : 'prohibited', 'exists:tenants,id'],
        ]);

        $tenantId = $user->isSuperAdmin() ? $request->tenant_id : $user->tenant_id;

        $results = [];
        $created = 0;

        // SKUs ya usados en este tenant, para detectar choques sin una query por fila
        $existingSkus = Product::withTrashed()
            ->where('tenant_id', $tenantId)
            ->pluck('sku')
            ->map(fn ($sku) => mb_strtolower($sku))
            ->flip();

        foreach ($payload['items'] as $index => $item) {
            $validator = Validator::make($item, [
                'name'          => ['required', 'string', 'max:200'],
                'sku'           => ['required', 'string', 'max:100'],
                'barcode'       => ['nullable', 'string', 'max:100'],
                'category'      => ['nullable', 'string', 'max:100'],
                'description'   => ['nullable', 'string'],
                'stock_initial' => ['nullable', 'integer', 'min:0'],
                'stock_minimum' => ['nullable', 'integer', 'min:0'],
                'unit'          => ['nullable', 'string', 'max:30'],
                'cost'          => ['nullable', 'numeric', 'min:0'],
                'price'         => ['nullable', 'numeric', 'min:0'],
                'supplier'      => ['nullable', 'string', 'max:200'],
            ]);

            if ($validator->fails()) {
                $results[] = [
                    'index'  => $index,
                    'ok'     => false,
                    'error'  => $validator->errors()->first(),
                    'errors' => $validator->errors()->toArray(),
                ];
                continue;
            }

            $data   = $validator->validated();
            $skuKey = mb_strtolower($data['sku']);

            if ($existingSkus->has($skuKey)) {
                $results[] = [
                    'index' => $index,
                    'ok'    => false,
                    'error' => "Ya existe un producto con el SKU '{$data['sku']}'.",
                ];
                continue;
            }

            try {
                $product = DB::transaction(function () use ($data, $tenantId, $user) {
                    $stockInitial = (int) ($data['stock_initial'] ?? 0);

                    $product = Product::create([
                        'id'            => Str::uuid(),
                        'tenant_id'     => $tenantId,
                        'name'          => $data['name'],
                        'sku'           => $data['sku'],
                        'barcode'       => $data['barcode']     ?? null,
                        'category'      => $data['category']    ?? null,
                        'description'   => $data['description'] ?? null,
                        'stock_current' => $stockInitial,
                        'stock_minimum' => (int) ($data['stock_minimum'] ?? 5),
                        'unit'          => $data['unit']  ?? 'unidad',
                        'cost'          => $data['cost']  ?? 0,
                        'price'         => $data['price'] ?? 0,
                        'supplier'      => $data['supplier'] ?? null,
                        'active'        => true,
                    ]);

                    if ($stockInitial > 0) {
                        $product->inventoryMovements()->create([
                            'id'           => Str::uuid(),
                            'product_id'   => $product->id,
                            'tenant_id'    => $tenantId,
                            'user_id'      => $user->id,
                            'type'         => 'entrada',
                            'quantity'     => $stockInitial,
                            'stock_before' => 0,
                            'stock_after'  => $stockInitial,
                            'unit_cost'    => $data['cost'] ?? 0,
                            'note'         => 'Stock inicial (carga masiva).',
                            'moved_at'     => now(),
                        ]);
                    }

                    return $product;
                });

                $existingSkus->put($skuKey, true);
                $created++;

                $results[] = [
                    'index' => $index,
                    'ok'    => true,
                    'data'  => new ProductResource($product),
                ];
            } catch (\Throwable $e) {
                report($e);
                $results[] = [
                    'index' => $index,
                    'ok'    => false,
                    'error' => 'No se pudo guardar esta fila.',
                ];
            }
        }

        $failed = count($results) - $created;

        return response()->json([
            'message' => $failed === 0
                ? "Se crearon {$created} productos."
                : "Se crearon {$created} productos y {$failed} filas quedaron con errores.",
            'created' => $created,
            'failed'  => $failed,
            'results' => $results,
        ], $created > 0 ? 201 : 422);
    }

    /**
     * GET /api/products/{id}
     *
     * Muestra un producto.
     * TenantScope garantiza que solo puedes ver productos de tu tenant.
     */
    public function show(string $id): JsonResponse
    {
        // findOrFail + TenantScope: si el producto no existe en tu tenant → 404
        // Esto previene el acceso cruzado entre tenants automáticamente.
        $product = Product::with(['inventoryMovements' => function ($q) {
            $q->latest('moved_at')->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * PUT /api/products/{id}
     *
     * Actualiza un producto.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        // TenantScope: si el producto no pertenece al tenant → 404 automático
        $product = Product::findOrFail($id);

        $product->update($request->validated());

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data'    => new ProductResource($product->fresh()),
        ]);
    }

    /**
     * DELETE /api/products/{id}
     *
     * Soft delete del producto.
     *
     * SOFT DELETE elegido (no hard delete) porque:
     * - Los movimientos de inventario referencian al producto_id
     * - Eliminar físicamente rompería el histórico de movimientos
     * - El producto eliminado deja de aparecer en operaciones
     * - Pero sus movimientos históricos siguen siendo auditables
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->delete(); // SoftDelete: setea deleted_at, no borra el registro

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    /**
     * GET /api/products/categories
     *
     * Lista las categorías únicas de productos del tenant.
     * Útil para el filtro en el frontend.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = Product::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'data' => $categories,
        ]);
    }
}
