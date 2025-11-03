<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductDataRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'required|string|exists:customers,customer_id',
            'data' => 'required|array',
            'amount' => 'nullable|numeric|min:0',
            'effective_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,inactive,pending,closed,npl'
        ];
    }

    
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'customer_id.required' => 'Customer ID is required.',
            'customer_id.exists' => 'Customer does not exist.',
            'data.required' => 'Product data is required.',
            'data.array' => 'Product data must be an array.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'effective_date.date' => 'Effective date must be a valid date.',
            'status.in' => 'Status must be one of: active, inactive, pending, closed, npl.'
        ];
    }
}



