<?php

namespace App\Rules;

use App\Support\PakistanTaxHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PakistanCnicRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PakistanTaxHelper::isValidCnic($value)) {
            $fail('The CNIC must be a valid Pakistani CNIC.');
        }
    }
}
