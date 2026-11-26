@extends('layouts.app')

@section('title', 'Halaman kurir | GudangIn')

@section('content')
<section class="page-heading">
    <div>
        <p class="eyebrow">Pengiriman</p>
        <h1>Halaman kurir</h1>
        <p class="lead">Ambil tugas, perbarui status, dan bagikan lokasi selama perjalanan.</p>
    </div>
</section>
@if (session('status'))
    <div class="notice notice-success page-notice">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
@endif

<div class="courier-order-list">
    @forelse ($orders as $order)
        @php
            $isAssigned = $order->id_kurir === $courier->id_pegawai;
            $nextStatuses = match ($order->status_pengiriman) {
                'DIPROSES' => ['DISIAPKAN', 'DALAM_PENGIRIMAN'],
                'DISIAPKAN' => ['DALAM_PENGIRIMAN'],
                'DALAM_PENGIRIMAN' => ['SELESAI'],
                default => [],
            };
        @endphp
        <section class="table-panel courier-order-card">
            <div class="courier-order-heading">
                <div><span class="eyebrow">PESANAN {{ $order->id_penjualan }}</span><h2>{{ $order->nama_penerima ?: $order->pelanggan->nama_pelanggan }}</h2></div>
                <span class="status-pill">{{ str_replace('_', ' ', $order->status_pengiriman) }}</span>
            </div>
            <p>{{ $order->alamat_pengiriman ?: $order->pelanggan->alamat }}</p>
            <p>{{ $order->telepon_penerima ?: $order->pelanggan->no_hp }}</p>
            @if ($order->latitude_pengiriman !== null && $order->longitude_pengiriman !== null)
                <a class="text-link" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $order->latitude_pengiriman }},{{ $order->longitude_pengiriman }}">Navigasi ke pelanggan</a>
            @endif
            <div class="courier-order-items">
                @foreach ($order->detailPenjualan as $line)
                    <span>{{ $line->persediaan?->nama_barang ?? $line->id_persediaan }} × {{ \App\Support\QuantityFormatter::format($line->jumlah_barang) }} {{ $line->satuan }}</span>
                @endforeach
            </div>
            @if (! $isAssigned)
                <form method="POST" action="{{ route('employee.courier.claim', $order) }}">
                    @csrf
                    <button class="button button-primary" type="submit">Ambil tugas ini</button>
                </form>
            @else
                @if ($nextStatuses !== [])
                    <form method="POST" action="{{ route('employee.courier.status', $order) }}" class="courier-status-form">
                        @csrf
                        @method('PATCH')
                        <label>Status berikutnya
                            <select name="status_pengiriman" required>
                                @foreach ($nextStatuses as $status)
                                    <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="button button-primary" type="submit">Perbarui status</button>
                    </form>
                @endif
                @if ($order->status_pengiriman === 'DALAM_PENGIRIMAN')
                    <p class="courier-gps-status" data-gps-status="{{ $order->id_penjualan }}" aria-live="polite">Lokasi GPS belum dikirim.</p>
                    <button class="button button-secondary" type="button" data-start-gps="{{ $order->id_penjualan }}" data-location-url="{{ route('employee.courier.location', $order) }}">Bagikan lokasi GPS</button>
                @endif
            @endif
        </section>
    @empty
        <div class="table-panel empty-cell">Belum ada pesanan pengiriman yang tersedia untuk Anda.</div>
    @endforelse
</div>
<div class="pagination-wrap">{{ $orders->links() }}</div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-start-gps]').forEach((button) => {
            let lastSentAt = 0;
            let watchId;
            const orderId = button.dataset.startGps;
            const status = document.querySelector(`[data-gps-status="${orderId}"]`);
            button.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    status.textContent = 'Browser ini tidak mendukung GPS.';
                    return;
                }
                if (watchId !== undefined) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = undefined;
                    button.textContent = 'Bagikan lokasi GPS';
                    status.textContent = 'Berbagi lokasi dihentikan.';
                    return;
                }
                watchId = navigator.geolocation.watchPosition(async ({ coords }) => {
                    if (Date.now() - lastSentAt < 15000) return;
                    lastSentAt = Date.now();
                    try {
                        const response = await fetch(button.dataset.locationUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ latitude: coords.latitude, longitude: coords.longitude }),
                        });
                        if (!response.ok) throw new Error('Lokasi gagal dikirim.');
                        status.textContent = `Lokasi diperbarui ${new Date().toLocaleTimeString('id-ID')}.`;
                    } catch {
                        status.textContent = 'Lokasi gagal dikirim. Coba lagi saat jaringan tersedia.';
                    }
                }, () => {
                    status.textContent = 'GPS tidak tersedia. Periksa izin lokasi dan koneksi aman HTTPS.';
                }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 });
                button.textContent = 'Hentikan berbagi lokasi';
                status.textContent = 'Berbagi lokasi aktif.';
            });
        });
    </script>
@endpush
