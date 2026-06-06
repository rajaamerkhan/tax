<?php

namespace App\Rules;

use App\Support\PakistanTaxHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PakistanNtnRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PakistanTaxHelper::isValidNtn($value)) {
            $fail('The NTN must be a valid Pakistani NTN.');
        }
    }
}
