<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('handle-sales') ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->except(PaymentMethod::Credit)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Le panier est vide.',
            'items.min' => 'Le panier est vide.',
        ];
    }
}
