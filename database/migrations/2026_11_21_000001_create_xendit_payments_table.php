<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('xendit_payments')) {
            return;
        }

        Schema::create('xendit_payments', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('reference_id')->unique();
            $table->string('payment_request_id')->unique();
            $table->string('payment_id')->nullable();
            $table->string('id_pelanggan', 10);
            $table->string('id_pegawai', 10);
            $table->string('id_penjualan', 10)->nullable();
            $table->date('tanggal');
            $table->decimal('amount', 15, 2);
            $table->json('items');
            $table->text('qr_string');
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['id_pegawai', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xendit_payments');
    }
};
