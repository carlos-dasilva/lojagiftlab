<?php

namespace App\Support;

final class Money
{
    public static function cents(string|int|null $amount): int
    {
        $value = (string) ($amount ?? '0');
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-+'), 2), 2, '');
        $cents = (int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return str_starts_with($value, '-') ? -$cents : $cents;
    }
}
