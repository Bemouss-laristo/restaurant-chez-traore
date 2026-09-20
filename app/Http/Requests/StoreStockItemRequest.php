<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-stock') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_key' => $this->boolean('is_key'),
            // Un champ laissé vide ne doit pas être stocké comme 0 : c'est « pas d'abonnement ».
            'supplier_id' => $this->input('supplier_id') ?: null,
            'default_payment_method' => $this->input('default_payment_method') ?: null,
            'daily_quantity' => $this->filled('daily_quantity') ? $this->input('daily_quantity') : null,
            'agreed_unit_price' => $this->filled('agreed_unit_price') ? $this->input('agreed_unit_price') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:stock_items,name'],
            'unit' => ['required', Rule::enum(Unit::class)],
            'quantity' => ['required', 'numeric', 'min:0'],
            'alert_threshold' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'default_payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'daily_quantity' => ['nullable', 'numeric', 'min:0'],
            'agreed_unit_price' => ['nullable', 'numeric', 'min:0'],
            'pack_label' => ['nullable', 'string', 'max:60'],
            'pack_quantity' => ['nullable', 'numeric', 'min:0'],
            'is_key' => ['boolean'],
        ];
    }
}
