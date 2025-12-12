<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function rechargeWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|string',
            'wallet_id'=>'required|integer|exists:wallets,wallet_id',
            'currency' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            Log::info('Recharge wallet request', [
                'user_id' => $request->user()?->user_id,
                'wallet_id' => $request->wallet_id,
                'amount' => $request->amount,
            ]);

            $result = $this->stripeService->rechargeWalletWithCard(
                $request->user(),
                (float) $request->amount,
                $request->payment_method_id,
                (int) $request->wallet_id,
                strtoupper($request->currency)
            );

            $statusCode = $result['success'] ? 200 : 400;
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Recharge wallet error: ' . $e->getMessage());
             return response() -> json(['errors' => $e->getMessage()],500);
        }
    }

   


    public function getWalletBalance(Request $request)
    {
        try {
            Log::info('Get wallet balance request', [
                'user_id' => $request->user()?->user_id,
            ]);

            $result = $this->stripeService->getWalletBalance($request->user());

            $statusCode = $result['success'] ? 200 : 404;

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Get wallet balance error: ' . $e->getMessage());
                        return response() -> json(['errors' => $e->getMessage()],500);

        }
    }


    public function listPaymentMethods(Request $request)
    {
        try {
            Log::info('List payment methods request', [
                'user_id' => $request->user()?->user_id,
            ]);

            $result = $this->stripeService->listPaymentMethods($request->user());

            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('List payment methods error: ' . $e->getMessage());
             return response() -> json(['errors' => $e->getMessage()],500);
        }
    }

    public function listStripeTransactions(Request $request)
    {
        try {
            $user = $request->user();
            
            $transactions = \App\Models\StripePayment::where('user_id', $user->user_id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($tx) {
                    return [
                        'stripe_payment_id' => $tx->stripe_payment_id,
                        'stripe_charge_id' => $tx->stripe_charge_id,
                        'stripe_payment_method_id' => $tx->stripe_payment_method_id,
                        'amount' => (float) $tx->amount,
                        'currency' => strtoupper($tx->currency),
                        'payment_type' => $tx->payment_type,
                        'status' => $tx->status,
                        'description' => $tx->description,
                        'stripe_metadata' => $tx->stripe_metadata,
                        'created_at' => $tx->created_at?->toIso8601String(),
                        'updated_at' => $tx->updated_at?->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching stripe transactions: ' . $e->getMessage());
            
            return response() -> json(['errors' => $e->getMessage()],500);
        }
    }
// public function transferToBank(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'wallet_id' => 'required|integer|exists:wallets,wallet_id',
//         'bank_account_id' => 'required|integer|exists:bank_accounts,bank_account_id',
//         'amount' => 'required|numeric|min:0.01',
//         'currency' => 'required|string|size:3',
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'success' => false,
//             'errors' => $validator->errors()
//         ], 422);
//     }

//     $result = $this->stripeService->sendToBank(
//         $request->user,
//         (float)$request->amount,
//         (int)$request->wallet_id,
//         strtoupper($request->currency),
//         (int)$request->bank_account_id
//     );

//     return response()->json($result, $result['success'] ? 200 : 400);
// }
public function transferToBank(Request $request)
{
    $validator = Validator::make($request->all(), [
        'wallet_id' => 'required|integer|exists:wallets,wallet_id',
        'amount' => 'required|numeric|min:0.01',
        'currency' => 'required|string|size:3',
        'beneficiary_id' => 'nullable|integer|exists:beneficiaries,beneficiary_id',
        'external_account_number' => 'required_without:beneficiary_id|string',
        'external_holder_name' => 'required_without:beneficiary_id|string',
        'external_bank_name' => 'required_without:beneficiary_id|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    try {
        Log::info('Transfer to bank request', [
            'user_id' => $request->user()?->user_id,
            'wallet_id' => $request->wallet_id,
            'amount' => $request->amount,
        ]);

        $result = $this->stripeService->transferToBank(
            $request->user(),
            $request->amount,
            $request->wallet_id,
            $request->currency,
            $request->beneficiary_id,
            $request->external_account_number,
            $request->external_holder_name,
            $request->external_bank_name
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
        Log::error('Transfer to bank error: ' . $e->getMessage());
                  return response() -> json(['errors' => $e->getMessage()],500);

    }
}



}
