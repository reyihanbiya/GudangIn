@extends('layouts.app')

@section('title', 'Penyiapan awal | GudangIn')

@section('content')
    <section class="form-layout setup-layout">
        <div class="form-intro">
            <p class="eyebrow">Penyiapan awal</p>
            <h1>Siapkan akun<br>GudangIn Anda.</h1>
            <p class="lead">Buat akun Owner dan Pegawai pertama. Halaman ini akan terkunci setelah penyiapan selesai.</p>
        </div>
        <div class="form-panel setup-panel">
            <h2>Akun awal</h2>
            @if ($errors->any())
                <div class="notice notice-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('setup.store') }}" class="stack-form">
                @csrf
                <fieldset>
                    <legend>Owner</legend>
                    <label for="owner_name">Nama Owner</label>
                    <input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" maxlength="100" required>
                    <label for="owner_username">Username Owner</label>
                    <input id="owner_username" name="owner_username" value="{{ old('owner_username') }}" maxlength="50" autocomplete="username" required>
                    <label for="owner_password">Password Owner</label>
                    <input id="owner_password" name="owner_password" type="password" minlength="8" autocomplete="new-password" required>
                    <label for="owner_password_confirmation">Ulangi Password Owner</label>
                    <input id="owner_password_confirmation" name="owner_password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                </fieldset>
                <fieldset>
                    <legend>Pegawai</legend>
                    <label for="employee_name">Nama Pegawai</label>
                    <input id="employee_name" name="employee_name" value="{{ old('employee_name') }}" maxlength="100" required>
                    <label for="employee_username">Username Pegawai</label>
                    <input id="employee_username" name="employee_username" value="{{ old('employee_username') }}" maxlength="50" autocomplete="username" required>
                    <label for="employee_password">Password Pegawai</label>
                    <input id="employee_password" name="employee_password" type="password" minlength="8" autocomplete="new-password" required>
                    <label for="employee_password_confirmation">Ulangi Password Pegawai</label>
                    <input id="employee_password_confirmation" name="employee_password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                </fieldset>
                <button class="button button-primary button-wide" type="submit">Simpan akun dan mulai</button>
            </form>
        </div>
    </section>
@endsection
