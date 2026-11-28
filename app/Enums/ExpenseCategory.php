<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Material = 'material';
    case Labor = 'labor';
    case Equipment = 'equipment';
    case Transport = 'transport';
    case Fuel = 'fuel';
    case Office = 'office';
    case Utilities = 'utilities';
    case Rent = 'rent';
    case ProfessionalServices = 'professional_services';
    case Tax = 'tax';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Material => 'Material',
            self::Labor => 'Labor',
            self::Equipment => 'Equipment',
            self::Transport => 'Transport',
            self::Fuel => 'Fuel',
            self::Office => 'Office',
            self::Utilities => 'Utilities',
            self::Rent => 'Rent',
            self::ProfessionalServices => 'Professional Services',
            self::Tax => 'Tax',
            self::Other => 'Other',
        };
    }
}
