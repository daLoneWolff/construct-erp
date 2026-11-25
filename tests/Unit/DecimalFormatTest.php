<?php

namespace Tests\Unit;

use App\Support\DecimalFormat;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalFormatTest extends TestCase
{
    public function test_it_formats_grouped_decimal_values(): void
    {
        $this->assertSame('1,234,567.80', DecimalFormat::format(1234567.8, 2));
    }

    public function test_it_rejects_non_numeric_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DecimalFormat::format('not a number', 2);
    }

    public function test_it_rejects_negative_precision(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DecimalFormat::format(12.5, -1);
    }
}
