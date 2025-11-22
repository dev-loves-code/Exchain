<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'password_hash',
        'role_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Override password column for authentication
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function socialLogins(): HasMany
    {
        return $this->hasMany(SocialLogin::class, 'user_id', 'user_id');
    }

    public function agentProfile(): HasOne
    {
        return $this->hasOne(AgentProfile::class, 'agent_id', 'user_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id', 'user_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class, 'user_id', 'user_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class, 'user_id', 'user_id');
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_wallet_id', 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class, 'user_id', 'user_id');
    }

    public function cashOperations(): HasMany
    {
        return $this->hasMany(CashOperation::class, 'user_id', 'user_id');
    }

    public function stripePayments(): HasMany
    {
        return $this->hasMany(StripePayment::class, 'user_id', 'user_id');
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role->role_name === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role->role_name === 'agent';
    }

    public function isUser(): bool
    {
        return $this->role->role_name === 'user';
    }
}