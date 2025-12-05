@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')
<div class="dashboard-cards">
    <div class="card">
        <h3>Total Transactions</h3>
        <p>{{ $total_transactions }}</p>
    </div>
    <div class="card">
        <h3>Total Volume</h3>
        <p>{{ $total_volume }}</p>
    </div>
    <div class="card">
        <h3>Total Fees</h3>
        <p>{{ $total_fees }}</p>
    </div>
    <div class="card">
        <h3>Today Transactions</h3>
        <p>{{ $today_transactions }}</p>
    </div>
    <div class="card">
        <h3>This Week Transactions</h3>
        <p>{{ $week_transactions }}</p>
    </div>
</div>

<div class="top-agents">
    <h3>Top Agents</h3>
    <ul>
        @foreach($top_agents as $agent)
        <li>{{ $agent->full_name }} - Transactions: {{ $agent->total_transactions ?? 0 }}</li>
        @endforeach
    </ul>
</div>

<div class="active-users">
    <h3>Active Users</h3>
    <ul>
        @foreach($active_users as $user)
        <li>{{ $user->full_name }} - Transactions: {{ $user->tx_count ?? 0 }}</li>
        @endforeach
    </ul>
</div>
@endsection