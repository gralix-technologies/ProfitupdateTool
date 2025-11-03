<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLgdLookupRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'collateral_type' => 'sometimes|string|max:100',
            'lgd_default' => 'sometimes|numeric|min:0|max:1'
        ];
    }

    
    public function messages(): array
    {
        return [
            'collateral_type.max' => 'Collateral type must not exceed 100 characters.',
            'lgd_default.numeric' => 'LGD default value must be a number.',
            'lgd_default.min' => 'LGD default value must be at least 0.',
            'lgd_default.max' => 'LGD default value must not exceed 1.'
        ];
    }
}



