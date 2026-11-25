<?php

namespace App\Enums;

enum OwnershipType: string
{
    case Owned = 'owned';
    case Hired = 'hired';
}
