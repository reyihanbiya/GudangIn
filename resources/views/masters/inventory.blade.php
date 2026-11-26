@extends('layouts.app')

@section('title', 'Persediaan | GudangIn')

@section('content')
    <section class="page-heading inventory-heading">
        <div>
            <p class="eyebrow">Data operasional</p>
            <h1>Persediaan</h1>
            <p class="lead">Kelola barang, harga jual, satuan ukur, dan stok yang tersedia.</p>
        </div>
        <a class="button button-primary" href="{{ route('employee.persediaan.create') }}">Tambah barang</a>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

    <section class="inventory-stats" aria-label="Ringkasan persediaan">
        <article class="inventory-stat-card">
            <span class="inventory-stat-icon" aria-hidden="true">▦</span>
            <p>Total barang</p>
            <strong>{{ number_format($totalItems, 0, ',', '.') }}</strong>
            <span>Jenis barang terdaftar</span>
        </article>
        <article class="inventory-stat-card inventory-stat-warning">
            <span class="inventory-stat-icon" aria-hidden="true">!</span>
            <p>Stok habis</p>
            <strong>{{ number_format($outOfStockItems, 0, ',', '.') }}</strong>
            <span>Barang yang perlu diisi ulang</span>
        </article>
        <article class="inventory-stat-card">
            <span class="inventory-stat-icon" aria-hidden="true">◇</span>
            <p>Jenis satuan</p>
            <strong>{{ number_format($unitTypes, 0, ',', '.') }}</strong>
            <span>Satuan berbeda digunakan</span>
        </article>
    </section>

    <section class="table-panel inventory-panel">
        <form method="GET" class="inventory-search-form">
            <label class="sr-only" for="search">Cari persediaan</label>
            @if ($category)
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari nama atau kode barang...">
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search || $category)
                <a class="text-link" href="{{ route('employee.persediaan.index') }}">Reset</a>
            @endif
        </form>

        <nav class="inventory-categories" aria-label="Filter kategori barang">
            <a class="inventory-category-button {{ $category ? '' : 'active' }}" href="{{ route('employee.persediaan.index', array_filter(['search' => $search])) }}">Semua</a>
            @foreach ($categories as $itemCategory)
                <a
                    class="inventory-category-button {{ $category === $itemCategory ? 'active' : '' }}"
                    href="{{ route('employee.persediaan.index', array_filter(['search' => $search, 'category' => $itemCategory])) }}"
                    @if ($category === $itemCategory) aria-current="true" @endif
                >{{ $itemCategory }}</a>
            @endforeach
        </nav>

        <div class="table-scroll">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama barang</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th class="actions-heading">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="inventory-code">{{ $row->id_persediaan }}</td>
                            <td class="inventory-name">{{ $row->nama_barang }}</td>
                            <td>{{ $row->kategori_barang ?: '—' }}</td>
                            <td>{{ $row->harga_jual_format }}</td>
                            <td class="inventory-stock">{{ $row->stok_dengan_satuan }}</td>
                            <td>
                                @if ((float) $row->stok <= 0)
                                    <span class="inventory-status inventory-status-empty">Habis</span>
                                @else
                                    <span class="inventory-status inventory-status-available">Tersedia</span>
                                @endif
                            </td>
                            <td class="row-actions">
                                <a class="text-link" href="{{ route('employee.persediaan.edit', $row) }}">Ubah</a>
                                <form method="POST" action="{{ route('employee.persediaan.destroy', $row) }}" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-button text-danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="7">Belum ada barang yang cocok dengan pencarian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $rows->links() }}</div>
    </section>
@endsection
