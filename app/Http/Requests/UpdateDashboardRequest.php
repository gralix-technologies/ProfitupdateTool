<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'layout' => 'sometimes|array',
            'filters' => 'sometimes|array',
            'widgets' => 'sometimes|array',
            'widgets.*.title' => 'sometimes|string|max:255',
            'widgets.*.type' => 'sometimes|string|in:KPI,Table,PieChart,BarChart,LineChart,Heatmap',
            'widgets.*.configuration' => 'sometimes|array',
            'widgets.*.position' => 'sometimes|array_or_object',
            'widgets.*.data_source' => 'sometimes|nullable',
            'widgets.*.is_active' => 'sometimes|boolean',
            'product_id' => 'sometimes|nullable|exists:products,id',
            'description' => 'sometimes|nullable|string',
            'is_public' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean'
        ];
    }

    
    public function messages(): array
    {
        return [
            'name.max' => 'Dashboard name cannot exceed 255 characters'
        ];
    }
}


