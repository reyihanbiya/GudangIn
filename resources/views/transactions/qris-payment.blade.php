@extends('layouts.app')

@section('title', ($payment->isSimulation() ? 'Simulasi QRIS' : 'Pembayaran QRIS').' | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Kasir / Pembayaran</p>
            <h1>{{ $payment->isSimulation() ? 'Simulasi QRIS' : 'Bayar dengan QRIS' }}</h1>
            <p class="lead">
                {{ $payment->isSimulation() ? 'Simulasi lokal saja; tidak ada pembayaran sungguhan.' : 'Pindai kode QR di bawah untuk menyelesaikan checkout.' }}
            </p>
        </div>
        <a class="button button-secondary" href="{{ route('employee.penjualan.index') }}">Kembali ke kasir</a>
    </section>

    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

    <section
        class="qris-payment-panel"
        data-payment-status-url="{{ route('employee.penjualan.payment.status', $payment) }}"
        data-qr-value="{{ $payment->qr_string }}"
    >
        <div class="qris-payment-details">
            <span class="qris-payment-label">Total pembayaran</span>
            <strong>Rp{{ number_format((float) $payment->amount, fmod((float) $payment->amount, 1) === 0.0 ? 0 : 2, ',', '.') }}</strong>
            <span class="qris-payment-reference">Referensi {{ $payment->reference_id }}</span>
            @if ($payment->expires_at)
                <span class="qris-payment-expiry">Berlaku sampai {{ $payment->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
            @endif
        </div>

        @if ($payment->status === 'PENDING')
            <div class="qris-code-frame">
                <canvas id="qris-code" aria-label="Kode pembayaran QRIS"></canvas>
                <p id="qris-render-error" role="alert" hidden>Kode QR tidak dapat ditampilkan. Muat ulang halaman untuk mencoba kembali.</p>
            </div>
            @if ($payment->isSimulation())
                <div class="notice notice-error">Mode simulasi. QR ini tidak dapat digunakan untuk membayar sungguhan.</div>
                <form method="POST" action="{{ route('employee.penjualan.payment.simulate', $payment) }}">
                    @csrf
                    <button class="button button-primary" type="submit">Bayar (Simulasi)</button>
                </form>
            @else
                <p class="qris-payment-instructions">Buka aplikasi mobile banking atau e-wallet, pilih menu pindai QRIS, lalu konfirmasi pembayaran sesuai nominal di atas.</p>
                <p class="qris-payment-status" id="qris-payment-status" aria-live="polite">Menunggu pembayaran...</p>
            @endif
        @elseif ($payment->status === 'SUCCEEDED')
            <div class="notice notice-success">
                {{ $payment->isSimulation() ? 'Simulasi berhasil. Tidak ada pembayaran sungguhan.' : 'Pembayaran berhasil dikonfirmasi.' }}
            </div>
            <a class="button button-primary" href="{{ route('employee.penjualan.show', $payment->id_penjualan) }}">Lihat transaksi</a>
        @elseif ($payment->status === 'PAID_STOCK_CONFLICT')
            <div class="notice notice-error">Pembayaran terkonfirmasi, tetapi stok sudah berubah. Hubungi owner untuk menindaklanjuti pesanan ini.</div>
        @else
            <div class="notice notice-error">Pembayaran {{ $payment->status === 'EXPIRED' ? 'kedaluwarsa' : 'gagal' }}. Silakan buat checkout baru.</div>
        @endif
    </section>
@endsection

@if ($payment->status === 'PENDING')
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const panel = document.querySelector('.qris-payment-panel');
                const status = document.getElementById('qris-payment-status');
                const canvas = document.getElementById('qris-code');
                const renderError = document.getElementById('qris-render-error');

                window.QRCode.toCanvas(canvas, panel.dataset.qrValue, { width: 260, margin: 2 })
                    .catch(() => {
                        canvas.hidden = true;
                        renderError.hidden = false;
                    });

                @if (! $payment->isSimulation())
                const checkPayment = async () => {
                    try {
                        const response = await fetch(panel.dataset.paymentStatusUrl, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                        });
                        if (!response.ok) throw new Error('Status pembayaran tidak dapat diperiksa.');

                        const payment = await response.json();
                        if (payment.status === 'SUCCEEDED' && payment.sale_url) {
                            window.location.assign(payment.sale_url);
                            return;
                        }
                        if (['FAILED', 'EXPIRED', 'PAID_STOCK_CONFLICT'].includes(payment.status)) {
                            window.location.reload();
                            return;
                        }
                    } catch {
                        status.textContent = 'Belum menerima konfirmasi pembayaran. Halaman akan mencoba lagi.';
                    }

                    window.setTimeout(checkPayment, 5000);
                };

                window.setTimeout(checkPayment, 5000);
                @endif
            });
        </script>
    @endpush
@endif
