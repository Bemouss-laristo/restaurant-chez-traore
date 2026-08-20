<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
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
        ];
    }

    // ----- Relations -----

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

    public function isLow(): bool
    {
        return (float) $this->quantity <= (float) $this->alert_threshold;
    }
}
