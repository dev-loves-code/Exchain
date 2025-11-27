<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripePayment extends Model
{
    protected $primaryKey = 'stripe_payment_id';

    protected $fillable = [
        'user_id',
        'stripe_charge_id',
        'stripe_payment_method_id',
        'amount',
        'currency',
        'payment_type',
        'status',
        'description',
        'stripe_metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'stripe_metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
