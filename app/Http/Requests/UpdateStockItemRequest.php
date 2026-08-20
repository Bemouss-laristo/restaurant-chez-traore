<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-stock') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('stockItem')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('stock_items', 'name')->ignore($id)],
            'unit' => ['required', Rule::enum(Unit::class)],
            // La quantité n'est PAS modifiable ici : elle ne change que via un mouvement.
            'alert_threshold' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
