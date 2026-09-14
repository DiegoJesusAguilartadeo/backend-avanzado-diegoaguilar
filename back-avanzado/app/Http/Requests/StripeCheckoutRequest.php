<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StripeCheckoutRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // La autenticación la maneja el middleware auth:api
    }

    /**
     * Reglas de validación aplicables a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    /**
     * Mensajes de error personalizados (Opcional).
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'El ID de la orden es obligatorio.',
            'order_id.integer'  => 'El ID de la orden debe ser un número entero.',
            'order_id.exists'   => 'La orden seleccionada no existe en la base de datos.',
        ];
    }
}