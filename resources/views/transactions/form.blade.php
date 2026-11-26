@extends('layouts.app')

@section('title', (isset($transaction) ? 'Ubah penjualan' : 'Buat '.$type).' | GudangIn')

@php

    $transaction = $transaction ?? null;
    $oldTransaction = $oldTransaction ?? null;
    $ocrPrefill = $ocrPrefill ?? [];

    $isEditing = $transaction !== null;

    $partyField = $type === 'Penjualan' ? 'id_pelanggan' : 'id_supplier';

    $partyLabel = $type === 'Penjualan' ? 'Pelanggan' : 'Supplier';

    $partyName = $type === 'Penjualan' ? 'nama_pelanggan' : 'nama_supplier';

    $priceField = $type === 'Penjualan' ? 'harga_satuan' : 'harga_beli';

    $priceLabel = $type === 'Penjualan' ? 'Harga jual satuan' : 'Harga beli satuan';

    $selectedPartyId = old($partyField, $transaction?->{$partyField} ?? ($oldTransaction->{$partyField} ?? ($ocrPrefill['id_supplier'] ?? '')));

    $oldItems = old('items');
    if ($oldItems === null) {
        if ($transaction !== null && isset($transactionItems)) {
            $oldItems = $transactionItems;
        } elseif ($oldTransaction !== null) {
            $oldItems = $oldTransaction->detailPembelian->map(fn ($line) => [
                'id_persediaan' => $line->id_persediaan,
                'jumlah_barang' => $line->jumlah_barang,
                $priceField => $line->{$priceField},
            ])->all();
        } elseif (($ocrPrefill['items'] ?? []) !== []) {
            $oldItems = $ocrPrefill['items'];
        } else {
            $oldItems = [[
                'id_persediaan' => '',
                'jumlah_barang' => '',
                $priceField => '',
            ]];
        }
    }

@endphp

@section('content')

    <section class="page-heading">

        <div>

            <p class="eyebrow">Transaksi</p>

            <h1>{{ $isEditing ? 'Ubah '.strtolower($type) : 'Buat '.strtolower($type) }}</h1>
            <p class="lead">
                @if ($isEditing)
                    Transaksi {{ $transaction->id_penjualan ?? $transaction->id_pembelian }}.
                    @if ($type === 'Penjualan' && filled($transaction->id_pengiriman))
                        ID pengiriman {{ $transaction->id_pengiriman }}.
                    @endif
                    Stok akan diperbarui sesuai perubahan yang disimpan.
                @else
                    Isi rincian dengan benar.
                    {{ $type === 'Pembelian' ? 'Stok akan bertambah setelah pembelian dikonfirmasi diterima.' : 'Stok akan berkurang otomatis saat penjualan disimpan.' }}
                @endif

            </p>

        </div>

    </section>

    <section class="form-panel transaction-form-panel">

        @if ($errors->any())

            <div class="notice notice-error">{{ $errors->first() }}</div>

        @endif

        <form method="POST" action="{{ $action }}" class="stack-form">

            @csrf

            @if ($isEditing)
                @method('PUT')
            @elseif (isset($method))
                @method($method)

            @endif

            <div class="transaction-meta">

                <div>

                    <label for="{{ $partyField }}">{{ $partyLabel }}</label>

                    <select id="{{ $partyField }}" name="{{ $partyField }}" required>

                        <option value="">Pilih {{ strtolower($partyLabel) }}</option>

                        @foreach ($counterparts as $counterpart)

                            <option value="{{ $counterpart->getKey() }}" @selected((string) $selectedPartyId === (string) $counterpart->getKey())>

                                {{ $counterpart->{$partyName} }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <label for="tanggal">Tanggal</label>

                    <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $transaction?->tanggal ?? ($oldTransaction->tanggal ?? ($ocrPrefill['tanggal'] ?? $date))) }}" required>

                </div>

            </div>

            <div class="line-heading">

                <div>

                    <h2>Rincian barang</h2>

                    <p class="muted">Harga dan subtotal disimpan untuk setiap barang.</p>

                </div>

                <button class="button button-secondary" id="add-transaction-line" type="button">Tambah barang</button>

            </div>

            @if (isset($receiptToken))

                <input type="hidden" name="receipt_token" value="{{ $receiptToken }}">

                <div class="ocr-review">

                    <label for="hasil_ocr">Hasil OCR nota — periksa dan koreksi sebelum disimpan</label>

                    <textarea id="hasil_ocr" name="hasil_ocr" maxlength="50000" required>{{ old('hasil_ocr', $ocrText) }}</textarea>

                    <p class="muted">Teks OCR menjadi referensi nota. Cocokkan lalu isi barang, jumlah, dan harga pada rincian di bawah.</p>

                </div>

            @endif

            <div class="transaction-lines" id="transaction-lines" data-next-index="{{ count($oldItems) }}">

                @foreach ($oldItems as $index => $oldItem)

                    <div class="transaction-line" data-transaction-line>

                        <div>

                            <label for="item-{{ $index }}-product">Barang</label>

                            <select id="item-{{ $index }}-product" name="items[{{ $index }}][id_persediaan]">

                                <option value="">Pilih barang</option>

                                @foreach ($products as $product)

                                    <option

                                        value="{{ $product->id_persediaan }}"

                                        data-unit="{{ $product->satuan }}"

                                        @selected((string) ($oldItem['id_persediaan'] ?? '') === (string) $product->id_persediaan)

                                        @disabled($type === 'Penjualan' && $product->stok <= 0 && ! $isEditing)

                                    >

                                        {{ $product->nama_barang }} (stok: {{ \App\Support\QuantityFormatter::format($product->stok) }} {{ $product->satuan }})

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div>

                            <label for="item-{{ $index }}-quantity">Jumlah barang (gunakan satuan barang)</label>

                            <div class="quantity-input-wrap">

                                <input id="item-{{ $index }}-quantity" name="items[{{ $index }}][jumlah_barang]" type="number" min="0.001" step="0.001" value="{{ $oldItem['jumlah_barang'] ?? '' }}">

                                <span class="quantity-unit" data-quantity-unit>{{ $oldItem['satuan'] ?? '' }}</span>

                            </div>

                        </div>

                        <div>

                            <label for="item-{{ $index }}-price">{{ $priceLabel }}</label>

                            <input id="item-{{ $index }}-price" name="items[{{ $index }}][{{ $priceField }}]" type="number" min="0.01" step="0.01" value="{{ $oldItem[$priceField] ?? '' }}">

                        </div>

                        <button class="text-button text-danger remove-line" type="button" data-remove-line aria-label="Hapus baris barang">Hapus</button>

                    </div>

                @endforeach

            </div>

            <template id="transaction-line-template">

                <div class="transaction-line" data-transaction-line>

                    <div>

                        <label for="item-__INDEX__-product">Barang</label>

                        <select id="item-__INDEX__-product" name="items[__INDEX__][id_persediaan]">

                            <option value="">Pilih barang</option>

                            @foreach ($products as $product)

                                @if ($type !== 'Penjualan' || $product->stok > 0 || $isEditing)
                                    <option value="{{ $product->id_persediaan }}" data-unit="{{ $product->satuan }}">{{ $product->nama_barang }} (stok: {{ \App\Support\QuantityFormatter::format($product->stok) }} {{ $product->satuan }})</option>

                                @endif

                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label for="item-__INDEX__-quantity">Jumlah barang (gunakan satuan barang)</label>

                        <div class="quantity-input-wrap">

                            <input id="item-__INDEX__-quantity" name="items[__INDEX__][jumlah_barang]" type="number" min="0.001" step="0.001">

                            <span class="quantity-unit" data-quantity-unit></span>

                        </div>

                    </div>

                    <div>

                        <label for="item-__INDEX__-price">{{ $priceLabel }}</label>

                        <input id="item-__INDEX__-price" name="items[__INDEX__][{{ $priceField }}]" type="number" min="0.01" step="0.01">

                    </div>

                    <button class="text-button text-danger remove-line" type="button" data-remove-line aria-label="Hapus baris barang">Hapus</button>

                </div>

            </template>

            <div class="form-actions">

                <a class="button button-secondary" href="{{ $isEditing ? route('employee.pengiriman.index') : route($type === 'Penjualan' ? 'employee.penjualan.index' : 'employee.pembelian.index') }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $isEditing ? 'Simpan perubahan' : ($submitLabel ?? 'Simpan '.strtolower($type)) }}</button>

            </div>

        </form>

        @if (isset($receiptToken))

            <form method="POST" action="{{ route('employee.pembelian.ocr.discard', $receiptToken) }}" class="discard-ocr-form">

                @csrf

                <button class="text-button text-danger" type="submit">Batalkan dan hapus nota sementara</button>

            </form>

        @endif

    </section>

@endsection

@push('scripts')

    <script>

        document.addEventListener('DOMContentLoaded', () => {

            const list = document.getElementById('transaction-lines');

            const template = document.getElementById('transaction-line-template');

            const addButton = document.getElementById('add-transaction-line');

            let nextIndex = Number(list.dataset.nextIndex);

            const updateQuantityUnit = (row) => {

                const product = row.querySelector('select');

                const selected = product?.selectedOptions[0];

                const unit = row.querySelector('[data-quantity-unit]');

                if (unit && unit.textContent.trim() === '') {

                    unit.textContent = selected?.dataset.unit || '';

                }

            };

            list.querySelectorAll('[data-transaction-line]').forEach(updateQuantityUnit);

            list.addEventListener('change', (event) => {

                const row = event.target.closest('[data-transaction-line]');

                if (row && event.target.matches('select')) updateQuantityUnit(row);

            });

            addButton.addEventListener('click', () => {

                const row = template.content.cloneNode(true);

                row.querySelectorAll('[name], [id], [for]').forEach((element) => {

                    ['name', 'id', 'for'].forEach((attribute) => {

                        if (element.hasAttribute(attribute)) {

                            element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', nextIndex));

                        }

                    });

                });

                list.appendChild(row);

                nextIndex += 1;

                updateQuantityUnit(list.lastElementChild);

            });

            list.addEventListener('click', (event) => {

                const removeButton = event.target.closest('[data-remove-line]');

                if (!removeButton) return;

                const rows = list.querySelectorAll('[data-transaction-line]');

                const row = removeButton.closest('[data-transaction-line]');

                if (rows.length === 1) {

                    row.querySelectorAll('input').forEach((input) => input.value = '');

                    row.querySelector('select').selectedIndex = 0;

                } else {

                    row.remove();

                }

            });

        });

    </script>

@endpush