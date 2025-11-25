<?php

namespace App\Http\Controllers;

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

    public function initiateWalletToPersonTransfer(Request $request){

        try {
            $request->validate([
                'sender_wallet_id' => 'required|integer',
                'receiver_email' => 'required|string|email|max:255',
                'transfer_amount' => 'required|numeric|min:5',
                'currency_code' => 'required|string|size:3',
                'include_fees' => 'required|boolean',
            ]);
        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'errors' => $e->getMessage(),
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
                $request->include_fees);

            // Add Notification here Priority 1 <---------------------------------------------------------------------

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
        try{
            $request->validate([
                'reference_code' => 'required|string',
            ]);
        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->walletToPersonService->verifyTransaction(
            $request->reference_code,
        );
    }

    public function completeTransactionAgent(Request $request){
        try {
            $request->validate([
                'transaction_id' => 'required|integer',
            ]);

            return $this->walletToPersonService->completeWalletToPersonTransactions(
                $request->transaction_id,
                $request->user()->user_id
            );
        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
