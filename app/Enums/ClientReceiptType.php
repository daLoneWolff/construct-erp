<?php

namespace App\Enums;

enum ClientReceiptType: string
{
    case Advance = 'advance';
    case RunningBill = 'running_bill';
    case Retention = 'retention';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Advance => 'Advance',
            self::RunningBill => 'Running bill',
            self::Retention => 'Retention',
            self::Other => 'Other',
        };
    }
}
