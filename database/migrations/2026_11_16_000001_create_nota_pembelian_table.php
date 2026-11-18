<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! Schema::hasTable('nota_pembelian')) {
            Schema::create('nota_pembelian', function (Blueprint $table) use ($driver): void {
                $table->bigIncrements('id_nota');
                $idPembelian = $table->string('id_pembelian', 10);

                if ($driver === 'mysql') {
                    $idPembelian->collation('utf8mb4_general_ci');
                }

                $idPembelian->unique();
                $table->string('file_path', 255)->unique();
                $table->longText('hasil_ocr');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('pembelian')) {
            return;
        }

        $foreignKeyExists = false;

        if ($driver === 'mysql') {
            DB::statement(
                'ALTER TABLE nota_pembelian MODIFY id_pembelian VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL'
            );

            $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
                ->where('TABLE_NAME', 'nota_pembelian')
                ->where('COLUMN_NAME', 'id_pembelian')
                ->where('REFERENCED_TABLE_NAME', 'pembelian')
                ->exists();
        }

        if (! $foreignKeyExists) {
            Schema::table('nota_pembelian', function (Blueprint $table): void {
                $table->foreign('id_pembelian')
                    ->references('id_pembelian')
                    ->on('pembelian')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_pembelian');
    }
};
