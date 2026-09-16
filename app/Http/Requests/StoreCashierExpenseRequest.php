<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashierExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('record-cash-expenses') ?? false;
    }

    public function rules(): array
    {
        return [
            'expense_category' => ['required', Rule::enum(ExpenseCategory::class)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            // Obligatoire : c'est la ligne du « cahier » (ex : « pain pour les sandwichs »).
            'description' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Indique le montant.',
            'amount.gt' => 'Le montant doit être supérieur à 0.',
            'description.required' => "Écris à quoi a servi l'argent.",
        ];
    }
}
