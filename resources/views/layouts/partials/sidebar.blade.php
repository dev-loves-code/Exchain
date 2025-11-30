{{-- resources/views/layouts/partials/sidebar.blade.php --}}
<aside class="sidebar">
    <div class="sidebar-logo">
        <h2>Exchain</h2>
    </div>

    <ul class="sidebar-menu">
        @if(auth()->user()->isAdmin())
            <li><a href="{{ url('/admin/dashboard') }}">Dashboard</a></li>
            <li><a href="{{ url('/services') }}">Services</a></li>
            <li><a href="{{ url('/admin/agents') }}">Agents</a></li>
        @elseif(auth()->user()->isAgent())
            <li><a href="{{ url('/agent/dashboard') }}">Dashboard</a></li>
            <li><a href="{{ url('/agent/profile') }}">Profile</a></li>
            <li><a href="{{ url('/transactions') }}">Transactions</a></li>
        @else
            <li><a href="{{ url('/user/dashboard') }}">Dashboard</a></li>
            <li><a href="{{ url('/transactions') }}">My Transfers</a></li>
        @endif
    </ul>
</aside>

<style>
.sidebar {
    width: 220px;
    background: #34495e;
    color: white;
    min-height: 100vh;
    padding: 1rem;
}
.sidebar-logo h2 {
    text-align: center;
    margin-bottom: 2rem;
}
.sidebar-menu {
    list-style: none;
    padding: 0;
}
.sidebar-menu li {
    margin-bottom: 1rem;
}
.sidebar-menu a {
    color: white;
    text-decoration: none;
    display: block;
    padding: 0.5rem;
    border-radius: 3px;
}
.sidebar-menu a:hover {
    background: #2c3e50;
}
</style>