<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembelian')) {
            return;
        }

        Schema::table('pembelian', function (Blueprint $table): void {
            if (! Schema::hasColumn('pembelian', 'metode_pembayaran')) {
                $table->string('metode_pembayaran', 20)->default('Tunai');
            }

            if (! Schema::hasColumn('pembelian', 'tanggal_jatuh_tempo')) {
                $table->date('tanggal_jatuh_tempo')->nullable();
            }

            if (! Schema::hasColumn('pembelian', 'tanggal_pembayaran')) {
                $table->date('tanggal_pembayaran')->nullable();
            }
        });

    }

    public function down(): void
    {
        if (! Schema::hasTable('pembelian')) {
            return;
        }

        foreach (['tanggal_pembayaran', 'tanggal_jatuh_tempo', 'metode_pembayaran'] as $column) {
            if (! Schema::hasColumn('pembelian', $column)) {
                continue;
            }

            Schema::table('pembelian', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }
};
