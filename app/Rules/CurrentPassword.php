<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class CurrentPassword implements ValidationRule
{
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = request()->user();
        
        if (!$user || !Hash::check($value, $user->password)) {
            $fail('The current password is incorrect.');
        }
    }
}


