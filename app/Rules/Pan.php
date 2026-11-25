<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Pan implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[1-9][0-9]{8}$/', (string) $value)) {
            $fail('PAN must be a 9-digit number starting with 1–9.');
        }
    }
}
