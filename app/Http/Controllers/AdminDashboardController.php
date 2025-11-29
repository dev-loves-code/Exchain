<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use App\Models\CashOperation;

class AdminDashboardController extends Controller
{
   public function dashboard(Request $request)
{
    $from = $request->from ? Carbon::parse($request->from) : now()->subMonth();
    $to = $request->to ? Carbon::parse($request->to) : now();

    $transactions = Transaction::all();

        $totalUsers = User::where('role_id', '2')->count() ?? 0;
        $totalAgents = User::whereHas('agentProfile')->count();

    $usersPerDay = User::selectRaw('DATE(created_at) as date, COUNT(*) as total')
        ->whereBetween('created_at', [$from, $to])
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

    $transactionsByStatus = $transactions->groupBy('status')->map->count();


    $transactionsPerDay = Transaction::selectRaw('DATE(created_at) as date, COUNT(*) as total')
        ->whereBetween('created_at', [$from, $to])
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

    $activeUsers = User::withCount(['sentTransactions as total_sent_transactions'])
        ->orderByDesc('total_sent_transactions')
        ->take(5)
        ->get(['user_id', 'full_name']);
    $totalAgentCommission = CashOperation::where('status', 'approved')->sum('agent_commission');


    return response()->json([
        'summary' => [
            'total_transactions' => $transactions->count(),
            'total_volume' => $transactions->sum('transfer_amount'),
            'total_fees' => $transactions->sum('transfer_fee'),
            'total_users' => $totalUsers,
            'total_agents' => $totalAgents,
            'total_agent_commission' => $totalAgentCommission,
            'transactions_by_status'=>$transactionsByStatus,
        ],

        'history' => [
            'transactions_per_day' => $transactionsPerDay,
            'users_per_day' => $usersPerDay,
            'latest_transactions' => $transactions->sortByDesc('created_at')->take(10)->values(),
        ],
        'active_users' => $activeUsers,
    ]);
}
}