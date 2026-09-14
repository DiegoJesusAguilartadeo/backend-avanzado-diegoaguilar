<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'total'            => (float) $this->total,
            'status'           => $this->status,
            'shipping_address' => $this->shipping_address,
            'city'             => $this->city,
            'postal_code'      => $this->postal_code,
            'country'          => $this->country,
            'phone'            => $this->phone,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at?->toIso8601String(),
            'items'            => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity'     => $item->quantity,
                    'price'        => (float) $item->price,
                ]);
            }),
        ];
    }
}