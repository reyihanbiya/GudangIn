@extends('layouts.app')

@section('title', 'Penyiapan awal | GudangIn')

@section('content')
    <section class="form-layout setup-layout">
        <div class="form-intro">
            <p class="eyebrow">Penyiapan awal</p>
            <h1>Siapkan akun<br>GudangIn Anda.</h1>
            <p class="lead">Buat akun Owner dan Pegawai sebelum mulai. Kamu bisa menambahkan beberapa akun sekaligus; halaman ini akan terkunci setelah penyiapan selesai.</p>
        </div>
        <div class="form-panel setup-panel">
            <h2>Akun awal</h2>
            @if ($errors->any())
                <div class="notice notice-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('setup.store') }}" class="stack-form">
                @csrf
                @php
                    $ownerAccounts = old('owners', [['name' => '', 'username' => '']]);
                    $employeeAccounts = old('employees', [['name' => '', 'username' => '']]);
                    $ownerAccounts = is_array($ownerAccounts) && $ownerAccounts !== [] ? $ownerAccounts : [['name' => '', 'username' => '']];
                    $employeeAccounts = is_array($employeeAccounts) && $employeeAccounts !== [] ? $employeeAccounts : [['name' => '', 'username' => '']];
                    $ownerNextIndex = max(array_map('intval', array_keys($ownerAccounts))) + 1;
                    $employeeNextIndex = max(array_map('intval', array_keys($employeeAccounts))) + 1;
                @endphp
                <section class="setup-account-group" data-account-group="owners" data-next-index="{{ $ownerNextIndex }}">
                    <h3>Akun Owner</h3>
                    <div class="setup-account-list">
                        @foreach ($ownerAccounts as $index => $account)
                            <fieldset class="setup-account-card" data-account-entry>
                                <legend data-account-legend>Owner {{ $loop->iteration }}</legend>
                                <label for="owner-{{ $index }}-name">Nama Owner</label>
                                <input id="owner-{{ $index }}-name" name="owners[{{ $index }}][name]" value="{{ is_array($account) ? ($account['name'] ?? '') : '' }}" maxlength="100" required>
                                <label for="owner-{{ $index }}-username">Username Owner</label>
                                <input id="owner-{{ $index }}-username" name="owners[{{ $index }}][username]" value="{{ is_array($account) ? ($account['username'] ?? '') : '' }}" maxlength="50" autocomplete="username" required>
                                <label for="owner-{{ $index }}-password">Password Owner</label>
                                <input id="owner-{{ $index }}-password" name="owners[{{ $index }}][password]" type="password" minlength="8" autocomplete="new-password" required>
                                <label for="owner-{{ $index }}-password-confirmation">Ulangi Password Owner</label>
                                <input id="owner-{{ $index }}-password-confirmation" name="owners[{{ $index }}][password_confirmation]" type="password" minlength="8" autocomplete="new-password" required>
                                <button class="text-button text-danger setup-remove-account" type="button" data-remove-account>Hapus akun ini</button>
                            </fieldset>
                        @endforeach
                    </div>
                    <button class="button button-secondary" type="button" data-add-account>Tambah Owner</button>
                </section>

                <section class="setup-account-group" data-account-group="employees" data-next-index="{{ $employeeNextIndex }}">
                    <h3>Akun Pegawai</h3>
                    <div class="setup-account-list">
                        @foreach ($employeeAccounts as $index => $account)
                            <fieldset class="setup-account-card" data-account-entry>
                                <legend data-account-legend>Pegawai {{ $loop->iteration }}</legend>
                                <label for="employee-{{ $index }}-name">Nama Pegawai</label>
                                <input id="employee-{{ $index }}-name" name="employees[{{ $index }}][name]" value="{{ is_array($account) ? ($account['name'] ?? '') : '' }}" maxlength="100" required>
                                <label for="employee-{{ $index }}-username">Username Pegawai</label>
                                <input id="employee-{{ $index }}-username" name="employees[{{ $index }}][username]" value="{{ is_array($account) ? ($account['username'] ?? '') : '' }}" maxlength="50" autocomplete="username" required>
                                <label for="employee-{{ $index }}-password">Password Pegawai</label>
                                <input id="employee-{{ $index }}-password" name="employees[{{ $index }}][password]" type="password" minlength="8" autocomplete="new-password" required>
                                <label for="employee-{{ $index }}-password-confirmation">Ulangi Password Pegawai</label>
                                <input id="employee-{{ $index }}-password-confirmation" name="employees[{{ $index }}][password_confirmation]" type="password" minlength="8" autocomplete="new-password" required>
                                <button class="text-button text-danger setup-remove-account" type="button" data-remove-account>Hapus akun ini</button>
                            </fieldset>
                        @endforeach
                    </div>
                    <button class="button button-secondary" type="button" data-add-account>Tambah Pegawai</button>
                </section>
                <button class="button button-primary button-wide" type="submit">Simpan akun dan mulai</button>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-account-group]').forEach((group) => {
            const list = group.querySelector('.setup-account-list');
            const addButton = group.querySelector('[data-add-account]');
            const role = group.dataset.accountGroup === 'owners' ? 'Owner' : 'Pegawai';
            const templateId = `account-template-${group.dataset.accountGroup}`;

            const updateEntries = () => {
                const entries = [...list.querySelectorAll('[data-account-entry]')];
                entries.forEach((entry, index) => {
                    entry.querySelector('[data-account-legend]').textContent = `${role} ${index + 1}`;
                    entry.querySelector('[data-remove-account]').disabled = entries.length === 1;
                });
            };

            addButton.addEventListener('click', () => {
                const index = Number(group.dataset.nextIndex);
                group.dataset.nextIndex = String(index + 1);
                const template = document.getElementById(templateId);
                list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
                updateEntries();
            });

            list.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-account]');
                if (removeButton && list.querySelectorAll('[data-account-entry]').length > 1) {
                    removeButton.closest('[data-account-entry]').remove();
                    updateEntries();
                }
            });

            updateEntries();
        });
    </script>
    <template id="account-template-owners">
        <fieldset class="setup-account-card" data-account-entry>
            <legend data-account-legend>Owner</legend>
            <label for="owner-__INDEX__-name">Nama Owner</label>
            <input id="owner-__INDEX__-name" name="owners[__INDEX__][name]" maxlength="100" required>
            <label for="owner-__INDEX__-username">Username Owner</label>
            <input id="owner-__INDEX__-username" name="owners[__INDEX__][username]" maxlength="50" autocomplete="username" required>
            <label for="owner-__INDEX__-password">Password Owner</label>
            <input id="owner-__INDEX__-password" name="owners[__INDEX__][password]" type="password" minlength="8" autocomplete="new-password" required>
            <label for="owner-__INDEX__-password-confirmation">Ulangi Password Owner</label>
            <input id="owner-__INDEX__-password-confirmation" name="owners[__INDEX__][password_confirmation]" type="password" minlength="8" autocomplete="new-password" required>
            <button class="text-button text-danger setup-remove-account" type="button" data-remove-account>Hapus akun ini</button>
        </fieldset>
    </template>
    <template id="account-template-employees">
        <fieldset class="setup-account-card" data-account-entry>
            <legend data-account-legend>Pegawai</legend>
            <label for="employee-__INDEX__-name">Nama Pegawai</label>
            <input id="employee-__INDEX__-name" name="employees[__INDEX__][name]" maxlength="100" required>
            <label for="employee-__INDEX__-username">Username Pegawai</label>
            <input id="employee-__INDEX__-username" name="employees[__INDEX__][username]" maxlength="50" autocomplete="username" required>
            <label for="employee-__INDEX__-password">Password Pegawai</label>
            <input id="employee-__INDEX__-password" name="employees[__INDEX__][password]" type="password" minlength="8" autocomplete="new-password" required>
            <label for="employee-__INDEX__-password-confirmation">Ulangi Password Pegawai</label>
            <input id="employee-__INDEX__-password-confirmation" name="employees[__INDEX__][password_confirmation]" type="password" minlength="8" autocomplete="new-password" required>
            <button class="text-button text-danger setup-remove-account" type="button" data-remove-account>Hapus akun ini</button>
        </fieldset>
    </template>
@endpush
