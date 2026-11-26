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
    @if (request()->routeIs('employee.penjualan.index'))
        <link rel="stylesheet" href="{{ asset('css/cashier.css') }}">
    @endif
</head>
@php
    $isEmployee = auth()->guard('employee')->check();
    $isOwner = auth()->guard('owner')->check();
    $isCashier = request()->routeIs('employee.penjualan.index');
@endphp
<body class="{{ $isEmployee || $isOwner ? 'app-shell-body'.($isCashier ? '' : ' app-document-body') : '' }}">
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
                    <a class="{{ request()->routeIs('employee.dashboard') ? 'active' : '' }}" href="{{ route('employee.dashboard') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="dashboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg></span>
                        Dashboard
                    </a>
                    <a class="{{ request()->routeIs('employee.persediaan.*') ? 'active' : '' }}" href="{{ route('employee.persediaan.index') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="inventory" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2.8 8.5 4.7v9L12 21.2l-8.5-4.7v-9L12 2.8Z"/><path d="m3.8 7.7 8.2 4.6 8.2-4.6M12 12.3v8.4M8 5l8.4 4.7"/></svg></span>
                        Persediaan
                    </a>
                    <a class="{{ request()->routeIs('employee.pembelian.*') ? 'active' : '' }}" href="{{ route('employee.pembelian.index') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="purchases" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l1.2 12H3.8L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span>
                        Pembelian
                    </a>
                    <a class="{{ request()->routeIs('employee.penjualan.history', 'employee.penjualan.show') ? 'active' : '' }}" href="{{ route('employee.penjualan.history') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="receipt" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h12v17l-3-1.8-3 1.8-3-1.8-3 1.8v-17Z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg></span>
                        Riwayat transaksi
                    </a>
                    <a class="{{ request()->routeIs('employee.penjualan.index', 'employee.penjualan.create') ? 'active' : '' }}" href="{{ route('employee.penjualan.index') }}" @if ($isCashier) aria-current="page" @endif>
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="cashier" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2.8" width="16" height="18.4" rx="2"/><path d="M7.5 6.5h9M7.5 10.5h9"/><circle cx="8" cy="14.5" r=".7"/><circle cx="12" cy="14.5" r=".7"/><circle cx="16" cy="14.5" r=".7"/><circle cx="8" cy="18" r=".7"/><circle cx="12" cy="18" r=".7"/><circle cx="16" cy="18" r=".7"/></svg></span>
                        Kasir
                    </a>
                    <a class="{{ request()->routeIs('employee.pengiriman.*', 'employee.pelanggan.*') ? 'active' : '' }}" href="{{ route('employee.pengiriman.index') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="delivery" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6h12v11h-12zM14.5 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/><path d="M4.5 9h5M2.5 12h7"/></svg></span>
                        Pengiriman
                    </a>
                    <a class="{{ request()->routeIs('employee.payments.*') ? 'active' : '' }}" href="{{ route('employee.payments.index') }}">
                        <span class="cashier-nav-icon" aria-hidden="true">Rp</span>
                        Pembayaran pelanggan
                    </a>
                    @if (mb_strtolower(trim((string) auth()->guard('employee')->user()->jabatan)) === 'kurir')
                        <a class="{{ request()->routeIs('employee.courier.*') ? 'active' : '' }}" href="{{ route('employee.courier.index') }}">
                            <span class="cashier-nav-icon" aria-hidden="true">⌖</span>
                            Halaman kurir
                        </a>
                    @endif
                    <a class="{{ request()->routeIs('employee.supplier.*') ? 'active' : '' }}" href="{{ route('employee.supplier.index') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="supplier" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9 5 4h14l2 5M3 9h18M4 9v11h16V9M2.5 20h19"/><path d="M8 20v-6h8v6M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/></svg></span>
                        Supplier
                    </a>
                    @else
                    <a class="{{ request()->routeIs('owner.dashboard') ? 'active' : '' }}" href="{{ route('owner.dashboard') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="dashboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg></span>
                        Ringkasan
                    </a>
                    <a class="{{ request()->routeIs('owner.accounts.*') ? 'active' : '' }}" href="{{ route('owner.accounts.index') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="accounts" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg></span>
                        Akun Owner
                    </a>
                    <a class="{{ request()->routeIs('owner.payments.*') ? 'active' : '' }}" href="{{ route('owner.payments.index') }}">
                        <span class="cashier-nav-icon" aria-hidden="true">Rp</span>
                        Verifikasi pembayaran
                    </a>
                    <a class="{{ request()->routeIs('owner.reports.sales') ? 'active' : '' }}" href="{{ route('owner.reports.sales') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="receipt" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h12v17l-3-1.8-3 1.8-3-1.8-3 1.8v-17Z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg></span>
                        Laporan penjualan
                    </a>
                    <a class="{{ request()->routeIs('owner.reports.purchases') ? 'active' : '' }}" href="{{ route('owner.reports.purchases') }}">
                        <span class="cashier-nav-icon" aria-hidden="true"><svg data-icon="purchases" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l1.2 12H3.8L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span>
                        Laporan pembelian
                    </a>
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
