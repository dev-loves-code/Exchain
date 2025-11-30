<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionTrackingController extends Controller
{
     public function index(Request $request)
    {
        $query = Transaction::with(['senderWallet.user', 'receiverWallet.user', 'agent', 'service']);

        if ($request->status)
            $query->where('status', $request->status);

        if ($request->agent_id)
            $query->where('agent_id', $request->agent_id);

        if ($request->user_id)
            $query->whereHas('senderWallet', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });

        if ($request->from && $request->to)
            $query->whereBetween('created_at', [$request->from, $request->to]);

        return response()->json($query->orderBy('transaction_id', 'desc')->get());
    }

    // GET /transactions/{id}
    public function show($id)
    {
        $transaction = Transaction::with(['senderWallet.user', 'receiverWallet.user', 'agent', 'service'])
            ->findOrFail($id);

        return response()->json($transaction);
    }
}
