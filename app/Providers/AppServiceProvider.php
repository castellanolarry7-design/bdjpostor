<?php
namespace App\Providers;

use App\Models\InventoryMovement;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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

        // Registro explícito de policies. Laravel 11 las descubre por
        // convención, pero dejarlo escrito evita que un renombrado silencioso
        // desactive el control de acceso sin que nadie se entere.
        Gate::policy(User::class, UserPolicy::class);
    }
}
