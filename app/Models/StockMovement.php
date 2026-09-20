<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'stock_item_id',
        'user_id',
        'type',
        'reason',
        'quantity',
        'unit_cost',
        'note',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'reason' => StockMovementReason::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    /** Valeur du mouvement en MRU (coût du moment si connu, sinon coût actuel de l'article). */
    public function value(): float
    {
        $cost = $this->unit_cost !== null
            ? (float) $this->unit_cost
            : (float) ($this->stockItem->unit_cost ?? 0);

        return (float) $this->quantity * $cost;
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Origine polymorphe du mouvement (une vente, un achat, etc.). */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
