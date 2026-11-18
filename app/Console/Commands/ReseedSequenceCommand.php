<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\CodeGeneratorService;

class ReseedSequenceCommand extends Command
{
    protected $signature = 'sequence:reseed 
                            {--entity=all : Entitas yang di-reseed (pegawai, owner, pelanggan, supplier, persediaan, penjualan, pembelian, atau all)}
                            {--period= : Periode transaksi format YYMM dengan bulan 01-12 (misal: 2611). Kosongkan untuk seluruh periode otomatis.}';

    protected $description = 'Menyinkronkan counter sys_sequences secara monotonik berdasarkan ID tertinggi pada tabel target';

    public function handle(): int
    {
        $entityInput = strtolower((string)$this->option('entity'));
        $periodInput = $this->option('period') ? trim((string)$this->option('period')) : null;

        // Validasi Ketat Parameter --period
        if ($periodInput !== null) {
            if (!preg_match('/^[0-9]{2}(0[1-9]|1[0-2])$/', $periodInput)) {
                $this->error("Parameter --period '{$periodInput}' TIDAK VALID. Format wajib 4 digit YYMM dengan bulan 01-12 (contoh: 2611).");
                return self::FAILURE;
            }
        }

        $allEntities = ['pegawai', 'owner', 'pelanggan', 'supplier', 'persediaan', 'penjualan', 'pembelian'];
        $targets = ($entityInput === 'all') ? $allEntities : [$entityInput];

        $results = [];

        $this->info("Memulai sinkronisasi sys_sequences (SOP Monotonic Safe Mode)...");

        foreach ($targets as $entity) {
            if (!in_array($entity, $allEntities, true)) {
                $this->error("Entitas '{$entity}' tidak valid. Pilihan: " . implode(', ', $allEntities));
                return self::FAILURE;
            }

            // Entitas Transaksi Berbasis Periode (penjualan & pembelian)
            if (in_array($entity, ['penjualan', 'pembelian'], true)) {
                $periodsToProcess = [];

                if ($periodInput !== null) {
                    $periodsToProcess = [$periodInput];
                } else {
                    $table = $entity;
                    $idCol = ($entity === 'penjualan') ? 'id_penjualan' : 'id_pembelian';
                    $prefix = ($entity === 'penjualan') ? 'PJ' : 'PB';

                    try {
                        $periodsToProcess = $this->detectAndValidateDistinctPeriods($table, $idCol, $prefix);
                    } catch (\Throwable $e) {
                        $this->error("Validasi ID tabel '{$table}' GAGAL: " . $e->getMessage());
                        return self::FAILURE;
                    }
                }

                foreach ($periodsToProcess as $period) {
                    try {
                        $res = CodeGeneratorService::reseed($entity, $period);
                        $results[] = $this->formatResultRow($res, $period);
                    } catch (\Throwable $e) {
                        $this->error("Gagal reseed {$entity} periode {$period}: " . $e->getMessage());
                        return self::FAILURE;
                    }
                }
            } else {
                // Entitas Master Non-Periode
                try {
                    $res = CodeGeneratorService::reseed($entity);
                    $results[] = $this->formatResultRow($res, '-');
                } catch (\Throwable $e) {
                    $this->error("Gagal reseed master {$entity}: " . $e->getMessage());
                    return self::FAILURE;
                }
            }
        }

        $this->table(
            ['Entitas', 'Periode', 'Sequence Key', 'Nilai Awal', 'Max di Tabel', 'Nilai Akhir', 'Status'],
            $results
        );

        $this->info("Sinkronisasi selesai secara aman. Nilai counter tidak pernah mengalami penurunan.");
        return self::SUCCESS;
    }

    private function detectAndValidateDistinctPeriods(string $table, string $idColumn, string $prefix): array
    {
        $rawPeriods = DB::table($table)
            ->where($idColumn, 'LIKE', "{$prefix}%")
            ->select(DB::raw("DISTINCT SUBSTRING({$idColumn}, 3, 4) AS period"))
            ->pluck('period')
            ->toArray();

        $validPeriods = [];

        foreach ($rawPeriods as $periodStr) {
            $periodStr = (string)$periodStr;

            if (!preg_match('/^[0-9]{2}(0[1-9]|1[0-2])$/', $periodStr)) {
                $sampleBadId = DB::table($table)
                    ->where($idColumn, 'LIKE', "{$prefix}{$periodStr}%")
                    ->value($idColumn);

                throw new \RuntimeException(
                    "Ditemukan periode tidak valid '{$periodStr}' pada ID '{$sampleBadId}' di tabel '{$table}'. " .
                    "Format periode harus YYMM dengan bulan 01-12."
                );
            }

            $validPeriods[] = $periodStr;
        }

        // Selalu sertakan periode bulan aktif saat ini
        $currentMonthPeriod = date('ym');
        if (!in_array($currentMonthPeriod, $validPeriods, true)) {
            $validPeriods[] = $currentMonthPeriod;
        }

        sort($validPeriods);
        return array_values(array_unique($validPeriods));
    }

    private function formatResultRow(array $res, string $period): array
    {
        return [
            'Entitas'       => $res['entity'],
            'Periode'       => $period,
            'Sequence Key'  => $res['sequence_key'],
            'Nilai Awal'    => $res['old_value'],
            'Max di Tabel'  => $res['max_table'],
            'Nilai Akhir'   => $res['final_value'],
            'Status'        => $res['adjusted'] ? '<comment>DISINKRONKAN (NAIK)</comment>' : '<info>SUDAH SINKRON</info>',
        ];
    }
}
