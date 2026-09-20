<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mouvement de réserve : argent mis de côté (positif) ou repris (négatif).
 * L'argent quitte l'exploitation mais reste à toi : ce n'est jamais une dépense.
 */
class ReserveMovement extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'cash_session_id', 'amount', 'payment_method', 'moved_on', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'moved_on' => 'date',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isWithdrawal(): bool
    {
        return (float) $this->amount < 0;
    }
}
