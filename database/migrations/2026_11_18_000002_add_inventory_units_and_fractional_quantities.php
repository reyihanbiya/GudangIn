<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const QUANTITY_COLUMNS = [
        'detail_penjualan' => ['harga_satuan', 'subtotal'],
        'detail_pembelian' => ['harga_beli', 'subtotal'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('persediaan')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new \RuntimeException('Migrasi satuan persediaan hanya mendukung MySQL/MariaDB.');
        }

        if (! Schema::hasColumn('persediaan', 'satuan')) {
            Schema::table('persediaan', function (Blueprint $table): void {
                $table->string('satuan', 20)->default('unit');
            });
        }

        DB::statement('ALTER TABLE persediaan MODIFY stok DECIMAL(15,3) NOT NULL DEFAULT 0');

        foreach (self::QUANTITY_COLUMNS as $table => [$priceColumn, $subtotalColumn]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'jumlah_barang')) {
                continue;
            }

            if (Schema::hasColumn($table, $subtotalColumn)) {
                DB::statement(
                    "ALTER TABLE {$table}
                    DROP COLUMN {$subtotalColumn},
                    MODIFY jumlah_barang DECIMAL(15,3) NOT NULL,
                    ADD COLUMN {$subtotalColumn} DECIMAL(15,2)
                    GENERATED ALWAYS AS ({$priceColumn} * jumlah_barang) STORED"
                );
            } else {
                DB::statement("ALTER TABLE {$table} MODIFY jumlah_barang DECIMAL(15,3) NOT NULL");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('persediaan') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $hasCustomUnits = DB::table('persediaan')->where('satuan', '<>', 'unit')->exists();
        $hasFractionalStock = DB::table('persediaan')->whereRaw('stok <> FLOOR(stok)')->exists();

        if ($hasCustomUnits || $hasFractionalStock) {
            throw new \RuntimeException(
                'Migrasi satuan tidak dapat dibatalkan karena satuan khusus atau stok pecahan sudah digunakan.'
            );
        }

        foreach (self::QUANTITY_COLUMNS as $table => [$priceColumn, $subtotalColumn]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'jumlah_barang')) {
                continue;
            }

            if (DB::table($table)->whereRaw('jumlah_barang <> FLOOR(jumlah_barang)')->exists()) {
                throw new \RuntimeException(
                    "Migrasi satuan tidak dapat dibatalkan karena {$table} memiliki jumlah pecahan."
                );
            }

            if (Schema::hasColumn($table, $subtotalColumn)) {
                DB::statement(
                    "ALTER TABLE {$table}
                    DROP COLUMN {$subtotalColumn},
                    MODIFY jumlah_barang INT(11) NOT NULL,
                    ADD COLUMN {$subtotalColumn} DECIMAL(15,2)
                    GENERATED ALWAYS AS ({$priceColumn} * jumlah_barang) STORED"
                );
            } else {
                DB::statement("ALTER TABLE {$table} MODIFY jumlah_barang INT(11) NOT NULL");
            }

        }

        DB::statement('ALTER TABLE persediaan MODIFY stok INT(11) NOT NULL DEFAULT 0');
        Schema::table('persediaan', function (Blueprint $table): void {
            $table->dropColumn('satuan');
        });
    }
};
