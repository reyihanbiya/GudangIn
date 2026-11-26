
@extends('customer.layouts.app')

@section('title', $product->nama_barang)

@section('content')

<section class="detail-section">

    <a class="back-link"
       href="{{ route('customer.products.index') }}">
        ← Kembali ke katalog
    </a>


    <div class="detail-card">

        <div class="detail-visual">

            <span class="detail-symbol">
                {{ strtoupper(mb_substr($product->nama_barang, 0, 1)) }}
            </span>

            <span>GUDANGIN MATERIAL</span>

        </div>


        <div class="detail-copy">

            <span class="eyebrow">
                {{ $product->kategori_barang ?: 'MATERIAL BANGUNAN' }}
            </span>

            <h1>{{ $product->nama_barang }}</h1>


            <div class="detail-price">
                {{ $product->harga_jual_format }}

                <small>
                    / {{ $product->satuan ?: 'unit' }}
                </small>
            </div>


            <div class="stock-status {{ (float) $product->stok > 0 ? 'available' : 'unavailable' }}">

                {{ (float) $product->stok > 0
                    ? 'Stok tersedia: '.$product->stok_dengan_satuan
                    : 'Stok sedang habis'
                }}

            </div>

            <div class="detail-facts">
                <div>
                    <span>Kategori</span>
                    <strong>{{ $product->kategori_barang ?: 'Material bangunan' }}</strong>
                </div>
                <div>
                    <span>Satuan</span>
                    <strong>{{ $product->satuan ?: 'unit' }}</strong>
                </div>
                <div>
                    <span>Kode barang</span>
                    <strong>{{ $product->id_persediaan }}</strong>
                </div>
            </div>

            <p class="detail-note">
                Informasi harga dan ketersediaan mengikuti data persediaan GudangIn.
            </p>


            @if(session('error'))
                <div class="customer-alert error">
                    {{ session('error') }}
                </div>
            @endif


            @if((float) $product->stok > 0)

                <form action="{{ route('customer.cart.store') }}"
                      method="POST"
                      class="add-cart-form">

                    @csrf

                    <input type="hidden"
                           name="id_persediaan"
                           value="{{ $product->id_persediaan }}">

                    <label for="jumlah">
                        Jumlah pembelian
                    </label>

                    <input
                        type="number"
                        id="jumlah"
                        name="jumlah"
                        value="1"
                        min="0.001"
                        max="{{ $product->stok }}"
                        step="0.001"
                        required
                    >

                    <button class="hero-button" type="submit">
                        Tambahkan ke Keranjang
                        <span>+</span>
                    </button>

                </form>

            @else

                <button class="hero-button" type="button" disabled>
                    Stok Habis
                </button>

            @endif

        </div>

    </div>

</section>

@endsection
