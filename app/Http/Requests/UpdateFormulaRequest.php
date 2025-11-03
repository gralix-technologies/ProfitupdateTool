<?php

namespace App\Http\Requests;

use App\Models\Formula;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFormulaRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return auth()->check();
    }

    
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'expression' => 'sometimes|required|string|max:1000',
            'description' => 'nullable|string|max:1000',
            'product_id' => 'nullable|exists:products,id',
            'return_type' => 'sometimes|required|string|in:' . implode(',', Formula::getReturnTypes()),
            'parameters' => 'nullable|array',
            'is_active' => 'boolean'
        ];
    }

    
    public function messages(): array
    {
        return [
            'name.required' => 'Formula name is required',
            'name.max' => 'Formula name cannot exceed 255 characters',
            'expression.required' => 'Formula expression is required',
            'expression.max' => 'Formula expression cannot exceed 1000 characters',
            'product_id.exists' => 'Selected product does not exist',
            'return_type.required' => 'Return type is required',
            'return_type.in' => 'Invalid return type selected',
            'description.max' => 'Description cannot exceed 1000 characters'
        ];
    }

    
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'return_type' => 'return type',
            'is_active' => 'active status'
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


