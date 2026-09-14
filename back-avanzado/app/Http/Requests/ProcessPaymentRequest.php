<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'       => ['required', 'integer', 'exists:orders,id'],
            'payment_method' => ['required', 'string'], // PaymentMethod ID de Stripe (ej: pm_card_visa)
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'order_id'       => [
                'description' => 'ID de la orden a pagar.',
                'example'     => 1,
            ],
            'payment_method' => [
                'description' => 'Token o ID del método de pago devuelto por Stripe SDK en el frontend.',
                'example'     => 'pm_card_visa',
            ],
        ];
    }
}