<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ReceiptOcrService
{
    public function extract(string $imagePath): string
    {
        $geminiKey = trim((string) config('services.gemini.api_key', ''));

        if ($geminiKey !== '') {
            try {
                return $this->extractWithGemini($imagePath, $geminiKey);
            } catch (ReceiptOcrException $exception) {
                Log::warning('Gemini receipt OCR failed.', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $this->extractWithTesseract($imagePath);
    }

    private function extractWithGemini(string $imagePath, string $apiKey): string
    {
        if (! is_readable($imagePath)) {
            throw new ReceiptOcrException('File nota tidak dapat dibaca.');
        }

        $bytes = file_get_contents($imagePath);
        if ($bytes === false) {
            throw new ReceiptOcrException('File nota tidak dapat dibaca.');
        }

        $mimeType = mime_content_type($imagePath);
        $mimeType = is_string($mimeType) ? $mimeType : 'image/jpeg';
        $timeout = (int) config('services.gemini.timeout', 60);
        $configuredModel = trim((string) config('services.gemini.model', 'gemini-3.5-flash-lite'));
        $models = array_values(array_unique(array_filter([
            $configuredModel,
            'gemini-3.5-flash-lite',
            'gemini-3.8-flash',
            'gemini-flash-lite-latest',
        ])));

        $prompt = <<<'PROMPT'
Baca seluruh isi foto nota pembelian ini.
Kembalikan JSON valid dengan struktur:
{
  "supplier_name": "nama pemasok atau kosong",
  "purchase_date": "tanggal dalam format YYYY-MM-DD atau kosong",
  "items": [
    {"name": "nama barang", "quantity": angka atau null, "unit": "satuan atau kosong", "unit_price": angka atau null}
  ]
}
Gunakan angka numerik tanpa Rp, titik, atau koma ribuan. Jangan menebak, tetapi ekstrak dari nota.
Jika teks tidak terbaca, kembalikan items kosong dan field kosong.
PROMPT;

        $payload = [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => base64_encode($bytes),
                            ],
                        ],
                    ],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0,
                ],
            ];

        $lastStatus = null;
        $response = null;

        foreach ($models as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
                .rawurlencode($model).':generateContent';

            try {
                $candidate = Http::timeout($timeout)
                    ->acceptJson()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post($url, $payload);
            } catch (RequestException $exception) {
                $lastStatus = $exception->response?->status();
                continue;
            } catch (ConnectionException $exception) {
                throw new ReceiptOcrException('Tidak dapat terhubung ke layanan Gemini. Periksa koneksi internet.', previous: $exception);
            }

            $lastStatus = $candidate->status();
            if ($candidate->successful()) {
                $response = $candidate;
                break;
            }

            Log::warning('Gemini model could not process receipt.', [
                'model' => $model,
                'status' => $candidate->status(),
            ]);
        }

        if ($response === null) {
            if ($lastStatus === 429) {
                throw new ReceiptOcrException('Kuota Gemini API habis atau permintaan terlalu sering. Coba lagi beberapa saat.');
            }
            if ($lastStatus === 503) {
                throw new ReceiptOcrException('Semua model Gemini sedang sibuk. Coba unggah nota lagi beberapa saat.');
            }
            throw new ReceiptOcrException('Gemini gagal memproses nota (HTTP '.$lastStatus.') dan tidak ada model cadangan yang berhasil.');
        }

        $text = $this->geminiResponseText($response->json());
        if ($text === '') {
            throw new ReceiptOcrException('Gemini tidak menemukan teks pada nota.');
        }

        $data = json_decode($this->stripCodeFence($text), true);
        if (! is_array($data)) {
            return trim($text);
        }

        return $this->formatGeminiResult($data);
    }

    private function geminiResponseText(mixed $body): string
    {
        if (! is_array($body)) {
            return '';
        }

        $parts = $body['candidates'][0]['content']['parts'] ?? [];
        $text = '';

        foreach ($parts as $part) {
            if (is_array($part) && is_string($part['text'] ?? null)) {
                $text .= $part['text'];
            }
        }

        return trim($text);
    }

    private function stripCodeFence(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        }

        return trim($text);
    }

    private function formatGeminiResult(array $data): string
    {
        $lines = [];
        $supplier = trim((string) ($data['supplier_name'] ?? ''));
        $date = trim((string) ($data['purchase_date'] ?? ''));

        if ($supplier !== '') {
            $lines[] = 'Supplier: '.$supplier;
        }
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $lines[] = 'Tanggal: '.date('d/m/Y', strtotime($date));
        }

        foreach (($data['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $quantity = $item['quantity'] ?? null;
            $unit = trim((string) ($item['unit'] ?? ''));
            $price = $item['unit_price'] ?? null;
            $line = $name;

            if (is_numeric($quantity)) {
                $line .= ' '.$this->formatNumber((float) $quantity);
            }
            if ($unit !== '') {
                $line .= ' '.$unit;
            }
            if (is_numeric($price)) {
                $line .= ' Rp '.number_format((float) $price, 0, ',', '.');
            }

            $lines[] = $line;
        }

        $text = trim(implode(PHP_EOL, $lines));
        if ($text === '') {
            throw new ReceiptOcrException('Gemini tidak menemukan data pembelian pada nota.');
        }

        return $text;
    }

    private function formatNumber(float $number): string
    {
        return fmod($number, 1.0) === 0.0
            ? (string) (int) $number
            : rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function extractWithTesseract(string $imagePath): string
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
