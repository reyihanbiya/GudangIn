@extends('layouts.app')

@section('title', $title.' | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Data operasional</p>
            <h1>{{ $title }}</h1>
            <p class="lead">{{ $description }}</p>
        </div>
        <a class="button button-primary" href="{{ route($createRoute) }}">Tambah data</a>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

    <section class="table-panel">
        <form method="GET" class="search-form">
            <label class="sr-only" for="search">Cari {{ strtolower($title) }}</label>
            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari {{ strtolower($title) }}...">
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search)
                <a class="text-link" href="{{ url()->current() }}">Hapus pencarian</a>
            @endif
        </form>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                        <th class="actions-heading">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($columns as $column)
                                <td>{{ $row->{$column['field']} ?? '—' }}</td>
                            @endforeach
                            <td class="row-actions">
                                @if (isset($stockCardRoute))
                                    <a class="text-link" href="{{ route($stockCardRoute, $row) }}">Kartu stok</a>
                                @endif
                                <a class="text-link" href="{{ route($editRoute, $row) }}">Ubah</a>
                                <form method="POST" action="{{ route($deleteRoute, $row) }}" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-button text-danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="{{ count($columns) + 1 }}">Belum ada data untuk ditampilkan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $rows->links() }}</div>
    </section>
@endsection
