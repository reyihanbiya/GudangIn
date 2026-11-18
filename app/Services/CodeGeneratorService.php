<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use OverflowException;
use RuntimeException;

class CodeGeneratorService
{
    /**
     * Menghasilkan ID unik terurut untuk entitas tertentu.
     * 
     * @param string $entityType Jenis entitas (penjualan, pembelian, pegawai, owner, pelanggan, supplier, persediaan)
     * @param string|null $customPeriod Periode transaksi format YYMM (opsional, default: bulan berjalan date('ym'))
     * @return string ID terformat maksimal 10 karakter
     * @throws InvalidArgumentException Jika entitas tidak dikenal atau periode YYMM tidak valid
     * @throws OverflowException Jika counter melampaui batas 9999
     */
    public static function generate(string $entityType, ?string $customPeriod = null): string
    {
        return DB::transaction(function () use ($entityType, $customPeriod) {
            $config = self::resolveEntityConfig($entityType, $customPeriod);
            $sequenceKey = $config['sequence_key'];
            $prefix = $config['prefix'];
            $digitLength = $config['digit_length'];

            DB::table('sys_sequences')->insertOrIgnore([
                'sequence_key' => $sequenceKey,
                'current_value' => 0,
                'updated_at' => now(),
            ]);

            // 2. FASE 2: EXCLUSIVE ROW LOCK (Mengunci baris pasti yang sudah ada)
            $seq = DB::table('sys_sequences')
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();

            $currentVal = (int) $seq->current_value;

            // Jika baris baru diinisialisasi (current_value == 0), sinkronkan dengan max tabel target
            if ($currentVal === 0) {
                $maxExisting = self::detectMaxExistingNumber($config);
                $currentVal = $maxExisting;
            }

            // Kenaikan nilai terurut
            $nextNumber = $currentVal + 1;

            if ($nextNumber > 9999 && $digitLength === 4) {
                throw new OverflowException(
                    "Gagal membuat ID untuk {$entityType}: Nomor urut telah melampaui batas 9999."
                );
            }

            DB::table('sys_sequences')
                ->where('sequence_key', $sequenceKey)
                ->update([
                    'current_value' => $nextNumber,
                    'updated_at' => now(),
                ]);

            $formattedId = $prefix . str_pad((string)$nextNumber, $digitLength, '0', STR_PAD_LEFT);

            if (strlen($formattedId) > 10) {
                throw new OverflowException(
                    "Gagal membuat ID untuk {$entityType}: ID '{$formattedId}' melebihi batas 10 karakter."
                );
            }

            return $formattedId;
        });
    }

    /**
     * Reseed sequence secara monotonik (hanya naik, tidak pernah turun).
     * Aman dijalankan saat transaksi lain sedang berlangsung.
     * 
     * @param string $entityType Jenis entitas
     * @param string|null $customPeriod Periode transaksi format YYMM (opsional, default: bulan berjalan date('ym'))
     * @return array Metadata hasil reseed
     * @throws InvalidArgumentException Jika entitas tidak dikenal atau periode YYMM tidak valid
     * @throws RuntimeException Jika ditemukan data ID yang menyimpang di database
     */
    public static function reseed(string $entityType, ?string $customPeriod = null): array
    {
        return DB::transaction(function () use ($entityType, $customPeriod) {
            $config = self::resolveEntityConfig($entityType, $customPeriod);
            $sequenceKey = $config['sequence_key'];

            DB::table('sys_sequences')->insertOrIgnore([
                'sequence_key' => $sequenceKey,
                'current_value' => 0,
                'updated_at' => now(),
            ]);

            // 2. FASE 2: Kunci baris spesifik ini
            $seq = DB::table('sys_sequences')
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();

            $currentVal = (int) $seq->current_value;

            // 3. Scan nilai angka ID tertinggi dari data riil tabel dengan validasi format
            $maxFromTable = self::detectMaxExistingNumber($config);

            // 4. Aturan Monotonik: Hanya naik jika maxFromTable > currentVal
            $finalVal = max($currentVal, $maxFromTable);

            if ($finalVal > $currentVal) {
                DB::table('sys_sequences')
                    ->where('sequence_key', $sequenceKey)
                    ->update([
                        'current_value' => $finalVal,
                        'updated_at' => now(),
                    ]);
                Log::info("Reseed {$sequenceKey}: dinaikkan dari {$currentVal} ke {$finalVal}.");
            }

            return [
                'entity'       => $entityType,
                'sequence_key' => $sequenceKey,
                'old_value'    => $currentVal,
                'max_table'    => $maxFromTable,
                'final_value'  => $finalVal,
                'adjusted'     => ($finalVal > $currentVal),
            ];
        });
    }

    /**
     * Resolusi konfigurasi entitas dan validasi ketat periode YYMM pada lapisan service.
     */
    public static function resolveEntityConfig(string $entityType, ?string $customPeriod = null): array
    {
        // Null coalescing: Jika customPeriod string kosong '', tetap '' (tidak fallback ke date('ym'))
        $period = $customPeriod ?? date('ym');

        // Validasi Service Level: Wajib eksak 4 digit YYMM dengan bulan 01-12
        if (in_array($entityType, ['penjualan', 'pembelian'], true)) {
            if (!preg_match('/^[0-9]{2}(0[1-9]|1[0-2])$/', $period)) {
                throw new InvalidArgumentException(
                    "Periode '{$period}' tidak valid untuk entitas '{$entityType}'. " .
                    "Format wajib 4 digit YYMM dengan bulan 01-12 (contoh: 2611)."
                );
            }
        }

        return match ($entityType) {
            'penjualan' => [
                'table'        => 'penjualan',
                'id_column'    => 'id_penjualan',
                'prefix'       => 'PJ' . $period,
                'sequence_key' => 'penjualan_' . $period,
                'digit_length' => 4,
            ],
            'pembelian' => [
                'table'        => 'pembelian',
                'id_column'    => 'id_pembelian',
                'prefix'       => 'PB' . $period,
                'sequence_key' => 'pembelian_' . $period,
                'digit_length' => 4,
            ],
            'pegawai' => [
                'table'        => 'pegawai',
                'id_column'    => 'id_pegawai',
                'prefix'       => 'PGW',
                'sequence_key' => 'pegawai',
                'digit_length' => 4,
            ],
            'owner' => [
                'table'        => 'owner',
                'id_column'    => 'id_owner',
                'prefix'       => 'OWN',
                'sequence_key' => 'owner',
                'digit_length' => 4,
            ],
            'pelanggan' => [
                'table'        => 'pelanggan',
                'id_column'    => 'id_pelanggan',
                'prefix'       => 'PLG',
                'sequence_key' => 'pelanggan',
                'digit_length' => 4,
            ],
            'supplier' => [
                'table'        => 'supplier',
                'id_column'    => 'id_supplier',
                'prefix'       => 'SUP',
                'sequence_key' => 'supplier',
                'digit_length' => 4,
            ],
            'persediaan' => [
                'table'        => 'persediaan',
                'id_column'    => 'id_persediaan',
                'prefix'       => 'BRG',
                'sequence_key' => 'persediaan',
                'digit_length' => 4,
            ],
            default => throw new InvalidArgumentException("Entitas '{$entityType}' tidak dikenal.")
        };
    }

    /**
     * Memindai angka ID tertinggi sekaligus memvalidasi format seluruh ID yang tersimpan.
     * 
     * @throws RuntimeException Jika ditemukan ID yang tidak sah atau melampaui batas 9999
     */
    private static function detectMaxExistingNumber(array $config): int
    {
        $prefix = $config['prefix'];
        $table = $config['table'];
        $column = $config['id_column'];
        $digitLength = $config['digit_length']; // 4 digit

        $exactPattern = '/^' . preg_quote($prefix, '/') . '[0-9]{' . $digitLength . '}$/';

        $records = DB::table($table)
            ->where($column, 'LIKE', "{$prefix}%")
            ->pluck($column);

        if ($records->isEmpty()) {
            return 0;
        }

        $maxNumber = 0;
        $invalidIds = [];

        foreach ($records as $rawId) {
            $idStr = (string)$rawId;

            // Validasi format eksak dan batas 10 karakter
            if (!preg_match($exactPattern, $idStr) || strlen($idStr) > 10) {
                $invalidIds[] = $idStr;
                continue;
            }

            $numericPart = substr($idStr, strlen($prefix));
            $numVal = (int)$numericPart;

            if ($numVal < 1 || $numVal > 9999) {
                $invalidIds[] = $idStr;
                continue;
            }

            if ($numVal > $maxNumber) {
                $maxNumber = $numVal;
            }
        }

        if (!empty($invalidIds)) {
            $sample = implode(', ', array_slice($invalidIds, 0, 5));
            throw new RuntimeException(
                "Validasi Data Gagal: Ditemukan ID tidak sah atau melampaui batas 9999 pada tabel '{$table}': [{$sample}]. " .
                "Proses dihentikan demi mencegah anomali sequence database."
            );
        }

        return $maxNumber;
    }
}
