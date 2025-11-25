<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'sender_wallet_id',
        'receiver_wallet_id',
        'receiver_bank_account',
        'receiver_email',
        'agent_id',
        'service_id',
        'transfer_amount',
        'transfer_fee',
        'received_amount',
        'exchange_rate',
        'status',
    ];

    protected $casts = [
        'transfer_amount' => 'decimal:2',
        'transfer_fee' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function senderWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id', 'wallet_id');
    }

    public function receiverWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'receiver_wallet_id', 'wallet_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id', 'user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function refundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class, 'transaction_id', 'transaction_id');
    }
}
