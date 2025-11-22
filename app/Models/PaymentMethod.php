<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $primaryKey = 'payment_method_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'method_type',
        'card_last_four',
        'card_brand',
        'stripe_payment_method_id',
        'stripe_customer_id',
        'is_default',
        'exp_month',
        'exp_year',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function beneficiaries(): HasMany{
        return $this->hasMany(Beneficiary::class, 'payment_method_id', 'payment_method_id');
    }
}
