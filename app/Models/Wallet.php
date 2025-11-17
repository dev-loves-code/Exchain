<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $primaryKey = 'wallet_id';

    protected $fillable = [
        'user_id',
        'balance',
        'currency_code',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_wallet_id', 'wallet_id');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'receiver_wallet_id', 'wallet_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class, 'wallet_id', 'wallet_id');
    }

    public function cashOperations(): HasMany
    {
        return $this->hasMany(CashOperation::class, 'wallet_id', 'wallet_id');
    }
}
