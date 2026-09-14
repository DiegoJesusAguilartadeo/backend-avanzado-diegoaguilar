<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total = $this->whenLoaded('items', function () {
            return $this->items->sum(function ($item) {
                return $item->product ? $item->product->price * $item->quantity : 0;
            });
        }, 0);

        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'items'      => CartItemResource::collection($this->whenLoaded('items')),
            'total'      => round((float) $total, 2),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
