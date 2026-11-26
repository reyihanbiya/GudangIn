<?php

use App\Services\CodeGeneratorService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('penjualan')) {
            return;
        }

        if (! Schema::hasColumn('penjualan', 'id_pengiriman')) {
            Schema::table('penjualan', function (Blueprint $table): void {
                $table->string('id_pengiriman', 10)->nullable()->unique();
            });
        }

        $transactionIds = DB::table('penjualan')
            ->whereNull('id_pengiriman')
            ->orderBy('tanggal')
            ->orderBy('id_penjualan')
            ->pluck('id_penjualan');

        foreach ($transactionIds as $transactionId) {
            DB::table('penjualan')
                ->where('id_penjualan', $transactionId)
                ->update([
                    'id_pengiriman' => CodeGeneratorService::generate('pengiriman'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('penjualan') || ! Schema::hasColumn('penjualan', 'id_pengiriman')) {
            return;
        }

        Schema::table('penjualan', function (Blueprint $table): void {
            $table->dropUnique(['id_pengiriman']);
            $table->dropColumn('id_pengiriman');
        });
    }
};
