<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletToPersonService
{
    protected $walletService;
    protected $currencyService;
    public function __construct(CurrencyRateService $currencyService, WalletService $walletService){
        $this->currencyService = $currencyService;
        $this->walletService = $walletService;
    }

    public function initiateWalletToPersonTransfer($user_id, $sender_wallet_id, $receiver_email, $amount, $currency, $include_fees){
        DB::beginTransaction();

        try{
            // Get Sender wallet:
            $sender_wallet = $this->walletService->getUserWallet($user_id,$sender_wallet_id);

            if(!$sender_wallet){
                throw new Exception('Sender wallet not found');
            }

            // Get the service
            $service = Service::Where('service_type','cash_out')->firstOrFail();
            if(!$service){
                throw new Exception('Service not found');
            }

            $requested_currency = strtoupper($currency);
            $requested_amount = $amount;

            if($sender_wallet->currency_code !== $currency){
                $exchanged_amount = $this->currencyService->exchange($requested_amount,$sender_wallet->currency_code,$requested_currency);
                $exchange_rate = $exchanged_amount['exchange_rate'];
            }
            else{
                $exchanged_amount = $requested_amount;
                $exchange_rate = 1;
            }

            // Calculate fees based on service fee rate
            $fees = $service->fee_percentage * $exchanged_amount;

            if($include_fees) {
                $total_amount = $exchanged_amount + $fees;
            }else{
                $total_amount = $exchanged_amount - $fees;
            }

            if($sender_wallet->balance < $total_amount){
                throw new Exception('Insufficient balance');
            }

            $sender_wallet->balance -= $total_amount;
            $sender_wallet->save();

            $transaction = Transaction::create([
                'sender_wallet_id' => $sender_wallet->wallet_id,
                'receiver_email' => $receiver_email,
                'service_id' => $service->service_id,
                'transfer_amount' => $amount,
                'transfer_fee' => $fees,
                'received_amount' => $total_amount,
                'exchange_rate' => $exchange_rate,
                'status' => 'pending'

            ]);

            DB::commit();

            return $transaction;

        }catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }
}
