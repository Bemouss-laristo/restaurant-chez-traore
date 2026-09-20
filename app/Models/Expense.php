<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cash_session_id',
        'supplier_id',
        'staff_id',
        'salary_month',
        'expense_category',
        'amount',
        'description',
        'spent_at',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'expense_category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'spent_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Livraison prise à crédit : la dépense est comptée, mais l'argent n'est pas encore sorti. */
    /** Mouvements de stock nés de cet achat : c'est là que sont les quantités. */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    public function isCredit(): bool
    {
        return $this->payment_method === \App\Enums\PaymentMethod::Credit;
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }
}
