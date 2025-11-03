<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePdLookupRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'credit_rating' => 'sometimes|string|max:50',
            'pd_default' => 'sometimes|numeric|min:0|max:1'
        ];
    }

    
    public function messages(): array
    {
        return [
            'credit_rating.max' => 'Credit rating must not exceed 50 characters.',
            'pd_default.numeric' => 'PD default value must be a number.',
            'pd_default.min' => 'PD default value must be at least 0.',
            'pd_default.max' => 'PD default value must not exceed 1.'
        ];
    }
}



