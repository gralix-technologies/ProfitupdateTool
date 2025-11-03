<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        $user = $this->user();
        
        // Log authorization check
        \Log::info('StoreProductRequest authorization check', [
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'can_create_products' => $user ? $user->can('create-products') : false,
            'user_permissions' => $user ? $user->getAllPermissions()->pluck('name')->toArray() : []
        ]);
        
        return $user && $user->can('create-products');
    }

    
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:products,name'
            ],
            'category' => [
                'required',
                'string',
                Rule::in(Product::getCategories())
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'is_active' => [
                'boolean'
            ],
            'field_definitions' => [
                'nullable',
                'array'
            ],
            'field_definitions.*.name' => [
                'required_with:field_definitions',
                'string',
                'max:255'
            ],
            'field_definitions.*.type' => [
                'required_with:field_definitions',
                'string',
                Rule::in(Product::getFieldTypes())
            ],
            'field_definitions.*.required' => [
                'boolean'
            ],
            'field_definitions.*.options' => [
                'required_if:field_definitions.*.type,Lookup',
                'array',
                'min:1'
            ],
            'field_definitions.*.options.*' => [
                'string',
                'max:255'
            ]
        ];
    }

    
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.unique' => 'A product with this name already exists.',
            'category.required' => 'Product category is required.',
            'category.in' => 'Invalid product category. Must be one of: ' . implode(', ', Product::getCategories()),
            'field_definitions.*.name.required_with' => 'Field name is required.',
            'field_definitions.*.type.required_with' => 'Field type is required.',
            'field_definitions.*.type.in' => 'Invalid field type. Must be one of: ' . implode(', ', Product::getFieldTypes()),
            'field_definitions.*.options.required_if' => 'Lookup fields must have options.',
            'field_definitions.*.options.min' => 'Lookup fields must have at least one option.'
        ];
    }

    
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fieldDefinitions = $this->input('field_definitions', []);
            
            if (!empty($fieldDefinitions)) {
                $fieldNames = array_column($fieldDefinitions, 'name');
                $duplicates = array_diff_assoc($fieldNames, array_unique($fieldNames));
                
                if (!empty($duplicates)) {
                    $validator->errors()->add('field_definitions', 'Field names must be unique.');
                }
            }
        });
    }
}


