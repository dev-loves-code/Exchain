<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportRequestsController extends Controller
{



    /**
    User Side for support requests
     **/
    public function store(Request $request)
    {
        try {
            $request->validate([
                'subject' => 'required|string|max:150',
                'description' => 'required|string',
            ]);
        }catch(ValidationException $e){
            return response() -> json([
                'errors' => $e->errors()
            ]);
        }

        $support = SupportRequest::create([
            'user_id' => $request->user()->user_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json([
            'success'=>true,
            'message'=> 'Support request sent successfully',
            'data' => $support
        ],201);

    }

    public function viewAllRequests(Request $request){

        $query = SupportRequest::where('user_id',$request->user()->user_id);

        $supportRequests = $query->latest()->get();

        $result = $supportRequests->map(function ($req) {
            return [
                'support_id' => $req->support_id,
                'subject' => $req->subject,
                'status' => $req->status,
                'sent_at' => $req->created_at,
            ];
        });

        return response()->json($result);
    }

    public function showSingleRequest(Request $request, $id)
    {
        $user_id = $request->user()->user_id;
        $supportRequest = SupportRequest::where('user_id',$user_id)->where('support_id',$id)->firstOrFail();


        return response()->json([
            'support_request' => [
                'support_id' => $supportRequest->support_id,
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

        try{
        $request->validate([
            'status' => ['nullable',Rule::in($valid_statuses)],
            'order_by' => ['nullable',Rule::in($valid_order)],
        ]);
        }catch(ValidationException $e){
            return response() -> json([
                'errors' => $e->errors()
            ]);
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

    public function viewAllSupportRequestsAdmin(Request $request)
    {
        $role = $request->user()->role->role_name;

        if($role !== 'admin')
        {
            return response() ->json([
                'error' => 'Unauthorized User'
            ], 403);
        }
        $supportRequests = SupportRequest::with('user')->get();

        return response()->json([
            'support_requests' => $supportRequests->map(function ($req) {
                return [
                    'support_id' => $req->support_id,
                    'user_full_name' => $req->user ? $req->user->full_name : null,
                    'user_email' => $req->user ? $req->user->email : null,
                    'subject' => $req->subject,
                    'status' => $req->status,
                    'received_at' => $req->created_at,
                ];
            }),
        ]);

    }

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

    public function update(Request $request, $id)
    {
        $role = $request->user()->role->role_name;


    try{
        $request->validate([
            'status' => 'required|in:pending,resolved,closed',
        ]);
    }catch(ValidationException $e){
        return response() -> json([
            'errors' => $e->errors()
        ]);
    }

        if($role !== 'admin')
        {
            return response() ->json([
                'error' => 'Unauthorized User'
            ], 403);
        }

        $support_request = SupportRequest::findOrFail($id);

        $support_request->update([
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        return response()->json([
           'success' => true,
           'message' => 'Support request status updated successfully.',
           'data' => $support_request,
        ]);


    }

}
