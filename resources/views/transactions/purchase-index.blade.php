@extends('layouts.app')

@section('title', 'Pembelian | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Transaksi</p>
            <h1>Pembelian</h1>
            <p class="lead">Catat pembelian barang dan konfirmasi penerimaan untuk menambah stok.</p>
        </div>
        <div class="heading-actions">
            <a class="button button-secondary" href="{{ route('employee.pembelian.export', array_filter($filters, fn ($value) => $value !== null && $value !== '')) }}">Ekspor daftar (Excel)</a>
            <a class="button button-secondary" href="{{ route('employee.pembelian.ocr.create') }}">Baca nota (OCR)</a>
            <a class="button button-primary" href="{{ route('employee.pembelian.create') }}">Buat pembelian</a>
        </div>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

    <section class="table-panel purchase-filter-panel" aria-label="Filter pembelian">
        <form method="GET" action="{{ route('employee.pembelian.index') }}" class="purchase-filter-form" data-purchase-filter-form>
            <div class="purchase-filter-grid">
                <div class="purchase-search-field">
                    <label class="sr-only" for="purchase-search">Cari ID atau supplier</label>
                    <input
                        id="purchase-search"
                        name="search"
                        type="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Cari ID atau supplier"
                    >
                    <button class="purchase-search-button" type="submit" aria-label="Cari pembelian">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <circle cx="10.8" cy="10.8" r="6.8"></circle>
                            <path d="m16 16 4.5 4.5"></path>
                        </svg>
                    </button>
                </div>
                <div class="purchase-date-range">
                    <span class="purchase-filter-label sr-only">Rentang tanggal</span>
                    <div class="purchase-date-inputs">
                        <label class="sr-only" for="purchase-from">Dari tanggal</label>
                        <input id="purchase-from" name="from" type="date" value="{{ $filters['from'] }}">
                        <span aria-hidden="true">–</span>
                        <label class="sr-only" for="purchase-to">Sampai tanggal</label>
                        <input id="purchase-to" name="to" type="date" value="{{ $filters['to'] }}">
                    </div>
                </div>
                <div>
                    <label class="sr-only" for="purchase-status">Status</label>
                    <select id="purchase-status" name="status">
                        <option value="">Semua status</option>
                        @foreach ($statusLabels as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="purchase-supplier">Supplier</label>
                    <select id="purchase-supplier" name="supplier">
                        <option value="">Semua supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id_supplier }}" @selected($filters['supplier'] === $supplier->id_supplier)>
                                {{ $supplier->nama_supplier }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if ($hasFilters)
            <div class="purchase-filter-chips" aria-label="Filter aktif">
                @if ($filters['search'] !== null)
                    <a class="purchase-filter-chip" href="{{ route('employee.pembelian.index', request()->except(['search', 'page'])) }}" aria-label="Hapus filter pencarian">
                        Pencarian: {{ $filters['search'] }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                @if ($filters['from'])
                    <a class="purchase-filter-chip" href="{{ route('employee.pembelian.index', request()->except(['from', 'page'])) }}" aria-label="Hapus filter tanggal awal">
                        Dari: {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d/m/Y') }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                @if ($filters['to'])
                    <a class="purchase-filter-chip" href="{{ route('employee.pembelian.index', request()->except(['to', 'page'])) }}" aria-label="Hapus filter tanggal akhir">
                        Sampai: {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d/m/Y') }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                @if ($filters['status'])
                    <a class="purchase-filter-chip" href="{{ route('employee.pembelian.index', request()->except(['status', 'page'])) }}" aria-label="Hapus filter status">
                        Status: {{ $statusLabels[$filters['status']] }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                @if ($filters['supplier'])
                    <a class="purchase-filter-chip" href="{{ route('employee.pembelian.index', request()->except(['supplier', 'page'])) }}" aria-label="Hapus filter supplier">
                        Supplier: {{ $selectedSupplier->nama_supplier }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                <a class="purchase-filter-reset" href="{{ route('employee.pembelian.index') }}">Hapus semua</a>
            </div>
        @endif

        <div class="purchase-list-toolbar">
            <p>
                @if ($transactions->total() > 0)
                    Menampilkan {{ number_format($transactions->firstItem(), 0, ',', '.') }}-{{ number_format($transactions->lastItem(), 0, ',', '.') }} dari {{ number_format($transactions->total(), 0, ',', '.') }} pembelian
                @else
                    Menampilkan 0 dari 0 pembelian
                @endif
            </p>
            @php
                $firstVisiblePage = max(1, min($transactions->currentPage() - 1, $transactions->lastPage() - 3));
                $lastVisiblePage = min($transactions->lastPage(), $firstVisiblePage + 3);
            @endphp
            <nav class="purchase-pagination" aria-label="Navigasi halaman pembelian">
                @if ($transactions->previousPageUrl())
                    <a href="{{ $transactions->previousPageUrl() }}" aria-label="Halaman sebelumnya">&lsaquo;</a>
                @else
                    <span aria-disabled="true">&lsaquo;</span>
                @endif
                @if ($firstVisiblePage > 1)
                    <a href="{{ $transactions->url(1) }}">1</a>
                    @if ($firstVisiblePage > 2)
                        <span aria-hidden="true">&hellip;</span>
                    @endif
                @endif
                @foreach ($transactions->getUrlRange($firstVisiblePage, $lastVisiblePage) as $page => $url)
                    @if ($page === $transactions->currentPage())
                        <span aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
                @if ($lastVisiblePage < $transactions->lastPage())
                    @if ($lastVisiblePage < $transactions->lastPage() - 1)
                        <span aria-hidden="true">&hellip;</span>
                    @endif
                    <a href="{{ $transactions->url($transactions->lastPage()) }}">{{ $transactions->lastPage() }}</a>
                @endif
                @if ($transactions->nextPageUrl())
                    <a href="{{ $transactions->nextPageUrl() }}" aria-label="Halaman berikutnya">&rsaquo;</a>
                @else
                    <span aria-disabled="true">&rsaquo;</span>
                @endif
            </nav>
        </div>
    </section>

    <section class="table-panel purchase-table-panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Tanggal</th><th>ID</th><th>Supplier</th><th>Pegawai</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($transaction->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $transaction->id_pembelian }}</td>
                            <td>{{ $transaction->supplier->nama_supplier }}</td>
                            <td>{{ $transaction->pegawai->nama_pegawai }}</td>
                            <td>Rp {{ number_format((float) $transaction->total, 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $purchaseStatus = $transaction->status_pengiriman;
                                    $hasPartialReceipt = $transaction->penerimaanPembelian->isNotEmpty();
                                    $statusLabel = $purchaseStatus === 'Diproses'
                                        ? ($hasPartialReceipt ? 'Backorder' : 'Menunggu')
                                        : $purchaseStatus;
                                    $statusClass = match ($purchaseStatus) {
                                        'Diproses' => 'purchase-status-pending',
                                        'Diterima' => 'purchase-status-received',
                                        'Dibatalkan' => 'purchase-status-cancelled',
                                        default => 'purchase-status-unknown',
                                    };
                                @endphp
                                <span class="purchase-status {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="purchase-action-cell">
                                <div class="purchase-row-actions">
                                    <a class="text-link" href="{{ route('employee.pembelian.purchase-order', $transaction) }}">PO</a>
                                    @if ($transaction->status_pengiriman === 'Diproses' && ! $hasPartialReceipt)
                                        <a class="text-link" href="{{ route('employee.pembelian.edit', $transaction) }}">Ubah</a>
                                    @endif
                                    <form method="POST" action="{{ route('employee.pembelian.destroy', $transaction) }}" onsubmit="return confirm('Hapus pembelian ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-button text-danger" type="submit">Hapus</button>
                                    </form>
                                    @if ($transaction->status_pengiriman === 'Diproses')
                                        <button
                                            class="text-button"
                                            type="button"
                                            data-open-receive
                                            data-dialog-id="receive-purchase-{{ $transaction->id_pembelian }}"
                                        >Terima</button>
                                        <dialog
                                            class="purchase-receive-dialog"
                                            id="receive-purchase-{{ $transaction->id_pembelian }}"
                                            aria-labelledby="receive-heading-{{ $transaction->id_pembelian }}"
                                            @if ($transaction->detailPembelian->contains(fn ($detail) => old('items.'.$detail->id_detail.'.jumlah_datang') !== null))
                                                data-open-after-validation
                                            @endif
                                        >
                                            <form method="POST" action="{{ route('employee.pembelian.receive', $transaction) }}" class="purchase-receive-form">
                                                @csrf
                                                @method('PATCH')
                                                <div class="purchase-receive-heading">
                                                    <div>
                                                        <h2 id="receive-heading-{{ $transaction->id_pembelian }}">Terima pembelian {{ $transaction->id_pembelian }}</h2>
                                                        <p>{{ $transaction->supplier->nama_supplier }} · {{ \Illuminate\Support\Carbon::parse($transaction->tanggal)->format('d/m/Y') }}</p>
                                                    </div>
                                                    <button class="purchase-receive-close" type="button" data-close-receive aria-label="Tutup popup penerimaan">&times;</button>
                                                </div>
                                                <div class="purchase-receive-table-wrap">
                                                    <table class="purchase-receive-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Barang</th>
                                                                <th>Dipesan</th>
                                                                <th>Sudah</th>
                                                                <th>Datang</th>
                                                                <th>Sisa</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($transaction->detailPembelian as $detail)
                                                                @php
                                                                    $alreadyReceived = (float) $detail->penerimaanLines->sum('jumlah_datang');
                                                                    $remaining = round(max(0, (float) $detail->jumlah_barang - $alreadyReceived), 3);
                                                                @endphp
                                                                <tr data-receive-line data-outstanding="{{ number_format($remaining, 3, '.', '') }}">
                                                                    <td>{{ $detail->persediaan->nama_barang }} <small>{{ $detail->satuan }}</small></td>
                                                                    <td>{{ \App\Support\QuantityFormatter::format($detail->jumlah_barang) }}</td>
                                                                    <td>{{ \App\Support\QuantityFormatter::format($alreadyReceived) }}</td>
                                                                    <td>
                                                                        <label class="sr-only" for="receive-{{ $transaction->id_pembelian }}-{{ $detail->id_detail }}">Jumlah datang {{ $detail->persediaan->nama_barang }}</label>
                                                                        <input
                                                                            id="receive-{{ $transaction->id_pembelian }}-{{ $detail->id_detail }}"
                                                                            name="items[{{ $detail->id_detail }}][jumlah_datang]"
                                                                            type="number"
                                                                            min="0"
                                                                            max="{{ number_format($remaining, 3, '.', '') }}"
                                                                            step="0.001"
                                                                            value="{{ old('items.'.$detail->id_detail.'.jumlah_datang', number_format($remaining, 3, '.', '')) }}"
                                                                            required
                                                                            data-receive-quantity
                                                                        >
                                                                    </td>
                                                                    <td>
                                                                        <span
                                                                            class="purchase-receive-badge {{ $remaining > 0 ? 'purchase-receive-backorder' : 'purchase-receive-complete' }}"
                                                                            data-receive-balance
                                                                        >
                                                                            {{ $remaining > 0 ? 'Backorder '.\App\Support\QuantityFormatter::format($remaining) : 'Lengkap' }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if ($transaction->penerimaanPembelian->isNotEmpty())
                                                    <section class="purchase-receipt-history">
                                                        <h3>Riwayat penerimaan</h3>
                                                        @foreach ($transaction->penerimaanPembelian as $receiptEvent)
                                                            <p>
                                                                {{ $receiptEvent->created_at->format('d/m/Y H:i') }}
                                                                · {{ $receiptEvent->catatan ?: 'Tanpa catatan' }}
                                                            </p>
                                                        @endforeach
                                                    </section>
                                                @endif
                                                <div class="purchase-receive-note">
                                                    <label for="receive-note-{{ $transaction->id_pembelian }}">Catatan penerimaan</label>
                                                    <textarea
                                                        id="receive-note-{{ $transaction->id_pembelian }}"
                                                        name="catatan"
                                                        maxlength="1000"
                                                        placeholder="Contoh: 20 sak menyusul Senin"
                                                        rows="2"
                                                    >{{ old('catatan') }}</textarea>
                                                </div>
                                                <div class="purchase-receive-actions">
                                                    <button class="button button-secondary" type="button" data-close-receive>Batal</button>
                                                    <button class="button button-primary" type="submit">Konfirmasi terima</button>
                                                </div>
                                            </form>
                                        </dialog>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="7">Tidak ada transaksi pembelian yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="purchase-detail-actions" style="position: fixed; right: 24px; bottom: 68px; z-index: 20;">
        <a class="button button-primary" href="{{ route('employee.pembelian.details') }}">Detail pembelian</a>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const purchaseFilterForm = document.querySelector('[data-purchase-filter-form]');
            if (purchaseFilterForm) {
                const fromDate = purchaseFilterForm.querySelector('[name="from"]');
                const toDate = purchaseFilterForm.querySelector('[name="to"]');
                purchaseFilterForm.querySelectorAll('[name="from"], [name="to"], [name="status"], [name="supplier"]')
                    .forEach((field) => field.addEventListener('change', () => {
                        if (fromDate.value && toDate.value && toDate.value < fromDate.value) return;
                        purchaseFilterForm.requestSubmit();
                    }));
            }

            const formatQuantity = (quantity) => new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 3,
            }).format(quantity);

            const updateReceiveBalance = (line) => {
                const input = line.querySelector('[data-receive-quantity]');
                const badge = line.querySelector('[data-receive-balance]');
                const outstanding = Number(line.dataset.outstanding);
                const arriving = Number(input.value || 0);

                if (arriving > outstanding) {
                    input.setCustomValidity('Jumlah datang tidak boleh melebihi sisa pesanan.');
                    badge.className = 'purchase-receive-badge purchase-receive-invalid';
                    badge.textContent = 'Melebihi sisa';
                    return;
                }

                input.setCustomValidity('');
                const remaining = Math.max(0, outstanding - arriving);
                badge.className = remaining > 0
                    ? 'purchase-receive-badge purchase-receive-backorder'
                    : 'purchase-receive-badge purchase-receive-complete';
                badge.textContent = remaining > 0
                    ? `Backorder ${formatQuantity(remaining)}`
                    : 'Lengkap';
            };

            document.querySelectorAll('[data-receive-line]').forEach((line) => {
                const input = line.querySelector('[data-receive-quantity]');
                input.addEventListener('input', () => updateReceiveBalance(line));
                updateReceiveBalance(line);
            });

            document.querySelectorAll('[data-open-receive]').forEach((button) => {
                const dialog = document.getElementById(button.dataset.dialogId);
                button.addEventListener('click', () => dialog.showModal());
            });

            document.querySelectorAll('[data-close-receive]').forEach((button) => {
                button.addEventListener('click', () => button.closest('dialog').close());
            });

            document.querySelectorAll('[data-open-after-validation]').forEach((dialog) => dialog.showModal());
        });
    </script>
@endpush
