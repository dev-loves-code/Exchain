<?php

namespace App\Http\Controllers;

use App\Models\CashOperation;
use App\Services\WalletService;
use Illuminate\Http\Request;

class CashOperationController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'wallet_id' => 'required|exists:wallets,wallet_id',
            'operation_type' => 'required|in:deposit,withdrawal',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $agentProfile = $request->user()->agentProfile;
        $commissionRate = $agentProfile->commission_rate ?? 0;
        $commissionValue = ($commissionRate / 100) * $request->amount;

        $wallet = \App\Models\Wallet::findOrFail($request->wallet_id);

        if ($request->operation_type === 'withdrawal' && $wallet->balance < $request->amount) {
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
            'agent_commission' => $commissionValue,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'cash_operation' => $cashOp]);
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
        $amountToApply = $cashOp->amount;

        if ($cashOp->operation_type === 'withdrawal') {
            $amountToApply += $cashOp->agent_commission;
        }

        $success = false;
        if ($cashOp->operation_type === 'deposit') {
            $success = $this->walletService->deposit($wallet, $amountToApply);
        } elseif ($cashOp->operation_type === 'withdrawal') {
            $success = $this->walletService->withdraw($wallet, $amountToApply);
            if (! $success) {
                return response()->json(['error' => 'Insufficient funds'], 400);
            }
        }

        $cashOp->status = 'approved';
        $cashOp->save();

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
