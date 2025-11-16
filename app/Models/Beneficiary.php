<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    protected $primaryKey = 'beneficiary_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'wallet_id',
        'bank_account_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'wallet_id');
    }

    public function bankAccount(): BelongsTo{
        return $this->belongsTo(BankAccount::class, 'bank_account_id', 'bank_account_id');
    }

    public function paymentMethod(): BelongsTo{
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }
}
