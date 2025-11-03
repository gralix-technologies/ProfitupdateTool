<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\Widget;
use App\Models\ProductData;
use App\Services\ChartDataService;
use App\Services\DashboardFilterService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    protected ChartDataService $chartDataService;
    protected DashboardFilterService $filterService;
    protected CurrencyService $currencyService;

    public function __construct(ChartDataService $chartDataService, DashboardFilterService $filterService, CurrencyService $currencyService)
    {
        $this->chartDataService = $chartDataService;
        $this->filterService = $filterService;
        $this->currencyService = $currencyService;
    }

    public function show(Request $request, Dashboard $dashboard): JsonResponse
    {
        try {
            $dashboard->load('widgets');
            $filters = $request->get('filters', []);
            

            $widgetData = [];
            foreach ($dashboard->widgets as $widget) {
                $widgetData[$widget->id] = $this->getWidgetData($widget, $filters);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'dashboard' => $dashboard,
                    'widget_data' => $widgetData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWidgetData(Widget $widget, array $filters = []): array
    {
        try {
            $configuration = is_string($widget->configuration) ? json_decode($widget->configuration, true) : ($widget->configuration ?? []);
            $dataSource = is_string($widget->data_source) ? json_decode($widget->data_source, true) : ($widget->data_source ?? []);


            if (isset($dataSource['type']) && $dataSource['type'] === 'cross_product') {
                $productIds = $dataSource['products'] ?? [];
                return $this->getCrossProductData($widget->type, $configuration, $productIds, $filters);
            }


            if (!isset($dataSource['product_id'])) {
                return ['error' => 'No product ID specified'];
            }

            $productId = $dataSource['product_id'];

            switch ($widget->type) {
                case 'KPI':
                    return $this->getKpiData($configuration, $productId, $filters);
                case 'Table':
                    return $this->getTableData($configuration, $productId, $filters);
                case 'PieChart':
                    return $this->getPieChartData($configuration, $productId, $filters);
                case 'BarChart':
                    return $this->getBarChartData($configuration, $productId, $filters);
                case 'LineChart':
                    return $this->getLineChartData($configuration, $productId, $filters);
                case 'Heatmap':
                    return $this->getHeatmapData($configuration, $productId, $filters);
                default:
                    return ['error' => 'Unknown widget type: ' . $widget->type];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getKpiData(array $configuration, int $productId, array $filters = []): array
    {
        try {

            if (isset($configuration['formula_name'])) {
                $formula = \App\Models\Formula::where('name', $configuration['formula_name'])
                    ->where('product_id', $productId)
                    ->where('is_active', true)
                    ->first();
                
                if ($formula) {

                    $value = $this->evaluateFormula($formula, $productId);
                } else {
                    return ['error' => "Formula '{$configuration['formula_name']}' not found for product {$productId}"];
                }
            } else {

        $metric = $configuration['metric'] ?? 'COUNT(*)';
        

            if ($metric === 'SUM(outstanding_balance WHERE days_past_due >= 90)') {
                $value = $this->calculateNPLRatio($productId, $filters);
            } elseif ($metric === 'SUM(outstanding_balance WHERE days_past_due >= 30)') {
                $value = $this->calculateDefaultRate($productId, $filters);
            } elseif ($metric === 'SUM(ead * (risk_weight / 100))') {
                $value = $this->calculateCAR($productId, $filters);
            } else {

                $formula = \App\Models\Formula::where('expression', $metric)
                    ->where('product_id', $productId)
                    ->where('is_active', true)
                    ->first();
                
                if ($formula) {

                    $value = $this->evaluateFormula($formula, $productId);
                } else {

                    $dataSource = ['product_id' => $productId];
                    $filters = [];
                    $result = $this->chartDataService->getChartData('KPI', $configuration, $dataSource, $filters);
                    
                    if (isset($result['error'])) {
                        return $result;
                    }
                    
                    return $result;
                    }
                }
            }

            $format = $configuration['format'] ?? 'number';
            $precision = $configuration['precision'] ?? 2;
            
            // Don't format here - let frontend handle formatting based on type
            return [
                'value' => round($value, $precision),
                'format' => $format,
                'precision' => $precision,
                'color' => $configuration['color'] ?? '#007bff'
            ];
        } catch (\Exception $e) {
            return ['error' => 'KPI calculation failed: ' . $e->getMessage()];
        }
    }

    
    protected function getPieChartData(array $configuration, int $productId, array $filters = []): array
    {
        try {

            // Support both old format (group_by, metric) and new format (x_axis, y_axis)
            $groupBy = $configuration['group_by'] ?? $configuration['x_axis'] ?? 'sector';
            $metric = $configuration['metric'] ?? $configuration['y_axis'] ?? 'SUM(outstanding_balance)';
            $aggregation = $configuration['aggregation'] ?? 'SUM';
            

            $valueField = 'outstanding_balance'; // Default
            if (preg_match('/SUM\(([^)]+)\)/', $metric, $matches)) {
                $valueField = trim($matches[1]);
            } else {
                // If metric is just a field name (new format), use it directly
                $valueField = $metric;
            }
            

            $fieldMapping = [
                'amount' => 'outstanding_balance',
                'principal' => 'outstanding_balance', 
                'balance' => 'outstanding_balance',
                'value' => 'outstanding_balance',
                'exposure' => 'outstanding_balance',
                'principal_amount' => 'outstanding_balance'
            ];
            
            $valueField = $fieldMapping[$valueField] ?? $valueField;
            

            if ($groupBy === 'pd') {

                $query = ProductData::where('product_id', $productId)
                    ->whereNotNull("data->pd")
                    ->where("data->pd", '>', 0)
                    ->where("data->{$valueField}", '>', 0)
                    ->whereNotNull("data->{$valueField}");
                
                $selectClause = match($aggregation) {
                    'COUNT' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        COUNT(*) as value
                    "),
                    'AVG' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        ROUND(AVG(JSON_EXTRACT(data, '$.{$valueField}')), 2) as value
                    "),
                    'MAX' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        MAX(JSON_EXTRACT(data, '$.{$valueField}')) as value
                    "),
                    'MIN' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        MIN(JSON_EXTRACT(data, '$.{$valueField}')) as value
                    "),
                    'SUM' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        ROUND(SUM(JSON_EXTRACT(data, '$.{$valueField}')), 2) as value
                    "),
                    default => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        ROUND(SUM(JSON_EXTRACT(data, '$.{$valueField}')), 2) as value
                    ")
                };
                
                $data = $query->select($selectClause)
                    ->groupBy(DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END
                    "))
                    ->havingRaw('value > 0')
                    ->orderBy('value', 'desc')
                    ->get()
                    ->map(function($item) {
                        return [
                            'name' => $item->label,
                            'value' => (float) $item->value
                        ];
                    })
                    ->filter(function($item) {
                        return $item['value'] > 0 && !empty($item['name']);
                    })
                    ->values();

                return ['data' => $data->toArray()];
            }
            

            // Use manual PHP aggregation instead of JSON extraction
            $records = ProductData::where('product_id', $productId)->get();
            $aggregated = [];
            
            foreach ($records as $record) {
                $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
                
                if (isset($data[$groupBy]) && isset($data[$valueField])) {
                    $label = $data[$groupBy];
                    $value = (float) $data[$valueField];
                    
                    if (!isset($aggregated[$label])) {
                        $aggregated[$label] = ['count' => 0, 'sum' => 0, 'values' => []];
                    }
                    
                    $aggregated[$label]['count']++;
                    $aggregated[$label]['sum'] += $value;
                    $aggregated[$label]['values'][] = $value;
                }
            }
            
            // Convert aggregated data to the expected format
            $data = collect();
            foreach ($aggregated as $label => $stats) {
                $finalValue = match($aggregation) {
                    'COUNT' => $stats['count'],
                    'AVG' => $stats['count'] > 0 ? $stats['sum'] / $stats['count'] : 0,
                    'MAX' => max($stats['values']),
                    'MIN' => min($stats['values']),
                    default => $stats['sum'] // SUM
                };
                
                // Format the label
                $formattedLabel = trim($label);
                switch ($groupBy) {
                    case 'credit_rating':
                        $formattedLabel = strtoupper($formattedLabel);
                        break;
                    case 'branch_code':
                        $formattedLabel = strtoupper($formattedLabel);
                        break;
                    case 'currency':
                        $formattedLabel = strtoupper($formattedLabel);
                        break;
                    case 'sector':
                        $formattedLabel = ucwords(strtolower($formattedLabel));
                        break;
                    case 'collateral_type':
                        $formattedLabel = ucwords(str_replace('_', ' ', $formattedLabel));
                        break;
                    case 'amortization_type':
                        $formattedLabel = ucwords(str_replace('_', ' ', $formattedLabel));
                        break;
                    default:
                        $formattedLabel = ucwords(str_replace('_', ' ', $formattedLabel));
                }
                
                $data->push([
                    'label' => $formattedLabel,
                    'value' => (float) $finalValue
                ]);
            }
            
            // Filter and sort
            $data = $data->filter(function($item) {
                return $item['value'] > 0 && !empty($item['label']);
            })->sortByDesc('value')->values();

            return ['data' => $data->toArray()];
        } catch (\Exception $e) {
            return ['error' => 'Pie chart data failed: ' . $e->getMessage()];
        }
    }

    
    protected function getBarChartData(array $configuration, int $productId, array $filters = []): array
    {
        try {

            $xAxis = $configuration['x_axis'] ?? 'credit_rating';
            $yAxis = $configuration['y_axis'] ?? 'COUNT(*)';
            $aggregation = $configuration['aggregation'] ?? 'SUM';
            

            $valueField = 'outstanding_balance'; // Default
            if (preg_match('/SUM\(([^)]+)\)/', $yAxis, $matches)) {
                $valueField = trim($matches[1]);
            }
            

            // Handle special case for PD risk categorization
            if ($xAxis === 'pd') {
                $query = ProductData::where('product_id', $productId)
                    ->whereNotNull("data->pd")
                    ->where("data->pd", '>', 0)
                    ->where("data->{$valueField}", '>', 0)
                    ->whereNotNull("data->{$valueField}");
                
                $this->filterService->applyFilters($query, $filters);
                
                $selectClause = match($aggregation) {
                    'COUNT' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        COUNT(*) as value
                    "),
                    'AVG' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        ROUND(AVG(JSON_EXTRACT(data, '$.{$valueField}')), 2) as value
                    "),
                    'MAX' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        MAX(JSON_EXTRACT(data, '$.{$valueField}')) as value
                    "),
                    'MIN' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        MIN(JSON_EXTRACT(data, '$.{$valueField}')) as value
                    "),
                    'SUM' => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        ROUND(SUM(JSON_EXTRACT(data, '$.{$valueField}')), 2) as value
                    "),
                    default => DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END as label, 
                        ROUND(SUM(JSON_EXTRACT(data, '$.{$valueField}')), 2) as value
                    ")
                };
                
                $data = $query->select($selectClause)
                    ->groupBy(DB::raw("
                        CASE 
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.01 THEN 'Low Risk (≤1%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.05 THEN 'Medium Risk (1-5%)'
                            WHEN JSON_EXTRACT(data, '$.pd') <= 0.15 THEN 'High Risk (5-15%)'
                            ELSE 'Very High Risk (>15%)'
                        END
                    "))
                    ->havingRaw('value > 0')
                    ->orderBy('value', 'desc')
                    ->get();
            } else {
            // Use manual PHP aggregation instead of JSON extraction
            $records = ProductData::where('product_id', $productId)->get();
            $aggregated = [];
            
            foreach ($records as $record) {
                $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
                
                if (isset($data[$xAxis]) && isset($data[$valueField])) {
                    $label = $data[$xAxis];
                    $value = (float) $data[$valueField];
                    
                    if (!isset($aggregated[$label])) {
                        $aggregated[$label] = ['count' => 0, 'sum' => 0, 'values' => []];
                    }
                    
                    $aggregated[$label]['count']++;
                    $aggregated[$label]['sum'] += $value;
                    $aggregated[$label]['values'][] = $value;
                }
            }
            
            // Convert aggregated data to the expected format
            $data = collect();
            foreach ($aggregated as $label => $stats) {
                $finalValue = match($aggregation) {
                    'COUNT' => $stats['count'],
                    'AVG' => $stats['count'] > 0 ? $stats['sum'] / $stats['count'] : 0,
                    'MAX' => max($stats['values']),
                    'MIN' => min($stats['values']),
                    default => $stats['sum'] // SUM
                };
                
                $data->push((object) [
                    'label' => $label,
                    'value' => (float) $finalValue
                ]);
            }
            
            // Sort by value descending
            $data = $data->sortByDesc('value');
            }

            $chartData = $data->map(function($item) {
                $label = trim($item->label, '"');
                return [
                    'name' => $label,
                    'value' => (float) $item->value
                ];
            })->filter(function($item) {
                return !empty($item['name']) && $item['name'] !== 'null' && $item['name'] !== 'NULL';
            });

            return ['data' => $chartData->values()->toArray()];
        } catch (\Exception $e) {
            return ['error' => 'Bar chart data failed: ' . $e->getMessage()];
        }
    }

    
    protected function getLineChartData(array $configuration, int $productId, array $filters = []): array
    {
        try {

            $xAxis = $configuration['x_axis'] ?? 'disbursement_date';
            $yAxis = $configuration['y_axis'] ?? 'SUM(outstanding_balance)';
            $aggregation = $configuration['aggregation'] ?? 'SUM';
            $dateFormat = $configuration['date_format'] ?? 'Y-m';
            

            $valueField = 'outstanding_balance'; // Default
            if (preg_match('/SUM\(([^)]+)\)/', $yAxis, $matches)) {
                $valueField = trim($matches[1]);
            }
            

            $fieldMapping = [
                'amount' => 'outstanding_balance',
                'principal' => 'outstanding_balance', 
                'balance' => 'outstanding_balance',
                'value' => 'outstanding_balance',
                'exposure' => 'outstanding_balance',
                'principal_amount' => 'outstanding_balance'
            ];
            
            $valueField = $fieldMapping[$valueField] ?? $valueField;
            

            // Use manual PHP aggregation instead of JSON extraction
            $allData = ProductData::where('product_id', $productId)->get();
            
            $groupedData = $allData->groupBy(function($item) use ($xAxis, $dateFormat) {
                $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                $date = $data[$xAxis] ?? null;
                if ($date) {
                    try {
                        $dateObj = \Carbon\Carbon::parse($date);
                        return $dateObj->format('Y-m');
                    } catch (\Exception $e) {
                        return date('Y-m', strtotime($item->created_at));
                    }
                }
                return date('Y-m', strtotime($item->created_at));
            });
            
            $data = $groupedData->map(function($group, $month) use ($valueField, $aggregation) {
                $value = match($aggregation) {
                    'COUNT' => $group->count(),
                    'AVG' => $group->avg(function($item) use ($valueField) {
                        $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                        return (float)($data[$valueField] ?? 0);
                    }),
                    'MAX' => $group->max(function($item) use ($valueField) {
                        $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                        return (float)($data[$valueField] ?? 0);
                    }),
                    'MIN' => $group->min(function($item) use ($valueField) {
                        $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                        return (float)($data[$valueField] ?? 0);
                    }),
                    default => $group->sum(function($item) use ($valueField) {
                        $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                        return (float)($data[$valueField] ?? 0);
                    })
                };
                
                return [
                    'name' => $month,
                    'value' => (float) $value
                ];
            })->sortBy('name')->values();

            return ['data' => $data->toArray()];
        } catch (\Exception $e) {
            return ['error' => 'Line chart data failed: ' . $e->getMessage()];
        }
    }

    
    protected function getHeatmapData(array $configuration, int $productId, array $filters = []): array
    {
        $xAxis = $configuration['x_axis'] ?? 'branch_code';
        $yAxis = $configuration['y_axis'] ?? 'sector';
        
        try {
            // Get all data and process in PHP to avoid JSON extraction issues
            $query = ProductData::where('product_id', $productId)
                ->where('amount', '>', 0);
                
            $this->filterService->applyFilters($query, $filters);
            
            $records = $query->get();
            
            // Group data manually in PHP
            $groupedData = [];
            foreach ($records as $record) {
                $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
                
                $xValue = $data[$xAxis] ?? null;
                $yValue = $data[$yAxis] ?? null;
                
                // Skip records with missing or invalid values
                if (empty($xValue) || empty($yValue) || 
                    $xValue === 'null' || $yValue === 'null' ||
                    $xValue === null || $yValue === null) {
                    continue;
                }
                
                $key = $xValue . '|' . $yValue;
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'x' => $xValue,
                        'y' => $yValue,
                        'value' => 0
                    ];
                }
                $groupedData[$key]['value'] += $record->amount;
            }
            
            // Convert to array and filter out zero values
            $chartData = array_values(array_filter($groupedData, function($item) {
                return $item['value'] > 0;
            }));

            return ['data' => $chartData];
        } catch (\Exception $e) {
            return ['error' => 'Heatmap data failed: ' . $e->getMessage()];
        }
    }

    protected function hasAggregationFunctions(array $columns): bool
    {
        $aggregationFunctions = ['COUNT(', 'SUM(', 'AVG(', 'MAX(', 'MIN('];
        foreach ($columns as $column) {
            foreach ($aggregationFunctions as $func) {
                if (strpos($column, $func) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function getAggregatedTableData(array $configuration, int $productId, array $filters = []): array
    {
        $columns = $configuration['columns'] ?? [];
        $groupBy = $configuration['group_by'] ?? null;
        $limit = $configuration['limit'] ?? 50;
        
        try {
            $query = ProductData::where('product_id', $productId);
            $this->filterService->applyFilters($query, $filters);
            
            // Build SELECT clause with proper field mappings
            $selectClause = [];
            foreach ($columns as $column) {
                if ($column === $groupBy) {
                    $selectClause[] = DB::raw("JSON_EXTRACT(data, '$.{$column}') as `{$column}`");
                } elseif (strpos($column, 'COUNT(*)') !== false) {
                    $selectClause[] = DB::raw("COUNT(*) as `COUNT(*)`");
                } elseif (strpos($column, 'SUM(amount)') !== false) {
                    $selectClause[] = DB::raw("SUM(amount) as `SUM(amount)`");
                } elseif (strpos($column, 'AVG(amount)') !== false) {
                    $selectClause[] = DB::raw("AVG(amount) as `AVG(amount)`");
                } elseif (strpos($column, 'AVG(interest_rate_annual)') !== false) {
                    $selectClause[] = DB::raw("AVG(JSON_EXTRACT(data, '$.interest_rate_annual')) as `AVG(interest_rate_annual)`");
                } elseif (strpos($column, 'AVG(pd)') !== false) {
                    $selectClause[] = DB::raw("AVG(JSON_EXTRACT(data, '$.pd')) as `AVG(pd)`");
                } elseif (strpos($column, 'AVG(lgd)') !== false) {
                    $selectClause[] = DB::raw("AVG(JSON_EXTRACT(data, '$.lgd')) as `AVG(lgd)`");
                } elseif (strpos($column, 'SUM(pd * lgd * ead)') !== false) {
                    $selectClause[] = DB::raw("SUM(JSON_EXTRACT(data, '$.pd') * JSON_EXTRACT(data, '$.lgd') * JSON_EXTRACT(data, '$.ead')) as `SUM(pd * lgd * ead)`");
                } elseif (strpos($column, 'SUM(CASE WHEN days_past_due >= 90 THEN amount ELSE 0 END)') !== false) {
                    $selectClause[] = DB::raw("SUM(CASE WHEN JSON_EXTRACT(data, '$.days_past_due') >= 90 THEN amount ELSE 0 END) as `NPL_Amount`");
                } else {
                    $selectClause[] = DB::raw("JSON_EXTRACT(data, '$.{$column}') as `{$column}`");
                }
            }
            
            $query->select($selectClause);
            
            if ($groupBy) {
                $query->groupBy(DB::raw("JSON_EXTRACT(data, '$.{$groupBy}')"));
            }
            
            $data = $query->limit($limit)->get()
                ->map(function($item) use ($columns) {
                    $row = [];
                    foreach ($columns as $column) {
                        $key = $column;
                        if (strpos($column, 'SUM(CASE WHEN days_past_due >= 90 THEN amount ELSE 0 END)') !== false) {
                            $key = 'NPL_Amount';
                        }
                        $value = $item->$key ?? null;
                        
                        // Format numeric values
                        if (is_numeric($value)) {
                            $value = number_format((float)$value, 2);
                        }
                        
                        $row[$column] = $value;
                    }
                    return $row;
                });

            return [
                'data' => $data->toArray(),
                'columns' => $columns,
                'total' => $data->count()
            ];
        } catch (\Exception $e) {
            return ['error' => 'Aggregated table data failed: ' . $e->getMessage()];
        }
    }

    
    protected function getTableData(array $configuration, int $productId, array $filters = []): array
    {
        $columns = $configuration['columns'] ?? ['loan_id', 'customer_id', 'outstanding_balance'];
        $limit = $configuration['limit'] ?? 50;
        $groupBy = $configuration['group_by'] ?? null;
        
        try {
            // Check if this is an aggregated table (has group_by or contains aggregation functions)
            $isAggregated = $groupBy || $this->hasAggregationFunctions($columns);
            
            if ($isAggregated) {
                return $this->getAggregatedTableData($configuration, $productId, $filters);
            }
            
            $query = ProductData::where('product_id', $productId);
            $this->filterService->applyFilters($query, $filters);
            $data = $query->limit($limit)->get()
                ->map(function($item) use ($columns) {
                    $row = [];

                    // Properly decode the JSON data
                    $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;
                    
                    if (!is_array($data)) {
                        \Log::error('ProductData data field is not an array', [
                            'item_id' => $item->id,
                            'data_type' => gettype($data),
                            'data_value' => $data
                        ]);
                        $data = [];
                    }
                    
                    foreach ($columns as $column) {
                        $columnKey = is_string($column) ? $column : (string) $column;
                        
                        // Handle special case for amount column
                        if ($columnKey === 'amount') {
                            $value = $item->amount; // Use the actual amount column from database
                        } else {
                            $value = isset($data[$columnKey]) ? $data[$columnKey] : null;
                        }
                        
                        if (is_array($value) || is_object($value)) {
                            $value = json_encode($value);
                        }
                        
                        $row[$columnKey] = $value;
                    }
                    

                    if (isset($data['customer_id'])) {
                        $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])->first();
                        $row['customer_name'] = $customer ? $customer->name : 'Unknown Customer';
                    }
                    
                    return $row;
                });


            $dataArray = [];
            foreach ($data as $row) {
                $dataArray[] = $row;
            }
            

            $finalColumns = $columns;
            if (in_array('customer_id', $columns) && !in_array('customer_name', $columns)) {
                $customerIdIndex = array_search('customer_id', $finalColumns);
                array_splice($finalColumns, $customerIdIndex + 1, 0, 'customer_name');
            }
            
            return [
                'data' => $dataArray,
                'columns' => $finalColumns,
                'total' => ProductData::where('product_id', $productId)->count()
            ];
        } catch (\Exception $e) {
            \Log::error('Table data error details', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'Table data failed: ' . $e->getMessage()];
        }
    }

    
    public function getWidgetDataById(Request $request, $dashboard, Widget $widget): JsonResponse
    {
        try {
            $filters = $request->get('filters', []);
            $data = $this->getWidgetData($widget, $filters);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading widget data: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function getFilterOptions(Dashboard $dashboard): JsonResponse
    {
        try {

            $productId = $dashboard->product_id ?? 7;
            
            $filterOptions = $this->filterService->getFilterOptions($productId);
            
            return response()->json([
                'success' => true,
                'data' => $filterOptions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading filter options: ' . $e->getMessage()
            ], 500);
        }
    }

    
    protected function evaluateFormula(\App\Models\Formula $formula, int $productId): float
    {
        try {
            // Handle special formula names that need custom calculation
            $formulaName = $formula->name;
            
            if ($formulaName === 'NPL Ratio') {
                return $this->calculateNPLRatio($productId, []);
            } elseif (in_array($formulaName, ['High Risk Loans Count', 'Medium Risk Loans Count', 'Low Risk Loans Count'])) {
                return $this->calculateRiskCount($productId, $formulaName);
            } elseif (in_array($formulaName, ['Agriculture Sector Loans', 'Manufacturing Sector Loans', 'Services Sector Loans', 'Trade Sector Loans', 'Construction Sector Loans', 'Technology Sector Loans'])) {
                return $this->calculateSectorCount($productId, $formulaName);
            } elseif (in_array($formulaName, ['Working Capital Loans', 'Equipment Purchase Loans', 'Business Expansion Loans', 'Inventory Loans', 'Real Estate Loans'])) {
                return $this->calculatePurposeCount($productId, $formulaName);
            } elseif (in_array($formulaName, ['Monthly Repayment Loans', 'Quarterly Repayment Loans', 'Semi-Annual Repayment Loans', 'Annual Repayment Loans'])) {
                return $this->calculateRepaymentCount($productId, $formulaName);
            } elseif (in_array($formulaName, ['Secured Loans Count', 'Unsecured Loans Count', 'Personal Guarantee Loans', 'Group Guarantee Loans'])) {
                return $this->calculateGuaranteeCount($productId, $formulaName);
            }
            
            $expression = $formula->expression;
            
            // Use SimpleFormulaEvaluator for proper JSON field handling
            $evaluator = app(\App\Services\SimpleFormulaEvaluator::class);
            return $evaluator->evaluate($expression, $productId);
            
        } catch (\Exception $e) {
            \Log::error('Formula evaluation failed', [
                'formula_id' => $formula->id,
                'expression' => $formula->expression,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }

    
    protected function evaluateSum($query, string $field): float
    {
        if (str_contains($field, '-')) {

            $parts = explode('-', $field);
            $field1 = trim($parts[0]);
            $field2 = trim($parts[1]);
            
            $allData = $query->get();
            return $allData->sum(function($item) use ($field1, $field2) {
                $data = $item->data;
                $value1 = $data[$field1] ?? 0;
                $value2 = $data[$field2] ?? 0;
                return $value1 - $value2;
            });
        }
        
        if (str_contains($field, '*')) {

            $parts = explode('*', $field);
            $allData = $query->get();
            return $allData->sum(function($item) use ($parts) {
                $data = $item->data;
                $result = 1;
                foreach ($parts as $part) {
                    $result *= $data[trim($part)] ?? 0;
                }
                return $result;
            });
        }
        
        if (str_contains($field, 'CASE WHEN')) {

            $allData = $query->get();
            return $allData->sum(function($item) use ($field) {
                $data = $item->data;
                

                if (preg_match('/CASE WHEN status="([^"]+)" THEN ([^ ]+) ELSE ([^ ]+) END/', $field, $matches)) {
                    $statusValue = $matches[1];
                    $thenField = $matches[2];
                    $elseValue = (float) $matches[3];
                    
                    $status = $data['status'] ?? '';
                    if ($status === $statusValue) {
                        return $data[$thenField] ?? 0;
                    } else {
                        return $elseValue;
                    }
                }
                
                return 0;
            });
        }
        

        $allData = $query->get();
        return $allData->sum(function($item) use ($field) {
            return $item->data[$field] ?? 0;
        });
    }

    
    protected function evaluateAvg($query, string $field): float
    {
        $allData = $query->get();
        return $allData->avg(function($item) use ($field) {
            return $item->data[$field] ?? 0;
        });
    }

    
    protected function evaluateComplexExpression(string $expression, $allData): float
    {
        try {

            return $this->parseAndEvaluateExpression($expression, $allData);
        } catch (\Exception $e) {
            \Log::error('Complex expression evaluation failed', [
                'expression' => $expression,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }

    
    protected function parseAndEvaluateExpression(string $expression, $allData): float
    {

        $expression = $this->cleanParentheses($expression);
        

        if (str_contains($expression, ' / ') && str_contains($expression, ' * 100')) {
            $divisionPos = $this->findDivisionPosition($expression);
            if ($divisionPos !== false) {
                $numeratorExpr = trim(substr($expression, 0, $divisionPos));
                $denominatorExpr = trim(substr($expression, $divisionPos + 3));
                $denominatorExpr = trim(str_replace(' * 100', '', $denominatorExpr));
                

                if (str_contains($denominatorExpr, 'NULLIF(')) {
                    $denominatorExpr = $this->parseNullIfExpression($denominatorExpr);
                }
                
                $numerator = $this->evaluateSimpleExpression($numeratorExpr, $allData);
                $denominator = $this->evaluateSimpleExpression($denominatorExpr, $allData);
                
                return $denominator > 0 ? ($numerator / $denominator) * 100 : 0;
            }
        }
        

        return $this->evaluateSimpleExpression($expression, $allData);
    }

    
    protected function parseNullIfExpression(string $expression): string
    {


        if (preg_match('/NULLIF\(([^,]+),\s*0\)/', $expression, $matches)) {
            return trim($matches[1]);
        }
        return $expression;
    }

    
    protected function cleanParentheses(string $expression): string
    {
        $expression = trim($expression);
        

        if ($expression[0] === '(' && $expression[-1] === ')') {
            $inner = substr($expression, 1, -1);

            $openCount = 0;
            for ($i = 0; $i < strlen($inner); $i++) {
                if ($inner[$i] === '(') $openCount++;
                if ($inner[$i] === ')') $openCount--;
                if ($openCount < 0) return $expression; // Unbalanced, don't remove
            }
            if ($openCount === 0) return $inner; // Balanced, safe to remove
        }
        
        return $expression;
    }

    
    protected function findDivisionPosition(string $expression): int|false
    {
        $openCount = 0;
        $len = strlen($expression);
        
        for ($i = 0; $i < $len - 2; $i++) {
            if ($expression[$i] === '(') $openCount++;
            if ($expression[$i] === ')') $openCount--;
            


            if (($openCount === 0 || $openCount === 1) && substr($expression, $i, 3) === ' / ') {
                return $i;
            }
        }
        
        return false;
    }

    
    protected function evaluateSimpleExpression(string $expression, $allData): float
    {
        $expression = trim($expression);
        

        if (preg_match('/SUM\(([^)]+)\)/', $expression, $matches)) {
            return $this->evaluateSumExpression($matches[1], $allData);
        }
        

        if (preg_match('/AVG\(([^)]+)\)/', $expression, $matches)) {
            return $this->evaluateAvgExpression($matches[1], $allData);
        }
        

        if (preg_match('/COUNT\(\*\)/', $expression)) {
            return (float) $allData->count();
        }
        

        if (preg_match('/COUNT\(CASE WHEN ([^)]+)\)/', $expression, $matches)) {
            return $this->evaluateCountCaseWhenExpression($matches[1], $allData);
        }
        

        if (str_contains($expression, '+')) {
            $parts = explode('+', $expression);
            $sum = 0;
            foreach ($parts as $part) {
                $part = trim($part);

                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $part)) {
                    $sum += $allData->sum(function($item) use ($part) {
                        return $item->data[$part] ?? 0;
                    });
                } else {
                    $sum += $this->evaluateSimpleExpression($part, $allData);
                }
            }
            return $sum;
        }
        
        return 0.0;
    }

    
    protected function evaluateSumExpression(string $field, $allData): float
    {

        if (str_contains($field, 'CASE WHEN')) {
            return $this->evaluateCaseWhenExpression($field, $allData);
        }
        

        if (str_contains($field, '+')) {
            $parts = explode('+', $field);
            return $allData->sum(function($item) use ($parts) {
                $data = $item->data;
                $sum = 0;
                foreach ($parts as $part) {
                    $sum += $data[trim($part)] ?? 0;
                }
                return $sum;
            });
        }
        

        if (str_contains($field, '-')) {
            $parts = explode('-', $field);
            $field1 = trim($parts[0]);
            $field2 = trim($parts[1]);
            
            return $allData->sum(function($item) use ($field1, $field2) {
                $data = $item->data;
                return ($data[$field1] ?? 0) - ($data[$field2] ?? 0);
            });
        }
        

        if (str_contains($field, '*')) {
            $parts = explode('*', $field);
            return $allData->sum(function($item) use ($parts) {
                $data = $item->data;
                $result = 1;
                foreach ($parts as $part) {
                    $result *= $data[trim($part)] ?? 0;
                }
                return $result;
            });
        }
        

        return $allData->sum(function($item) use ($field) {
            return $item->data[$field] ?? 0;
        });
    }

    
    protected function evaluateCaseWhenExpression(string $caseExpr, $allData): float
    {

        if (preg_match('/CASE WHEN status="([^"]+)" THEN ([^ ]+) ELSE ([^ ]+) END/', $caseExpr, $matches)) {
            $statusValue = $matches[1];
            $thenField = $matches[2];
            $elseValue = (float) $matches[3];
            
            return $allData->sum(function($item) use ($statusValue, $thenField, $elseValue) {
                $data = $item->data;
                $status = $data['status'] ?? '';
                
                if ($status === $statusValue) {
                    return $data[$thenField] ?? 0;
                } else {
                    return $elseValue;
                }
            });
        }
        
        return 0.0;
    }

    
    protected function evaluateAvgExpression(string $field, $allData): float
    {
        return $allData->avg(function($item) use ($field) {
            return $item->data[$field] ?? 0;
        });
    }

    
    protected function evaluateCountCaseWhenExpression(string $caseExpr, $allData): float
    {

        if (preg_match('/status="([^"]+)" THEN 1/', $caseExpr, $matches)) {
            $statusValue = $matches[1];
            return (float) $allData->where('data.status', $statusValue)->count();
        }
        
        return 0.0;
    }

    
    protected function getCrossProductData(string $widgetType, array $configuration, array $productIds): array
    {
        try {
            switch ($widgetType) {
                case 'KPI':
                    return $this->getCrossProductKpiData($configuration, $productIds);
                case 'Table':
                    return $this->getCrossProductTableData($configuration, $productIds);
                case 'PieChart':
                    return $this->getCrossProductPieChartData($configuration, $productIds);
                case 'BarChart':
                    return $this->getCrossProductBarChartData($configuration, $productIds);
                case 'LineChart':
                    return $this->getCrossProductLineChartData($configuration, $productIds);
                default:
                    return ['error' => 'Cross-product data not supported for widget type: ' . $widgetType];
            }
        } catch (\Exception $e) {
            return ['error' => 'Cross-product data failed: ' . $e->getMessage()];
        }
    }

    
    protected function getCrossProductKpiData(array $configuration, array $productIds): array
    {
        $metric = $configuration['metric'] ?? 'COUNT(*)';
        

        $allData = ProductData::whereIn('product_id', $productIds)->get();
        

        $value = $this->evaluateFormulaFromExpression($metric, $allData);
        
        $format = $configuration['format'] ?? 'number';
        $precision = $configuration['precision'] ?? 2;
        $prefix = $configuration['prefix'] ?? '';
        $suffix = $configuration['suffix'] ?? '';

        // Only apply automatic formatting if no custom prefix/suffix is specified
        if ($prefix === '' && $suffix === '') {
            $formattedValue = match($format) {
                'currency' => $this->currencyService->formatAmount($value),
                'percentage' => number_format($value, $precision) . '%',
                'decimal' => number_format($value, $precision),
                default => number_format($value, $precision)
            };
        } else {
            // Use custom prefix/suffix or just the formatted number
            $baseValue = match($format) {
                'percentage' => number_format($value * 100, $precision), // Convert decimal to percentage
                'decimal' => number_format($value, $precision),
                default => number_format($value, $precision)
            };
            $formattedValue = $prefix . $baseValue . $suffix;
        }

        return [
            'value' => $value,
            'formatted_value' => $formattedValue,
            'format' => $format,
            'color' => $configuration['color'] ?? 'primary'
        ];
    }

    
    protected function getCrossProductPieChartData(array $configuration, array $productIds): array
    {
        $groupBy = $configuration['group_by'] ?? 'product_name';
        $valueField = $configuration['value_field'] ?? 'principal_amount';
        $aggregation = $configuration['aggregation'] ?? 'SUM';
        

        $products = \App\Models\Product::whereIn('id', $productIds)->pluck('name', 'id');
        
        $data = [];
        foreach ($productIds as $productId) {
            $productData = ProductData::where('product_id', $productId)->get();
            $productName = $products[$productId] ?? "Product {$productId}";
            
            $value = match($aggregation) {
                'COUNT' => $productData->count(),
                'AVG' => $productData->avg(function($item) use ($valueField) {
                    return $item->data[$valueField] ?? 0;
                }),
                'MAX' => $productData->max(function($item) use ($valueField) {
                    return $item->data[$valueField] ?? 0;
                }),
                'MIN' => $productData->min(function($item) use ($valueField) {
                    return $item->data[$valueField] ?? 0;
                }),
                default => $productData->sum(function($item) use ($valueField) {
                    return $item->data[$valueField] ?? 0;
                })
            };
            
            $data[] = [
                'label' => $productName,
                'value' => (float) $value
            ];
        }

        return ['data' => $data];
    }

    
    protected function getCrossProductBarChartData(array $configuration, array $productIds): array
    {
        $xAxis = $configuration['x_axis'] ?? 'credit_rating';
        $yAxis = $configuration['y_axis'] ?? 'COUNT(*)';
        $aggregation = $configuration['aggregation'] ?? 'COUNT';
        

        $allData = ProductData::whereIn('product_id', $productIds)->get();
        

        $groupedData = $allData->groupBy(function($item) use ($xAxis) {
            return $item->data[$xAxis] ?? 'Unknown';
        });
        
        $chartData = [];
        foreach ($groupedData as $group => $items) {
            $value = match($aggregation) {
                'COUNT' => $items->count(),
                'AVG' => $items->avg(function($item) use ($yAxis) {
                    return $item->data[$yAxis] ?? 0;
                }),
                'MAX' => $items->max(function($item) use ($yAxis) {
                    return $item->data[$yAxis] ?? 0;
                }),
                'MIN' => $items->min(function($item) use ($yAxis) {
                    return $item->data[$yAxis] ?? 0;
                }),
                default => $items->sum(function($item) use ($yAxis) {
                    return $item->data[$yAxis] ?? 0;
                })
            };
            
            $chartData[] = [
                'x' => $group,
                'y' => (float) $value
            ];
        }

        return ['data' => $chartData];
    }

    
    protected function getCrossProductTableData(array $configuration, array $productIds): array
    {
        $columns = $configuration['columns'] ?? ['sector', 'loan_count', 'total_value', 'average_size', 'npl_count', 'npl_ratio'];
        

        $allData = ProductData::whereIn('product_id', $productIds)->get();
        

        $groupedData = $allData->groupBy(function($item) {
            return $item->data['sector'] ?? 'Unknown';
        });
        
        $tableData = [];
        foreach ($groupedData as $sector => $items) {
            $loanCount = $items->count();
            $totalValue = $items->sum(function($item) {
                return $item->data['principal_amount'] ?? 0;
            });
            $averageSize = $loanCount > 0 ? $totalValue / $loanCount : 0;
            $nplCount = $items->where('data.status', 'NPL')->count();
            $nplRatio = $loanCount > 0 ? ($nplCount / $loanCount) * 100 : 0;
            
            $row = [];
            foreach ($columns as $column) {
                switch ($column) {
                    case 'sector':
                        $row[$column] = $sector;
                        break;
                    case 'loan_count':
                        $row[$column] = $loanCount;
                        break;
                    case 'total_value':
                        $row[$column] = $totalValue;
                        break;
                    case 'average_size':
                        $row[$column] = $averageSize;
                        break;
                    case 'npl_count':
                        $row[$column] = $nplCount;
                        break;
                    case 'npl_ratio':
                        $row[$column] = $nplRatio;
                        break;
                    default:
                        $row[$column] = 0;
                }
            }
            $tableData[] = $row;
        }

        return [
            'data' => $tableData,
            'columns' => $columns
        ];
    }

    
    protected function getCrossProductLineChartData(array $configuration, array $productIds): array
    {
        $xAxis = $configuration['x_axis'] ?? 'disbursement_month';
        $yAxis = $configuration['y_axis'] ?? 'SUM(principal_amount)';
        $aggregation = $configuration['aggregation'] ?? 'SUM';
        

        $allData = ProductData::whereIn('product_id', $productIds)->get();
        

        $groupedData = $allData->groupBy(function($item) use ($xAxis) {
            $date = $item->data['disbursement_date'] ?? $item->created_at;
            return date('Y-m', strtotime($date));
        });
        
        $chartData = [];
        foreach ($groupedData as $month => $items) {
            $value = match($aggregation) {
                'COUNT' => $items->count(),
                'AVG' => $items->avg(function($item) use ($yAxis) {
                    return $item->data[str_replace('SUM(', '', str_replace(')', '', $yAxis))] ?? 0;
                }),
                'MAX' => $items->max(function($item) use ($yAxis) {
                    return $item->data[str_replace('SUM(', '', str_replace(')', '', $yAxis))] ?? 0;
                }),
                'MIN' => $items->min(function($item) use ($yAxis) {
                    return $item->data[str_replace('SUM(', '', str_replace(')', '', $yAxis))] ?? 0;
                }),
                default => $items->sum(function($item) use ($yAxis) {
                    $field = str_replace('SUM(', '', str_replace(')', '', $yAxis));
                    return $item->data[$field] ?? 0;
                })
            };
            
            $chartData[] = [
                'x' => $month,
                'y' => (float) $value
            ];
        }


        usort($chartData, function($a, $b) {
            return strcmp($a['x'], $b['x']);
        });

        return ['data' => $chartData];
    }

    
    protected function evaluateFormulaFromExpression(string $expression, $allData): float
    {

        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('parseAndEvaluateExpression');
        $method->setAccessible(true);
        
        return $method->invoke($this, $expression, $allData);
    }

    
    private function calculateNPLRatio(int $productId, array $filters = []): float
    {
        $query = \App\Models\ProductData::where('product_id', $productId);
        $this->filterService->applyFilters($query, $filters);
        $records = $query->get();
        
        // Check if we have npl_status field (microfinance data)
        $hasNplStatus = false;
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            if (isset($data['npl_status'])) {
                $hasNplStatus = true;
                break;
            }
        }
        
        if ($hasNplStatus) {
            // Use npl_status field for microfinance data
            $nplCount = 0;
            $totalCount = 0;
            
            foreach ($records as $record) {
                $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
                if (isset($data['npl_status'])) {
                    $totalCount++;
                    if ($data['npl_status'] === 'NPL') {
                        $nplCount++;
                    }
                }
            }
            
            return $totalCount > 0 ? ($nplCount / $totalCount) * 100 : 0;
        } else {
            // Use days_past_due field for traditional data
            $totalOutstanding = $records->sum(function($record) {
                return $record->data['outstanding_balance'] ?? 0;
            });
            
            $nplOutstanding = $records->sum(function($record) {
                $daysPastDue = $record->data['days_past_due'] ?? 0;
                $outstanding = $record->data['outstanding_balance'] ?? 0;
                return $daysPastDue >= 90 ? $outstanding : 0;
            });
            
            return $totalOutstanding > 0 ? ($nplOutstanding / $totalOutstanding) * 100 : 0;
        }
    }

    
    private function calculateDefaultRate(int $productId, array $filters = []): float
    {
        $query = \App\Models\ProductData::where('product_id', $productId);
        $this->filterService->applyFilters($query, $filters);
        $records = $query->get();
        
        $totalOutstanding = $records->sum(function($record) {
            return $record->data['outstanding_balance'] ?? 0;
        });
        
        $defaultOutstanding = $records->sum(function($record) {
            $daysPastDue = $record->data['days_past_due'] ?? 0;
            $outstanding = $record->data['outstanding_balance'] ?? 0;
            return $daysPastDue >= 30 ? $outstanding : 0;
        });
        
        return $totalOutstanding > 0 ? ($defaultOutstanding / $totalOutstanding) * 100 : 0;
    }

    
    private function calculateCAR(int $productId, array $filters = []): float
    {
        $query = \App\Models\ProductData::where('product_id', $productId);
        $this->filterService->applyFilters($query, $filters);
        $records = $query->get();
        
        return $records->sum(function($record) {
            $ead = $record->data['ead'] ?? 0;
            $riskWeight = $record->data['risk_weight'] ?? 0;
            return $ead * ($riskWeight / 100);
        });
    }


    /**
     * Calculate risk count for a product
     */
    private function calculateRiskCount(int $productId, string $formulaName): float
    {
        $riskType = str_replace(' Loans Count', '', $formulaName);
        $riskType = str_replace(' Risk', '', $riskType);
        
        $query = \App\Models\ProductData::where('product_id', $productId);
        $records = $query->get();
        
        $count = 0;
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            if (isset($data['risk_rating']) && $data['risk_rating'] === $riskType) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Calculate sector count for a product
     */
    private function calculateSectorCount(int $productId, string $formulaName): float
    {
        $sectorType = str_replace(' Sector Loans', '', $formulaName);
        
        $query = \App\Models\ProductData::where('product_id', $productId);
        $records = $query->get();
        
        $count = 0;
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            if (isset($data['sector']) && $data['sector'] === $sectorType) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Calculate purpose count for a product
     */
    private function calculatePurposeCount(int $productId, string $formulaName): float
    {
        $purposeType = str_replace(' Loans', '', $formulaName);
        
        $query = \App\Models\ProductData::where('product_id', $productId);
        $records = $query->get();
        
        $count = 0;
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            if (isset($data['loan_purpose']) && $data['loan_purpose'] === $purposeType) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Calculate repayment count for a product
     */
    private function calculateRepaymentCount(int $productId, string $formulaName): float
    {
        $repaymentType = str_replace(' Repayment Loans', '', $formulaName);
        
        $query = \App\Models\ProductData::where('product_id', $productId);
        $records = $query->get();
        
        $count = 0;
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            if (isset($data['repayment_frequency']) && $data['repayment_frequency'] === $repaymentType) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Calculate guarantee count for a product
     */
    private function calculateGuaranteeCount(int $productId, string $formulaName): float
    {
        $guaranteeType = str_replace(' Loans Count', '', $formulaName);
        $guaranteeType = str_replace(' Loans', '', $guaranteeType);
        
        $query = \App\Models\ProductData::where('product_id', $productId);
        $records = $query->get();
        
        $count = 0;
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            if (isset($data['guarantee_type']) && $data['guarantee_type'] === $guaranteeType) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get all widgets for a dashboard with their data
     */
    public function getDashboardWidgets(Request $request, Dashboard $dashboard): JsonResponse
    {
        try {
            $widgets = $dashboard->widgets;
            
            $widgetData = [];
            foreach ($widgets as $widget) {
                $widgetData[] = [
                    'id' => $widget->id,
                    'name' => $widget->name,
                    'type' => $widget->type,
                    'configuration' => $widget->configuration,
                    'data' => $this->getWidgetData($widget, $request->get('filters', []))
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'dashboard_id' => $dashboard->id,
                    'dashboard_name' => $dashboard->name,
                    'widgets' => $widgetData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard widgets: ' . $e->getMessage()
            ], 500);
        }
    }

}




