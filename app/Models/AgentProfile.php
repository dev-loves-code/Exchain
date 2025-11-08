<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentProfile extends Model
{
    protected $primaryKey = 'agent_id';
    public $incrementing = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'agent_id',
        'business_name',
        'business_license',
        'latitude',
        'longitude',
        'address',
        'city',
        'working_hours_start',
        'working_hours_end',
        'commission_rate',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'commission_rate' => 'decimal:2',
        'status' => 'string',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id', 'user_id');
    }
}
