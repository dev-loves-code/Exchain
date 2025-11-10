<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use Illuminate\Http\Request;

class SupportRequestsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:150',
            'description' => 'required|string',
        ]);

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

        return response() ->json([
            $query->latest()->get(),
        ]);
    }

    public function showSingleRequest(Request $request, $id)
    {
        $user_id = $request->user()->user_id;
        $supportRequest = SupportRequest::where('user_id',$user_id)->where('support_id',$id)->firstOrFail();

        return response() ->json($supportRequest);
    }

    public function filterSupportRequests(Request $request)
    {
        $user_id = $request->user()->user_id;
//        $valid_statuses = ['pending', 'processing', 'done'];
//        $valid_order = ['latest','oldest'];

        $query = SupportRequest::query();

        $query->where('user_id',$user_id);

        if($request->filled('status'))
        {
            $query->where('status',$request->status);
        }
        $order_by_date = $request->order_by;
        if($request->filled('order_by'))
        {
            if ($order_by_date === 'oldest') {
                $query->oldest();
            } else {
                $query->latest();

            }
        }

        $supportRequests = $query->get();
        return response() ->json($supportRequests);
    }
}
