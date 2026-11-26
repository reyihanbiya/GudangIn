<?php

namespace Database\Seeders;

use App\Models\Persediaan;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BuildingSupplyInventorySeeder extends Seeder
{
    public function run(): void
    {
        $employeeId = DB::table('pegawai')->orderBy('id_pegawai')->value('id_pegawai');
        if (! $employeeId) {
            throw new RuntimeException('Buat akun pegawai terlebih dahulu sebelum menambahkan data contoh persediaan.');
        }

        $products = [
            ['Semen Gresik 50 kg', 'Semen', 'sak', 35, 78000],
            ['Mortar instan 40 kg', 'Semen', 'sak', 20, 68000],
            ['Pasir pasang', 'Material', 'm3', 8, 350000],
            ['Batu split', 'Material', 'm3', 8, 380000],
            ['Bata merah', 'Bata', 'pcs', 1000, 1000],
            ['Bata ringan AAC', 'Bata', 'pcs', 400, 10000],
            ['Besi beton 8 mm', 'Besi', 'batang', 70, 55000],
            ['Besi beton 10 mm', 'Besi', 'batang', 60, 85000],
            ['Besi beton 12 mm', 'Besi', 'batang', 40, 120000],
            ['Hollow galvanis 4 x 4 cm', 'Besi', 'batang', 30, 75000],
            ['Pipa PVC 1/2 inci', 'Pipa', 'batang', 50, 25000],
            ['Pipa PVC 3/4 inci', 'Pipa', 'batang', 40, 35000],
            ['Pipa PVC 1 inci', 'Pipa', 'batang', 30, 55000],
            ['Keramik 40 x 40 cm', 'Keramik', 'dus', 40, 85000],
            ['Keramik 60 x 60 cm', 'Keramik', 'dus', 25, 135000],
            ['Cat tembok interior 5 kg', 'Cat', 'ember', 20, 150000],
            ['Cat kayu dan besi 1 kg', 'Cat', 'kaleng', 20, 75000],
            ['Paku beton 5 cm', 'Paku', 'kg', 20, 32000],
            ['Triplek 9 mm', 'Kayu', 'lembar', 25, 180000],
            ['Kawat bendrat', 'Besi', 'kg', 30, 25000],
            ['Kuas cat 2 inci', 'Alat', 'pcs', 30, 15000],
            ['Lem pipa PVC 45 g', 'Alat', 'tube', 20, 15000],
        ];

        foreach ($products as [$name, $category, $unit, $stock, $price]) {
            if (Persediaan::query()->where('nama_barang', $name)->exists()) {
                continue;
            }

            DB::transaction(function () use ($employeeId, $name, $category, $unit, $stock, $price): void {
                Persediaan::query()->create([
                    'id_persediaan' => CodeGeneratorService::generate('persediaan'),
                    'id_pegawai' => $employeeId,
                    'nama_barang' => $name,
                    'kategori_barang' => $category,
                    'satuan' => $unit,
                    'stok' => $stock,
                    'harga_jual' => $price,
                ]);
            });
        }
    }
}
