<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin() && $request->has('tenant_id')) {
            app()->instance('current_tenant_id', $request->tenant_id);
        }

        // ─── Métricas de productos ─────────────────────────────────────
        $totalProducts       = Product::active()->count();
        $totalStock          = Product::active()->sum('stock_current');
        $totalInventoryValue = Product::active()->sum(DB::raw('stock_current * cost'));
        $lowStockProducts    = Product::active()->lowStock()->orderBy('stock_current')->limit(10)->get();
        $outOfStockCount     = Product::active()->where('stock_current', 0)->count();

        // ─── Métricas de movimientos (últimos 30 días) ─────────────────
        $thirtyDaysAgo  = now()->subDays(30);
        $recentMovements = InventoryMovement::where('moved_at', '>=', $thirtyDaysAgo)
            ->selectRaw('type, COUNT(*) as total_movements, SUM(quantity) as total_quantity')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $lastMovements = InventoryMovement::with(['product:id,name,sku', 'user:id,name'])
            ->orderBy('moved_at', 'desc')
            ->limit(5)
            ->get();

        $topProducts = InventoryMovement::where('moved_at', '>=', $thirtyDaysAgo)
            ->select('product_id', DB::raw('SUM(quantity) as total_moved'))
            ->with('product:id,name,sku,stock_current')
            ->groupBy('product_id')
            ->orderByDesc('total_moved')
            ->limit(5)
            ->get();

        // ─── Métricas de ventas ────────────────────────────────────────
        $today     = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        $salesToday     = Sale::where('status', 'completed')->where('sold_at', '>=', $today);
        $salesYesterday = Sale::where('status', 'completed')
                              ->where('sold_at', '>=', $yesterday)
                              ->where('sold_at', '<', $today);
        $salesWeek      = Sale::where('status', 'completed')->where('sold_at', '>=', $weekStart);
        $salesMonth     = Sale::where('status', 'completed')->where('sold_at', '>=', $monthStart);

        $revenueToday     = (float) (clone $salesToday)->sum('total');
        $countToday       = (clone $salesToday)->count();
        $revenueYesterday = (float) (clone $salesYesterday)->sum('total');
        $revenueWeek      = (float) (clone $salesWeek)->sum('total');
        $revenueMonth     = (float) (clone $salesMonth)->sum('total');
        $countMonth       = (clone $salesMonth)->count();

        // Últimas 5 ventas
        $lastSales = Sale::where('status', 'completed')
            ->with('user:id,name')
            ->orderBy('sold_at', 'desc')
            ->limit(5)
            ->get();

        // Gráfico: ventas por día últimos 14 días
        $chartDays = 14;
        $chartData = Sale::where('status', 'completed')
            ->where('sold_at', '>=', now()->subDays($chartDays - 1)->startOfDay())
            ->selectRaw('DATE(sold_at) as day, SUM(total) as revenue, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $chartLabels  = [];
        $chartRevenue = [];
        $chartCounts  = [];
        for ($i = $chartDays - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[]  = now()->subDays($i)->format('d/m');
            $chartRevenue[] = isset($chartData[$date]) ? round((float) $chartData[$date]->revenue, 2) : 0;
            $chartCounts[]  = isset($chartData[$date]) ? (int) $chartData[$date]->count : 0;
        }

        // Top productos más vendidos (por ventas POS)
        $topSoldProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.sold_at', '>=', $monthStart)
            ->select('sale_items.product_name', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.subtotal) as total_revenue'))
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // ─── Respuesta ─────────────────────────────────────────────────
        return response()->json([
            'data' => [
                'kpis' => [
                    'total_products'        => $totalProducts,
                    'total_stock'           => (int) $totalStock,
                    'total_inventory_value' => round((float) $totalInventoryValue, 2),
                    'low_stock_count'       => $lowStockProducts->count(),
                    'out_of_stock_count'    => $outOfStockCount,
                ],

                'sales_kpis' => [
                    'revenue_today'     => $revenueToday,
                    'revenue_yesterday' => $revenueYesterday,
                    'count_today'       => $countToday,
                    'revenue_week'      => $revenueWeek,
                    'revenue_month'     => $revenueMonth,
                    'count_month'       => $countMonth,
                ],

                'sales_chart' => [
                    'labels'  => $chartLabels,
                    'revenue' => $chartRevenue,
                    'counts'  => $chartCounts,
                ],

                'last_sales' => $lastSales->map(fn($s) => [
                    'id'          => $s->id,
                    'sale_number' => $s->sale_number,
                    'total'       => $s->total,
                    'user_name'   => $s->user?->name,
                    'sold_at'     => $s->sold_at?->toISOString(),
                ]),

                'top_sold_products' => $topSoldProducts,

                'movements_summary' => [
                    'entradas' => [
                        'count'    => $recentMovements->get('entrada')?->total_movements ?? 0,
                        'quantity' => $recentMovements->get('entrada')?->total_quantity ?? 0,
                    ],
                    'salidas' => [
                        'count'    => $recentMovements->get('salida')?->total_movements ?? 0,
                        'quantity' => $recentMovements->get('salida')?->total_quantity ?? 0,
                    ],
                ],

                'low_stock_products' => ProductResource::collection($lowStockProducts),

                'last_movements' => $lastMovements->map(fn($m) => [
                    'id'           => $m->id,
                    'type'         => $m->type,
                    'quantity'     => $m->quantity,
                    'product_name' => $m->product?->name,
                    'product_sku'  => $m->product?->sku,
                    'user_name'    => $m->user?->name,
                    'moved_at'     => $m->moved_at?->toISOString(),
                ]),

                'top_products' => $topProducts->map(fn($m) => [
                    'product_id'    => $m->product_id,
                    'product_name'  => $m->product?->name,
                    'product_sku'   => $m->product?->sku,
                    'stock_current' => $m->product?->stock_current,
                    'total_moved'   => (int) $m->total_moved,
                ]),
            ],
        ]);
    }
}
