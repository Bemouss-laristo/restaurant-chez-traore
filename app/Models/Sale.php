<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Support\BusinessDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'sale_number',
        'user_id',
        'cash_session_id',
        'sold_at',
        'subtotal',
        'total',
        'payment_method',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** Ventes non annulées : les SEULES à compter dans la caisse et les rapports. */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Qui peut annuler cette vente ?
     *  - admin / gérant : toujours (sauf si déjà annulée) ;
     *  - caissier : uniquement une vente de la journée en cours dont la caisse
     *    n'est pas encore clôturée (sinon l'écart de caisse déjà validé changerait).
     */
    public function canBeCancelledBy(User $user): bool
    {
        if ($this->isCancelled() || ! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isGerant()) {
            return true;
        }

        if (BusinessDay::dateFor($this->sold_at) !== BusinessDay::today()) {
            return false;
        }

        return $this->cashSession === null || $this->cashSession->isOpen();
    }

    /** Mouvements de stock générés par cette vente (via la recette). */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }
}
