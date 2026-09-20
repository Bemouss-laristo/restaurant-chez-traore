<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Photo de l'argent disponible un jour donné : caisse + comptes mobiles. */
class BalanceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'recorded_on', 'cash', 'bankily', 'masrivi', 'sedad', 'note'];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'cash' => 'decimal:2',
            'bankily' => 'decimal:2',
            'masrivi' => 'decimal:2',
            'sedad' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function total(): float
    {
        return (float) $this->cash + (float) $this->bankily + (float) $this->masrivi + (float) $this->sedad;
    }
}
