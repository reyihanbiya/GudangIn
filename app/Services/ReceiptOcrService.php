<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ReceiptOcrService
{
    public function extract(string $imagePath): string
    {
        $binary = (string) config('services.tesseract.binary', 'tesseract');
        $languages = (string) config('services.tesseract.languages', 'eng+ind');
        $timeout = (int) config('services.tesseract.timeout', 60);
        $process = new Process([
            $binary,
            $imagePath,
            'stdout',
            '-l',
            $languages,
            '--psm',
            '6',
        ]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (ProcessStartFailedException $exception) {
            Log::warning('Tesseract OCR could not be started.', ['binary' => $binary]);

            throw new ReceiptOcrException(
                'Mesin OCR belum tersedia. Instal Tesseract OCR dan pastikan lokasinya ada di PATH atau TESSERACT_BINARY.',
                previous: $exception
            );
        } catch (ProcessTimedOutException $exception) {
            Log::warning('Tesseract OCR timed out.', ['timeout' => $timeout]);

            throw new ReceiptOcrException(
                'Proses OCR melewati batas waktu. Coba gambar nota dengan ukuran lebih kecil atau lebih jelas.',
                previous: $exception
            );
        }

        if (! $process->isSuccessful()) {
            Log::warning('Tesseract OCR failed.', [
                'exit_code' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new ReceiptOcrException(
                'Tesseract OCR gagal membaca nota. Pastikan Tesseract dapat dijalankan dan bahasa yang dikonfigurasi sudah terpasang.'
            );
        }

        $text = trim($process->getOutput());

        if ($text === '') {
            throw new ReceiptOcrException('Tidak ada teks yang terbaca. Coba unggah gambar nota yang lebih jelas.');
        }

        return $text;
    }
}
