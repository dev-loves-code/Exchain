<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashOperation extends Model
{
    protected $primaryKey = 'cash_op_id';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'agent_id',
        'operation_type',
        'amount',
        'agent_commission',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'agent_commission' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'wallet_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id', 'user_id');
    }
}