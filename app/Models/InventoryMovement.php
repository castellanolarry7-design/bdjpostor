<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasUuids;

    // Los movimientos son INMUTABLES: no se actualizan ni eliminan.
    // Si hay un error, se crea un movimiento de ajuste/corrección.
    // Por eso NO usamos SoftDeletes aquí.

    protected $fillable = [
        'tenant_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'unit_price',
        'note',
        'reference',
        'moved_at',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'stock_before' => 'integer',
        'stock_after'  => 'integer',
        'unit_cost'   => 'decimal:2',
        'unit_price'  => 'decimal:2',
        'moved_at'    => 'datetime',
    ];

    // ─── Global Scope ─────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeEntradas($query)
    {
        return $query->where('type', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('type', 'salida');
    }

    public function scopeByDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('moved_at', '>=', $from);
        }
        if ($to) {
            $query->where('moved_at', '<=', $to . ' 23:59:59');
        }
        return $query;
    }
}
