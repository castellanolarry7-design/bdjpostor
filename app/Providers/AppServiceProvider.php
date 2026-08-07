<?php
namespace App\Providers;

use App\Models\InventoryMovement;
use App\Observers\InventoryMovementObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('current_tenant_id', false);
    }

    public function boot(): void
    {
        InventoryMovement::observe(InventoryMovementObserver::class);
    }
}
