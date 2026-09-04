<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'note',
        'status',
        'subtotal',
        'total',
        'sale_id',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** Commandes en attente de confirmation. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Nouvelle);
    }

    /** Commandes encore actives (nouvelles ou confirmées). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [OrderStatus::Nouvelle, OrderStatus::Confirmee]);
    }
}
