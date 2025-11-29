<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
class UserDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $userId = $request->user()->user_id;

        $transactions = Transaction::whereHas('senderWallet', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

       return response()->json([
    'total_transactions' => $transactions->count(),
    'pending' => $transactions->where('status', 'pending')->count(),
    'done' => $transactions->where('status', 'done')->count(),

    'total_sent_amount' => $transactions->sum('transfer_amount'),
    'total_fees_paid' => $transactions->sum('transfer_fee'),

    'latest_transactions' => $transactions->sortByDesc('created_at')->take(10)->values(),
]);
    }
}