
@extends('customer.layouts.app')

@section('title', 'Katalog Produk')

@section('content')

<section class="catalog-hero">

    <div class="hero-copy">

        <span class="eyebrow">
            <i></i> GUDANGIN BUILDING MATERIALS
        </span>

        <h1>
            Bangun lebih mudah,<br>
            <em>belanja lebih praktis.</em>
        </h1>

        <p>
            Temukan material bangunan untuk kebutuhan proyekmu.
        </p>

        <a class="hero-button" href="#product-list">
            Lihat Produk <span>↓</span>
        </a>

    </div>

    <div class="hero-art" aria-hidden="true">

        <div class="material-block block-one">
            SEMEN<br>
            <b>50 KG</b>
        </div>

        <div class="material-block block-two">
            BUILD<br>
            <b>BETTER</b>
        </div>

        <div class="material-bar"></div>

    </div>

</section>


<section class="catalog-section" id="product-list">

    <div class="section-heading">

        <div>
            <span class="eyebrow">KATALOG PRODUK</span>

            <h2>
                Pilih kebutuhanmu<span>.</span>
            </h2>

            <p>{{ $products->total() }} produk tersedia</p>
        </div>

    </div>


    <div class="catalog-toolbar">

        <form class="filter-search"
              method="GET"
              action="{{ route('customer.products.index') }}">

            <span aria-hidden="true">⌕</span>

            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Cari nama barang..."
            >

            @if($category !== '')
                <input type="hidden"
                       name="kategori"
                       value="{{ $category }}">
            @endif

            <input type="hidden" name="sort" value="{{ $sort }}">

            <button type="submit">Cari</button>

        </form>


        <form method="GET"
              action="{{ route('customer.products.index') }}"
              class="sort-form">

            @if($search !== '')
                <input type="hidden" name="q" value="{{ $search }}">
            @endif

            @if($category !== '')
                <input type="hidden"
                       name="kategori"
                       value="{{ $category }}">
            @endif

            <label for="sort">Urutkan:</label>

            <select id="sort"
                    name="sort"
                    onchange="this.form.submit()">

                <option value="latest" @selected($sort === 'latest')>
                    Nama produk
                </option>

                <option value="name" @selected($sort === 'name')>
                    Nama A–Z
                </option>

                <option value="price_asc" @selected($sort === 'price_asc')>
                    Harga terendah
                </option>

                <option value="price_desc" @selected($sort === 'price_desc')>
                    Harga tertinggi
                </option>

            </select>

        </form>

    </div>


    <div class="category-chips" id="product-categories">

        <a class="category-chip {{ $category === '' ? 'active' : '' }}"
           href="{{ route('customer.products.index', array_filter([
               'q' => $search,
               'sort' => $sort
           ])) }}">
            <span class="category-icon" aria-hidden="true">▦</span>
            <span>Semua</span>
        </a>

        @foreach($categories as $item)
            @php
                $categoryName = mb_strtolower($item);
                $categoryIcon = match (true) {
                    str_contains($categoryName, 'semen') => '▦',
                    str_contains($categoryName, 'pasir') => '◉',
                    str_contains($categoryName, 'besi') => '╱',
                    str_contains($categoryName, 'cat') => '▤',
                    str_contains($categoryName, 'keramik') => '▧',
                    default => mb_strtoupper(mb_substr($item, 0, 1)),
                };
            @endphp

            <a class="category-chip {{ $category === $item ? 'active' : '' }}"
               href="{{ route('customer.products.index', array_filter([
                   'q' => $search,
                   'kategori' => $item,
                   'sort' => $sort
               ])) }}">
                <span class="category-icon" aria-hidden="true">{{ $categoryIcon }}</span>
                <span>{{ $item }}</span>
            </a>

        @endforeach

    </div>


    @if($products->count())

        <div class="product-grid">

            @foreach($products as $product)

                <article class="product-card">

                    <a class="product-visual"
                       href="{{ route('customer.products.show', [
                           'id_persediaan' => $product->id_persediaan
                       ]) }}"
                       aria-label="Lihat {{ $product->nama_barang }}">

                        <span class="product-symbol">
                            {{ strtoupper(mb_substr($product->nama_barang, 0, 1)) }}
                        </span>

                    </a>


                    <div class="product-info">

                        <span class="product-unit">
                            {{ $product->kategori_barang ?: 'Material bangunan' }}
                            ·
                            {{ $product->satuan ?: 'unit' }}
                        </span>

                        <h3>
                            <a href="{{ route('customer.products.show', [
                                'id_persediaan' => $product->id_persediaan
                            ]) }}">
                                {{ $product->nama_barang }}
                            </a>
                        </h3>


                        <div class="product-bottom">

                            <div>

                                <strong>
                                    {{ $product->harga_jual_format }}
                                </strong>

                                <small>
                                    {{ (float) $product->stok > 0
                                        ? 'Stok: '.$product->stok_dengan_satuan
                                        : 'Stok habis'
                                    }}
                                </small>

                            </div>

                            <a class="add-product"
                               href="{{ route('customer.products.show', [
                                   'id_persediaan' => $product->id_persediaan
                               ]) }}"
                               aria-label="Detail {{ $product->nama_barang }}">
                                +
                            </a>

                        </div>

                    </div>

                </article>

            @endforeach

        </div>


        <div class="pagination-wrap">
            {{ $products->links() }}
        </div>


    @else

        <div class="empty-products">

            <span>⌕</span>

            <h3>Produk belum ditemukan</h3>

            <p>
                Coba gunakan kata kunci atau kategori yang berbeda.
            </p>

            <a href="{{ route('customer.products.index') }}">
                Tampilkan semua produk
            </a>

        </div>

    @endif

</section>

@endsection
