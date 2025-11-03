<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestFormulaRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return auth()->check();
    }

    
    public function rules(): array
    {
        return [
            'expression' => 'required|string|max:1000',
            'sample_data' => 'nullable|array',
            'product_id' => 'nullable|exists:products,id',
            'use_real_data' => 'nullable|boolean'
        ];
    }

    
    public function messages(): array
    {
        return [
            'expression.required' => 'Formula expression is required for testing',
            'expression.max' => 'Formula expression cannot exceed 1000 characters',
            'sample_data.required' => 'Sample data is required for testing',
            'sample_data.array' => 'Sample data must be an array',
            'product_id.exists' => 'Selected product does not exist'
        ];
    }

    
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'sample_data' => 'sample data'
        ];
    }

    
    protected function prepareForValidation(): void
    {
        if ($this->has('expression')) {
            $this->merge([
                'expression' => trim($this->input('expression'))
            ]);
        }
    }
}


