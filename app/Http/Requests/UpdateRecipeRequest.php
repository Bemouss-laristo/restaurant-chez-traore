<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-products') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'items.*.quantity_needed' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.stock_item_id.required' => 'Choisissez un ingrédient pour chaque ligne.',
            'items.*.quantity_needed.gt' => 'La quantité doit être supérieure à 0.',
        ];
    }
}
