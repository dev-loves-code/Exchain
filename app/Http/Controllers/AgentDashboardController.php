<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\CashOperation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgentDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $agentId = $request->user()->user_id;
        $commissionRate = $request->user()->agentProfile->commission_rate ?? 0;

        $from = $request->from ? Carbon::parse($request->from) : now()->subMonth();
        $to = $request->to ? Carbon::parse($request->to) : now();

        $cashPickups = Transaction::where('agent_id', $agentId)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $uniqueUsers = $cashPickups->pluck('sender_wallet_id')->unique()->count();

        $cashOperations = CashOperation::where('agent_id', $agentId)
            ->whereBetween('created_at', [$from, $to])
            ->get();
$totalCommission = round(
    $cashOperations
        ->where('status', 'approved')
        ->sum(function ($op) {
            return $op->amount * $op->agent_commission / 100;
        }),
    2
);
        $usersPerDay = $cashPickups
            ->groupBy(function ($t) {
                return $t->created_at->format('Y-m-d');
            })
            ->map(function ($day) {
                return $day->pluck('sender_wallet_id')->unique()->count();
            });

        return response()->json([
            'summary' => [
                'total_transactions' => $cashPickups->count(),
                'pending' => $cashPickups->where('status', 'pending')->count(),
                'done' => $cashPickups->where('status', 'done')->count(),
                'total_transfer_amount' => $cashPickups->sum('transfer_amount'),
                'unique_users' => $uniqueUsers,
                'commission_rate' => $commissionRate,
                'total_commission_earned' => $totalCommission
            ],

            'history' => [
                'users_per_day' => $usersPerDay,
                'latest_transactions' => $cashPickups->sortByDesc('created_at')->take(10)->values(),
                'latest_cash_operations' => $cashOperations->sortByDesc('created_at')->take(10)->values(),
            ],
        ]);
    }
}