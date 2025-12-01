<?php

namespace App\Http\Controllers;

use App\Models\CashOperation;
use App\Services\WalletService;
use App\Services\CurrencyRateService;
use Illuminate\Http\Request;

class CashOperationController extends Controller
{
    protected $walletService;
    protected $currencyService;

    public function __construct(WalletService $walletService, CurrencyRateService $currencyService)
    {
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
    }

    public function create(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,user_id',
        'wallet_id' => 'required|exists:wallets,wallet_id',
        'operation_type' => 'required|in:deposit,withdrawal',
        'amount' => 'required|numeric|min:0.01',
        'currency_code' => 'required|string|size:3', 
    ]);

    // Check if the wallet belongs to the given user
    $wallet = \App\Models\Wallet::where('wallet_id', $request->wallet_id)
        ->where('user_id', $request->user_id)
        ->first();
    
    if (!$wallet) {
        return response()->json([
            'success' => false,
            'message' => 'Wallet not found or does not belong to the specified user',
        ], 404);
    }
    
    // Rest of the code remains the same...
    $isValidCurrency = $this->currencyService->isValidCurrency($request->currency_code);
    if ($isValidCurrency->getData()->success === false) {
        return $isValidCurrency;
    }

    // Remove the redundant findOrFail since we already have the wallet
    // $wallet = \App\Models\Wallet::findOrFail($request->wallet_id);
    
    $rate_id = null;
    $exchange_rate = 1;
    $wallet_amount = $request->amount; 

    if ($request->currency_code !== $wallet->currency_code) {
        if ($request->operation_type === 'deposit') {
            // User gives cash in currency_code, wallet receives in wallet currency
            $exchangeResult = $this->currencyService->exchange(
                $request->amount,
                $request->currency_code,
                $wallet->currency_code
            );
            
            if (isset($exchangeResult['success']) && $exchangeResult['success'] === false) {
                return response()->json($exchangeResult, 500);
            }
            
            $wallet_amount = $exchangeResult['total'];
            $exchange_rate = $exchangeResult['exchange_rate'];
            $rate_id = $exchangeResult['rate_id'];
        } else {
            // Withdrawal: wallet pays in wallet currency, user receives cash in currency_code
            $exchangeResult = $this->currencyService->exchange(
                $request->amount,
                $request->currency_code,
                $wallet->currency_code
            );
            
            if (isset($exchangeResult['success']) && $exchangeResult['success'] === false) {
                return response()->json($exchangeResult, 500);
            }
            
            $wallet_amount = $exchangeResult['total'];
            $exchange_rate = $exchangeResult['exchange_rate'];
            $rate_id = $exchangeResult['rate_id'];
        }
    }

    $agentProfile = $request->user()->agentProfile;
    $commissionRate = $agentProfile->commission_rate ?? 0;
    $commissionValue = ($commissionRate / 100) * $request->amount;

    if ($request->operation_type === 'withdrawal' && $wallet->balance < $wallet_amount) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient funds in the wallet for this withdrawal',
        ], 400);
    }

    $cashOp = \App\Models\CashOperation::create([
        'user_id' => $request->user_id,
        'wallet_id' => $request->wallet_id,
        'agent_id' => $request->user()->user_id,
        'operation_type' => $request->operation_type,
        'amount' => $request->amount, 
        'currency_code' => $request->currency_code, 
        'wallet_amount' => $wallet_amount, // Amount in wallet currency
        'exchange_rate' => $exchange_rate,
        'rate_id' => $rate_id,
        'agent_commission' => $commissionValue,
        'status' => 'pending',
    ]);

    return response()->json([
        'success' => true,
        'cash_operation' => $cashOp,
        'details' => [
            'cash_amount' => $request->amount . ' ' . $request->currency_code,
            'wallet_amount' => $wallet_amount . ' ' . $wallet->currency_code,
            'exchange_rate' => $exchange_rate,
            'commission' => $commissionValue . ' ' . $request->currency_code,
        ]
    ]);
}

    public function approve(Request $request, $id)
    {
        $cashOp = CashOperation::findOrFail($id);

        if ($cashOp->user_id !== $request->user()->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($cashOp->status !== 'pending') {
            return response()->json(['error' => 'Operation is not pending'], 400);
        }

        $wallet = $cashOp->wallet;

        $success = false;
        if ($cashOp->operation_type === 'deposit') {
            // Deposit: add wallet_amount to wallet
            $success = $this->walletService->deposit($wallet, $cashOp->wallet_amount);
        } elseif ($cashOp->operation_type === 'withdrawal') {
            // Withdrawal: deduct wallet_amount from wallet
            $success = $this->walletService->withdraw($wallet, $cashOp->wallet_amount);
            if (!$success) {
                return response()->json(['error' => 'Insufficient funds'], 400);
            }
        }

        if ($success) {
            $cashOp->status = 'approved';
            $cashOp->save();
        }

        return response()->json(['success' => true, 'cash_operation' => $cashOp]);
    }

    public function reject(Request $request, $id)
    {
        $cashOp = CashOperation::findOrFail($id);

        if ($cashOp->user_id !== $request->user()->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($cashOp->status !== 'pending') {
            return response()->json(['error' => 'Operation is not pending'], 400);
        }

        $cashOp->status = 'rejected';
        $cashOp->save();

        return response()->json(['success' => true, 'cash_operation' => $cashOp]);
    }

    public function cancel(Request $request, $id)
    {
        $cashOp = CashOperation::findOrFail($id);

        if ($cashOp->agent_id !== $request->user()->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($cashOp->status !== 'pending') {
            return response()->json(['error' => 'Only pending operations can be cancelled'], 400);
        }

        $cashOp->status = 'cancelled';
        $cashOp->save();

        return response()->json(['success' => true, 'cash_operation' => $cashOp]);
    }
}