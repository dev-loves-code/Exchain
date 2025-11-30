{{-- resources/views/layouts/partials/header.blade.php --}}
<header class="main-header">
    <div class="logo">
        <a href="{{ url('/') }}">Exchain</a>
    </div>

    <nav class="navbar">
        <ul class="nav">
            <li>
                <span>Welcome, {{ auth()->user()->full_name }}</span>
            </li>
            <li>
                <form action="{{ url('auth/logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-logout">Logout</button>
                </form>
            </li>
        </ul>
    </nav>
</header>

<style>
.main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #2c3e50;
    color: white;
}
.navbar ul {
    display: flex;
    gap: 1rem;
    list-style: none;
}
.btn-logout {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 0.3rem 0.7rem;
    border-radius: 3px;
    cursor: pointer;
}
</style>