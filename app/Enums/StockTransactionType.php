<?php

namespace App\Enums;

enum StockTransactionType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case Adjustment = 'adjustment';
}
