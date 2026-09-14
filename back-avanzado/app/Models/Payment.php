<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'stripe_session_id',
        'stripe_payment_id',
        'amount',
        'status',
    ];

    /**
     * Relación: Un pago pertenece a una orden.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}