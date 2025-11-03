<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductData;
use App\Models\Formula;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GenericProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    
    public function summary(Product $product): JsonResponse
    {
        try {
            $summary = ProductData::getSummaryForProduct($product->id);
            $portfolioMetrics = $this->productService->calculatePortfolioMetrics($product);
            
            return response()->json([
                'success' => true,
                'data' => array_merge($summary, $portfolioMetrics)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate portfolio summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function index(Product $product, Request $request): JsonResponse
    {
        try {
            $query = ProductData::where('product_id', $product->id);
            
            $fieldDefinitions = $product->field_definitions ?? [];
            foreach ($fieldDefinitions as $field) {
                $fieldName = $field['name'];
                if ($request->has($fieldName)) {
                    $value = $request->get($fieldName);
                    $query->whereJsonContains("data->{$fieldName}", $value);
                }
            }
            
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->get('customer_id'));
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }
            
            if ($request->has('amount_min')) {
                $query->where('amount', '>=', $request->get('amount_min'));
            }
            
            if ($request->has('amount_max')) {
                $query->where('amount', '<=', $request->get('amount_max'));
            }
            
            $perPage = min($request->get('per_page', 15), 100);
            $data = $query->with(['customer', 'product'])
                ->orderBy('updated_at', 'desc')
                ->paginate($perPage);
            
            $data->getCollection()->transform(function ($record) {
                $metrics = $this->productService->calculateLoanMetrics($record);
                $record->calculated_metrics = $metrics;
                return $record;
            });
            
            return response()->json([
                'success' => true,
                'data' => $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function show(Product $product, string $recordId): JsonResponse
    {
        try {
            $record = ProductData::where('product_id', $product->id)
                ->where('id', $recordId)
                ->with(['customer', 'product'])
                ->firstOrFail();
            
            $metrics = $this->productService->calculateLoanMetrics($record);
            $record->calculated_metrics = $metrics;
            
            return response()->json([
                'success' => true,
                'data' => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product data record not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    
    public function store(Product $product, Request $request): JsonResponse
    {
        try {
            $rules = $this->buildValidationRules($product);
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $data = $request->only(array_keys($rules));
            $commonFields = ['customer_id', 'amount', 'effective_date', 'status'];
            $productSpecificData = array_diff_key($data, array_flip($commonFields));
            
            $record = ProductData::create([
                'product_id' => $product->id,
                'customer_id' => $data['customer_id'],
                'data' => $productSpecificData,
                'amount' => $data['amount'] ?? null,
                'effective_date' => $data['effective_date'] ?? null,
                'status' => $data['status'] ?? 'active'
            ]);
            
            $metrics = $this->productService->calculateLoanMetrics($record);
            $record->calculated_metrics = $metrics;
            
            return response()->json([
                'success' => true,
                'data' => $record,
                'message' => 'Product data created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function update(Product $product, string $recordId, Request $request): JsonResponse
    {
        try {
            $record = ProductData::where('product_id', $product->id)
                ->where('id', $recordId)
                ->firstOrFail();
            
            $rules = $this->buildValidationRules($product, false);
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $data = $request->only(array_keys($rules));
            $commonFields = ['customer_id', 'amount', 'effective_date', 'status'];
            $productSpecificData = array_diff_key($data, array_flip($commonFields));
            
            $record->update([
                'customer_id' => $data['customer_id'] ?? $record->customer_id,
                'data' => array_merge($record->data ?? [], $productSpecificData),
                'amount' => $data['amount'] ?? $record->amount,
                'effective_date' => $data['effective_date'] ?? $record->effective_date,
                'status' => $data['status'] ?? $record->status
            ]);
            
            $metrics = $this->productService->calculateLoanMetrics($record);
            $record->calculated_metrics = $metrics;
            
            return response()->json([
                'success' => true,
                'data' => $record,
                'message' => 'Product data updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function destroy(Product $product, string $recordId): JsonResponse
    {
        try {
            $record = ProductData::where('product_id', $product->id)
                ->where('id', $recordId)
                ->firstOrFail();
            
            $record->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Product data deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function export(Product $product, Request $request): JsonResponse
    {
        try {
            $query = ProductData::where('product_id', $product->id);
            
            $fieldDefinitions = $product->field_definitions ?? [];
            foreach ($fieldDefinitions as $field) {
                $fieldName = $field['name'];
                if ($request->has($fieldName)) {
                    $value = $request->get($fieldName);
                    $query->whereJsonContains("data->{$fieldName}", $value);
                }
            }
            
            $data = $query->with('customer')->get();
            
            $headers = ['ID', 'Customer ID', 'Customer Name', 'Amount', 'Effective Date', 'Status'];
            foreach ($fieldDefinitions as $field) {
                $headers[] = $field['name'];
            }
            
            if ($data->isNotEmpty()) {
                $firstRecord = $data->first();
                $metrics = $this->productService->calculateLoanMetrics($firstRecord);
                foreach (array_keys($metrics) as $metricName) {
                    $headers[] = $metricName;
                }
            }
            
            $csvData = [];
            $csvData[] = $headers;
            
            foreach ($data as $record) {
                $metrics = $this->productService->calculateLoanMetrics($record);
                
                $row = [
                    $record->id,
                    $record->customer_id,
                    $record->customer->name ?? '',
                    $record->amount,
                    $record->effective_date,
                    $record->status
                ];
                
                foreach ($fieldDefinitions as $field) {
                    $row[] = $record->getFieldValue($field['name']);
                }
                
                foreach ($metrics as $value) {
                    $row[] = $value;
                }
                
                $csvData[] = $row;
            }
            
            $filename = "{$product->name}_export_" . date('Y-m-d_H-i-s') . '.csv';
            
            return response()->json([
                'success' => true,
                'data' => $csvData,
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export product data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    protected function buildValidationRules(Product $product, bool $requireAll = true): array
    {
        $rules = [];
        $fieldDefinitions = $product->field_definitions ?? [];
        
        $rules['customer_id'] = $requireAll ? 'required|string|exists:customers,customer_id' : 'sometimes|string|exists:customers,customer_id';
        $rules['amount'] = 'sometimes|numeric|min:0';
        $rules['effective_date'] = 'sometimes|date';
        $rules['status'] = 'sometimes|string|in:active,inactive,closed,defaulted';
        
        foreach ($fieldDefinitions as $field) {
            $fieldName = $field['name'];
            $fieldRules = [];
            
            if (($field['required'] ?? false) && $requireAll) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'sometimes';
            }
            
            switch ($field['type']) {
                case 'text':
                    $fieldRules[] = 'string';
                    if (isset($field['max_length'])) {
                        $fieldRules[] = "max:{$field['max_length']}";
                    }
                    break;
                case 'numeric':
                    $fieldRules[] = 'numeric';
                    if (isset($field['min'])) {
                        $fieldRules[] = "min:{$field['min']}";
                    }
                    if (isset($field['max'])) {
                        $fieldRules[] = "max:{$field['max']}";
                    }
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'lookup':
                    if (isset($field['options']) && is_array($field['options'])) {
                        $fieldRules[] = 'in:' . implode(',', $field['options']);
                    }
                    break;
            }
            
            $rules[$fieldName] = implode('|', $fieldRules);
        }
        
        return $rules;
    }
}


