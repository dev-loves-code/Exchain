<?php

namespace App\Services;

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function deposit(Wallet $wallet, float $amount): bool
    {
        return DB::transaction(function () use ($wallet, $amount) {
            $wallet->balance += $amount;

            return $wallet->save();
        });
    }

    public function withdraw(Wallet $wallet, float $amount): bool
    {
        if ($wallet->balance < $amount) {
            return false;
        }

        return DB::transaction(function () use ($wallet, $amount) {
            $wallet->balance -= $amount;

            return $wallet->save();
        });
    }
}
