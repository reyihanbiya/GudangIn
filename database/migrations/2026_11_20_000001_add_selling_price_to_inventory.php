<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('persediaan')) {
            return;
        }

        if (! Schema::hasColumn('persediaan', 'harga_jual')) {
            Schema::table('persediaan', function (Blueprint $table): void {
                $table->decimal('harga_jual', 15, 2)->nullable();
            });
        }

        if (
            Schema::hasTable('detail_penjualan')
            && Schema::hasTable('penjualan')
            && Schema::hasColumn('detail_penjualan', 'id_detail')
        ) {
            DB::statement(
                'UPDATE persediaan
                SET harga_jual = (
                    SELECT detail_penjualan.harga_satuan
                    FROM detail_penjualan
                    INNER JOIN penjualan
                        ON penjualan.id_penjualan = detail_penjualan.id_penjualan
                    WHERE detail_penjualan.id_persediaan = persediaan.id_persediaan
                    ORDER BY penjualan.tanggal DESC, detail_penjualan.id_detail DESC
                    LIMIT 1
                )
                WHERE harga_jual IS NULL
                AND EXISTS (
                    SELECT 1
                    FROM detail_penjualan
                    WHERE detail_penjualan.id_persediaan = persediaan.id_persediaan
                )'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('persediaan') && Schema::hasColumn('persediaan', 'harga_jual')) {
            Schema::table('persediaan', function (Blueprint $table): void {
                $table->dropColumn('harga_jual');
            });
        }
    }
};
