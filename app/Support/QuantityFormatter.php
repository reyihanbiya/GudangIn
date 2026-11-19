<?php

namespace App\Support;

class QuantityFormatter
{
    public static function format(mixed $value): string
    {
        $formatted = number_format((float) $value, 3, ',', '.');
        [$whole, $fraction] = explode(',', $formatted);
        $fraction = rtrim($fraction, '0');

        return $fraction === '' ? $whole : $whole.','.$fraction;
    }
}
