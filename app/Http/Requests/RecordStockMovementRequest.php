<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-stock') ?? false;
    }

    public function rules(): array
    {
        // purchase = entrée, waste = sortie, adjustment = fixe la quantité comptée.
        $isAdjustment = $this->input('action') === 'adjustment';

        return [
            'action' => ['required', Rule::in(['purchase', 'waste', 'adjustment'])],
            // Pour un ajustement la valeur comptée peut être 0 ; sinon la quantité doit être > 0.
            'quantity' => ['required', 'numeric', $isAdjustment ? 'min:0' : 'gt:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
