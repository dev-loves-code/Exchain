<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Service;
use App\Services\CurrencyRateService;
use App\Services\WalletToWalletService;

class TransactionController extends Controller
{
    protected $currencyService;
    protected $walletToWalletService;

    public function __construct(CurrencyRateService $currencyService, WalletToWalletService $walletToWalletService){
        $this->currencyService = $currencyService;
        $this->walletToWalletService = $walletToWalletService;
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
}
