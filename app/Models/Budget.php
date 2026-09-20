<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Plafond de dépense fixé pour un mois et une catégorie. */
class Budget extends Model
{
    use HasFactory;

    protected $fillable = ['month', 'expense_category', 'amount'];

    protected function casts(): array
    {
        return [
            'expense_category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
        ];
    }
}
