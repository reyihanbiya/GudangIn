<?php

namespace App\Http\Controllers;

use App\Models\Persediaan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = session()->get('customer_cart', []);

        $products = Persediaan::query()
            ->whereIn('id_persediaan', array_keys($cart))
            ->get()
            ->keyBy('id_persediaan');

        $items = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $products->get($id);

            if (!$product) {
                continue;
            }

            $subtotal = (float) $product->harga_jual
                * (float) $quantity;

            $total += $subtotal;

            $items[] = [
                'product' => $product,
                'quantity' => (float) $quantity,
                'subtotal' => $subtotal,
            ];
        }

        return view('customer.cart.index', compact(
            'items',
            'total'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_persediaan' => ['required', 'string'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
        ]);

        $product = Persediaan::query()
            ->where('id_persediaan', $validated['id_persediaan'])
            ->whereNotNull('harga_jual')
            ->where('harga_jual', '>', 0)
            ->firstOrFail();

        $quantity = (float) $validated['jumlah'];

        if ($quantity > (float) $product->stok) {
            return back()->with(
                'error',
                'Jumlah melebihi stok yang tersedia.'
            );
        }

        $cart = session()->get('customer_cart', []);

        $oldQuantity = (float) (
            $cart[$product->id_persediaan] ?? 0
        );

        $newQuantity = $oldQuantity + $quantity;

        if ($newQuantity > (float) $product->stok) {
            return back()->with(
                'error',
                'Total jumlah di keranjang melebihi stok tersedia.'
            );
        }

        $cart[$product->id_persediaan] = $newQuantity;

        session()->put('customer_cart', $cart);

        return redirect()
            ->route('customer.cart.index')
            ->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(
        Request $request,
        string $id_persediaan
    ): RedirectResponse {
        $validated = $request->validate([
            'jumlah' => ['required', 'numeric', 'gt:0'],
        ]);

        $product = Persediaan::query()
            ->where('id_persediaan', $id_persediaan)
            ->firstOrFail();

        $quantity = (float) $validated['jumlah'];

        if ($quantity > (float) $product->stok) {
            return back()->with(
                'error',
                'Jumlah melebihi stok yang tersedia.'
            );
        }

        $cart = session()->get('customer_cart', []);

        if (!array_key_exists($id_persediaan, $cart)) {
            return back()->with(
                'error',
                'Produk tidak ditemukan di keranjang.'
            );
        }

        $cart[$id_persediaan] = $quantity;

        session()->put('customer_cart', $cart);

        return back()->with(
            'success',
            'Jumlah produk berhasil diperbarui.'
        );
    }

    public function remove(string $id_persediaan): RedirectResponse
    {
        $cart = session()->get('customer_cart', []);

        unset($cart[$id_persediaan]);

        session()->put('customer_cart', $cart);

        return back()->with(
            'success',
            'Produk berhasil dihapus dari keranjang.'
        );
    }

    public function clear(): RedirectResponse
    {
        session()->forget('customer_cart');

        return redirect()
            ->route('customer.cart.index')
            ->with('success', 'Keranjang berhasil dikosongkan.');
    }
}
