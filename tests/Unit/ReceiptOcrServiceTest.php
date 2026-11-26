<?php

namespace Tests\Unit;

use App\Services\ReceiptOcrException;
use App\Services\ReceiptOcrService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReceiptOcrServiceTest extends TestCase
{
    public function test_missing_tesseract_binary_returns_actionable_error(): void
    {
        config(['services.gemini.api_key' => null]);
        config(['services.tesseract.binary' => '__gudangin_missing_tesseract_binary__']);

        try {
            (new ReceiptOcrService())->extract('receipt.png');
            $this->fail('Expected the OCR service to report a missing local engine.');
        } catch (ReceiptOcrException $exception) {
            $this->assertMatchesRegularExpression('/OCR|Tesseract/', $exception->getMessage());
        }
    }

    public function test_gemini_receipt_response_is_formatted_for_purchase_review(): void
    {
        $imagePath = tempnam(sys_get_temp_dir(), 'gudangin-receipt');
        file_put_contents((string) $imagePath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'supplier_name' => 'Toko Batu Bara',
                                'purchase_date' => '2026-11-24',
                                'items' => [[
                                    'name' => 'Semen',
                                    'quantity' => 10,
                                    'unit' => 'sak',
                                    'unit_price' => 50000,
                                ]],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $text = (new ReceiptOcrService())->extract((string) $imagePath);

        @unlink((string) $imagePath);

        $this->assertStringContainsString('Supplier: Toko Batu Bara', $text);
        $this->assertStringContainsString('Tanggal: 24/11/2026', $text);
        $this->assertStringContainsString('Semen 10 sak Rp 50.000', $text);
    }

    public function test_gemini_failure_falls_back_to_tesseract(): void
    {
        $imagePath = tempnam(sys_get_temp_dir(), 'gudangin-receipt');
        file_put_contents((string) $imagePath, 'not-used');

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-3.5-flash-lite',
            'services.tesseract.binary' => '__gudangin_missing_tesseract_binary__',
        ]);

        Http::fake([
            '*' => Http::response(['error' => ['message' => 'busy']], 503),
        ]);

        try {
            (new ReceiptOcrService())->extract((string) $imagePath);
            $this->fail('Expected Tesseract fallback to report a missing engine.');
        } catch (ReceiptOcrException $exception) {
            $this->assertStringContainsString('Tesseract', $exception->getMessage());
        } finally {
            @unlink((string) $imagePath);
        }
    }
}
