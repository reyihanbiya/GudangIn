<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('persediaan') || Schema::hasColumn('persediaan', 'stok_minimum')) {
            return;
        }

        Schema::table('persediaan', function (Blueprint $table): void {
            $table->decimal('stok_minimum', 15, 3)->default(0);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('persediaan') && Schema::hasColumn('persediaan', 'stok_minimum')) {
            Schema::table('persediaan', function (Blueprint $table): void {
                $table->dropColumn('stok_minimum');
            });
        }
    }
};
