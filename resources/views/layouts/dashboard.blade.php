{{-- resources/views/layouts/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @include('layouts.partials.header')

    <div class="main-wrapper" style="display:flex;">
        @include('layouts.partials.sidebar')

        <div class="content-wrapper" style="flex:1; padding: 2rem;">
            @yield('content')
        </div>
    </div>

    @include('layouts.partials.footer')
</body>
</html>