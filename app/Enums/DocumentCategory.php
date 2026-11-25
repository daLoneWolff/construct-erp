<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Drawing = 'drawing';
    case Contract = 'contract';
    case Permit = 'permit';
    case Boq = 'boq';
    case Po = 'po';
    case DsrPhoto = 'dsr_photo';
    case Other = 'other';
}
