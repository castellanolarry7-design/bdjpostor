<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'user_id', 'sale_number',
        'subtotal', 'tax', 'total',
        'payments', 'total_received_usd', 'change_amount',
        'base_currency', 'notes', 'status', 'sold_at',
    ];

    protected $casts = [
        'subtotal'          => 'decimal:2',
        'tax'               => 'decimal:2',
        'total'             => 'decimal:2',
        'total_received_usd'=> 'decimal:2',
        'change_amount'     => 'decimal:2',
        'payments'          => 'array',
        'sold_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function items(): HasMany    { return $this->hasMany(SaleItem::class); }
}
