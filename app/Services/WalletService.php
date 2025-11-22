<?php

namespace App\Services;

use App\Models\Wallet;

class WalletService
{
    public function getUserWallet($user_id, $wallet_id){
        $wallet = Wallet::where('user_id', $user_id)
            ->where('wallet_id', $wallet_id)
            ->where('is_active', 1)
            ->first();

        return $wallet;
    }

}