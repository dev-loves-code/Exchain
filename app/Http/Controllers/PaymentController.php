<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        $result = $this->stripeService->rechargeWalletWithCard(
            $request->user,
            (float) $request->amount,
            $request->payment_method_id,
            (int) $request->wallet_id,
            strtoupper($request->currency)
         
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

   


    public function getWalletBalance(Request $request)
    {
        $result = $this->stripeService->getWalletBalance($request->user);

        $statusCode = $result['success'] ? 200 : 404;

        return response()->json($result, $statusCode);
    }


    public function listPaymentMethods(Request $request)
    {
        $result = $this->stripeService->listPaymentMethods($request->user);

        return response()->json($result, 200);
    }


}
