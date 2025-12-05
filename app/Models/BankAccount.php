<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    protected $primaryKey = 'bank_account_id';

    protected $fillable = [
        'user_id',
        'holder_name',
        'account_number',
        'iban',
        'country',
        'routing_number',
        'swift_code',
        'bank_name',
        'stripe_bank_account_token',
        'is_primary',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
