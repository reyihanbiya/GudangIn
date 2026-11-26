<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pelanggan')) {
            Schema::table('pelanggan', function (Blueprint $table): void {
                if (! Schema::hasColumn('pelanggan', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable();
                }
                if (! Schema::hasColumn('pelanggan', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable();
                }
            });
        }

        if (Schema::hasTable('penjualan')) {
            Schema::table('penjualan', function (Blueprint $table): void {
                if (! Schema::hasColumn('penjualan', 'jenis_pengiriman')) {
                    $table->string('jenis_pengiriman', 20)->default('pengiriman');
                }
                if (! Schema::hasColumn('penjualan', 'status_pengiriman')) {
                    $table->string('status_pengiriman', 30)->default('SELESAI');
                }
                if (! Schema::hasColumn('penjualan', 'status_pembayaran')) {
                    $table->string('status_pembayaran', 30)->default('TERBAYAR');
                }
                if (! Schema::hasColumn('penjualan', 'metode_pembayaran')) {
                    $table->string('metode_pembayaran', 30)->nullable();
                }
                if (! Schema::hasColumn('penjualan', 'alamat_pengiriman')) {
                    $table->string('alamat_pengiriman', 500)->nullable();
                }
                if (! Schema::hasColumn('penjualan', 'nama_penerima')) {
                    $table->string('nama_penerima', 100)->nullable();
                }
                if (! Schema::hasColumn('penjualan', 'telepon_penerima')) {
                    $table->string('telepon_penerima', 20)->nullable();
                }
                if (! Schema::hasColumn('penjualan', 'latitude_pengiriman')) {
                    $table->decimal('latitude_pengiriman', 10, 7)->nullable();
                }
                if (! Schema::hasColumn('penjualan', 'longitude_pengiriman')) {
                    $table->decimal('longitude_pengiriman', 10, 7)->nullable();
                }
                if (! Schema::hasColumn('penjualan', 'biaya_pengiriman')) {
                    $table->decimal('biaya_pengiriman', 15, 2)->default(0);
                }
                if (! Schema::hasColumn('penjualan', 'id_kurir')) {
                    $table->string('id_kurir', 10)->nullable()->index();
                }
            });
        }

        if (! Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('id_penjualan', 10)->index();
                $table->string('method', 30);
                $table->string('provider', 30)->nullable();
                $table->string('reference_id', 50)->unique();
                $table->string('provider_order_id', 50)->nullable()->unique();
                $table->string('provider_transaction_id', 100)->nullable();
                $table->text('checkout_url')->nullable();
                $table->decimal('amount', 15, 2);
                $table->string('status', 30)->default('PENDING');
                $table->string('proof_path')->nullable();
                $table->json('metadata')->nullable();
                $table->string('verified_by', 10)->nullable();
                $table->string('verified_by_type', 20)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('order_events')) {
            Schema::create('order_events', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('id_penjualan', 10)->index();
                $table->unsignedBigInteger('customer_payment_id')->nullable()->index();
                $table->string('event_type', 30);
                $table->string('status', 30)->nullable();
                $table->string('message', 255);
                $table->string('actor_type', 20)->nullable();
                $table->string('actor_id', 10)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('courier_locations')) {
            Schema::create('courier_locations', function (Blueprint $table): void {
                $table->string('id_penjualan', 10)->primary();
                $table->string('id_kurir', 10);
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->timestamp('recorded_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_locations');
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('customer_payments');

        if (Schema::hasTable('penjualan')) {
            $columns = [
                'id_kurir',
                'longitude_pengiriman',
                'latitude_pengiriman',
                'telepon_penerima',
                'nama_penerima',
                'alamat_pengiriman',
                'biaya_pengiriman',
                'metode_pembayaran',
                'status_pembayaran',
                'status_pengiriman',
                'jenis_pengiriman',
            ];
            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('penjualan', $column)
            ));

            if ($existing !== []) {
                Schema::table('penjualan', function (Blueprint $table) use ($existing): void {
                    $table->dropColumn($existing);
                });
            }
        }

        if (Schema::hasTable('pelanggan')) {
            $columns = array_values(array_filter(
                ['latitude', 'longitude'],
                fn (string $column): bool => Schema::hasColumn('pelanggan', $column)
            ));

            if ($columns !== []) {
                Schema::table('pelanggan', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
