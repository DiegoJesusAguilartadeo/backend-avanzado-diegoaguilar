<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'quantity'   => (int) $this->quantity,
            'price'      => (float) $this->price,
            'subtotal'   => (float) ($this->quantity * $this->price),
            'product'    => $this->whenLoaded('product', fn () => [
                'name'  => $this->product->name,
                'price' => (float) $this->product->price,
            ]),
        ];
    }
}