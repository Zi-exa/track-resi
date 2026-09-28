<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}ReturnTrack</title>
    @fonts('instrument-sans')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>*,*::before,*::after{outline:none!important;border-color:transparent}*:focus-visible{outline:2px solid #2367d1!important;outline-offset:2px!important}input:focus,select:focus,textarea:focus{outline:none!important;box-shadow:0 0 0 3px rgba(35,103,209,.1)!important;border-color:#79a7ec!important}.sidebar,.main-panel,.topbar,.page-heading,.page-heading h1{outline:none!important;box-shadow:none!important}</style>
</head>
<body class="app-body">
    <div class="app-shell" data-app-shell>
        <aside class="sidebar" data-sidebar>
            <a class="brand" href="{{ route('dashboard') }}" aria-label="ReturnTrack Dashboard">
                <span class="brand-mark">R</span>
                <span><strong>ReturnTrack</strong><small>EMZA STORE</small></span>
            </a>

            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <p class="nav-section">Overview</p>
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span> Dashboard</a>
                <a class="nav-item {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}"><span>⇧</span> Import Data</a>
                <p class="nav-section">Operasional</p>
                <a class="nav-item {{ request()->routeIs('returns.*') ? 'active' : '' }}" href="{{ route('returns.index') }}"><span>▤</span> Data Retur</a>
                <a class="nav-item {{ request()->routeIs('scan.*') ? 'active' : '' }}" href="{{ route('scan.index') }}"><span>⌗</span> Scan Paket</a>
                <a class="nav-item {{ request()->routeIs('inspections.*') ? 'active' : '' }}" href="{{ route('inspections.index') }}"><span>!</span> Perlu Diperiksa</a>
                <a class="nav-item {{ request()->routeIs('scan-history.*') ? 'active' : '' }}" href="{{ route('scan-history.index') }}"><span>↻</span> Riwayat Scan</a>
                <p class="nav-section">Analitik</p>
                <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span>▥</span> Laporan</a>
                <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span>⚙</span> Pengaturan</a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div>
                <form action="{{ route('logout') }}" method="POST">@csrf<button class="icon-button" type="submit" title="Keluar">↪</button></form>
            </div>
        </aside>

        <div class="sidebar-backdrop" data-sidebar-backdrop></div>

        <main class="main-panel">
            <header class="topbar">
                <button class="menu-button" type="button" data-menu-toggle aria-label="Buka menu">☰</button>
                <div class="topbar-copy"><span>{{ now()->translatedFormat('l, d F Y') }}</span></div>
                <a class="quick-scan" href="{{ route('scan.index') }}">⌗ <span>Scan paket</span></a>
            </header>

            <div class="content-wrap">
                @if (session('success')) <div class="alert alert-success"><span>✓</span><div>{{ session('success') }}</div></div> @endif
                @if (session('warning')) <div class="alert alert-warning"><span>!</span><div>{{ session('warning') }}</div></div> @endif
                @if (session('error')) <div class="alert alert-danger"><span>×</span><div>{{ session('error') }}</div></div> @endif
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
