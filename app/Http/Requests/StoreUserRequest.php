<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name'
        ];
    }

    
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name must not exceed 255 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email is already taken.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'roles.array' => 'Roles must be an array.',
            'roles.*.exists' => 'One or more selected roles do not exist.'
        ];
    }
}



