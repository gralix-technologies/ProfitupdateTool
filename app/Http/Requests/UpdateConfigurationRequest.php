<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        $configurationId = $this->route('configuration')->id ?? null;

        return [
            'key' => 'sometimes|string|max:255|unique:configurations,key,' . $configurationId,
            'value' => 'sometimes',
            'description' => 'nullable|string|max:1000'
        ];
    }

    
    public function messages(): array
    {
        return [
            'key.unique' => 'Configuration key already exists.',
            'key.max' => 'Configuration key must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 1000 characters.'
        ];
    }
}



