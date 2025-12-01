<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use App\Services\WalletToPersonService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Services\CurrencyRateService;
use App\Services\WalletToWalletService;

class TransactionController extends Controller
{
    protected $currencyService;
    protected $walletToWalletService;

    protected $walletToPersonService;

    public function __construct(CurrencyRateService $currencyService, WalletToWalletService $walletToWalletService, WalletToPersonService $walletToPersonService){
        $this->currencyService = $currencyService;
        $this->walletToWalletService = $walletToWalletService;
        $this->walletToPersonService = $walletToPersonService;
    }

    public function walletToWalletTransfer(Request $request){
        $user_id = $request->user()->user_id;

        //validate the input
        $validator = Validator::make($request->all(),[
            'sender_wallet_id' => 'required|integer|exists:wallets,wallet_id,is_active,1',
            'receiver_wallet_id' => 'required|integer|different:sender_wallet_id|exists:wallets,wallet_id,is_active,1|min:1',
            'amount' => 'required|numeric|min:0.01', //amount the receiver wants
            'currency_code' => 'required|string|size:3', //currency selected by the sender
        ]);
        if($validator->fails()){
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        //validate currency
        $isValidCurrency = $this->currencyService->isValidCurrency($request->currency_code);
        if($isValidCurrency->getData()->success === false){
            return $isValidCurrency;
        }

        $transfer = $this->walletToWalletService->initiateWalletToWalletTransfer(
            $user_id,
            $request->sender_wallet_id,
            $request->receiver_wallet_id,
            $request->amount,
            $request->currency_code
        );

        return $transfer;
    }

    //receiver accepts the transfer
    public function approveWalletToWalletTransfer(Request $request, $id){
        $approval= $this->walletToWalletService->approveTransfer($id, $request->user()->user_id);
        return $approval;
    }

    //receiver rejects the transfer
    public function rejectWalletToWalletTransfer(Request $request, $id){
        $rejection = $this->walletToWalletService->rejectTransfer($id, $request->user()->user_id);
        return $rejection;
    }

    public function getWalletToWalletTransactions(Request $request){
        $user_id = $request->user()->user_id;

        $data = $this->walletToWalletService->getWalletTransactions($user_id, $request->wallet_id, 1);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // Wallet To Person

    private function generateReferenceCode($transaction_id)
    {
        return 'REF-' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
    }
    public function initiateWalletToPersonTransfer(Request $request){

        $validator = Validator::make($request->all(), [
            'sender_wallet_id' => 'required|integer',
            'receiver_email' => 'required|string|email|max:255',
            'transfer_amount' => 'required|numeric|min:5',
            'currency_code' => 'required|string|size:3',
            'service_id' => 'required|integer|exists:services,service_id',
            'include_fees' => 'required|boolean',
        ],
        [
            'transfer_amount.min' => 'The transfer amount must be at least 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }



        try{
            $user_id = $request->user()->user_id;

            $transaction = $this->walletToPersonService->initiateWalletToPersonTransfer(
                $user_id,
                $request->sender_wallet_id,
                $request->receiver_email,
                $request->transfer_amount,
                $request->currency_code,
                $request->service_id,
                $request->include_fees);

            // Notification Area Start
            $emailService = app(EmailService::class);
            $payload = [
                'title' => 'Money Transfer Receipt',
                'subtitle' => 'Wallet-to-Person Transaction',
                'message' => 'You have received money through Exchain. Please use the transaction number below as a reference to collect your funds.',
                'receiver_name' => $transaction->receiver_name ?? $request->receiver_email ?? 'N/A',
                'receiver_email' => $transaction->receiver_email ?? $request->receiver_email ?? 'N/A',
                'transaction_id' => $this->generateReferenceCode($transaction->transaction_id),
                'received_amount' => $transaction->received_amount ?? $request->transfer_amount ?? 0,
                'currency' => $transaction->currency_code ?? $request->currency_code ?? 'USD',
                'cta_url' => url('/transactions/show'),
                'cta_text' => 'View Transaction',
                'note' => 'This is an automated receipt. Please do not reply to this email.',
            ];

            $emailService->sendWalletToPerson($request->user(), $payload);


            return response() -> json([
                'success' => true,
                'message' => 'Transfer initiated successfully.',
                'data' => $transaction,
            ]);


        }catch(Exception $e){
            return response()->json([
                'success' =>false,
                'message' => $e->getMessage(),
            ],  400);
        }
    }

    // Get single receipt of transaction
    public function getReceipt(Request $request, $transaction_id){
        try{
            return $this->walletToPersonService->getReceipt($transaction_id, $request->user()->user_id);
        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ],403);
        }
    }

    public function getTransactions(Request $request){
        $wallet_id = $request->query('wallet_id');

        return $this->walletToPersonService->getUserWalletToPersonTransactions(
          request()->user()->user_id,
          $wallet_id
        );
    }

    // Admin Wallet to person

    public function verifyTransactionAgent(Request $request){
        $validator = Validator::make($request->all(), [
            'reference_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }


        return $this->walletToPersonService->verifyTransaction(
            $request->reference_code,
        );
    }

    public function completeTransactionAgent(Request $request){
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return $this->walletToPersonService->completeWalletToPersonTransactions(
            $request->transaction_id,
            $request->user()->user_id
        );

    }

}
