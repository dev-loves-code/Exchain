<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $primaryKey = 'service_id';

    protected $fillable = [
        'service_type',
        'transfer_speed',
        'fee_percentage',
    ];

    protected $casts = [
        'base_fee' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'service_id', 'service_id');
    }
}