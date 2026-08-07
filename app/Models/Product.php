<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'barcode',
        'category',
        'description',
        'stock_current',
        'stock_minimum',
        'unit',
        'cost',
        'price',
        'supplier',
        'image_url',
        'active',
    ];

    protected $casts = [
        'stock_current' => 'integer',
        'stock_minimum' => 'integer',
        'cost'          => 'decimal:2',
        'price'         => 'decimal:2',
        'active'        => 'boolean',
    ];

    // ─── Global Scope ─────────────────────────────────────────────────────
    // Se aplica automáticamente en TODAS las queries de Product.
    // Solo activo cuando hay un tenant en el contexto (no afecta al super admin).

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    // ─── Accessors / Helpers ─────────────────────────────────────────────

    /**
     * Determina si el producto tiene stock bajo.
     * stock_current <= stock_minimum
     */
    public function isLowStock(): bool
    {
        return $this->stock_current <= $this->stock_minimum;
    }

    /**
     * Valor total del inventario para este producto.
     * stock_current * cost
     */
    public function inventoryValue(): float
    {
        return $this->stock_current * $this->cost;
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_current', '<=', 'stock_minimum');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")  // ilike = case-insensitive en PostgreSQL
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%");
        });
    }
}
