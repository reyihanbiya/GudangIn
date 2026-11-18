@extends('layouts.app')

@section('title', 'Kasir | GudangIn')

@section('content')
    <div class="cashier-screen">
        <section class="cashier-catalog" aria-label="Katalog barang">
            @if ($errors->any())
                <div class="notice notice-error cashier-notice">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="notice notice-success cashier-notice">{{ session('status') }}</div>
            @endif
            <label class="cashier-search">
                <span aria-hidden="true">⌕</span>
                <input id="cashier-search" type="search" placeholder="Cari nama atau kode barang..." autocomplete="off">
                <kbd>/</kbd>
            </label>
            <div class="cashier-categories" id="cashier-categories" aria-label="Filter kategori"></div>
            <div class="cashier-products" id="cashier-products">
                @forelse ($products as $product)
                    <article
                        class="cashier-product-card"
                        data-product-card
                        data-id="{{ $product->id_persediaan }}"
                        data-name="{{ $product->nama_barang }}"
                        data-category="{{ $product->kategori_barang ?: 'Lainnya' }}"
                        data-stock="{{ $product->stok }}"
                        data-price="{{ $product->harga_jual ?? '' }}"
                    >
                        <div class="cashier-product-art" aria-hidden="true"><span>{{ mb_strtoupper(mb_substr($product->nama_barang, 0, 1)) }}</span></div>
                        <div class="cashier-product-details">
                            <h2>{{ $product->nama_barang }}</h2>
                            <p>{{ $product->id_persediaan }} · {{ $product->kategori_barang ?: 'Lainnya' }}</p>
                            <strong>
                                @if ($product->harga_jual !== null)
                                    Rp{{ number_format((float) $product->harga_jual, 0, ',', '.') }}
                                @else
                                    Harga diisi di keranjang
                                @endif
                            </strong>
                            <span class="cashier-stock">Stok: {{ number_format((int) $product->stok, 0, ',', '.') }}</span>
                        </div>
                        <button class="cashier-add-button" type="button" data-add-product aria-label="Tambah {{ $product->nama_barang }} ke keranjang">+</button>
                    </article>
                @empty
                    <div class="cashier-empty-catalog">Belum ada barang yang memiliki stok. Tambahkan persediaan atau catat pembelian terlebih dahulu.</div>
                @endforelse
            </div>
        </section>

        <aside class="cashier-cart" aria-label="Keranjang transaksi">
            <div class="cashier-cart-heading">
                <h2>Keranjang</h2>
                <span id="cashier-cart-count">0</span>
                <button class="cashier-clear-button" type="button" id="cashier-clear" aria-label="Kosongkan keranjang">Kosongkan</button>
            </div>
            <form method="POST" action="{{ route('employee.penjualan.store') }}" id="cashier-checkout-form">
                @csrf
                <input type="hidden" name="tanggal" value="{{ today()->toDateString() }}">
                <label class="cashier-customer-label" for="cashier-customer">Pelanggan</label>
                <select id="cashier-customer" name="id_pelanggan" required>
                    <option value="">Pilih pelanggan</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id_pelanggan }}" @selected(old('id_pelanggan') === $customer->id_pelanggan)>
                            {{ $customer->nama_pelanggan }}
                        </option>
                    @endforeach
                </select>

                <div class="cashier-cart-lines" id="cashier-cart-lines" aria-live="polite"></div>
                <p class="cashier-cart-empty" id="cashier-cart-empty">
                    <span aria-hidden="true">🛒</span>
                    <strong>Keranjang masih kosong</strong>
                    <span>Pilih barang untuk memulai transaksi baru.</span>
                </p>
                <div class="cashier-cart-footer">
                    <div class="cashier-subtotal"><span>Subtotal</span><strong id="cashier-subtotal">Rp0</strong></div>
                    <div class="cashier-total"><span>Total</span><strong id="cashier-total">Rp0</strong></div>
                    <button class="cashier-checkout-button" id="cashier-checkout" type="submit" disabled>
                        Checkout <span aria-hidden="true">→</span>
                    </button>
                    <p class="cashier-checkout-hint" id="cashier-checkout-hint">0 barang dipilih</p>
                </div>
            </form>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const productCards = [...document.querySelectorAll('[data-product-card]')];
            const search = document.getElementById('cashier-search');
            const categories = document.getElementById('cashier-categories');
            const cartContainer = document.getElementById('cashier-cart-lines');
            const cartEmpty = document.getElementById('cashier-cart-empty');
            const cartCount = document.getElementById('cashier-cart-count');
            const customer = document.getElementById('cashier-customer');
            const checkout = document.getElementById('cashier-checkout');
            const checkoutHint = document.getElementById('cashier-checkout-hint');
            const money = (amount) => `Rp${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount || 0)}`;
            const cart = new Map();
            let activeCategory = 'Semua barang';

            function updateSummary() {
                let total = 0;
                let quantityTotal = 0;
                cart.forEach((item) => {
                    total += (Number(item.price) || 0) * item.quantity;
                    quantityTotal += item.quantity;
                });
                cartCount.textContent = quantityTotal;
                document.getElementById('cashier-subtotal').textContent = money(total);
                document.getElementById('cashier-total').textContent = money(total);
                checkoutHint.textContent = `${quantityTotal} barang dipilih`;
                checkout.disabled = cart.size === 0 || !customer.value || [...cart.values()].some((item) => !Number.isFinite(Number(item.price)) || Number(item.price) <= 0);
            }

            const categoryNames = [...new Set(productCards.map((card) => card.dataset.category))].sort((a, b) => a.localeCompare(b, 'id'));
            ['Semua barang', ...categoryNames].forEach((name) => {
                const button = document.createElement('button');
                button.className = 'cashier-category-button';
                button.type = 'button';
                button.textContent = name;
                button.setAttribute('aria-pressed', name === activeCategory ? 'true' : 'false');
                button.addEventListener('click', () => {
                    activeCategory = name;
                    categories.querySelectorAll('button').forEach((item) => item.setAttribute('aria-pressed', String(item.textContent === name)));
                    filterProducts();
                });
                categories.appendChild(button);
            });

            function filterProducts() {
                const term = search.value.trim().toLocaleLowerCase('id-ID');
                productCards.forEach((card) => {
                    const matchesSearch = `${card.dataset.name} ${card.dataset.id} ${card.dataset.category}`.toLocaleLowerCase('id-ID').includes(term);
                    const matchesCategory = activeCategory === 'Semua barang' || card.dataset.category === activeCategory;
                    card.hidden = !matchesSearch || !matchesCategory;
                });
            }

            function renderCart() {
                cartContainer.replaceChildren();

                cart.forEach((item, id) => {
                    const lineTotal = (Number(item.price) || 0) * item.quantity;

                    const row = document.createElement('div');
                    row.className = 'cashier-cart-line';
                    row.innerHTML = `
                        <div class="cashier-line-heading">
                            <strong></strong>
                            <button type="button" data-remove-item aria-label="Hapus barang dari keranjang">×</button>
                        </div>
                        <label class="cashier-price-label">Harga satuan
                            <span><span>Rp</span><input type="number" min="0.01" step="0.01" inputmode="decimal" data-price></span>
                        </label>
                        <div class="cashier-line-bottom">
                            <div class="cashier-quantity">
                                <button type="button" data-decrease aria-label="Kurangi jumlah">−</button>
                                <span></span>
                                <button type="button" data-increase aria-label="Tambah jumlah">+</button>
                            </div>
                            <strong data-line-total></strong>
                        </div>`;
                    row.dataset.id = id;
                    row.querySelector('.cashier-line-heading strong').textContent = item.name;
                    row.querySelector('[data-price]').value = item.price;
                    row.querySelector('.cashier-quantity span').textContent = item.quantity;
                    row.querySelector('[data-line-total]').textContent = money(lineTotal);
                    cartContainer.appendChild(row);
                });

                cartEmpty.hidden = cart.size > 0;
                updateSummary();
            }

            productCards.forEach((card) => {
                card.querySelector('[data-add-product]').addEventListener('click', () => {
                    const id = card.dataset.id;
                    const current = cart.get(id);
                    if (current) {
                        if (current.quantity >= Number(card.dataset.stock)) return;
                        current.quantity += 1;
                    } else {
                        cart.set(id, {
                            name: card.dataset.name,
                            price: card.dataset.price,
                            quantity: 1,
                            stock: Number(card.dataset.stock),
                        });
                    }
                    renderCart();
                });
            });

            cartContainer.addEventListener('input', (event) => {
                if (!event.target.matches('[data-price]')) return;
                const row = event.target.closest('.cashier-cart-line');
                const item = cart.get(row.dataset.id);
                item.price = event.target.value;
                row.querySelector('[data-line-total]').textContent = money((Number(item.price) || 0) * item.quantity);
                updateSummary();
            });

            cartContainer.addEventListener('click', (event) => {
                const row = event.target.closest('.cashier-cart-line');
                if (!row) return;
                const item = cart.get(row.dataset.id);
                if (event.target.closest('[data-remove-item]')) {
                    cart.delete(row.dataset.id);
                } else if (event.target.closest('[data-decrease]')) {
                    item.quantity -= 1;
                    if (item.quantity < 1) cart.delete(row.dataset.id);
                } else if (event.target.closest('[data-increase]')) {
                    if (item.quantity < item.stock) item.quantity += 1;
                } else {
                    return;
                }
                renderCart();
            });

            document.getElementById('cashier-clear').addEventListener('click', () => {
                cart.clear();
                renderCart();
            });
            search.addEventListener('input', filterProducts);
            customer.addEventListener('change', renderCart);
            document.addEventListener('keydown', (event) => {
                if (event.key === '/' && !['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                    event.preventDefault();
                    search.focus();
                }
            });

            document.getElementById('cashier-checkout-form').addEventListener('submit', (event) => {
                if (checkout.disabled) {
                    event.preventDefault();
                    return;
                }
                cartContainer.querySelectorAll('[data-cashier-hidden]').forEach((input) => input.remove());
                [...cart.entries()].forEach(([id, item], index) => {
                    [['id_persediaan', id], ['jumlah_barang', item.quantity], ['harga_satuan', item.price]].forEach(([field, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${index}][${field}]`;
                        input.value = value;
                        input.dataset.cashierHidden = '';
                        cartContainer.appendChild(input);
                    });
                });
            });

            @if (old('items'))
                const oldItems = @json(old('items'));
                oldItems.forEach((item) => {
                    const card = productCards.find((product) => product.dataset.id === item.id_persediaan);
                    const quantity = Number(item.jumlah_barang);
                    if (!card || !Number.isInteger(quantity) || quantity < 1) return;
                    cart.set(card.dataset.id, {
                        name: card.dataset.name,
                        price: item.harga_satuan || '',
                        quantity: Math.min(quantity, Number(card.dataset.stock)),
                        stock: Number(card.dataset.stock),
                    });
                });
            @endif
            renderCart();
        });
    </script>
@endpush
