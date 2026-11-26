
@extends('customer.layouts.app')

@section('title', 'Keranjang Belanja')

@section('content')

<section class="cart-section">

    <a href="{{ route('customer.products.index') }}"
       class="back-link">
        ← Lanjut Belanja
    </a>


    <div class="section-heading">

        <div>
            <span class="eyebrow">GUDANGIN ONLINE</span>

            <h2>
                Keranjang Belanja<span>.</span>
            </h2>

            <p>
                Periksa kembali material yang ingin kamu beli.
            </p>
        </div>

    </div>


    @if(session('success'))
        <div class="customer-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="customer-alert error">
            {{ session('error') }}
        </div>
    @endif


    @if(count($items) > 0)

        <div class="cart-list">

            @foreach($items as $item)

                <article class="cart-item">

                    <div class="cart-product-symbol">
                        {{ strtoupper(mb_substr($item['product']->nama_barang, 0, 1)) }}
                    </div>


                    <div class="cart-product-info">

                        <span class="product-unit">
                            {{ $item['product']->kategori_barang ?: 'Material bangunan' }}
                        </span>

                        <h3>
                            {{ $item['product']->nama_barang }}
                        </h3>

                        <strong>
                            Rp{{ number_format(
                                $item['product']->harga_jual,
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Subtotal:
                            Rp{{ number_format(
                                $item['subtotal'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </small>


                        <form
                            action="{{ route('customer.cart.update', $item['product']->id_persediaan) }}"
                            method="POST"
                            class="cart-quantity-form"
                        >

                            @csrf
                            @method('PATCH')

                            <input
                                type="number"
                                name="jumlah"
                                value="{{ $item['quantity'] }}"
                                min="0.001"
                                max="{{ $item['product']->stok }}"
                                step="0.001"
                                required
                            >

                            <button type="submit">
                                Perbarui
                            </button>

                        </form>


                        <form
                            action="{{ route('customer.cart.remove', $item['product']->id_persediaan) }}"
                            method="POST"
                            onsubmit="return confirm('Hapus produk ini dari keranjang?')"
                        >

                            @csrf
                            @method('DELETE')

                            <button type="submit" class="remove-cart">
                                Hapus produk
                            </button>

                        </form>

                    </div>

                </article>

            @endforeach

        </div>


        <div class="cart-summary">

            <div class="cart-total">

                <span>Total Belanja</span>

                <strong>
                    Rp{{ number_format($total, 0, ',', '.') }}
                </strong>

            </div>

            <p>
                Ongkos kirim akan dihitung pada tahap checkout
                jika memilih delivery.
            </p>

            <a class="hero-button" href="{{ route('customer.checkout') }}">
                Lanjut Checkout
                <span>→</span>
            </a>

        </div>


        <form
            action="{{ route('customer.cart.clear') }}"
            method="POST"
            onsubmit="return confirm('Kosongkan seluruh keranjang?')"
        >

            @csrf
            @method('DELETE')

            <button type="submit" class="remove-cart">
                Kosongkan Keranjang
            </button>

        </form>


    @else

        <div class="empty-products">

            <span>🛒</span>

            <h3>Keranjang masih kosong</h3>

            <p>
                Yuk, cari material bangunan yang kamu butuhkan.
            </p>

            <a href="{{ route('customer.products.index') }}">
                Lihat Katalog Produk
            </a>

        </div>

    @endif

</section>

@endsection
