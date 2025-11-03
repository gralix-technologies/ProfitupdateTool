<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $userId,
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name'
        ];
    }

    
    public function messages(): array
    {
        return [
            'name.max' => 'Name must not exceed 255 characters.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email is already taken.',
            'password.confirmed' => 'Password confirmation does not match.',
            'roles.array' => 'Roles must be an array.',
            'roles.*.exists' => 'One or more selected roles do not exist.'
        ];
    }
}



