<?php

namespace App\Support;

use InvalidArgumentException;

class DecimalFormat
{
    public static function format(int|float|string $value, int $decimals = 0): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('The value to format must be numeric.');
        }

        if ($decimals < 0) {
            throw new InvalidArgumentException('The number of decimal places must be non-negative.');
        }

        return number_format((float) $value, $decimals, '.', ',');
    }
}
