<?php

namespace App\Support;

class Npr
{
    /**
     * Format integer paisa to Nepali lakh/crore grouping string.
     * 1234567800 paisa => "रू 1,23,45,678.00"
     * (1234567800 paisa = 12,345,678 rupees = 1.2345678 crore)
     */
    public static function format(int $paisa, bool $withSymbol = true): string
    {
        $sign = $paisa < 0 ? '-' : '';
        $paisa = abs($paisa);
        $rupees = intdiv($paisa, 100);
        $paise = $paisa % 100;

        // Indian/Nepali grouping: last 3 digits, then groups of 2
        $s = (string) $rupees;
        if (strlen($s) > 3) {
            $last3 = substr($s, -3);
            $rest = substr($s, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $s = $rest.','.$last3;
        }

        $formatted = $s.'.'.str_pad((string) $paise, 2, '0', STR_PAD_LEFT);

        return $sign.($withSymbol ? 'रू ' : '').$formatted;
    }

    /**
     * Parse a user-entered rupee amount into integer paisa.
     * Accepts "1234.5", "1,234.50", "रू 1,234.50".
     */
    public static function toPaisa(string|int|float|null $rupees): int
    {
        if ($rupees === null || $rupees === '') {
            return 0;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $rupees);

        return (int) round(((float) $clean) * 100);
    }

    /**
     * Format paisa as a plain rupee decimal for form inputs.
     */
    public static function toInput(int $paisa): string
    {
        return number_format($paisa / 100, 2, '.', '');
    }
}
