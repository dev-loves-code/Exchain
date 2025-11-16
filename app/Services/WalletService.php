<?php

namespace App\Services;

use App\Models\Wallet;
use App\Services\CurrencyRateService;

class WalletService
{
    protected $currencyService;
    public function __construct(CurrencyRateService $currencyService){
        $this->currencyService = $currencyService;
    }

    public function getUserWallet($user_id, $wallet_id){
        $wallet = Wallet::where('user_id', $user_id)
            ->where('wallet_id', $wallet_id)
            ->first();

        return $wallet;
    }

    public function canDeleteWallet($wallet){
        if($wallet->currency_code != 'USD'){
            $balance_in_usd = $this->currencyService->exchange($wallet->balance, $wallet->currency_code, 'USD');
        } else {
            $balance_in_usd = $wallet->balance;
        }

        return $balance_in_usd <= 10;

        //check if there a pending transactions for this wallet ->sprint2
    }

}