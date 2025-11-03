<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLgdLookupRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'collateral_type' => 'required|string|max:100',
            'lgd_default' => 'required|numeric|min:0|max:1'
        ];
    }

    
    public function messages(): array
    {
        return [
            'collateral_type.required' => 'Collateral type is required.',
            'collateral_type.max' => 'Collateral type must not exceed 100 characters.',
            'lgd_default.required' => 'LGD default value is required.',
            'lgd_default.numeric' => 'LGD default value must be a number.',
            'lgd_default.min' => 'LGD default value must be at least 0.',
            'lgd_default.max' => 'LGD default value must not exceed 1.'
        ];
    }
}



