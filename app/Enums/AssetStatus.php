<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case OnSite = 'on_site';
    case Maintenance = 'maintenance';
}
