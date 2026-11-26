<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_pembelian', function (Blueprint $table): void {
            $table->bigIncrements('id_penerimaan');
            $table->string('id_pembelian', 10)->index();
            $table->string('id_pegawai', 10);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_penerimaan_pembelian', function (Blueprint $table): void {
            $table->bigIncrements('id_detail_penerimaan');
            $table->unsignedBigInteger('id_penerimaan')->index();
            $table->unsignedInteger('id_detail_pembelian')->index();
            $table->decimal('jumlah_datang', 15, 3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penerimaan_pembelian');
        Schema::dropIfExists('penerimaan_pembelian');
    }
};
