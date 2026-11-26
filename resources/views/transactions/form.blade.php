@extends('layouts.app')

@section('title', 'Buat '.$type.' | GudangIn')

@php
    $partyField = $type === 'Penjualan' ? 'id_pelanggan' : 'id_supplier';
    $partyLabel = $type === 'Penjualan' ? 'Pelanggan' : 'Supplier';
    $partyName = $type === 'Penjualan' ? 'nama_pelanggan' : 'nama_supplier';
    $priceField = $type === 'Penjualan' ? 'harga_satuan' : 'harga_beli';
    $priceLabel = $type === 'Penjualan' ? 'Harga jual satuan' : 'Harga beli satuan';
    $oldItems = old('items');
    if ($oldItems === null) {
        if (isset($oldTransaction)) {
            $oldItems = $oldTransaction->detailPembelian->map(fn ($line) => [
                'id_persediaan' => $line->id_persediaan,
                'jumlah_barang' => $line->jumlah_barang,
                $priceField => $line->{$priceField},
            ])->all();
        } elseif (isset($ocrPrefill) && ($ocrPrefill['items'] ?? []) !== []) {
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
            <h1>{{ isset($oldTransaction) ? 'Ubah' : 'Buat' }} {{ strtolower($type) }}</h1>
            <p class="lead">
                Isi rincian dengan benar.
                {{ $type === 'Pembelian' ? 'Stok akan bertambah setelah pembelian dikonfirmasi diterima.' : 'Stok akan berkurang otomatis saat penjualan disimpan.' }}
            </p>
        </div>
    </section>

    <section class="form-panel transaction-form-panel">
        @if ($errors->any())
            <div class="notice notice-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ $action }}" class="stack-form">
            @csrf
            @if (isset($method))
                @method($method)
            @endif
            <div class="transaction-meta">
                <div>
                    <label for="{{ $partyField }}">{{ $partyLabel }}</label>
                    <select id="{{ $partyField }}" name="{{ $partyField }}" required>
                        <option value="">Pilih {{ strtolower($partyLabel) }}</option>
                        @foreach ($counterparts as $counterpart)
                            <option value="{{ $counterpart->getKey() }}" @selected(old($partyField, $oldTransaction->{$partyField} ?? ($ocrPrefill['id_supplier'] ?? '')) === $counterpart->getKey())>
                                {{ $counterpart->{$partyName} }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tanggal">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $oldTransaction->tanggal ?? ($ocrPrefill['tanggal'] ?? $date)) }}" required>
                </div>
            </div>

            @if ($type === 'Pembelian')
                @if (($restockRecommendations ?? collect())->isNotEmpty())
                    <section class="purchase-restock-panel" data-restock-panel aria-labelledby="restock-heading">
                        <div class="purchase-restock-heading">
                            <div>
                                <h2 id="restock-heading">Rekomendasi restock</h2>
                                <p>Ada {{ $restockRecommendations->count() }} barang dengan stok di bawah minimum. Saran mempertimbangkan penjualan 30 hari terakhir.</p>
                            </div>
                            <button class="button button-secondary" type="button" data-fill-restock>Isi dari stok menipis</button>
                        </div>
                        <div class="purchase-restock-table-wrap">
                            <table class="purchase-restock-table">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Stok / min</th>
                                        <th>Terjual 30 hari</th>
                                        <th>Jumlah saran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($restockRecommendations as $recommendation)
                                        @php
                                            $recommendedProduct = $recommendation['product'];
                                            $quantityLabel = 'Jumlah saran untuk '.$recommendedProduct->nama_barang;
                                        @endphp
                                        <tr
                                            data-restock-item
                                            data-product-id="{{ $recommendedProduct->id_persediaan }}"
                                            data-sold-in-thirty-days="{{ number_format($recommendation['sold_in_thirty_days'], 3, '.', '') }}"
                                        >
                                            <td>{{ $recommendedProduct->nama_barang }}</td>
                                            <td>
                                                {{ \App\Support\QuantityFormatter::format($recommendedProduct->stok) }}
                                                /
                                                {{ \App\Support\QuantityFormatter::format($recommendedProduct->stok_minimum) }}
                                                {{ $recommendedProduct->satuan }}
                                            </td>
                                            <td>
                                                {{ \App\Support\QuantityFormatter::format($recommendation['sold_in_thirty_days']) }}
                                                {{ $recommendedProduct->satuan }}
                                            </td>
                                            <td>
                                                <div class="purchase-restock-quantity">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.001"
                                                        value="{{ number_format($recommendation['suggested_quantity'], 3, '.', '') }}"
                                                        data-restock-quantity
                                                        aria-label="{{ $quantityLabel }}"
                                                    >
                                                    <span>{{ $recommendedProduct->satuan }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

            @endif

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
                                @if ($type !== 'Penjualan' || $product->stok > 0)
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
                <a class="button button-secondary" href="{{ route($type === 'Penjualan' ? 'employee.penjualan.index' : 'employee.pembelian.index') }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $submitLabel ?? 'Simpan '.strtolower($type) }}</button>
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

            list.querySelectorAll('[data-transaction-line]').forEach((row) => {
                updateQuantityUnit(row);
            });

            list.addEventListener('change', (event) => {
                const row = event.target.closest('[data-transaction-line]');
                if (row && event.target.matches('select')) {
                    updateQuantityUnit(row);
                }
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

            const restockPanel = document.querySelector('[data-restock-panel]');
            const fillRestockButton = restockPanel?.querySelector('[data-fill-restock]');
            fillRestockButton?.addEventListener('click', () => {
                restockPanel.querySelectorAll('[data-restock-item]').forEach((recommendation) => {
                    const productId = recommendation.dataset.productId;
                    const quantity = recommendation.querySelector('[data-restock-quantity]').value;
                    const rows = [...list.querySelectorAll('[data-transaction-line]')];
                    let row = rows.find((line) => line.querySelector('select').value === productId);

                    if (row) {
                        const quantityInput = row.querySelector('input[name$="[jumlah_barang]"]');
                        if (quantityInput.value === '') quantityInput.value = quantity;
                        return;
                    }

                    row = rows.find((line) => (
                        line.querySelector('select').value === ''
                        && line.querySelector('input[name$="[jumlah_barang]"]').value === ''
                    ));

                    if (!row) {
                        addButton.click();
                        row = list.lastElementChild;
                    }

                    const productSelect = row.querySelector('select');
                    productSelect.value = productId;
                    productSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    row.querySelector('input[name$="[jumlah_barang]"]').value = quantity;
                });
            });
        });
    </script>
@endpush
