<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /** Nombre maximum de produits différents dans une commande. */
    public const MAX_LINES = 30;

    /** Quantité maximum pour un même produit. */
    public const MAX_QUANTITY = 50;

    /** Formulaire public : accessible à tout le monde. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s().-]{8,30}$/'],
            'note' => ['nullable', 'string', 'max:500'],
            // Limites anti-abus : un panier raisonnable pour un restaurant.
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Votre nom est requis.',
            'customer_phone.required' => 'Votre numéro de téléphone est requis.',
            'items.required' => 'Votre panier est vide.',
            'items.min' => 'Votre panier est vide.',
            'items.max' => 'Votre commande contient trop de produits différents (maximum '.self::MAX_LINES.').',
            'items.*.quantity.max' => 'Quantité maximum : '.self::MAX_QUANTITY.' par produit. Pour une grosse commande, appelez-nous.',
            'customer_phone.regex' => 'Numéro de téléphone invalide.',
        ];
    }
}
