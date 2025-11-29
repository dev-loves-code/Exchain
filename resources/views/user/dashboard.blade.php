@extends('layouts.dashboard')

@section('title', 'User Dashboard')

@section('content')
<div class="dashboard-cards">
    <div class="card">
        <h3>Total Transactions</h3>
        <p>{{ $total_transactions }}</p>
    </div>
    <div class="card">
        <h3>Pending</h3>
        <p>{{ $pending }}</p>
    </div>
    <div class="card">
        <h3>Completed</h3>
        <p>{{ $done }}</p>
    </div>
    <div class="card">
        <h3>Refunded</h3>
        <p>{{ $refunded }}</p>
    </div>
    <div class="card">
        <h3>Rejected</h3>
        <p>{{ $rejected }}</p>
    </div>
    <div class="card">
        <h3>Total Sent Amount</h3>
        <p>{{ $total_transfer_amount }}</p>
    </div>
</div>

<div class="recent-transactions">
    <h3>Recent Transactions</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Receiver</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($latest_transactions as $tx)
            <tr>
                <td>{{ $tx->transaction_id }}</td>
                <td>
                    @if($tx->receiver_wallet)
                        {{ $tx->receiver_wallet->user->full_name }}
                    @else
                        {{ $tx->receiver_bank_account ?? $tx->receiver_email }}
                    @endif
                </td>
                <td>{{ $tx->transfer_amount }}</td>
                <td>{{ $tx->status }}</td>
                <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection