<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/v1/health', fn() => response()->json([
    'status'  => 'ok',
    'service' => 'JPStore API',
    'version' => '1.0',
    'time'    => now()->toISOString(),
]));

// Rutas públicas
Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
});

// Rutas protegidas
Route::prefix('v1')
     ->middleware(['auth:sanctum', 'tenant.scope'])
     ->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',     [AuthController::class, 'me']);
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Solo super admin
    Route::middleware('role:super_admin')->group(function () {
        Route::prefix('tenants')->group(function () {
            Route::get('/',                         [TenantController::class, 'index']);
            Route::post('/',                        [TenantController::class, 'store']);
            Route::get('/{tenant}',                 [TenantController::class, 'show']);
            Route::put('/{tenant}',                 [TenantController::class, 'update']);
            Route::patch('/{tenant}/toggle-active', [TenantController::class, 'toggleActive']);
            Route::delete('/{tenant}',              [TenantController::class, 'destroy']);
        });
    });

    // Super admin + admin
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::prefix('users')->group(function () {
            Route::get('/',                       [UserController::class, 'index']);
            Route::post('/',                      [UserController::class, 'store']);
            Route::get('/{user}',                 [UserController::class, 'show']);
            Route::put('/{user}',                 [UserController::class, 'update']);
            Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive']);
            Route::delete('/{user}',              [UserController::class, 'destroy']);
        });
    });

    // Todos los roles de empresa (incluyendo cajero) — lectura productos + POS cobro
    Route::middleware('role:super_admin,admin,user,cashier')->group(function () {
        Route::get('/products/categories', [ProductController::class, 'categories']);
        Route::get('/products',            [ProductController::class, 'index']);
        Route::get('/products/{product}',  [ProductController::class, 'show']);
        Route::prefix('pos')->group(function () {
            Route::get('/scan/{code}', [PosController::class, 'scanProduct']);
            Route::post('/sales',      [PosController::class, 'store']);
            Route::get('/sales/{id}',  [PosController::class, 'show']);
        });
    });

    // Admin + usuario — gestión de inventario + estadísticas POS
    Route::middleware('role:super_admin,admin,user')->group(function () {
        Route::post('/products',              [ProductController::class, 'store']);
        Route::put('/products/{product}',     [ProductController::class, 'update']);
        Route::patch('/products/{product}',   [ProductController::class, 'update']);
        Route::delete('/products/{product}',  [ProductController::class, 'destroy']);
        Route::get('/inventory-movements',            [InventoryMovementController::class, 'index']);
        Route::post('/inventory-movements',           [InventoryMovementController::class, 'store']);
        Route::get('/inventory-movements/{movement}', [InventoryMovementController::class, 'show']);
        Route::get('/inventory-movements/export',     [InventoryMovementController::class, 'export']);
        Route::get('/pos/sales',                      [PosController::class, 'salesList']);
        Route::get('/pos/sales/export',               [PosController::class, 'exportSales']);
        Route::post('/pos/sales/{id}/cancel',         [PosController::class, 'cancel']);
        Route::get('/pos/stats/users',                [PosController::class, 'statsByUser']);
    });
});

Route::fallback(fn() => response()->json(['message' => 'Endpoint no encontrado.'], 404));
