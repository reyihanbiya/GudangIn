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
                        <a class="{{ request()->routeIs('employee.dashboard') ? 'active' : '' }}" href="{{ route('employee.dashboard') }}"><span aria-hidden="true">⌂</span>Dashboard</a>
                        <a class="{{ request()->routeIs('employee.persediaan.*') ? 'active' : '' }}" href="{{ route('employee.persediaan.index') }}"><span aria-hidden="true">▦</span>Persediaan</a>
                        <a class="{{ request()->routeIs('employee.pembelian.*') ? 'active' : '' }}" href="{{ route('employee.pembelian.index') }}"><span aria-hidden="true">↙</span>Pembelian</a>
                        <a class="{{ request()->routeIs('employee.penjualan.history', 'employee.penjualan.show') ? 'active' : '' }}" href="{{ route('employee.penjualan.history') }}"><span aria-hidden="true">▤</span>Riwayat transaksi</a>
                        <a class="{{ request()->routeIs('employee.penjualan.index', 'employee.penjualan.create') ? 'active' : '' }}" href="{{ route('employee.penjualan.index') }}" @if ($isCashier) aria-current="page" @endif><span aria-hidden="true">▣</span>Kasir</a>
                        <a class="{{ request()->routeIs('employee.pelanggan.*') ? 'active' : '' }}" href="{{ route('employee.pelanggan.index') }}"><span aria-hidden="true">♙</span>Pengiriman</a>
                        <a class="{{ request()->routeIs('employee.supplier.*') ? 'active' : '' }}" href="{{ route('employee.supplier.index') }}"><span aria-hidden="true">⚒</span>Supplier</a>
                    @else
                        <a class="{{ request()->routeIs('owner.dashboard') ? 'active' : '' }}" href="{{ route('owner.dashboard') }}"><span aria-hidden="true">⌂</span>Ringkasan</a>
                        <a class="{{ request()->routeIs('owner.accounts.*') ? 'active' : '' }}" href="{{ route('owner.accounts.index') }}"><span aria-hidden="true">♙</span>Akun Owner</a>
                        <a class="{{ request()->routeIs('owner.reports.sales') ? 'active' : '' }}" href="{{ route('owner.reports.sales') }}"><span aria-hidden="true">▤</span>Laporan penjualan</a>
                        <a class="{{ request()->routeIs('owner.reports.purchases') ? 'active' : '' }}" href="{{ route('owner.reports.purchases') }}"><span aria-hidden="true">↙</span>Laporan pembelian</a>
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
