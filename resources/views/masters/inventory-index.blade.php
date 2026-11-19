@extends('layouts.app')

@section('title', 'Persediaan | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Data operasional</p>
            <h1>Persediaan</h1>
            <p class="lead">Kelola barang, satuan ukur, dan jumlah stok yang tersedia.</p>
        </div>
        <a class="button button-primary" href="{{ route($createRoute) }}">Tambah data</a>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

    <section class="inventory-summary" aria-label="Ringkasan persediaan">
        <article class="inventory-summary-card">
            <p>Jenis barang</p>
            <strong>{{ $summary->total_items }}</strong>
            <span>Semua kategori</span>
        </article>
        <article class="inventory-summary-card inventory-summary-low">
            <p>Stok menipis</p>
            <strong>{{ $summary->low_stock_items }}</strong>
            <span>Jumlah stok sampai {{ $lowStockThreshold }}</span>
        </article>
        <article class="inventory-summary-card inventory-summary-empty">
            <p>Stok habis</p>
            <strong>{{ $summary->out_of_stock_items }}</strong>
            <span>Tidak bisa dijual</span>
        </article>
    </section>

    <section class="table-panel inventory-panel">
        <form method="GET" class="inventory-search">
            <label class="sr-only" for="search">Cari nama atau kode barang</label>
            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari nama atau kode barang...">
            @if ($selectedCategory)
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search || $selectedCategory)
                <a class="text-link" href="{{ route('employee.persediaan.index') }}">Hapus filter</a>
            @endif
        </form>

        <nav class="inventory-categories" aria-label="Filter kategori">
            <a class="inventory-category {{ $selectedCategory ? '' : 'active' }}"
                href="{{ route('employee.persediaan.index', array_filter(['search' => $search], fn ($value) => $value !== null && $value !== '')) }}"
                @if (! $selectedCategory) aria-current="true" @endif>Semua</a>
            @foreach ($categories as $category)
                <a class="inventory-category {{ $selectedCategory === $category ? 'active' : '' }}"
                    href="{{ route('employee.persediaan.index', array_filter(['search' => $search, 'category' => $category], fn ($value) => $value !== null && $value !== '')) }}"
                    @if ($selectedCategory === $category) aria-current="true" @endif>{{ $category }}</a>
            @endforeach
        </nav>

        <div class="table-scroll">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama barang</th>
                        <th>Kategori</th>
                        <th>Harga jual / satuan</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th class="actions-heading">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $stock = (float) $row->stok;
                            $stockPercent = $maxStock > 0 ? min(100, ($stock / $maxStock) * 100) : 0;
                            $stockStatus = $stock <= 0 ? 'Habis' : ($stock <= $lowStockThreshold ? 'Menipis' : 'Aman');
                        @endphp
                        <tr>
                            <td class="inventory-code">{{ $row->id_persediaan }}</td>
                            <td class="inventory-name">{{ $row->nama_barang }}</td>
                            <td>{{ $row->kategori_barang ?: '—' }}</td>
                            <td>
                                @if ($row->harga_jual !== null)
                                    Rp {{ number_format((float) $row->harga_jual, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <div class="inventory-stock">
                                    <span>{{ \App\Support\QuantityFormatter::format($row->stok) }} {{ $row->satuan }}</span>
                                    <span class="inventory-stock-track" role="img" aria-label="Stok {{ $stockStatus }}">
                                        <span class="inventory-stock-fill inventory-stock-{{ strtolower($stockStatus) }}" style="width: {{ $stockPercent }}%"></span>
                                    </span>
                                </div>
                            </td>
                            <td><span class="inventory-status inventory-status-{{ strtolower($stockStatus) }}">{{ $stockStatus }}</span></td>
                            <td class="row-actions">
                                <a class="text-link" href="{{ route($editRoute, $row->id_persediaan) }}">Ubah</a>
                                <form method="POST" action="{{ route($deleteRoute, $row->id_persediaan) }}" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-button text-danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="7">Belum ada data untuk ditampilkan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $rows->links() }}</div>
    </section>
@endsection
