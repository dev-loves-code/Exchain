<?php

namespace App\Services;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Service;
use App\Services\CurrencyRateService;
use App\Models\User;
use App\Models\RefundRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletToWalletService
{
    protected $currencyService;
    protected $walletService;

    public function __construct(CurrencyRateService $currencyService, WalletService $walletService)
    {
        $this->currencyService =  $currencyService;
        $this->walletService = $walletService;
    }

    public function initiateWalletToWalletTransfer($user_id, $sender_wallet_id, $receiver_wallet_id, $amount, $currency){
        
        DB::beginTransaction();

        try{
            //check if sender wallet belongs to the user
            $senderWallet = $this->walletService->getUserWallet($user_id, $sender_wallet_id);
           
            if(!$senderWallet){
                return response()->json([
                    'success' => false,
                    'message' => 'Sender wallet not found or does not belong to the user',
                ], 404);
            }

            //get the receiver wallet
            $receiverWallet = Wallet::where('wallet_id', $receiver_wallet_id)
                ->select('wallet_id','balance','currency_code')
                ->first();

            $service = Service::where('service_type', 'transfer')->firstOrFail();
            if(!$service){
                return response()->json([
                    'success' => false,
                    'message' => 'Service Type not found',
                ], 404);
            }

            $requested_currency = strtoupper($currency);
            $requested_amount = $amount;
            
            //calculation the amount to send in sender currency
            if($senderWallet->currency_code != $currency){
                $amount_in_sender_currency = $this->currencyService->exchange($requested_amount, $requested_currency, $senderWallet->currency_code)['total'];
            }else{
                $amount_in_sender_currency = $requested_amount;
            }

            //amount to be retrieved from the sender wallet including the fee
            $transfer_fee = $service->fee_percentage * $amount_in_sender_currency;
            $amount_to_debit = $amount_in_sender_currency + $transfer_fee;

            //checking sender balance
            if($senderWallet->balance < $amount_to_debit){
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance',
                ], 400);
            }

            //calculating the amount to credit to the receiver wallet based on his currency
            if($receiverWallet->currency_code != $senderWallet->currency_code){
                $exchange = $this->currencyService->exchange
                    ($amount_in_sender_currency, $senderWallet->currency_code, $receiverWallet->currency_code);
                $amount_to_credit = $exchange['total'];
                $exchange_rate = $exchange['rate_id'];
            }else{
                $amount_to_credit = $amount_in_sender_currency;
                $exchange_rate = null;
            }

            $senderWallet->balance = $senderWallet->balance - $amount_to_debit;
            $senderWallet->save();

            $transaction = Transaction::create([
                'sender_wallet_id' => $sender_wallet_id,
                'receiver_wallet_id' => $receiver_wallet_id,
                'service_id' => $service->service_id,
                
                //amount to retreive from sender wallet = amount in sender currency + fee
                'transfer_amount' => $amount_in_sender_currency,
                'transfer_fee' => $transfer_fee,

                'received_amount' => $amount_to_credit,
                
                'exchange_rate' => $exchange_rate, //from the sender to the receiver
                'status' => 'pending',
            ]);

            DB::commit();

            return $transaction; 

        }catch(\Exception $exp){
            DB::rollback();
            throw $exp;
        }
    }

    public function approveTransfer($transaction_id, $user_id){
        try{
            DB::beginTransaction();
            
            //get the tranaction
            $transaction = Transaction::findOrFail($transaction_id);
            if($transaction->status !== 'pending'){
                abort(404, 'Transaction not found or not pending');
            }

            //check if the wallet exists and belongs to the user
            $receiver_wallet = $this->walletService->getUserWallet($user_id, $transaction->receiver_wallet_id);
            if(!$receiver_wallet){
                return response()->json([
                    'success' => false,
                    'message' => 'Receiver wallet not found or does not belong to the user',
                ], 404);
            }

            $refund_request = RefundRequest::where('transaction_id',$transaction_id)
               ->where('status','pending')
               ->first();
            
            if($refund_request){
                throw new \Exception('A refund request on this transaction is taking place. Cannot complete right now.');
            }

            //update the receiver wallet balance
            $receiver_wallet->balance = $receiver_wallet->balance + $transaction->received_amount;
            $receiver_wallet->save();
            
            //update the transaction status
            $transaction->status = 'done';
            $transaction->save();

            DB::commit();

            return response()->json([
                'message' => 'Transfer approved successfully',
                'transaction' => $transaction
            ]);
        }catch (\Exception $exp) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $exp->getMessage()
            ], 500);
        }
    }

    public function rejectTransfer($transaction_id, $user_id){
        try{
            DB::beginTransaction();

            //get the transaction
            $transaction = Transaction::findOrFail($transaction_id);
            if($transaction->status !== 'pending'){
                abort(404, 'Transaction not found or not pending');
            }

            //check if the wallet exists and belongs to the user
            $receiver_wallet = $this->walletService->getUserWallet($user_id, $transaction->receiver_wallet_id);
            if(!$receiver_wallet){
                return response->json([
                    'success'=>false,
                    'message' => 'Receiver wallet not found or does not belong to the user',
                ]);
            }

            $sender_wallet = Wallet::where('wallet_id', $transaction->sender_wallet_id)
                ->first();
            if(!$sender_wallet){
                return response->json([
                    'success'=>false,
                    'message' => 'Sender wallet not found',
                ]);
            }
            
            $refund_request = RefundRequest::where('transaction_id',$transaction_id)
               ->where('status','pending')
               ->first();
            
            if($refund_request){
                throw new \Exception('A refund request on this transaction is taking place. Cannot complete right now.');
            }
            
            //update the sender wallet balance
            $sender_wallet->balance = $sender_wallet->balance + $transaction->transfer_amount;
            $sender_wallet->save();

            //update the transaction status
            $transaction->status = 'rejected';
            $transaction->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer rejected successfully',
                'transaction' => $transaction
            ]);

        }catch(\Exception $exp){
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $exp->getMessage()
            ], 500);
        }
    }

    public function getWalletTransactions($user_id, $wallet_id, $service_id){

        $wallet = $this->walletService->getUserWallet($user_id, $wallet_id);
        if(!$wallet){
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found or does not belong to the user',
            ], 404);
        }

        $transactions = Transaction::where('service_id', $service_id)
            ->where('sender_wallet_id', $wallet_id)
            ->orWhere('receiver_wallet_id', $wallet_id)
            ->select('transaction_id','receiver_wallet_id', 'sender_wallet_id', 'transfer_amount', 'transfer_fee',  'received_amount', 'status', 'exchange_rate', 'created_at')
            ->get();

        $total_received = Transaction::where('receiver_wallet_id', $wallet_id)
            ->where('status', 'done')
            ->sum('received_amount');

        $total_transfer_fee = Transaction::where('sender_wallet_id', $wallet_id)
            ->sum('transfer_fee');
        $total_transfer_amount = Transaction::where('sender_wallet_id', $wallet_id)
            ->sum('transfer_amount');
        $total_sent_amount = $total_transfer_amount + $total_transfer_fee;

        return [
            'transactions' => $transactions,
            'total_received_amount' => $total_received,
            'total_transfer_fee' => $total_transfer_fee,
            'total_transfer_amount' => $total_transfer_amount,
            'total_sent_amount' => $total_transfer_amount + $total_transfer_fee,
        ];
        
    }

}