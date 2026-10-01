<?php

namespace App\Support;

use InvalidArgumentException;

class MoneyInput
{
    public static function toMinorUnits(string $value): int
    {
        $normalized = str_replace(',', '.', trim($value));

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    public static function fromMinorUnits(int $amountMinor): string
    {
        $whole = intdiv($amountMinor, 100);
        $fraction = abs($amountMinor % 100);

        return sprintf('%d.%02d', $whole, $fraction);
    }
}
