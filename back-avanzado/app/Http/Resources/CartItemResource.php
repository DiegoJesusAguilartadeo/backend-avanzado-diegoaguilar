<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            'price'        => $this->whenLoaded('product', fn() => (float) $this->product->price),
            'quantity'     => (int) $this->quantity,
            'subtotal'     => $this->whenLoaded('product', function () {
                return round((float) $this->product->price * $this->quantity, 2);
            }),
        ];
    }
}
