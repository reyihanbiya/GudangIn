@extends('customer.layouts.app')

@section('title', 'Pesanan '.$sale->id_penjualan)

@php
    $payment = $sale->payments->first();
    $paymentLabels = [
        'PENDING' => 'Menunggu pembayaran',
        'AWAITING_PAYMENT' => 'Menunggu pembayaran',
        'AWAITING_VERIFICATION' => 'Menunggu verifikasi',
        'REJECTED' => 'Bukti ditolak',
        'PAID' => 'Lunas',
        'FAILED' => 'Gagal',
        'EXPIRED' => 'Kedaluwarsa',
        'CANCELLED' => 'Dibatalkan',
    ];
    $deliveryLabels = [
        'DIPROSES' => 'Pesanan diproses',
        'DISIAPKAN' => 'Pesanan disiapkan',
        'DALAM_PENGIRIMAN' => 'Dalam pengiriman',
        'SIAP_DIAMBIL' => 'Siap diambil di toko',
        'SELESAI' => 'Selesai',
        'DIBATALKAN' => 'Dibatalkan',
    ];
    $destinationLat = $sale->latitude_pengiriman;
    $destinationLng = $sale->longitude_pengiriman;
    $hasRoute = filled($storeLatitude) && filled($storeLongitude)
        && filled($destinationLat) && filled($destinationLng);
@endphp

@section('content')
<section class="customer-flow">
    <a class="back-link" href="{{ route('customer.tracking.index') }}">← Lacak pesanan lain</a>
    @if (session('success'))
        <div class="customer-alert success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="customer-alert error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="customer-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="order-summary-card">
        <div class="order-summary-heading">
            <div><span class="eyebrow">KODE PESANAN</span><strong>{{ $sale->id_penjualan }}</strong></div>
            <span class="status-pill">{{ $paymentLabels[$payment?->status ?? $sale->status_pembayaran] ?? $sale->status_pembayaran }}</span>
        </div>
        <p>{{ \Illuminate\Support\Carbon::parse($sale->tanggal)->format('d M Y') }} · ID pengiriman {{ $sale->id_pengiriman }}</p>
        <div class="checkout-line"><span>Total pesanan</span><strong>Rp{{ number_format((float) $sale->detailPenjualan->sum('subtotal') + (float) $sale->biaya_pengiriman, 0, ',', '.') }}</strong></div>
        <div class="checkout-line"><span>Metode pembayaran</span><strong>{{ str_replace('_', ' ', ucfirst($payment?->method ?? $sale->metode_pembayaran ?? 'Tunai')) }}</strong></div>
    </section>

    <section class="order-timeline-card">
        <h2>Status pesanan</h2>
        <ol class="order-timeline">
            @forelse ($sale->orderEvents as $event)
                <li>
                    <span class="timeline-dot"></span>
                    <div><strong>{{ $event->message }}</strong><small>{{ $event->created_at->format('d M Y H:i') }}</small></div>
                </li>
            @empty
                <li><span class="timeline-dot"></span><div><strong>{{ $deliveryLabels[$sale->status_pengiriman] ?? $sale->status_pengiriman }}</strong><small>Status awal pesanan.</small></div></li>
            @endforelse
            <li class="timeline-current"><span class="timeline-dot"></span><div><strong>{{ $deliveryLabels[$sale->status_pengiriman] ?? $sale->status_pengiriman }}</strong><small>Status pengiriman saat ini.</small></div></li>
        </ol>
    </section>

    @if ($payment?->checkout_url && $payment->status === 'PENDING')
        <section class="order-action-card">
            <h2>Selesaikan pembayaran</h2>
            <p>Pembayaran otomatis diproses oleh Midtrans. Pesanan akan diperbarui setelah notifikasi terverifikasi.</p>
            <a class="hero-button" href="{{ $payment->checkout_url }}" rel="noopener">Buka halaman pembayaran <span>→</span></a>
        </section>
    @elseif ($payment?->provider === 'midtrans' && $payment->status === 'PENDING')
        <section class="order-action-card">
            <h2>Konfirmasi pembayaran sedang diperiksa</h2>
            <p>Gateway belum memberikan tautan pembayaran. Jangan membayar ulang; hubungi toko dengan kode pesanan ini untuk memastikan statusnya.</p>
        </section>
    @elseif ($payment?->method === 'bank_transfer' && in_array($payment->status, ['AWAITING_PAYMENT', 'REJECTED'], true))
        <section class="order-action-card">
            <h2>Transfer bank</h2>
            <p>Nominal transfer: <strong>Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</strong></p>
            @if ($bankDetails['name'] && $bankDetails['account'])
                <p>{{ $bankDetails['name'] }} · {{ $bankDetails['account'] }}<br>a.n. {{ $bankDetails['holder'] ?: 'GudangIn' }}</p>
            @else
                <p>Informasi rekening toko belum dikonfigurasi. Hubungi toko sebelum melakukan transfer.</p>
            @endif
            @if ($payment->status === 'REJECTED')
                <p class="customer-alert error">{{ $payment->metadata['rejection_reason'] ?? 'Bukti pembayaran ditolak. Silakan unggah ulang.' }}</p>
            @endif
            <form method="POST" action="{{ route('customer.orders.proof', $sale) }}" enctype="multipart/form-data" class="customer-form">
                @csrf
                <label>Unggah bukti transfer (JPG, PNG, atau PDF; maks. 8 MB)
                    <input type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.pdf" required>
                </label>
                <button class="hero-button" type="submit">Kirim bukti pembayaran <span>→</span></button>
            </form>
        </section>
    @elseif (in_array($payment?->method, ['store_cash', 'cod'], true) && $payment->status === 'PENDING')
        <section class="order-action-card">
            <h2>{{ $payment->method === 'cod' ? 'Pembayaran COD' : 'Bayar di toko' }}</h2>
            <p>{{ $payment->method === 'cod' ? 'Pembayaran dilakukan kepada kurir saat pesanan diterima.' : 'Silakan lakukan pembayaran di kasir saat mengambil pesanan.' }}</p>
        </section>
    @endif

    @if ($sale->jenis_pengiriman === 'pengiriman')
        <section class="order-action-card">
            <h2>Alamat pengiriman</h2>
            <p>{{ $sale->alamat_pengiriman ?: $sale->pelanggan->alamat }}</p>
            @if ($hasRoute)
                <a class="text-link" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&origin={{ $storeLatitude }},{{ $storeLongitude }}&destination={{ $destinationLat }},{{ $destinationLng }}">Lihat rute dari toko</a>
            @endif
            @if ($mapsApiKey && $destinationLat !== null && $destinationLng !== null)
                <div id="tracking-map" class="customer-map customer-map-tracking"
                    data-destination-lat="{{ $destinationLat }}"
                    data-destination-lng="{{ $destinationLng }}"
                    data-store-lat="{{ $storeLatitude }}"
                    data-store-lng="{{ $storeLongitude }}"
                    data-location-url="{{ route('customer.orders.courier-location', $sale) }}"></div>
                <p class="form-help" id="courier-location-status" aria-live="polite">Menunggu lokasi kurir.</p>
            @endif
        </section>
    @endif

    <section class="order-action-card">
        <h2>Rincian pembayaran</h2>
        @foreach ($sale->payments as $paymentEntry)
            <div class="checkout-line">
                <span>{{ $paymentEntry->created_at->format('d/m/Y H:i') }} · {{ $paymentLabels[$paymentEntry->status] ?? $paymentEntry->status }}</span>
                <strong>Rp{{ number_format((float) $paymentEntry->amount, 0, ',', '.') }}</strong>
            </div>
        @endforeach
        @foreach ($sale->detailPenjualan as $line)
            <div class="checkout-line">
                <span>{{ $line->persediaan?->nama_barang ?? $line->id_persediaan }} × {{ \App\Support\QuantityFormatter::format($line->jumlah_barang) }} {{ $line->satuan }}</span>
                <strong>Rp{{ number_format((float) $line->subtotal, 0, ',', '.') }}</strong>
            </div>
        @endforeach
    </section>
</section>
@endsection

@if ($mapsApiKey && $sale->jenis_pengiriman === 'pengiriman' && $destinationLat !== null && $destinationLng !== null)
    @push('scripts')
        <script>
            (() => {
                window.initOrderTrackingMap = () => {
                    const mapElement = document.getElementById('tracking-map');
                    const destination = {
                        lat: Number(mapElement.dataset.destinationLat),
                        lng: Number(mapElement.dataset.destinationLng),
                    };
                    const store = {
                        lat: Number(mapElement.dataset.storeLat),
                        lng: Number(mapElement.dataset.storeLng),
                    };
                    const hasStore = mapElement.dataset.storeLat !== '' && mapElement.dataset.storeLng !== '';
                    const map = new google.maps.Map(mapElement, {
                        center: destination,
                        zoom: 14,
                        mapTypeControl: false,
                        streetViewControl: false,
                    });
                    new google.maps.Marker({ map, position: destination, title: 'Tujuan pengiriman' });
                    if (hasStore) new google.maps.Marker({ map, position: store, title: 'Toko GudangIn' });

                    const status = document.getElementById('courier-location-status');
                    let courierMarker;
                    const updateCourier = async () => {
                        try {
                            const response = await fetch(mapElement.dataset.locationUrl, {
                                headers: { Accept: 'application/json' },
                                cache: 'no-store',
                            });
                            if (!response.ok) throw new Error('Lokasi kurir tidak dapat dimuat.');
                            const data = await response.json();
                            if (data.status !== 'DALAM_PENGIRIMAN') {
                                status.textContent = data.status === 'SELESAI'
                                    ? 'Pesanan telah selesai.'
                                    : `Status pesanan: ${data.status}. Lokasi kurir muncul saat pengiriman dimulai.`;
                                if (['SELESAI', 'DIBATALKAN'].includes(data.status)) return;
                            } else if (data.location) {
                                const point = { lat: data.location.latitude, lng: data.location.longitude };
                                if (!courierMarker) courierMarker = new google.maps.Marker({ map, title: 'Lokasi kurir' });
                                courierMarker.setPosition(point);
                                status.textContent = `Lokasi kurir diperbarui ${new Date(data.location.recorded_at).toLocaleTimeString('id-ID')}.`;
                            } else {
                                status.textContent = 'Kurir belum membagikan lokasi terbaru.';
                            }
                        } catch {
                            status.textContent = 'Lokasi kurir sementara tidak tersedia.';
                        }
                        window.setTimeout(updateCourier, 15000);
                    };
                    updateCourier();
                };
            })();
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($mapsApiKey) }}&callback=initOrderTrackingMap"></script>
    @endpush
@endif
