<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'supplier_id',
        'default_payment_method',
        'daily_quantity',
        'agreed_unit_price',
        'unit',
        'pack_label',
        'pack_quantity',
        'is_key',
        'quantity',
        'alert_threshold',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'unit' => Unit::class,
            'quantity' => 'decimal:3',
            'alert_threshold' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'pack_quantity' => 'decimal:3',
            'daily_quantity' => 'decimal:3',
            'agreed_unit_price' => 'decimal:2',
            'default_payment_method' => \App\Enums\PaymentMethod::class,
            'is_key' => 'boolean',
        ];
    }

    // ----- Relations -----

    /** Fournisseur habituel : pré-sélectionné quand on saisit un achat de cet article. */
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'recipe_items')
            ->withPivot('quantity_needed')
            ->withTimestamps();
    }

    // ----- Scopes / helpers -----

    /** Articles dont la quantité est descendue au seuil d'alerte ou en dessous. */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'alert_threshold');
    }

    /** Articles clés : ceux que l'on compte chaque soir (pain, poulet, boissons…). */
    public function scopeKey(Builder $query): Builder
    {
        return $query->where('is_key', true);
    }

    /** Nombre d'unités contenues dans un conditionnement d'achat (carton, sachet…). */
    public function unitsPerPack(): ?float
    {
        $qty = (float) $this->pack_quantity;

        return $qty > 0 ? $qty : null;
    }

    /**
     * Article dont le prix est convenu d'avance avec un fournisseur.
     * C'est la condition pour ne saisir que la quantité prise : le montant suit.
     */
    public function hasAgreedPrice(): bool
    {
        return $this->supplier_id !== null && (float) $this->agreed_unit_price > 0;
    }

    /** Quantité habituelle de la prise du jour, quand elle ne change jamais (2 kg de viande). */
    public function dailyTotal(): ?float
    {
        if (! $this->hasAgreedPrice() || (float) $this->daily_quantity <= 0) {
            return null;
        }

        return round((float) $this->daily_quantity * (float) $this->agreed_unit_price, 2);
    }

    /** Ce qui a déjà été pris aujourd'hui, pour ne pas saisir deux fois sans le savoir. */
    public function takenToday(): float
    {
        [$start, $end] = \App\Support\BusinessDay::window(\App\Support\BusinessDay::today());

        return (float) $this->movements()
            ->where('reason', \App\Enums\StockMovementReason::Purchase->value)
            ->whereBetween('created_at', [$start, $end])
            ->sum('quantity');
    }

    /** Date du dernier comptage physique (ajustement d'inventaire) de cet article. */
    public function lastCountedAt(): ?\Illuminate\Support\Carbon
    {
        $movement = $this->movements()
            ->where('reason', \App\Enums\StockMovementReason::Manual->value)
            ->latest('created_at')
            ->first(['created_at']);

        return $movement?->created_at;
    }

    /** Valeur du stock actuel en MRU. */
    public function stockValue(): float
    {
        return (float) $this->quantity * (float) $this->unit_cost;
    }

    public function isLow(): bool
    {
        return (float) $this->quantity <= (float) $this->alert_threshold;
    }
}
