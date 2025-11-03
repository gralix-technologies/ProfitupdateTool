<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskWeightRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'credit_rating' => 'required|string|max:50',
            'collateral_type' => 'required|string|max:100',
            'risk_weight_percent' => 'required|numeric|min:0|max:100'
        ];
    }

    
    public function messages(): array
    {
        return [
            'credit_rating.required' => 'Credit rating is required.',
            'credit_rating.max' => 'Credit rating must not exceed 50 characters.',
            'collateral_type.required' => 'Collateral type is required.',
            'collateral_type.max' => 'Collateral type must not exceed 100 characters.',
            'risk_weight_percent.required' => 'Risk weight percentage is required.',
            'risk_weight_percent.numeric' => 'Risk weight percentage must be a number.',
            'risk_weight_percent.min' => 'Risk weight percentage must be at least 0.',
            'risk_weight_percent.max' => 'Risk weight percentage must not exceed 100.'
        ];
    }
}



