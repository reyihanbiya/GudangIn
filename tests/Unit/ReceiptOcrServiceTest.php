<?php

namespace Tests\Unit;

use App\Services\ReceiptOcrException;
use App\Services\ReceiptOcrService;
use Tests\TestCase;

class ReceiptOcrServiceTest extends TestCase
{
    public function test_missing_tesseract_binary_returns_actionable_error(): void
    {
        config(['services.tesseract.binary' => '__gudangin_missing_tesseract_binary__']);

        try {
            (new ReceiptOcrService())->extract('receipt.png');
            $this->fail('Expected the OCR service to report a missing local engine.');
        } catch (ReceiptOcrException $exception) {
            $this->assertMatchesRegularExpression('/OCR|Tesseract/', $exception->getMessage());
        }
    }
}
