<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $primaryKey = 'rate_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'exchange_rate',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
    ];
}
