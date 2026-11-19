<?php

namespace Tests\Unit;

use App\Services\ReceiptOcrParser;
use Tests\TestCase;

class ReceiptOcrParserTest extends TestCase
{
    public function test_parser_matches_each_numbered_row_to_the_correct_product(): void
    {
        $products = [
            (object) ['id_persediaan' => 'BRG001', 'nama_barang' => 'Semen Tiga Roda', 'satuan' => 'unit'],
            (object) ['id_persediaan' => 'BRG002', 'nama_barang' => 'Pasir Pasang', 'satuan' => 'm3'],
            (object) ['id_persediaan' => 'BRG003', 'nama_barang' => 'Batu Bata Merah', 'satuan' => 'pcs'],
            (object) ['id_persediaan' => 'BRG004', 'nama_barang' => 'Besi Beton 10mm', 'satuan' => 'batang'],
        ];

        $text = implode("\n", [
            '1 Semen Tiga Roda 50kg 40 sak 62.000 2.480.000',
            '2 Pasir Pasang 2 m3 280.000 560.000',
            '3 Batu Bata Merah 1000 pcs 800 800.000',
            '4 Besi Beton 10mm 20 btg 78.000 1.560.000',
        ]);

        $result = (new ReceiptOcrParser())->parse($text, [], $products);

        $this->assertSame(
            ['BRG001', 'BRG002', 'BRG003', 'BRG004'],
            array_column($result['items'], 'id_persediaan')
        );
        $this->assertSame(
            [40.0, 2.0, 1000.0, 20.0],
            array_map(fn (array $item) => $item['jumlah_barang'], $result['items'])
        );
        $this->assertSame(
            ['sak', 'm3', 'pcs', 'btg'],
            array_column($result['items'], 'satuan')
        );
    }

    public function test_parser_extracts_quantity_unit_and_unit_price_from_table_row(): void
    {
        $product = (object) [
            'id_persediaan' => 'BRG0001',
            'nama_barang' => 'Semen Tiga Roda',
            'satuan' => 'unit',
        ];

        $result = (new ReceiptOcrParser())->parse(
            "1 Semen Tiga Roda 50kg 40 sak 62.000 2.480.000",
            [],
            [$product]
        );

        $this->assertSame(40.0, $result['items'][0]['jumlah_barang']);
        $this->assertSame('sak', $result['items'][0]['satuan']);
        $this->assertSame(62000.0, $result['items'][0]['harga_beli']);
    }

    public function test_parser_matches_partial_supplier_name(): void
    {
        $supplier = (object) [
            'id_supplier' => 'SUP0001',
            'nama_supplier' => 'Toko Batu bara',
        ];

        $result = (new ReceiptOcrParser())->parse(
            "Tuan\nToko\nNOTA NO. 123",
            [$supplier],
            []
        );

        $this->assertSame('SUP0001', $result['id_supplier']);
    }

    public function test_parser_extracts_supplier_date_and_purchase_line(): void
    {
        $supplier = (object) [
            'id_supplier' => 'SUP0001',
            'nama_supplier' => 'Toko Batu bara',
        ];
        $product = (object) [
            'id_persediaan' => 'BRG0001',
            'nama_barang' => 'Semen',
            'satuan' => 'sak',
        ];

        $result = (new ReceiptOcrParser())->parse(
            "NOTA NO. 123\nToko Batu bara\n19/11/2026\nSemen 4 sak Rp 60.000",
            [$supplier],
            [$product]
        );

        $this->assertSame('SUP0001', $result['id_supplier']);
        $this->assertSame('2026-11-19', $result['tanggal']);
        $this->assertSame('BRG0001', $result['items'][0]['id_persediaan']);
        $this->assertSame(4.0, $result['items'][0]['jumlah_barang']);
        $this->assertSame(60000.0, $result['items'][0]['harga_beli']);
        $this->assertSame('sak', $result['items'][0]['satuan']);
    }
}
