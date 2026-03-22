<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Cpf;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Cpf::isValid(is_string($value) ? $value : null)) {
            $fail('The :attribute field must contain a valid CPF.');
        }
    }
}
