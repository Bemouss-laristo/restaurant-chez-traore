<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Enveloppe d'un mois : l'argent de travail prévu au départ. */
class MonthPlan extends Model
{
    use HasFactory;

    protected $fillable = ['month', 'opening_amount', 'user_id', 'note'];

    protected function casts(): array
    {
        return ['opening_amount' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
