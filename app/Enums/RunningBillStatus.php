<?php

namespace App\Enums;

enum RunningBillStatus: string
{
    case Draft = 'draft';
    case Certified = 'certified';
    case Paid = 'paid';
}
