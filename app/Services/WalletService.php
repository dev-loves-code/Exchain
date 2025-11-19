<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\CurrencyRateService;
use Illuminate\Database\Eloquent\Builder;

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
        
        //1-check if balance > 10 $
        if($wallet->currency_code != 'USD'){
            $balance_in_usd = $this->currencyService->exchange($wallet->balance, $wallet->currency_code, 'USD');
        } else {
            $balance_in_usd = $wallet->balance;
        }

        if( $balance_in_usd > 10){
            return 'Balance is greater than 10 $';
        }

        //2-check if has transactions
        if($this->hasPendingTransactions($wallet->wallet_id)){
            return 'Wallet has transactions';
        }

        return true;
    }

    public function hasPendingTransactions($wallet_id){
        return Transaction::where('sender_wallet_id', $wallet_id)
            ->orWhere('receiver_wallet_id', $wallet_id)            
            ->exists();
    }
}