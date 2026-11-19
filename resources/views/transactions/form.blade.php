@extends('layouts.app')

@section('title', 'Buat '.$type.' | GudangIn')

@php
    $partyField = $type === 'Penjualan' ? 'id_pelanggan' : 'id_supplier';
    $partyLabel = $type === 'Penjualan' ? 'Pelanggan' : 'Supplier';
    $partyName = $type === 'Penjualan' ? 'nama_pelanggan' : 'nama_supplier';
    $priceField = $type === 'Penjualan' ? 'harga_satuan' : 'harga_beli';
    $priceLabel = $type === 'Penjualan' ? 'Harga jual satuan' : 'Harga beli satuan';
    $oldItems = old('items', [[
        'id_persediaan' => '',
        'jumlah_barang' => '',
        $priceField => '',
    ]]);
@endphp

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Transaksi</p>
            <h1>Buat {{ strtolower($type) }}</h1>
            <p class="lead">Isi rincian dengan benar. Stok akan diperbarui saat transaksi disimpan sesuai statusnya.</p>
        </div>
    </section>

    <section class="form-panel transaction-form-panel">
        @if ($errors->any())
            <div class="notice notice-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ $action }}" class="stack-form">
            @csrf
            <div class="transaction-meta">
                <div>
                    <label for="{{ $partyField }}">{{ $partyLabel }}</label>
                    <select id="{{ $partyField }}" name="{{ $partyField }}" required>
                        <option value="">Pilih {{ strtolower($partyLabel) }}</option>
                        @foreach ($counterparts as $counterpart)
                            <option value="{{ $counterpart->getKey() }}" @selected(old($partyField) === $counterpart->getKey())>
                                {{ $counterpart->{$partyName} }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tanggal">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $date) }}" required>
                </div>
                @if ($showStatus)
                    <div>
                        <label for="status_pengiriman">Status pengiriman</label>
                        <select id="status_pengiriman" name="status_pengiriman" required>
                            <option value="Diproses" @selected(old('status_pengiriman', 'Diproses') === 'Diproses')>Diproses</option>
                            <option value="Diterima" @selected(old('status_pengiriman') === 'Diterima')>Diterima (stok langsung bertambah)</option>
                        </select>
                    </div>
                @endif
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
                                        @selected(($oldItem['id_persediaan'] ?? '') === $product->id_persediaan)
                                        @disabled($type === 'Penjualan' && $product->stok <= 0)
                                    >
                                        {{ $product->nama_barang }} (stok: {{ \App\Support\QuantityFormatter::format($product->stok) }} {{ $product->satuan }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="item-{{ $index }}-quantity">Jumlah barang (gunakan satuan barang)</label>
                            <input id="item-{{ $index }}-quantity" name="items[{{ $index }}][jumlah_barang]" type="number" min="0.001" step="0.001" value="{{ $oldItem['jumlah_barang'] ?? '' }}">
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
                                @if ($type !== 'Penjualan' || $product->stok > 0)
                                    <option value="{{ $product->id_persediaan }}">{{ $product->nama_barang }} (stok: {{ \App\Support\QuantityFormatter::format($product->stok) }} {{ $product->satuan }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="item-__INDEX__-quantity">Jumlah barang (gunakan satuan barang)</label>
                        <input id="item-__INDEX__-quantity" name="items[__INDEX__][jumlah_barang]" type="number" min="0.001" step="0.001">
                    </div>
                    <div>
                        <label for="item-__INDEX__-price">{{ $priceLabel }}</label>
                        <input id="item-__INDEX__-price" name="items[__INDEX__][{{ $priceField }}]" type="number" min="0.01" step="0.01">
                    </div>
                    <button class="text-button text-danger remove-line" type="button" data-remove-line aria-label="Hapus baris barang">Hapus</button>
                </div>
            </template>

            <div class="form-actions">
                <a class="button button-secondary" href="{{ route($type === 'Penjualan' ? 'employee.penjualan.index' : 'employee.pembelian.index') }}">Batal</a>
                <button class="button button-primary" type="submit">Simpan {{ strtolower($type) }}</button>
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
