<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScope — Global Scope de Eloquent
 *
 * Se registra en los modelos Product e InventoryMovement.
 * Añade automáticamente WHERE tenant_id = ? a TODAS las queries
 * cuando hay un tenant activo en el contexto de la aplicación.
 *
 * Si el usuario es super_admin (no hay tenant en contexto),
 * el scope NO se aplica y el super_admin ve todos los registros.
 *
 * El tenant_id se inyecta al contexto en el middleware EnsureTenantScope.
 *
 * Uso:
 *   // En el modelo:
 *   protected static function booted(): void {
 *       static::addGlobalScope(new TenantScope());
 *   }
 *
 *   // Para ignorar el scope puntualmente (solo super_admin debería hacerlo):
 *   Product::withoutGlobalScope(TenantScope::class)->get();
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Obtener el tenant_id del contexto de la aplicación.
        // Este valor lo inyecta EnsureTenantScope middleware.
        $tenantId = app('current_tenant_id');

        // Si hay un tenant en contexto, filtrar por él.
        // Si no hay (super_admin), no añadir el filtro.
        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
