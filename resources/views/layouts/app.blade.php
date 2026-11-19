<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GudangIn')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
@php
    $isEmployee = auth()->guard('employee')->check();
    $isOwner = auth()->guard('owner')->check();
    $isCashier = request()->routeIs('employee.penjualan.index');
@endphp
<body class="{{ $isEmployee || $isOwner ? 'app-shell-body' : '' }}">
    @if ($isEmployee || $isOwner)
        <main class="cashier-layout app-layout">
            <aside class="cashier-sidebar">
                <a class="brand" href="{{ $isEmployee ? route('employee.dashboard') : route('owner.dashboard') }}">
                    <img class="brand-mark" src="{{ asset('images/gudangin-mark.png') }}" alt="">
                    <span>Gudang<span class="brand-highlight">In</span></span>
                </a>
                <p class="cashier-nav-label">Menu utama</p>
                <nav class="cashier-nav" aria-label="Navigasi utama">
                    @if ($isEmployee)
                        <a class="{{ request()->routeIs('employee.dashboard') ? 'active' : '' }}" href="{{ route('employee.dashboard') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="11" width="7" height="10" rx="1"/><rect x="3" y="14" width="8" height="7" rx="1"/></svg></span>Dashboard</a>
                        <a class="{{ request()->routeIs('employee.persediaan.*') ? 'active' : '' }}" href="{{ route('employee.persediaan.index') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 8 9 5 9-5"/><path d="M3 8v9l9 5 9-5V8"/><path d="M12 13v9"/></svg></span>Persediaan</a>
                        <a class="{{ request()->routeIs('employee.pembelian.*') ? 'active' : '' }}" href="{{ route('employee.pembelian.index') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg></span>Pembelian</a>
                        <a class="{{ request()->routeIs('employee.penjualan.history', 'employee.penjualan.show') ? 'active' : '' }}" href="{{ route('employee.penjualan.history') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12a2 2 0 0 1 2 2v17l-3-2-3 2-3-2-3 2-3-2-3 2V5a2 2 0 0 1 2-2Z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg></span>Riwayat transaksi</a>
                        <a class="{{ request()->routeIs('employee.penjualan.index', 'employee.penjualan.create') ? 'active' : '' }}" href="{{ route('employee.penjualan.index') }}" @if ($isCashier) aria-current="page" @endif><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8v4H8zM8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg></span>Kasir</a>
                        <a class="{{ request()->routeIs('employee.pelanggan.*') ? 'active' : '' }}" href="{{ route('employee.pelanggan.index') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h12v11H3zM15 11h4l3 4v3h-7"/><path d="M3 11h12M6 18a2 2 0 1 0 4 0M17 18a2 2 0 1 0 4 0M17 11v4h5"/></svg></span>Pengiriman</a>
                        <a class="{{ request()->routeIs('employee.supplier.*') ? 'active' : '' }}" href="{{ route('employee.supplier.index') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18l-2-6H5l-2 6Z"/><path d="M4 10v10h16V10M2 20h20"/><path d="M8 20v-6h8v6M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/></svg></span>Supplier</a>
                    @else
                        <a class="{{ request()->routeIs('owner.dashboard') ? 'active' : '' }}" href="{{ route('owner.dashboard') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="11" width="7" height="10" rx="1"/><rect x="3" y="14" width="8" height="7" rx="1"/></svg></span>Ringkasan</a>
                        <a class="{{ request()->routeIs('owner.reports.sales') ? 'active' : '' }}" href="{{ route('owner.reports.sales') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12a2 2 0 0 1 2 2v17l-3-2-3 2-3-2-3 2-3-2-3 2V5a2 2 0 0 1 2-2Z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg></span>Laporan penjualan</a>
                        <a class="{{ request()->routeIs('owner.reports.purchases') ? 'active' : '' }}" href="{{ route('owner.reports.purchases') }}"><span aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg></span>Laporan pembelian</a>
                    @endif
                </nav>
                <div class="cashier-sidebar-bottom">
                    <span class="cashier-sidebar-user">
                        {{ $isEmployee ? auth()->guard('employee')->user()->nama_pegawai : auth()->guard('owner')->user()->nama_owner }}
                        <small>{{ $isEmployee ? 'Pegawai' : 'Owner' }}</small>
                    </span>
                    <form method="POST" action="{{ $isEmployee ? route('employee.logout') : route('owner.logout') }}">
                        @csrf
                        <button class="cashier-logout" type="submit">Keluar</button>
                    </form>
                </div>
            </aside>
            <section class="cashier-workspace app-workspace">
                <header class="cashier-topbar">
                    <div>
                        <h1>{{ $isCashier ? 'Kasir' : ($isEmployee ? 'Operasional toko' : 'Pemantauan toko') }}</h1>
                        <p>{{ $isCashier ? 'Catat transaksi penjualan dengan cepat dan mudah.' : 'GudangIn · Kelola aktivitas toko dengan rapi.' }}</p>
                    </div>
                    <div class="cashier-topbar-meta">
                        <span>{{ now()->translatedFormat('d F Y') }}</span>
                        <span class="cashier-employee">
                            {{ $isEmployee ? auth()->guard('employee')->user()->nama_pegawai : auth()->guard('owner')->user()->nama_owner }}
                            <small>{{ $isEmployee ? 'Pegawai' : 'Owner' }}</small>
                        </span>
                    </div>
                </header>
                @if ($isCashier)
                    @yield('content')
                @else
                    <main class="page-shell app-page-shell">
                        @yield('content')
                        <footer class="footer">GudangIn <span>Kelola stok, tumbuh bersama.</span></footer>
                    </main>
                @endif
            </section>
        </main>
    @else
        <main class="page-shell">
            <header class="topbar">
                <a class="brand" href="{{ route('home') }}">
                    <img class="brand-mark" src="{{ asset('images/gudangin-mark.png') }}" alt="">
                    <span>Gudang<span class="brand-highlight">In</span></span>
                </a>
                @include('partials.session-actions')
            </header>
            @yield('content')
            <footer class="footer">GudangIn <span>Kelola stok, tumbuh bersama.</span></footer>
        </main>
    @endif
    @stack('scripts')
</body>
</html>
