<?php

namespace App\Http\Controllers;

use App\Models\Persediaan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('kategori', ''));
        $sort = (string) $request->query('sort', 'latest');

        $productsQuery = Persediaan::query()
            ->whereNotNull('harga_jual')
            ->where('harga_jual', '>', 0);

        if ($search !== '') {
            $productsQuery->where(
                'nama_barang',
                'like',
                '%' . $search . '%'
            );
        }

        if ($category !== '') {
            $productsQuery->where('kategori_barang', $category);
        }

        switch ($sort) {
            case 'price_asc':
                $productsQuery->orderBy('harga_jual', 'asc');
                break;

            case 'price_desc':
                $productsQuery->orderBy('harga_jual', 'desc');
                break;

            case 'name':
            default:
                $productsQuery->orderBy('nama_barang', 'asc');
                break;
        }

        $products = $productsQuery
            ->paginate(12)
            ->withQueryString();

        $categories = Persediaan::query()
            ->whereNotNull('harga_jual')
            ->where('harga_jual', '>', 0)
            ->whereNotNull('kategori_barang')
            ->where('kategori_barang', '<>', '')
            ->distinct()
            ->orderBy('kategori_barang')
            ->pluck('kategori_barang');

        return view('customer.products.index', compact(
            'products',
            'categories',
            'search',
            'category',
            'sort'
        ));
    }

    public function show(string $id_persediaan): View
    {
        $product = Persediaan::query()
            ->where('id_persediaan', $id_persediaan)
            ->firstOrFail();

        abort_unless(
            $product->harga_jual !== null
            && (float) $product->harga_jual > 0,
            404
        );

        return view('customer.products.show', compact('product'));
    }
}
