<?php

namespace App\Http\Requests;

use App\Services\InputSanitizationService;
use Illuminate\Foundation\Http\FormRequest;

abstract class SecureFormRequest extends FormRequest
{
    
    public function sanitized(): array
    {
        $sanitizationService = app(InputSanitizationService::class);
        $data = $this->validated();
        
        return $this->sanitizeData($data, $sanitizationService);
    }

    
    public function sanitizedInput(string $key, $default = null)
    {
        $sanitized = $this->sanitized();
        return data_get($sanitized, $key, $default);
    }

    
    protected function sanitizeData(array $data, InputSanitizationService $sanitizer): array
    {
        $sanitized = [];
        $fieldTypes = $this->getFieldTypes();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value, $sanitizer);
            } else {
                $type = $fieldTypes[$key] ?? 'string';
                $sanitized[$key] = $sanitizer->sanitizeByType($value, $type);
            }
        }
        
        return $sanitized;
    }

    
    protected function getFieldTypes(): array
    {
        return [];
    }

    
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'email' => 'The :attribute field must be a valid email address.',
            'numeric' => 'The :attribute field must be a number.',
            'integer' => 'The :attribute field must be an integer.',
            'boolean' => 'The :attribute field must be true or false.',
            'array' => 'The :attribute field must be an array.',
            'max' => 'The :attribute field must not exceed :max characters.',
            'min' => 'The :attribute field must be at least :min characters.',
            'unique' => 'The :attribute has already been taken.',
            'exists' => 'The selected :attribute is invalid.',
        ];
    }

    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateSecurityConstraints($validator);
        });
    }

    
    protected function validateSecurityConstraints($validator): void
    {
        $sanitizationService = app(InputSanitizationService::class);
        
        foreach ($this->all() as $key => $value) {
            if (is_string($value)) {
                if ($sanitizationService->containsSqlInjection($value)) {
                    $validator->errors()->add($key, 'The ' . $key . ' field contains invalid characters.');
                }
                
                if ($sanitizationService->containsXss($value)) {
                    $validator->errors()->add($key, 'The ' . $key . ' field contains invalid characters.');
                }
            }
        }
    }
}


