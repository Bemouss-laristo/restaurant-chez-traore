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

    /** Ignore les lignes laissées complètement vides dans le formulaire. */
    protected function prepareForValidation(): void
    {
        $items = collect((array) $this->input('items', []))
            ->filter(fn ($row) => is_array($row) && (trim((string) ($row['label'] ?? '')) !== '' || trim((string) ($row['price'] ?? '')) !== ''))
            ->map(fn ($row) => ['label' => trim((string) ($row['label'] ?? '')), 'price' => $row['price'] ?? null])
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'expense_category' => ['required', Rule::enum(ExpenseCategory::class)],
            // Le « cahier » : une ligne par article acheté, avec son prix.
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.label' => ['required', 'string', 'min:2', 'max:100'],
            'items.*.price' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Ajoute au moins un article acheté avec son prix.',
            'items.min' => 'Ajoute au moins un article acheté avec son prix.',
            'items.*.label.required' => "Écris le nom de l'article.",
            'items.*.price.required' => "Indique le prix de l'article.",
            'items.*.price.gt' => 'Le prix doit être supérieur à 0.',
        ];
    }
}
