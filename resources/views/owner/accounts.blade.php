@extends('layouts.app')

@section('title', 'Akun Owner | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Kelola akses Owner</p>
            <h1>Akun Owner</h1>
            <p class="lead">Daftar akun Owner yang terdaftar di GudangIn.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

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

    <section class="table-panel account-list-panel">
        <div class="account-list-section">
            <h2>Akses Pegawai dan Kurir</h2>
            <p class="lead">Peran Kurir memberi akses ke halaman penugasan dan pembaruan lokasi pengiriman.</p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr><th>Nama Pegawai</th><th>Username</th><th>Peran</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td>{{ $employee->nama_pegawai }}</td>
                                <td>{{ $employee->username }}</td>
                                <td>
                                    <form method="POST" action="{{ route('owner.accounts.employee-role', $employee) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="jabatan" aria-label="Peran {{ $employee->nama_pegawai }}">
                                            @foreach (['Pegawai', 'Kasir', 'Kurir'] as $role)
                                                <option value="{{ $role }}" @selected($employee->jabatan === $role)>{{ $role }}</option>
                                            @endforeach
                                        </select>
                                        <button class="button button-secondary" type="submit">Simpan peran</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="empty-cell" colspan="3">Belum ada akun Pegawai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
