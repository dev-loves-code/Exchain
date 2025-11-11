<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class RefundRequestsController extends Controller
{
    public function create(Request $request)
    {

        try {
            $request->validate([
                'description'=> 'required|string',
            ]);
        }catch(ValidationException $e){
            return response() -> json(['errors' => $e->errors()]);
        }

        $already_exists = RefundRequest::where('transaction_id',$request->transaction_id);
        if (!is_null($already_exists)) {
            return response() -> json(['errors' => ["Refund request for this transaction already exists!"]],409);
        }

        $refund_request = RefundRequest::create([
            'transaction_id' => $request ->transaction_id,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        return response() -> json([
            'success' => true,
            'message' => 'Refund request created!',
            'data' => $refund_request,

        ],201);

        // Notification for user and admin
    }

    // Single Refund request

    public function viewSingleRefundRequest(Request $request,$id)
    {
        $user_id = $request->user()->user_id;
        $transaction = Transaction::find($id);

        $refund_request = RefundRequest::where('transaction_id',$id)->firstOrFail();
        $wallet = Wallet::find($transaction->sender_wallet_id);


        if ($user_id !== $wallet->user_id && $request->user()->role->role_name !== "admin")
        {
            return response() ->json([
                'message' => 'You do not have permission to view this refund request!',
            ]);
        }

        return response() -> json([
            'amount' => $transaction->transfer_amount,
            'currency' => $wallet->currency_code,
            'description' => $refund_request -> description,
            'status' => $refund_request -> status,
        ]);
    }
}

