<?php

namespace Database\Seeders;

use App\Models\DetailPembelian;
use App\Models\DetailPenjualan;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Persediaan;
use App\Models\Supplier;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BuildingSupplyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $employeeId = DB::table('pegawai')->orderBy('id_pegawai')->value('id_pegawai');
        if (! $employeeId) {
            throw new RuntimeException('Buat akun pegawai terlebih dahulu sebelum menambahkan data contoh toko bangunan.');
        }

        $suppliers = [
            ['CV Mitra Bangun Jaya', 'Jl. Raya Industri No. 18, Bekasi', '0812-3456-7890'],
            ['PT Semen Nusantara', 'Jl. Gatot Subroto KM 7, Jakarta', '0813-5566-7788'],
            ['UD Baja Karya Abadi', 'Jl. Raya Cileungsi No. 42, Bogor', '0821-3344-5566'],
            ['Toko Material Sejahtera', 'Jl. Ahmad Yani No. 88, Bandung', '0857-8899-1122'],
            ['CV Catindo Prima', 'Jl. Veteran No. 21, Tangerang', '0815-6677-2233'],
            ['UD Kayu Lestari', 'Jl. Raya Bogor KM 25, Depok', '0878-1234-5678'],
        ];

        foreach ($suppliers as [$name, $address, $phone]) {
            if (Supplier::query()->where('nama_supplier', $name)->exists()) {
                continue;
            }

            Supplier::query()->create([
                'id_supplier' => CodeGeneratorService::generate('supplier'),
                'nama_supplier' => $name,
                'alamat' => $address,
                'no_hp' => $phone,
            ]);
        }

        $customers = [
            ['Budi Santoso', 'Jl. Mawar No. 12, Bekasi', '0812-1000-2001'],
            ['CV Konstruksi Mandiri', 'Jl. Raya Narogong No. 55, Bekasi', '0813-2000-3002'],
            ['Pak Agus Wijaya', 'Perumahan Bumi Sentosa Blok C2, Cikarang', '0821-3000-4003'],
            ['Siti Rahmawati', 'Jl. Melati Barat No. 8, Tambun', '0857-4000-5004'],
            ['PT Bangun Prima', 'Jl. Industri Raya Kav. 14, Cikarang', '0815-5000-6005'],
        ];

        foreach ($customers as [$name, $address, $phone]) {
            if (Pelanggan::query()->where('nama_pelanggan', $name)->exists()) {
                continue;
            }

            Pelanggan::query()->create([
                'id_pelanggan' => CodeGeneratorService::generate('pelanggan'),
                'nama_pelanggan' => $name,
                'alamat' => $address,
                'no_hp' => $phone,
            ]);
        }

        $this->seedPurchases((string) $employeeId);
        $this->seedSales((string) $employeeId);
    }

    private function seedPurchases(string $employeeId): void
    {
        $suppliers = Supplier::query()->orderBy('nama_supplier')->get();
        if ($suppliers->count() === 0) {
            return;
        }

        $purchaseItems = [
            ['2026-11-18', 'Semen Gresik 50 kg', 20, 73000, 'Diterima'],
            ['2026-11-19', 'Besi beton 10 mm', 30, 78000, 'Diterima'],
            ['2026-11-20', 'Keramik 60 x 60 cm', 15, 118000, 'Diterima'],
            ['2026-11-21', 'Cat tembok interior 5 kg', 12, 128000, 'Diterima'],
            ['2026-11-22', 'Pipa PVC 3/4 inci', 25, 29500, 'Diproses'],
            ['2026-11-23', 'Pasir pasang', 5, 300000, 'Diterima'],
        ];

        foreach ($purchaseItems as $index => [$date, $productName, $quantity, $purchasePrice, $status]) {
            $product = Persediaan::query()->where('nama_barang', $productName)->first();
            if (! $product) {
                continue;
            }

            $idPembelian = 'PB'.substr(str_replace('-', '', $date), 2, 4).str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            if (Pembelian::query()->where('id_pembelian', $idPembelian)->exists()) {
                continue;
            }

            $supplier = $suppliers[$index % $suppliers->count()];
            DB::transaction(function () use ($idPembelian, $supplier, $employeeId, $date, $status, $product, $quantity, $purchasePrice): void {
                $purchase = Pembelian::query()->create([
                    'id_pembelian' => $idPembelian,
                    'id_supplier' => $supplier->id_supplier,
                    'id_pegawai' => $employeeId,
                    'tanggal' => $date,
                    'status_pengiriman' => $status,
                ]);

                DetailPembelian::query()->create([
                    'id_pembelian' => $purchase->id_pembelian,
                    'id_persediaan' => $product->id_persediaan,
                    'satuan' => $product->satuan,
                    'harga_beli' => $purchasePrice,
                    'jumlah_barang' => $quantity,
                ]);

                if ($status === 'Diterima') {
                    $product->increment('stok', $quantity);
                }
            });
        }
    }

    private function seedSales(string $employeeId): void
    {
        $customers = Pelanggan::query()->orderBy('nama_pelanggan')->get();
        if ($customers->count() === 0) {
            return;
        }

        $sales = [
            ['2026-11-20', 'Semen Gresik 50 kg', 10],
            ['2026-11-21', 'Bata merah', 500],
            ['2026-11-22', 'Besi beton 10 mm', 8],
            ['2026-11-23', 'Keramik 60 x 60 cm', 6],
            ['2026-11-24', 'Cat tembok interior 5 kg', 3],
            ['2026-11-24', 'Pipa PVC 3/4 inci', 10],
        ];

        foreach ($sales as $index => [$date, $productName, $quantity]) {
            $product = Persediaan::query()->where('nama_barang', $productName)->first();
            if (! $product || (float) $product->stok < (float) $quantity) {
                continue;
            }

            $idPenjualan = 'PJ'.substr(str_replace('-', '', $date), 2, 4).str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            if (Penjualan::query()->where('id_penjualan', $idPenjualan)->exists()) {
                continue;
            }

            $customer = $customers[$index % $customers->count()];
            DB::transaction(function () use ($idPenjualan, $customer, $employeeId, $date, $product, $quantity): void {
                $sale = Penjualan::query()->create([
                    'id_penjualan' => $idPenjualan,
                    'id_pelanggan' => $customer->id_pelanggan,
                    'id_pegawai' => $employeeId,
                    'tanggal' => $date,
                ]);

                DetailPenjualan::query()->create([
                    'id_penjualan' => $sale->id_penjualan,
                    'id_persediaan' => $product->id_persediaan,
                    'satuan' => $product->satuan,
                    'harga_satuan' => $product->harga_jual,
                    'jumlah_barang' => $quantity,
                ]);

                $product->decrement('stok', $quantity);
            });
        }
    }
}
