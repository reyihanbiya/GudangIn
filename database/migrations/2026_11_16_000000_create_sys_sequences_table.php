<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sys_sequences')) {
            Schema::create('sys_sequences', function (Blueprint $table) {
                $table->string('sequence_key', 64)->primary();
                $table->unsignedInteger('current_value')->default(0);
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_sequences');
    }
};
