@extends('layouts.app')

@section('title', 'Pembayaran pelanggan | GudangIn')

@section('content')
<section class="page-heading">
    <div>
        <p class="eyebrow">Pesanan online</p>
        <h1>Verifikasi pembayaran</h1>
        <p class="lead">Periksa bukti transfer dan konfirmasi pembayaran tunai/COD.</p>
    </div>
</section>

@if (session('status'))
    <div class="notice notice-success page-notice">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
@endif

<section class="table-panel">
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>Pesanan</th><th>Pelanggan</th><th>Metode</th><th>Total</th><th>Status</th><th>Bukti / tindakan</th></tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    @php
                        $ownerArea = request()->routeIs('owner.*');
                        $routePrefix = $ownerArea ? 'owner' : 'employee';
                    @endphp
                    <tr>
                        <td>{{ $payment->penjualan->id_penjualan }}<br><small>{{ $payment->created_at->format('d/m/Y H:i') }}</small></td>
                        <td>{{ $payment->penjualan->nama_penerima ?: $payment->penjualan->pelanggan->nama_pelanggan }}<br><small>{{ $payment->penjualan->telepon_penerima ?: $payment->penjualan->pelanggan->no_hp }}</small></td>
                        <td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td>
                        <td>Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                        <td>{{ $payment->status }}</td>
                        <td>
                            @if ($payment->proof_path)
                                <a href="{{ route($routePrefix.'.payments.proof', $payment) }}" target="_blank" rel="noopener">Lihat bukti</a>
                            @endif
                            @if (
                                ($payment->method === 'bank_transfer' && $payment->status === 'AWAITING_VERIFICATION')
                                || (in_array($payment->method, ['store_cash', 'cod'], true) && $payment->status === 'PENDING')
                            )
                                <form method="POST" action="{{ route($routePrefix.'.payments.update', $payment) }}" class="payment-review-actions">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button class="button button-primary" type="submit">Verifikasi lunas</button>
                                </form>
                            @endif
                            @if ($payment->method === 'bank_transfer' && $payment->status === 'AWAITING_VERIFICATION')
                                <form method="POST" action="{{ route($routePrefix.'.payments.update', $payment) }}" class="payment-review-actions">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <label>Alasan penolakan
                                        <input name="rejection_reason" maxlength="255" required>
                                    </label>
                                    <button class="button button-secondary" type="submit">Tolak bukti</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="empty-cell" colspan="6">Tidak ada pembayaran yang menunggu tindakan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $payments->links() }}</div>
</section>

<section class="page-heading">
    <div>
        <p class="eyebrow">Ambil di toko</p>
        <h2>Pesanan siap diambil</h2>
    </div>
</section>
<section class="table-panel">
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>Pesanan</th><th>Pelanggan</th><th>Pembayaran</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($pickupOrders as $order)
                    @php $latestPayment = $order->payments->first(); @endphp
                    <tr>
                        <td>{{ $order->id_penjualan }}</td>
                        <td>{{ $order->nama_penerima ?: $order->pelanggan->nama_pelanggan }}</td>
                        <td>{{ $latestPayment?->status ?? $order->status_pembayaran }}</td>
                        <td>{{ $order->status_pengiriman }}</td>
                        <td>
                            @if ($order->status_pembayaran === 'TERBAYAR')
                                <form method="POST" action="{{ route(request()->routeIs('owner.*') ? 'owner.payments.pickup.complete' : 'employee.payments.pickup.complete', $order) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="button button-primary" type="submit">Tandai selesai diambil</button>
                                </form>
                            @else
                                <span class="muted">Lunasi pembayaran dahulu</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="empty-cell" colspan="5">Tidak ada pesanan yang menunggu diambil.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
