<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class ReceiptOcrParser
{
    public function parse(string $text, iterable $suppliers, iterable $products): array
    {
        $productList = is_array($products)
            ? array_values($products)
            : iterator_to_array($products, false);

        $supplierId = null;
        $supplierName = $this->normalize($text);
        $supplierMatches = [];

        foreach ($suppliers as $supplier) {
            $name = $this->normalize((string) $supplier->nama_supplier);
            $partialMatch = $name !== ''
                && (
                    str_contains($supplierName, $name)
                    || str_contains($name, $supplierName)
                    || $this->hasUniqueWordMatch($supplierName, $name)
                );

            if ($partialMatch) {
                $supplierMatches[] = [
                    'length' => strlen($name),
                    'id' => $supplier->id_supplier,
                ];
            }
        }

        if ($supplierMatches !== []) {
            usort($supplierMatches, fn (array $left, array $right) => $right['length'] <=> $left['length']);
            $supplierId = $supplierMatches[0]['id'];
        }

        $date = null;
        $datePatterns = [
            '/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})\b/',
            '/\b(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})\b/',
        ];

        foreach ($datePatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    $date = $index === 0
                        ? Carbon::createFromFormat('!d/m/Y', sprintf('%02d/%02d/%04d', $matches[1], $matches[2], $matches[3]))
                        : Carbon::createFromFormat('!Y-m-d', sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]));
                    $date = $date->toDateString();
                } catch (\Throwable) {
                    $date = null;
                }
            }
        }

        $items = [];
        $seenProducts = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $product = $this->findBestProductMatch($line, $productList);
            if ($product === null || isset($seenProducts[$product->id_persediaan])) {
                continue;
            }

            $fields = $this->extractLineFields($line);
            $quantity = $fields['quantity'];
            $unit = $fields['unit'];
            $price = $fields['price'];

            if ($quantity !== null || $price !== null) {
                $items[] = [
                    'id_persediaan' => $product->id_persediaan,
                    'jumlah_barang' => $quantity,
                    'harga_beli' => $price,
                    'satuan' => $unit ?: $product->satuan,
                ];
                $seenProducts[$product->id_persediaan] = true;
            }
        }

        if ($items === [] && count($productList) === 1) {
            $quantity = null;
            $price = null;

            if (preg_match('/(?:jumlah|qty|banyak(?:nya)?|ranyak(?:nya)?|x)\s*[:=]?\s*([0-9]+(?:[.,][0-9]+)?)/i', $text, $matches)) {
                $quantity = $this->number($matches[1]);
            }

            if (preg_match('/(?:rp\.?|idr)\s*([0-9][0-9.,]*)/i', $text, $matches)) {
                $price = $this->number($matches[1]);
            }

            if ($quantity !== null || $price !== null) {
                $product = $productList[0];
                $items[] = [
                    'id_persediaan' => $product->id_persediaan,
                    'jumlah_barang' => $quantity,
                    'harga_beli' => $price,
                    'satuan' => $product->satuan,
                ];
            }
        }

        return [
            'id_supplier' => $supplierId,
            'tanggal' => $date,
            'items' => $items,
        ];
    }

    private function findBestProductMatch(string $line, array $productList): ?object
    {
        $normalizedLine = $this->normalize(preg_replace('/^\s*\d+\s+/', '', $line) ?? $line);
        $bestProduct = null;
        $bestScore = 0;

        foreach ($productList as $product) {
            $productName = $this->normalize((string) $product->nama_barang);
            if ($productName === '') {
                continue;
            }

            $score = 0;
            if (str_contains($normalizedLine, $productName)) {
                $score = 100 + strlen($productName);
            } else {
                $lineWords = preg_split('/\s+/', $normalizedLine) ?: [];
                $nameWords = preg_split('/\s+/', $productName) ?: [];
                $matchedWords = 0;

                foreach ($nameWords as $nameWord) {
                    foreach ($lineWords as $lineWord) {
                        if (
                            strlen($nameWord) >= 3
                            && (
                                $lineWord === $nameWord
                                || str_contains($lineWord, $nameWord)
                                || str_contains($nameWord, $lineWord)
                            )
                        ) {
                            $matchedWords++;
                            break;
                        }
                    }
                }

                $score = $matchedWords * 20;
                if ($this->isFuzzyMatch($normalizedLine, $productName)) {
                    $score += 5;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestProduct = $product;
            }
        }

        return $bestScore >= 20 ? $bestProduct : null;
    }

    private function hasUniqueWordMatch(string $text, string $name): bool
    {
        $textWords = array_values(array_filter(preg_split('/\s+/', $text) ?: []));
        $nameWords = array_values(array_filter(preg_split('/\s+/', $name) ?: []));

        foreach ($nameWords as $word) {
            if (strlen($word) < 4) {
                continue;
            }

            foreach ($textWords as $textWord) {
                if (
                    strlen($textWord) >= 4
                    && (
                        str_contains($textWord, $word)
                        || str_contains($word, $textWord)
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function extractLineFields(string $line): array
    {
        $quantity = null;
        $unit = null;
        $price = null;

        $unitPattern = '/([0-9]+(?:[.,][0-9]+)?)[\s]*'
            .'(sak|pcs|m3|m2|m|kg|gram|btg|pail|dus|box|roll|liter|ltr|karung|pak|set|galon|unit|bti|bks|lembar)\b/i';

        if (preg_match_all($unitPattern, $line, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $match = $matches[count($matches) - 1];
            $quantity = $this->number($match[1][0]);

            $ocrUnit = strtolower(trim($match[2][0]));
            $unitAliases = [
                'bti' => 'batang',
                'bks' => 'pak',
                'ltr' => 'liter',
            ];
            $unit = $unitAliases[$ocrUnit] ?? $ocrUnit;

            $afterUnit = substr($line, $match[0][1] + strlen($match[0][0]));
            if (preg_match('/([0-9][0-9.,]*)/', $afterUnit, $priceMatch)) {
                $price = $this->number($priceMatch[1]);
            }

            return [
                'quantity' => $quantity,
                'unit' => $unit,
                'price' => $price,
            ];
        }

        if (preg_match('/(?:jumlah|qty|jumlahnya|x)\s*[:=]?\s*([0-9]+(?:[.,][0-9]+)?)/i', $line, $matches)) {
            $quantity = $this->number($matches[1]);
        }

        if (preg_match('/(?:rp\.?|idr)\s*([0-9][0-9.,]*)/i', $line, $matches)) {
            $price = $this->number($matches[1]);
        }

        return [
            'quantity' => $quantity,
            'unit' => $unit,
            'price' => $price,
        ];
    }

    private function isFuzzyMatch(string $text, string $name): bool
    {
        if ($name === '') {
            return false;
        }

        foreach (preg_split('/\s+/', $text) ?: [] as $word) {
            if (strlen($word) < 3 || strlen($name) < 3) {
                continue;
            }

            $threshold = max(1, (int) floor(min(strlen($word), strlen($name)) * 0.3));
            if (levenshtein($word, $name) <= $threshold) {
                return true;
            }
        }

        return levenshtein(substr($text, 0, strlen($name)), $name)
            <= max(1, (int) floor(strlen($name) * 0.35));
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s]/i', ' ', $value) ?? '';

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function number(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(['.', ' '], '', $value);
        $value = str_replace(',', '.', $value);
        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }
}
