<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCommissionW2P extends Model
{
    protected $table = 'agent_commission_w2_p_s';

    protected $primaryKey = 'commission_id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'agent_id',
        'commission_amount',
        'commission_rate',
        'created_at',
    ];


    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id', 'user_id');
    }
}
