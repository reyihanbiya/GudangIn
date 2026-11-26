
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Belanja Material') - GudangIn</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/customer.css') }}">

    @stack('styles')
</head>

<body>

    <div class="phone-shell">
        <header class="customer-header">

            <a class="brand"
               href="{{ route('customer.products.index') }}"
               aria-label="GudangIn beranda">
                <img class="brand-logo" src="{{ asset('images/gudangin-logo.png') }}" alt="GudangIn">
            </a>

            <div class="header-actions">

                <a class="header-location"
                   href="{{ route('customer.tracking.index') }}">
                    <span>⌖</span>
                    <span>Lacak pesanan</span>
                </a>

                <a href="{{ route('customer.cart.index') }}"
                   class="cart-header-link">
                    🛒
                    <span>Keranjang</span>
                    <b>{{ count(session('customer_cart', [])) }}</b>
                </a>

            </div>

            <form class="header-search"
                  action="{{ route('customer.products.index') }}"
                  method="GET">

                <span aria-hidden="true">⌕</span>

                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari semen, cat, paku..."
                    aria-label="Cari produk"
                >

                <button type="submit">Cari</button>
            </form>

        </header>

        <main>
            @yield('content')
        </main>

        <footer class="customer-footer">

            <div class="footer-brand">
                <img class="brand-logo" src="{{ asset('images/gudangin-logo.png') }}" alt="GudangIn">
            </div>

            <p>
                Solusi praktis untuk kebutuhan material bangunan Anda.
            </p>
            <a class="customer-footer-link" href="{{ route('customer.tracking.index') }}">Lacak pesanan</a>

            <small>
                © {{ date('Y') }} GudangIn. Semua hak dilindungi.
            </small>

        </footer>

        <nav class="customer-bottom-nav" aria-label="Navigasi customer">
            <a class="{{ request()->routeIs('customer.products.*') ? 'active' : '' }}" href="{{ route('customer.products.index') }}">
                <span aria-hidden="true">⌂</span>Beranda
            </a>
            <a href="{{ route('customer.products.index') }}#product-categories">
                <span aria-hidden="true">▦</span>Kategori
            </a>
            <a class="{{ request()->routeIs('customer.cart.*') ? 'active' : '' }}" href="{{ route('customer.cart.index') }}">
                <span aria-hidden="true">🛒</span>Keranjang
            </a>
            <a class="{{ request()->routeIs('customer.tracking.*', 'customer.orders.*') ? 'active' : '' }}" href="{{ route('customer.tracking.index') }}">
                <span aria-hidden="true">▤</span>Pesanan
            </a>
        </nav>

    </div>

    @stack('scripts')

</body>
</html>
