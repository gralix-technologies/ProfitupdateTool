<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductData;
use Illuminate\Http\JsonResponse;

class TestApiController extends Controller
{
    
    public function test(): JsonResponse
    {
        try {
            $productId = 7; // Working Capital product
            $totalOutstanding = ProductData::where('product_id', $productId)->sum('amount');
            $loanCount = ProductData::where('product_id', $productId)->count();
            
            return response()->json([
                'success' => true,
                'message' => 'API is working!',
                'data' => [
                    'total_outstanding' => $totalOutstanding,
                    'loan_count' => $loanCount,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API Error: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function getKpi(string $type): JsonResponse
    {
        try {
            $productId = 7;
            
            switch ($type) {
                case 'total_portfolio':
                    $value = ProductData::where('product_id', $productId)->sum('amount');
                    $format = 'currency';
                    break;
                case 'npl_ratio':
                    $total = ProductData::where('product_id', $productId)->sum('amount');
                    $par90 = ProductData::where('product_id', $productId)
                        ->whereRaw("JSON_EXTRACT(data, '$.days_past_due') >= 90")
                        ->sum('amount');
                    $value = $total > 0 ? ($par90 / $total) * 100 : 0;
                    $format = 'percentage';
                    break;
                case 'expected_loss':
                    $value = ProductData::where('product_id', $productId)
                        ->sum(\DB::raw("CAST(JSON_EXTRACT(data, '$.pd') AS DECIMAL(9,6)) * CAST(JSON_EXTRACT(data, '$.lgd') AS DECIMAL(9,6)) * CAST(JSON_EXTRACT(data, '$.ead') AS DECIMAL(18,2))"));
                    $format = 'currency';
                    break;
                case 'avg_interest_rate':
                    $totalWeighted = ProductData::where('product_id', $productId)
                        ->sum(\DB::raw("amount * CAST(JSON_EXTRACT(data, '$.interest_rate_annual') AS DECIMAL(9,6))"));
                    $totalAmount = ProductData::where('product_id', $productId)->sum('amount');
                    $value = $totalAmount > 0 ? ($totalWeighted / $totalAmount) * 100 : 0;
                    $format = 'percentage';
                    break;
                default:
                    $value = 0;
                    $format = 'number';
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'value' => round($value, 2),
                    'format' => $format,
                    'type' => $type
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'KPI Error: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function getChart(string $type): JsonResponse
    {
        try {
            $productId = 7;
            
            switch ($type) {
                case 'sector_pie':
                    $data = ProductData::where('product_id', $productId)
                        ->select(\DB::raw("JSON_EXTRACT(data, '$.sector') as label"), \DB::raw('SUM(amount) as value'))
                        ->groupBy(\DB::raw("JSON_EXTRACT(data, '$.sector')"))
                        ->get()
                        ->map(function($item) {
                            return [
                                'label' => trim($item->label, '"'),
                                'value' => (float) $item->value
                            ];
                        });
                    break;
                case 'rating_bar':
                    $data = ProductData::where('product_id', $productId)
                        ->select(\DB::raw("JSON_EXTRACT(data, '$.credit_rating') as label"), \DB::raw('COUNT(*) as value'))
                        ->groupBy(\DB::raw("JSON_EXTRACT(data, '$.credit_rating')"))
                        ->get()
                        ->map(function($item) {
                            return [
                                'label' => trim($item->label, '"'),
                                'value' => (int) $item->value
                            ];
                        });
                    break;
                default:
                    $data = [];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chart Error: ' . $e->getMessage()
            ], 500);
        }
    }
}


