<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}ReturnTrack — EMZA STORE</title>
    @fonts('plus-jakarta-sans')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <div class="app-shell" data-app-shell>
        <aside class="sidebar" data-sidebar>
            <a class="brand" href="{{ route('dashboard') }}" aria-label="ReturnTrack Dashboard">
                <span class="brand-mark">R</span>
                <span><strong>ReturnTrack</strong><small>EMZA STORE</small></span>
            </a>

            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <div>
                    <p class="nav-section">Overview</p>
                    <div class="nav-group">
                        <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <span class="nav-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></span>
                            Dashboard
                        </a>
                        <a class="nav-item {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}">
                            <span class="nav-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg></span>
                            Import Data
                        </a>
                    </div>
                </div>
                <div>
                    <p class="nav-section">Operasional</p>
                    <div class="nav-group">
                        <a class="nav-item {{ request()->routeIs('returns.*') ? 'active' : '' }}" href="{{ route('returns.index') }}">
                            <span class="nav-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg></span>
                            Data Retur
                            @if(($navReturnCount ?? 0) > 0)<span class="nav-badge">{{ $navReturnCount > 99 ? '99+' : $navReturnCount }}</span>@endif
                        </a>
                        <a class="nav-item {{ request()->routeIs('scan.*') ? 'active' : '' }}" href="{{ route('scan.index') }}">
                            <span class="nav-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg></span>
                            Scan Paket
                        </a>
                        <a class="nav-item {{ request()->routeIs('inspections.*') ? 'active' : '' }}" href="{{ route('inspections.index') }}">
                            <span class="nav-ico" style="{{ ($navAttentionCount ?? 0) > 0 ? 'color:var(--amber)' : '' }}"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></span>
                            Perlu Diperiksa
                            @if(($navAttentionCount ?? 0) > 0)<span class="nav-badge nav-badge-amber">{{ $navAttentionCount > 99 ? '99+' : $navAttentionCount }}</span>@endif
                        </a>
                        <a class="nav-item {{ request()->routeIs('scan-history.*') ? 'active' : '' }}" href="{{ route('scan-history.index') }}">
                            <span class="nav-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                            Riwayat Scan
                        </a>
                    </div>
                </div>
                <div>
                    <p class="nav-section">Analitik &amp; Sistem</p>
                    <div class="nav-group">
                        <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <span class="nav-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                            Laporan
                        </a>
                        <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                            <span class="nav-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                            Pengaturan
                        </a>
                    </div>
                </div>
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
                <div class="topbar-copy">
                    <span class="live-pill"><span class="live-dot"></span>Live Monitoring</span>
                    <span class="topbar-date">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
                <form class="topbar-search" method="GET" action="{{ route('returns.index') }}" role="search">
                    <span>⌕</span>
                    <input name="search" value="{{ request('search') }}" placeholder="Cari No. Resi / Order..." autocomplete="off">
                </form>
                <a class="scan-btn" href="{{ route('scan.index') }}">⌗ <span>Scan Paket</span></a>
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
