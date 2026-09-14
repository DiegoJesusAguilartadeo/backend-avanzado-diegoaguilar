<?php

namespace App\Http\Requests;

use App\Models\Cart;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $cartItems = Cart::where('user_id', $this->user()->id)->get();

            if ($cartItems->isEmpty()) {
                $validator->errors()->add('cart', 'El carrito de compras está vacío.');
            }
        });
    }
}