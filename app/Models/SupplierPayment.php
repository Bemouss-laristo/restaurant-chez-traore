<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Règlement d'une facture fournisseur. Ce n'est PAS une dépense de plus :
 * la dépense a déjà été enregistrée à la livraison. C'est uniquement
 * de l'argent qui sort (caisse ou mobile) pour éponger la dette.
 */
class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id', 'user_id', 'cash_session_id', 'amount', 'payment_method', 'paid_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }
}
