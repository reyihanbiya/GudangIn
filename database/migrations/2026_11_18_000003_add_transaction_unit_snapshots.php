<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['detail_penjualan', 'detail_pembelian'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'satuan')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('satuan', 20)->default('unit');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'satuan')) {
                continue;
            }

            if (DB::table($tableName)->where('satuan', '<>', 'unit')->exists()) {
                throw new \RuntimeException(
                    "Satuan transaksi pada {$tableName} sudah digunakan dan tidak dapat dihapus."
                );
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('satuan');
            });
        }
    }
};
