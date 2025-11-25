<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashOperation extends Model
{
    use HasFactory;

    protected $primaryKey = 'cash_op_id';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'agent_id',
        'operation_type',
        'amount',
        'currency_code',
        'wallet_amount',
        'exchange_rate',
        'rate_id',
        'agent_commission',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'wallet_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'agent_commission' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'wallet_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id', 'user_id');
    }

    public function currencyRate()
    {
        return $this->belongsTo(CurrencyRate::class, 'rate_id', 'rate_id');
    }
}