<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigurationRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'key' => 'required|string|max:255|unique:configurations',
            'value' => 'required',
            'description' => 'nullable|string|max:1000'
        ];
    }

    
    public function messages(): array
    {
        return [
            'key.required' => 'Configuration key is required.',
            'key.unique' => 'Configuration key already exists.',
            'key.max' => 'Configuration key must not exceed 255 characters.',
            'value.required' => 'Configuration value is required.',
            'description.max' => 'Description must not exceed 1000 characters.'
        ];
    }
}



