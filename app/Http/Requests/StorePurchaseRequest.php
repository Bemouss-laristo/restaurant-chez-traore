<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-stock') ?? false;
    }

    /** Ignore les lignes vides du formulaire. */
    protected function prepareForValidation(): void
    {
        $lines = collect((array) $this->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ($row['stock_item_id'] ?? '') !== '')
            ->map(fn ($row) => [
                'stock_item_id' => $row['stock_item_id'],
                'quantity' => $row['quantity'] ?? null,
                'total_price' => $row['total_price'] ?? null,
                'pack' => (bool) ($row['pack'] ?? false),
            ])
            ->values()
            ->all();

        $this->merge(['lines' => $lines]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            // Une livraison à crédit doit être rattachée à un compte fournisseur.
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'spent_at' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1', 'max:30'],
            'lines.*.stock_item_id' => ['required', 'integer', 'exists:stock_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.total_price' => ['required', 'numeric', 'gt:0'],
            'lines.*.pack' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('payment_method') === PaymentMethod::Credit->value && ! $this->filled('supplier_id')) {
                $validator->errors()->add('supplier_id', 'Choisis le fournisseur pour une livraison à crédit.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Ajoute au moins un article acheté.',
            'lines.*.stock_item_id.required' => "Choisis l'article.",
            'lines.*.quantity.required' => 'Indique la quantité.',
            'lines.*.total_price.required' => 'Indique le prix payé.',
        ];
    }
}
