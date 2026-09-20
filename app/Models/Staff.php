<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Employé du restaurant : fonction, salaire mensuel, et suivi des paiements. */
class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = ['name', 'job_title', 'monthly_salary', 'phone', 'is_active'];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Ce qui a déjà été versé à cet employé pour un mois donné (AAAA-MM). */
    public function paidFor(string $month): float
    {
        return (float) $this->salaryPayments()->where('salary_month', $month)->sum('amount');
    }

    public function isPaidFor(string $month): bool
    {
        return $this->paidFor($month) >= (float) $this->monthly_salary && (float) $this->monthly_salary > 0;
    }
}
