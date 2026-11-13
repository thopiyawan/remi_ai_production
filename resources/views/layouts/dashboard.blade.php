<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ภาพรวมการดูแล') · REMI</title>
    <link rel="icon" href="{{ asset('css/remi.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/remi-dashboard.css') }}?v={{ filemtime(public_path('css/remi-dashboard.css')) }}">
    @stack('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.1.0/dist/chartjs-plugin-annotation.min.js" defer></script>
    <script src="{{ asset('js/remi-dashboard.js') }}?v={{ filemtime(public_path('js/remi-dashboard.js')) }}" defer></script>
    @stack('scripts')
</head>
<body class="remi-app dashboard-page">
    <div class="dashboard-shell">
        <x-dashboard.sidebar
            :doctor-name="Session::get('doctor_name', '')"
            :doctor-id="Session::get('doctor_id', '')"
            :doctor-hospital="Session::get('doctor_hospital', '')"
            :doctor-qr-code="Session::get('qr_code', '')"
        />
        <main class="dashboard-main">
            @yield('content')
        </main>
        @yield('dashboard-floating-ui')
    </div>
</body>
</html>
