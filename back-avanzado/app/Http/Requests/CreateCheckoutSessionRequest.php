<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->user()) {
            return false;
        }

        $orderId = $this->input('order_id');

        if (!$orderId) {
            return true;
        }

        // Seguridad IDOR: Garantizar que la orden pertenece al usuario autenticado
        return Order::where('id', $orderId)
            ->where('user_id', $this->user()->id)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $order = Order::find($value);

                    if (!$order) {
                        return;
                    }

                    if ($order->status !== 'pending') {
                        $fail("La orden #{$value} no está pendiente (Estado actual: '{$order->status}').");
                    }

                    if ($order->items()->count() === 0) {
                        $fail("La orden #{$value} no contiene ítems.");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'El campo order_id es obligatorio.',
            'order_id.integer'  => 'El campo order_id debe ser un entero.',
            'order_id.exists'   => 'La orden seleccionada no existe.',
        ];
    }
}