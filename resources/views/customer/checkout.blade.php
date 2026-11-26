@extends('customer.layouts.app')

@section('title', 'Checkout Pesanan')

@section('content')
<section class="customer-flow">
    <a class="back-link" href="{{ route('customer.cart.index') }}">← Kembali ke keranjang</a>
    <div class="section-heading">
        <div>
            <span class="eyebrow">CHECKOUT</span>
            <h2>Data pengiriman<span>.</span></h2>
            <p>Isi kontak yang dapat menerima konfirmasi pesanan.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="customer-alert error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('customer.checkout.store') }}" class="customer-form" id="customer-checkout-form">
        @csrf
        <label>Nama penerima
            <input name="nama_pelanggan" value="{{ old('nama_pelanggan') }}" autocomplete="name" maxlength="100" required>
        </label>
        <label>Nomor WhatsApp
            <input name="no_hp" type="tel" value="{{ old('no_hp') }}" autocomplete="tel" maxlength="20" required>
        </label>

        <fieldset>
            <legend>Metode penerimaan</legend>
            <label class="choice-row">
                <input type="radio" name="jenis_pengiriman" value="pengiriman" @checked(old('jenis_pengiriman', 'pengiriman') === 'pengiriman')>
                <span>Antar ke alamat</span>
            </label>
            <label class="choice-row">
                <input type="radio" name="jenis_pengiriman" value="ambil_di_toko" @checked(old('jenis_pengiriman') === 'ambil_di_toko')>
                <span>Ambil di toko</span>
            </label>
        </fieldset>

        <div id="delivery-fields">
            <label>Alamat lengkap
                <input id="delivery-address" name="alamat" value="{{ old('alamat') }}" autocomplete="street-address" maxlength="500">
            </label>
            <input type="hidden" name="latitude" id="delivery-latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="delivery-longitude" value="{{ old('longitude') }}">
            @if ($mapsApiKey)
                <p class="form-help">Cari alamat, pilih hasil, atau ketuk peta untuk menandai titik pengiriman.</p>
                <div id="delivery-map" class="customer-map" data-store-lat="{{ $storeLatitude }}" data-store-lng="{{ $storeLongitude }}"></div>
                <button class="map-location-button" type="button" id="use-current-location">Gunakan lokasi saya</button>
                <p class="form-help" id="map-selection-status" aria-live="polite">Pin lokasi belum dipilih; alamat yang diketik tetap dapat digunakan.</p>
            @else
                <p class="form-help">Peta belum diaktifkan. Alamat tetap dapat diisi manual.</p>
            @endif
        </div>

        <fieldset>
            <legend>Metode pembayaran</legend>
            <label class="choice-row">
                <input type="radio" name="metode_pembayaran" value="bank_transfer" @checked(old('metode_pembayaran', 'bank_transfer') === 'bank_transfer') required>
                <span>Transfer bank manual <small>Konfirmasi dengan unggah bukti pembayaran.</small></span>
            </label>
            @if ($midtransAvailable)
                <label class="choice-row">
                    <input type="radio" name="metode_pembayaran" value="qris" @checked(old('metode_pembayaran') === 'qris')>
                    <span>QRIS otomatis <small>Status diperbarui setelah notifikasi Midtrans tervalidasi.</small></span>
                </label>
                <label class="choice-row">
                    <input type="radio" name="metode_pembayaran" value="transfer_gateway" @checked(old('metode_pembayaran') === 'transfer_gateway')>
                    <span>Transfer bank otomatis <small>Pilih VA bank pada halaman pembayaran Midtrans.</small></span>
                </label>
            @endif
            <label class="choice-row" data-pickup-payment>
                <input type="radio" name="metode_pembayaran" value="store_cash" @checked(old('metode_pembayaran') === 'store_cash')>
                <span>Bayar langsung di toko</span>
            </label>
            <label class="choice-row" data-delivery-payment>
                <input type="radio" name="metode_pembayaran" value="cod" @checked(old('metode_pembayaran') === 'cod')>
                <span>Bayar saat pesanan diterima (COD)</span>
            </label>
        </fieldset>

        <section class="checkout-items">
            <h3>Ringkasan pesanan</h3>
            @foreach ($items as $item)
                <div class="checkout-line">
                    <span>{{ $item['product']->nama_barang }} × {{ \App\Support\QuantityFormatter::format($item['quantity']) }} {{ $item['product']->satuan }}</span>
                    <strong>Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
                </div>
            @endforeach
            <div class="checkout-line"><span>Subtotal</span><strong>Rp{{ number_format($subtotal, 0, ',', '.') }}</strong></div>
            <div class="checkout-line" id="checkout-delivery-fee" data-fee="{{ $deliveryFee }}">
                <span>Biaya pengiriman</span>
                <strong>Rp{{ number_format($deliveryFee, 0, ',', '.') }}</strong>
            </div>
            <div class="checkout-total">
                <span>Total</span>
                <strong id="checkout-total" data-subtotal="{{ $subtotal }}">Rp{{ number_format($subtotal + $deliveryFee, 0, ',', '.') }}</strong>
            </div>
        </section>

        <button class="hero-button checkout-submit" type="submit">Buat pesanan <span>→</span></button>
    </form>
</section>
@endsection

@if ($mapsApiKey)
    @push('scripts')
        <script>
            (() => {
                const form = document.getElementById('customer-checkout-form');
                const mapElement = document.getElementById('delivery-map');
                const address = document.getElementById('delivery-address');
                const latitude = document.getElementById('delivery-latitude');
                const longitude = document.getElementById('delivery-longitude');
                const status = document.getElementById('map-selection-status');
                const deliveryFields = document.getElementById('delivery-fields');
                const fee = document.getElementById('checkout-delivery-fee');
                const total = document.getElementById('checkout-total');
                let map;
                let marker;
                let geocoder;

                const setDeliveryType = () => {
                    const pickup = form.querySelector('[name="jenis_pengiriman"]:checked').value === 'ambil_di_toko';
                    deliveryFields.hidden = pickup;
                    deliveryFields.querySelectorAll('input:not([type="hidden"])').forEach((input) => input.required = !pickup);
                    form.querySelectorAll('[data-pickup-payment], [data-delivery-payment]').forEach((row) => {
                        const pickupOnly = row.hasAttribute('data-pickup-payment');
                        row.hidden = pickupOnly ? !pickup : pickup;
                        const radio = row.querySelector('input');
                        radio.disabled = row.hidden;
                        if (row.hidden && radio.checked) radio.checked = false;
                    });
                    const shippingFee = pickup ? 0 : Number(fee.dataset.fee || 0);
                    fee.hidden = pickup;
                    total.textContent = `Rp${new Intl.NumberFormat('id-ID').format(Number(total.dataset.subtotal) + shippingFee)}`;
                };

                form.querySelectorAll('[name="jenis_pengiriman"]').forEach((radio) => {
                    radio.addEventListener('change', setDeliveryType);
                });
                setDeliveryType();

                window.initCustomerDeliveryMap = () => {
                    const configuredLat = Number(mapElement.dataset.storeLat);
                    const configuredLng = Number(mapElement.dataset.storeLng);
                    const hasStoreLocation = Number.isFinite(configuredLat) && Number.isFinite(configuredLng)
                        && mapElement.dataset.storeLat !== '' && mapElement.dataset.storeLng !== '';
                    const initialCenter = hasStoreLocation
                        ? { lat: configuredLat, lng: configuredLng }
                        : { lat: 0, lng: 0 };
                    map = new google.maps.Map(mapElement, {
                        center: initialCenter,
                        zoom: hasStoreLocation ? 12 : 2,
                        mapTypeControl: false,
                        streetViewControl: false,
                    });
                    geocoder = new google.maps.Geocoder();
                    if (hasStoreLocation) {
                        new google.maps.Marker({
                            map,
                            position: initialCenter,
                            title: 'Lokasi toko GudangIn',
                            label: 'T',
                        });
                    }
                    marker = new google.maps.Marker({ map, draggable: true });

                    const chooseLocation = (point) => {
                        marker.setPosition(point);
                        map.panTo(point);
                        latitude.value = point.lat().toFixed(7);
                        longitude.value = point.lng().toFixed(7);
                        status.textContent = `Titik dipilih: ${latitude.value}, ${longitude.value}`;
                        geocoder.geocode({ location: point }, (results, resultStatus) => {
                            if (resultStatus === 'OK' && results[0] && !address.value) {
                                address.value = results[0].formatted_address;
                            }
                        });
                    };

                    map.addListener('click', (event) => chooseLocation(event.latLng));
                    marker.addListener('dragend', (event) => chooseLocation(event.latLng));
                    const autocomplete = new google.maps.places.Autocomplete(address, {
                        fields: ['formatted_address', 'geometry'],
                    });
                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        if (!place.geometry?.location) return;
                        address.value = place.formatted_address || address.value;
                        chooseLocation(place.geometry.location);
                        map.setZoom(16);
                    });

                    document.getElementById('use-current-location').addEventListener('click', () => {
                        if (!navigator.geolocation) {
                            status.textContent = 'Browser ini tidak mendukung lokasi perangkat.';
                            return;
                        }
                        navigator.geolocation.getCurrentPosition(
                            ({ coords }) => chooseLocation(new google.maps.LatLng(coords.latitude, coords.longitude)),
                            () => { status.textContent = 'Lokasi tidak dapat diakses. Periksa izin lokasi browser.'; },
                            { enableHighAccuracy: true, timeout: 10000 }
                        );
                    });

                    if (latitude.value && longitude.value) {
                        chooseLocation(new google.maps.LatLng(Number(latitude.value), Number(longitude.value)));
                    }
                };
            })();
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($mapsApiKey) }}&libraries=places&callback=initCustomerDeliveryMap"></script>
    @endpush
@endif
