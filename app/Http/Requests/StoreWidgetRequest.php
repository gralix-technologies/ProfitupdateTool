<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWidgetRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(['KPI', 'Table', 'PieChart', 'BarChart', 'LineChart', 'Heatmap'])
            ],
            'configuration' => 'required|array',
            'configuration.title' => 'required|string|max:255',
            'configuration.data_source' => 'sometimes|string',
            'configuration.formula_id' => 'sometimes|integer|exists:formulas,id',
            'configuration.chart_options' => 'sometimes|array',
            'position' => 'sometimes|array',
            'position.x' => 'sometimes|integer|min:0',
            'position.y' => 'sometimes|integer|min:0',
            'position.w' => 'sometimes|integer|min:1|max:12',
            'position.h' => 'sometimes|integer|min:1|max:12'
        ];
    }

    
    public function messages(): array
    {
        return [
            'type.required' => 'Widget type is required',
            'type.in' => 'Widget type must be one of: KPI, Table, PieChart, BarChart, LineChart, Heatmap',
            'configuration.required' => 'Widget configuration is required',
            'configuration.title.required' => 'Widget title is required',
            'configuration.title.max' => 'Widget title cannot exceed 255 characters',
            'position.w.max' => 'Widget width cannot exceed 12 grid units',
            'position.h.max' => 'Widget height cannot exceed 12 grid units'
        ];
    }
}


