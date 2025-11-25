<?php

namespace App\Services;

use App\Models\AgentCommissionW2P;
use App\Models\RefundRequest;
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

    public function initiateWalletToPersonTransfer(
        $user_id,
        $sender_wallet_id,
        $receiver_email,
        $amount,
        $currency,
        $include_fees
    ){
        DB::beginTransaction();

        try {
            $sender_wallet = $this->walletService->getUserWallet($user_id, $sender_wallet_id);

            if (!$sender_wallet) {
                throw new Exception('Sender wallet not found');
            }

            $service = Service::where('service_type', 'cash_out')->firstOrFail();

            $requested_currency = strtoupper($currency);
            $requested_amount   = $amount;



            if ($sender_wallet->currency_code !== $requested_currency) {

                $exchange = $this->currencyService->exchange(
                    $requested_amount,
                    $sender_wallet->currency_code,
                    $requested_currency
                );

                if (!isset($exchange['total'], $exchange['exchange_rate'])) {
                    throw new Exception('Exchange service returned invalid response.');
                }

                $receiver_amount = $exchange['total'];
                $exchange_rate   = $exchange['exchange_rate'];

            } else {
                $receiver_amount = $requested_amount;
                $exchange_rate   = 1;
            }

           // Fees Calculation for sender
            $fees_sender = $service->fee_percentage * $requested_amount;


            if ($include_fees) {
                $total_deduction = $requested_amount + $fees_sender;
                $received_amount = $receiver_amount;

            } else {
                $total_deduction = $requested_amount;

                $fees_receiver = $fees_sender * $exchange_rate;

                $received_amount = $receiver_amount - $fees_receiver;
            }

            if ($received_amount < 0) {
                throw new Exception('Received amount cannot be negative.');
            }


            if ($sender_wallet->balance < $total_deduction) {
                throw new Exception('Insufficient balance');
            }

            $sender_wallet->balance -= $total_deduction;
            $sender_wallet->save();


            $transaction = Transaction::create([
                'sender_wallet_id' => $sender_wallet->wallet_id,
                'receiver_email'   => $receiver_email,
                'service_id'       => $service->service_id,
                'transfer_amount'  => $requested_amount,
                'transfer_fee'     => $fees_sender,
                'received_amount'  => $received_amount,
                'exchange_rate'    => $exchange_rate,
                'status'           => 'pending'
            ]);

            DB::commit();
            return $transaction;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    private function generateReferenceCode($transaction_id)
    {
        return 'REF-' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
    }

    public function getReceipt($transaction_id, $user_id)
    {
        $transaction = Transaction::with([
            'senderWallet.user',
            'service',
            'agent'
        ])->findOrFail($transaction_id);

        if ($transaction->senderWallet->user_id != $user_id) {
            throw new Exception('Unauthorized access to transaction.');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id'   => $transaction->transaction_id,
                'reference_code'   => $this->generateReferenceCode($transaction_id),

                'sender' => [
                    'full_name' => $transaction->senderWallet->user->full_name,
                    'email'     => $transaction->senderWallet->user->email,
                    'wallet_id' => $transaction->sender_wallet_id,
                    'currency'  => $transaction->senderWallet->currency_code,
                ],

                'receiver' => [
                    'name'          => $transaction->receiver_name ?? 'N/A',
                    'email'         => $transaction->receiver_email,
                    'pickup_method' => 'Cash pickup at agent',
                ],

                'transfer_details' => [
                    'amount'         => $transaction->transfer_amount,
                    'fee'            => $transaction->transfer_fee,
                    'received_amount'=> $transaction->received_amount,
                    'exchange_rate'  => $transaction->exchange_rate,
                    'service_type'   => $transaction->service->service_type,
                    'transfer_speed' => $transaction->service->transfer_speed,
                ],

                'status' => $transaction->status,
            ]
        ]);
    }


    public function getUserWalletToPersonTransactions($user_id, $wallet_id  = null){

        $query = Transaction:: with(['senderWallet','service','agent'])
        ->whereHas('senderWallet', function($query) use ($user_id){
            $query->where('user_id', $user_id);
        });

        if($wallet_id){
            $query->where('sender_wallet_id',$wallet_id);
        }

        $transaction = $query->orderBy('created_at','DESC')->get();

        return response() ->json([
            'success' => true,
            'data' => $transaction->map(function($transaction){
                return [
                    'transaction_id' => $transaction->transaction_id,
                    'reference_code' => $this->generateReferenceCode($transaction->transaction_id),
                    'receiver_email' => $transaction->receiver_email,
                    'transfer_amount' => $transaction->transfer_amount,
                    'transfer_fee' => $transaction->transfer_fee,
                    'received_amount' => $transaction->received_amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ];
            })
        ]);
    }

    // Agent Side
    private function decodeReferenceCode($reference_code)
    {
        // Remove 'REF' prefix and leading zeros
        $reference_code = strtoupper(trim($reference_code));

        if (!str_starts_with($reference_code, 'REF-')) {
            return null;
        }

        $transaction_id = ltrim(substr($reference_code, 4), '0');

        return is_numeric($transaction_id) ? (int)$transaction_id : null;
    }
    public function verifyTransaction($reference_code)
    {
        $transaction_id = $this->decodeReferenceCode($reference_code);
        if (!$transaction_id) {
            return response()->json([
                'success' => false,
                'errors' => 'Reference Code not found'
            ], 400);
        }

        $transaction = Transaction::with(['senderWallet.user'])
            ->where('transaction_id', $transaction_id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        if ($transaction->status === 'done') {
            return response()->json([
                'success' => false,
                'errors' => 'Transaction already done'
            ], 400);
        }

        if ($transaction->status === 'rejected') {
            return response()->json([
                'success' => false,
                'errors' => 'Transaction already rejected'
            ]);
        }

        if ($transaction->status === 'refunded') {
            return response()->json([
                'success' => false,
                'errors' => 'Transaction already refunded'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction successfully verified',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'reference_code' => $reference_code,
                'receiver_email' => $transaction->receiver_email,
                'amount_to_release' => $transaction->received_amount,
                'currency' => $transaction->senderWallet->currency_code,
                'sender_name' => $transaction->senderWallet->user->full_name,
                'transfer_date' => $transaction->created_at->format('Y-m-d H:i:s'),
            ]

        ]);

    }
    private function recordAgentCommission($transaction, $agent_id)
    {
        $agent_wallet = DB::table('wallets')
            ->where('user_id', $agent_id)
            ->first();

        $agent_profile = DB::table('agent_profiles')
            ->where('agent_id', $agent_id)
            ->first();

        if ($agent_wallet && $agent_profile && $agent_profile->commission_rate > 0) {
            $commission_in_txn_currency = $transaction->transfer_fee * $agent_profile->commission_rate;

            if ($agent_wallet->currency_code !== $transaction->senderWallet->currency_code) {
                $exchange = $this->currencyService->exchange(
                    $commission_in_txn_currency,
                    $transaction->senderWallet->currency_code,
                    $agent_wallet->currency_code
                );

                if (!isset($exchange['total'])) {
                    throw new Exception('Currency conversion failed for agent commission.');
                }

                $commission = $exchange['total'];
            } else {
                $commission = $commission_in_txn_currency;
            }

            AgentCommissionW2P::create([
                'transaction_id' => $transaction->transaction_id,
                'agent_id' => $agent_id,
                'commission_amount' => $commission,
                'commission_rate' => $agent_profile->commission_rate,
                'created_at' => now(),
            ]);

            DB::table('wallets')
                ->where('wallet_id', $agent_wallet->wallet_id)
                ->increment('balance', $commission);


        }
    }


    public function completeWalletToPersonTransactions($transaction_id, $agent_id){
        DB::beginTransaction();

        try{
            $transaction = Transaction::findOrFail($transaction_id);

            if($transaction->status !== 'pending'){
                throw new Exception('This Receipt has already been processed');
            }

            $refund_request = RefundRequest::where('transaction_id',$transaction_id)
                ->where('status','pending')
                ->first();

            if($refund_request){
                throw new Exception('A refund request on this transaction is taking place. Cannot refund right now.');
            }

            $transaction->status = 'done';
            $transaction->agent_id = $agent_id;
            $transaction->updated_at = now();
            $transaction->save();

            //<----------------Agent Commission --------------------------->
            $this->recordAgentCommission($transaction, $agent_id);

            DB::commit();

            return response() ->json([
                'success' => true,
                'message' => 'Receipt has been completed',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount_received' => $transaction->transfer_amount,
                    'receiver_email' => $transaction->receiver_email,
                    'completed_at' => now(),
                ]
            ]);
        }catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

}
