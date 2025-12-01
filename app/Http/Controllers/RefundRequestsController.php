<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\EmailService;
use App\Services\RefundRequestService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }


        $already_exists = RefundRequest::where('transaction_id',$request->transaction_id)->exists();
        if ($already_exists) {
            return response() -> json(['errors' => ["Refund request for this transaction already exists!"]],409);
        }

        $transaction_not_pending = Transaction::where('transaction_id',$request->transaction_id)->first();
        if ($transaction_not_pending->status !== "pending") {
            return response() -> json(['errors' => ["Transaction cannot be refunded it is not pending."]],409);
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
        //<---------------------------------------------->
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

            return response()->json([
                'refund_id' => $refund_request->refund_id,
                'transaction_id' => $refund_request->transaction_id,
                'user_name' => $wallet->user->full_name ?? null,
                'user_email' => $wallet->user->email ?? null,
                'amount' => $transaction->transfer_amount,
                'currency' => $wallet->currency_code,
                'description' => $refund_request->description,
                'status' => $refund_request->status,
                'sent_at' => $refund_request->created_at,
            ]);

        }
        catch(Exception $e){
            return response() -> json(['errors' => $e->getMessage()]);
        }
    }

    /**
        * ViewAllRefundRequests Only for admin
        * Filtering Ability based on both status, and date.
     **/
    public function viewAllRefundRequests(Request $request)
    {
        $valid_order = ['latest','oldest'];
        $valid_statuses = ['pending', 'approved', 'rejected', 'completed'];

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in($valid_statuses)],
            'order_by' => ['nullable', Rule::in($valid_order)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $user_role = $request->user()->role->role_name;

        if($user_role !== "admin")
        {
            return response() -> json([
                'message' => 'You do not have the required permissions!',
            ],401);
        }

        //Filtering methods
        $query = RefundRequest::query();

        if($request->filled('status'))
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



    /**
     * Reject A refund request as Admin Only
    **/
    public function rejectRefund(Request $request,$id){
        try{

            if($request->user()->role->role_name !== "admin"){
                return response() -> json([
                    'message' => 'You do not have permission to approve refund requests!',
                ],401);
            }

            $validator = Validator::make($request->all(), [
                'rejected_reason' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();


            $refundRequest = $this->refundRequestService->rejectRefundRequest(
                $id,
                $validated['rejected_reason']??null
            );

            // Notification Area Start
            $emailService = app(EmailService::class);
            $payload = [
                'title' => 'Your Refund Request Update',
                'subtitle' => 'Here is the result of your refund review.',
                'status' => 'rejected',
                'message' => 'Unfortunately, your refund request could not be approved based on our policy.',
                'cta_url' => url('/admin/agents'), // make changes if changed,
                'cta_text' => 'View Details'
            ];
            $emailService->sendRefundRequest($request->user(), $payload);

            // End Notification Area

            return response() -> json([
                'success' => true,
                'message' => 'Refund request rejected',
                'data' => $refundRequest,
            ],200);

        }catch(Exception $e){
            return response() -> json(['errors' => $e->getMessage()],400);
        }
    }

    /**
     * Complete a refund after approval.
    **/
    public function completeRefund(Request $request,$id){

        try{

            $refundRequest = $this->refundRequestService->processRefund($id);

            // Notification Area Start
            $emailService = app(EmailService::class);
            $payload = [
                'title' => 'Your Refund Request Update',
                'subtitle' => 'Here is the result of your refund review.',
                'status' => 'approved', // or 'rejected'
                'message' => 'We’re happy to let you know that your refund has been approved.',
                'cta_url' => url('/admin/agents'), // make changes if changed,
                'cta_text' => 'View Details'
            ];
            $emailService->sendRefundRequest($request->user(), $payload);

            // End Notification Area

            return response() -> json([
                'success' => true,
                'message' => 'Refund request completed successfully',
                'data' => $refundRequest,
            ],200);

        }catch(Exception $e){
            return response() -> json(['errors' => $e->getMessage()],400);
        }
    }

    /**
     * Cancel a refund request
     * Only For Users
    **/
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

