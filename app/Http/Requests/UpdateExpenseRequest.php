<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-expenses') ?? false;
    }

    /** Ignore les lignes vides du formulaire. */
    protected function prepareForValidation(): void
    {
        $this->merge(['items' => ExpenseItems::clean($this->input('items', []))]);
    }

    public function rules(): array
    {
        return [
            'expense_category' => ['required', Rule::enum(ExpenseCategory::class)],
            'spent_at' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            // Une ligne par article acheté, avec son prix : le total est calculé par le serveur.
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.label' => ['required', 'string', 'min:2', 'max:100'],
            'items.*.price' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return ExpenseItems::messages();
    }
}
