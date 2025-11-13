<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\RefundRequestService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;

class RefundRequestsController extends Controller
{
    protected $refundRequestService;
    public function __construct(RefundRequestService $refundRequestService){
        $this->refundRequestService = $refundRequestService;
    }

    public function create(Request $request)
    {

        try {
            $request->validate([
                'description'=> 'required|string',
            ]);
        }catch(ValidationException $e){
            return response() -> json(['errors' => $e->errors()]);
        }

        $already_exists = RefundRequest::where('transaction_id',$request->transaction_id)->exists();
        if ($already_exists) {
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
        try {

        $user_id = $request->user()->user_id;

        $refund_request = RefundRequest::findOrFail($id);
        $transaction = Transaction::findOrFail($refund_request->transaction_id);

        $wallet = Wallet::findOrFail($transaction->sender_wallet_id);


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
        catch(Exception $e){
            return response() -> json(['errors' => $e->getMessage()]);
        }
    }

    public function viewAllRefundRequests(Request $request)
    {
        $valid_order = ['latest','oldest'];
        $valid_statuses = ['pending', 'approved', 'rejected', 'completed'];

        try{
            $request->validate([
                'status' => ['nullable',Rule::in($valid_statuses)],
                'order_by' => ['nullable',Rule::in($valid_order)],
            ]);
        }catch(Exception $e){
            return response() -> json(['errors' => $e->errors()],422);
        }
        $user_role = $request->user()->role->role_name;

        if($user_role !== "admin")
        {
            return response() -> json([
                'message' => 'You do not have the required permissions!',
            ]);
        }

        //Filtering methods
        $query = RefundRequest::query();

        if($request->filled('status') )
        {
            $query->where('status',$request->status);
        }
        if($request->filled('order_by') ){
            $order_by_date = $request->order_by;
            if($order_by_date === 'oldest')
            {
                $query->oldest();
            }
            else{
                $query->latest();
            }
        }

        $refund_requests = $query->with('transaction.senderWallet.user')->get();

        return response() -> json(
            $refund_requests->map(function($refund_request){
                return[
                'refund_id' => $refund_request ->refund_id,
                'transaction_id' => $refund_request ->transaction_id,
                'user_name' => $refund_request ->transaction->senderWallet->user->full_name ?? null,
                'user_email' => $refund_request ->transaction->senderWallet->user->email ?? null,
                'status' => $refund_request ->status,
                'sent_at' => $refund_request ->created_at,
                ];
            })
        );
    }

    public function approveRefund(Request $request, $id){
         try{
             if($request->user()->role->role_name !== "admin"){
                 return response() -> json([
                     'message' => 'You do not have permission to approve refund requests!',
                 ],401);
             }

              $refundRequest = $this->refundRequestService->approveRefundRequest($id);

             return response() -> json([
                 'success' => true,
                 'message' => 'Refund request approved',
                 'data' => $refundRequest,
             ],200);

         }catch(Exception $e){
             return response() -> json(['errors' => $e->getMessage()],400);
         }
    }

    public function rejectRefund(Request $request,$id){
        try{

            if($request->user()->role->role_name !== "admin"){
                return response() -> json([
                    'message' => 'You do not have permission to approve refund requests!',
                ],401);
            }

            $validated = $request->validate([
                'rejected_reason' => 'nullable|string|max:255',
            ]);

            $refundRequest = $this->refundRequestService->rejectRefundRequest(
                $id,
                $validated['rejected_reason']??null
            );

            return response() -> json([
                'success' => true,
                'message' => 'Refund request rejected',
                'data' => $refundRequest,
            ],200);

        }catch(Exception $e){
            return response() -> json(['errors' => $e->getMessage()],400);
        }
    }

    public function completeRefund(Request $request,$id){

        try{
            $user_id = $request->user()->user_id;

            $refundRequest = $this->refundRequestService->completeRefundRequest($id,$user_id);

            return response() -> json([
                'success' => true,
                'message' => 'Refund request completed successfully',
                'data' => $refundRequest,
            ],200);

        }catch(Exception $e){
            return response() -> json(['errors' => $e->getMessage()],400);
        }
    }

    public function cancelRefund(Request $request,$id){
        try{

            $user_id = $request->user()->user_id;
            $refund_request = $this->refundRequestService->cancelRefundRequest($id,$user_id);

            return response() -> json([
                'success' => true,
                'message' => 'Refund request canceled successfully',
            ],200);

        }catch(Exception $e){
            return response() -> json(['errors' => $e->getMessage()],400);
        }
    }


}

