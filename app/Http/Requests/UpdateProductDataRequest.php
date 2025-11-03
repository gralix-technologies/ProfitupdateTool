<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductDataRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'data' => 'sometimes|array',
            'amount' => 'nullable|numeric|min:0',
            'effective_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,inactive,pending,closed,npl'
        ];
    }

    
    public function messages(): array
    {
        return [
            'data.array' => 'Product data must be an array.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'effective_date.date' => 'Effective date must be a valid date.',
            'status.in' => 'Status must be one of: active, inactive, pending, closed, npl.'
        ];
    }
}



