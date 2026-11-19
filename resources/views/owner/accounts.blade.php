@extends('layouts.app')

@section('title', 'Akun Owner | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Kelola akses Owner</p>
            <h1>Akun Owner</h1>
            <p class="lead">Tambahkan Owner baru. Akun Owner hanya dapat dibuat dari area Owner.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

    <section class="form-panel master-form-panel">
        <h2>Tambah akun Owner</h2>
        <form method="POST" action="{{ route('owner.accounts.store') }}" class="stack-form">
            @csrf
            <label for="nama_owner">Nama Owner</label>
            <input id="nama_owner" name="nama_owner" value="{{ old('nama_owner') }}" maxlength="100" required>

            <label for="username">Username</label>
            <input id="username" name="username" value="{{ old('username') }}" maxlength="50" autocomplete="username" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>

            <label for="password_confirmation">Ulangi password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Buat akun Owner</button>
            </div>
        </form>
    </section>

    <section class="table-panel account-list-panel">
        <div class="account-list-section">
            <h2>Owner terdaftar ({{ $owners->count() }})</h2>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr><th>Nama Owner</th><th>Username</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($owners as $owner)
                            <tr>
                                <td>{{ $owner->nama_owner }}</td>
                                <td>{{ $owner->username }}</td>
                            </tr>
                        @empty
                            <tr><td class="empty-cell" colspan="2">Belum ada akun Owner.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
