<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportRequestsController extends Controller
{



    /**
    User Side for support requests
     **/
    public function store(Request $request)
    {
        // Validation for input
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:150',
            'description' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response() -> json([
                'errors' => $validator->errors()
            ]);
        }

        // Creating support Request
        $support = SupportRequest::create([
            'user_id' => $request->user()->user_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // Notification Area Start

        $emailService = app(EmailService::class);
        $payload = [
            'subject' => $support->subject ?? 'Issue with Wallet Transfer',
            'description' => $support->description ?? 'The user reports that a recent wallet-to-person transaction did not go through. Please investigate the payment logs and respond accordingly.',
            'email' => $request->user()->email ?? 'user@example.com',
            'cta_url' => url('/admin/support-requests/' . ($support->id ?? 0)),
            'cta_text' => 'View Request',
            'note' => 'This is an automated notification regarding a new support request.',
        ];
        $admin = User::where('role_id',1)->firstOrFail();
        $emailService->sendSupportRequest($admin, $payload);



        $notificationService = app(\App\Services\NotificationService::class);
        $admins = User::where('role_id', 1)->get();
        foreach ($admins as $admin) {
            $notificationService->createNotification(
                $admin,
                'New Support Request Submitted',
                "A new support request has been submitted by {$request->user()->full_name}. Subject: {$support->subject}."
            );
        }

        // End Notification Area

        // Returning response
        return response()->json([
            'success'=>true,
            'message'=> 'Support request sent successfully',
            'data' => $support
        ],201);

    }



    public function showSingleRequest(Request $request, $id)
    {
        $user_id = $request->user()->user_id;
        $supportRequest = SupportRequest::where('user_id',$user_id)->where('support_id',$id)->firstOrFail();


        return response()->json([
            'support_request' => [
                'support_id' => $supportRequest->support_id,
                'user_full_name' => $supportRequest->user ? $supportRequest->user->full_name : null,
                'user_email' => $supportRequest->user ? $supportRequest->user->email : null,
                'subject' => $supportRequest->subject,
                'description' => $supportRequest->description,
                'status' => $supportRequest->status,
                'sent_at' => $supportRequest->created_at,

            ],
        ]);
    }

    /**
    Common method for both admin and user
     **/
    public function filterSupportRequests(Request $request)
    {
        $user_id = $request->user()->user_id;
        $role = $request->user()->role->role_name;
        $valid_order = ['latest','oldest'];
        $valid_statuses = ['pending', 'resolved', 'closed'];

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in($valid_statuses)],
            'order_by' => ['nullable', Rule::in($valid_order)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = SupportRequest::query();

        if($role !== 'admin') {
            $query->where('user_id', $user_id);
        }
        else{
            $query->with('user');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $order_by_date = $request->order_by;
        if ($request->filled('order_by')) {
            if ($order_by_date === 'oldest') {
                $query->oldest();
            } else {
                $query->latest();

            }
        }

        $supportRequests = $query->get();

        return response()->json(
            $supportRequests->map(function ($req) use ($role) {
                return $role === 'admin'
                    ? [
                        'support_id' => $req->support_id,
                        'user_full_name' => $req->user ? $req->user->full_name : null,
                        'user_email' => $req->user ? $req->user->email : null,
                        'subject' => $req->subject,
                        'status' => $req->status,
                        'received_at' => $req->created_at,

                    ]
                    : [
                        'support_id' => $req->support_id,
                        'subject' => $req->subject,
                        'status' => $req->status,
                        'sent_at' => $req->created_at,

                    ];
            })
        );


    }

    /**
    Admin Side
     **/

    public function showSingleRequestAdmin(Request $request, $id)
    {
        $role = $request->user()->role->role_name;

        if($role !== 'admin')
        {
            return response() ->json([
                'error' => 'Unauthorized User'
            ], 403);
        }
        $supportRequests = SupportRequest::with('user')->findOrFail($id);

        return response()->json([
            'support_request' => [
                    'support_id' => $supportRequests->support_id,
                    'user_full_name' => $supportRequests->user ? $supportRequests->user->full_name : null,
                    'user_email' => $supportRequests->user ? $supportRequests->user->email : null,
                    'subject' => $supportRequests->subject,
                    'description' => $supportRequests->description,
                    'status' => $supportRequests->status,
                    'received_at' => $supportRequests->created_at,
                ],
        ]);
    }

    // Update status function
    public function update(Request $request, $id)
    {
        $role = $request->user()->role->role_name;
        $valid_statuses = ['pending', 'resolved', 'closed'];


    // Validation for status input
        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in($valid_statuses)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if !Admin
        if($role !== 'admin')
        {
            return response() ->json([
                'error' => 'Unauthorized User'
            ], 403);
        }

        $support_request = SupportRequest::findOrFail($id);

        // Update Status
        $support_request->update([
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        //notify user
        $notificationService = app(\App\Services\NotificationService::class);
        $user = User::find($support_request->user_id);
        $notificationService->createNotification(
            $user,
            'Support Request Status Updated',
            "The status of your support request (ID: {$support_request->support_id}) has been updated to '{$support_request->status}'."
        );

        return response()->json([
           'success' => true,
           'message' => 'Support request status updated successfully.',
           'data' => $support_request,
        ]);


    }

}
