<?php

namespace App\Support;

class NumberToWords
{
    private const ONES = [
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen',
    ];

    private const TENS = [
        2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
        6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety',
    ];

    /**
     * Convert a whole rupee amount (NPR) to English words using the
     * lakh/crore convention, e.g. 1234567 => "Twelve lakh thirty-four
     * thousand five hundred sixty-seven".
     */
    public static function toWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $words = [];

        $crores = intdiv($number, 10000000);
        $number %= 10000000;

        $lakhs = intdiv($number, 100000);
        $number %= 100000;

        $thousands = intdiv($number, 1000);
        $number %= 1000;

        if ($crores > 0) {
            $words[] = self::belowThousand($crores).' crore';
        }

        if ($lakhs > 0) {
            $words[] = self::belowThousand($lakhs).' lakh';
        }

        if ($thousands > 0) {
            $words[] = self::belowThousand($thousands).' thousand';
        }

        if ($number > 0) {
            $words[] = self::belowThousand($number);
        }

        $sentence = implode(' ', $words);

        return ucfirst($sentence);
    }

    /**
     * Format integer paisa as a words sentence, e.g.
     * 1234567800 paisa => "Rupees Twelve lakh thirty-four thousand
     * five hundred sixty-seven only".
     */
    public static function rupees(int $paisa): string
    {
        $rupees = intdiv(abs($paisa), 100);

        return 'Rupees '.self::toWords($rupees).' only';
    }

    private static function belowThousand(int $number): string
    {
        $words = [];

        if ($number >= 100) {
            $words[] = self::ONES[intdiv($number, 100)].' hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $words[] = self::TENS[intdiv($number, 10)];
            $number %= 10;
        }

        if ($number > 0) {
            $words[] = self::ONES[$number];
        }

        return implode(' ', $words);
    }
}
