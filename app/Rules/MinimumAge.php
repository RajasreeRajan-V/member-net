<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumAge implements ValidationRule
{
    public function __construct(private int $years = 5) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $age = Carbon::parse($value)->age;
        } catch (\Exception $e) {
            $fail('Enter a valid date of birth.');
            return;
        }

        if ($age < $this->years) {
            $fail("Member must be at least {$this->years} years old.");
        }
    }
}