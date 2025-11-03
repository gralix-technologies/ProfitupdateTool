<?php

namespace App\Services;

use App\Models\ProductData;
use App\Services\FormulaEngine;
use App\Services\SimpleFormulaEvaluator;
use Illuminate\Support\Facades\DB;

class ChartDataService
{
    protected $formulaEngine;
    protected $simpleEvaluator;

    public function __construct()
    {
        $this->simpleEvaluator = new SimpleFormulaEvaluator();
        $this->formulaEngine = null;
    }

    public function getChartData(string $chartType, array $configuration, array $dataSource, array $filters = []): array
    {
        try {
            switch (strtolower($chartType)) {
                case 'kpi':
                    return $this->getKPIData($configuration, $dataSource, $filters);
                case 'linechart':
                    return $this->getLineChartData($configuration, $dataSource, $filters);
                case 'piechart':
                    return $this->getPieChartData($configuration, $dataSource, $filters);
                case 'barchart':
                    return $this->getBarChartData($configuration, $dataSource, $filters);
                case 'heatmap':
                    return $this->getHeatmapData($configuration, $dataSource, $filters);
                case 'table':
                    return $this->getTableData($configuration, $dataSource, $filters);
                default:
                    return ['error' => "Unsupported chart type: {$chartType}"];
            }
        } catch (\Exception $e) {
            \Log::error('Chart data generation failed', [
                'chart_type' => $chartType,
                'configuration' => $configuration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['error' => 'Failed to generate chart data'];
        }
    }

    
    protected function getKPIData(array $configuration, array $dataSource, array $filters): array
    {
        $metric = $configuration['metric'] ?? null;
        if (!$metric || !isset($dataSource['product_id'])) {
            return ['value' => 0, 'formatted_value' => '0'];
        }

        $query = $this->buildBaseQuery($dataSource['product_id'], $filters);
        $value = $this->calculateMetric($query, $metric);
        
        $format = $configuration['format'] ?? 'number';
        $precision = $configuration['precision'] ?? 2;
        $prefix = $configuration['prefix'] ?? '';
        $suffix = $configuration['suffix'] ?? '';
        
        // Only apply automatic formatting if no custom prefix/suffix is specified
        if ($prefix === '' && $suffix === '') {
            $formattedValue = match($format) {
                'currency' => 'ZMW' . number_format($value, $precision),
                'percentage' => number_format($value, $precision) . '%',
                default => number_format($value, $precision)
            };
        } else {
            // Use custom prefix/suffix or just the formatted number
            $baseValue = match($format) {
                'percentage' => number_format($value * 100, $precision), // Convert decimal to percentage
                default => number_format($value, $precision)
            };
            $formattedValue = $prefix . $baseValue . $suffix;
        }

        return [
            'value' => $value,
            'formatted_value' => $formattedValue,
            'format' => $format,
            'color' => $configuration['color'] ?? 'blue',
            'trend' => $configuration['comparison'] ?? null
        ];
    }

    
    protected function getBarChartData(array $configuration, array $dataSource, array $filters): array
    {
        $xAxis = $configuration['x_axis'] ?? null;
        $yAxis = $configuration['y_axis'] ?? null;
        
        if (!$xAxis || !$yAxis || !isset($dataSource['product_id'])) {
            return ['data' => [], 'orientation' => $configuration['orientation'] ?? 'vertical'];
        }

        $query = $this->buildBaseQuery($dataSource['product_id'], $filters);
        
        $allData = $query->get();
        
        if ($yAxis === 'COUNT(*)') {
            $results = $allData->groupBy(function($item) use ($xAxis) {
                $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                return $data[$xAxis] ?? 'Unknown';
            })->map(function($group, $xValue) {
                return (object)['x' => $xValue, 'y' => $group->count()];
            })->sortByDesc('y')->values();
        } else {
            $results = $allData->groupBy(function($item) use ($xAxis) {
                $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                return $data[$xAxis] ?? 'Unknown';
            })->map(function($group, $xValue) use ($yAxis) {
                $total = $group->sum(function($item) use ($yAxis) {
                    $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                    return (float)($data[$yAxis] ?? 0);
                });
                return (object)['x' => $xValue, 'y' => $total];
            })->sortBy('x')->values();
        }

        return [
            'data' => $results->map(function($item) {
                return [
                    'x' => $item->x ?? 'Unknown',
                    'y' => (float) ($item->y ?? 0)
                ];
            })->toArray(),
            'orientation' => $configuration['orientation'] ?? 'vertical',
            'color' => $configuration['color'] ?? '#007bff'
        ];
    }

    
    protected function getLineChartData(array $configuration, array $dataSource, array $filters): array
    {
        $xAxis = $configuration['x_axis'] ?? null;
        $yAxis = $configuration['y_axis'] ?? null;
        
        if (!$xAxis || !$yAxis || !isset($dataSource['product_id'])) {
            return ['data' => [], 'line_color' => $configuration['line_color'] ?? '#007bff'];
        }

        $query = $this->buildBaseQuery($dataSource['product_id'], $filters);
        
        if ($yAxis === 'SUM(outstanding_balance)') {
            if ($xAxis === 'disbursement_date' && isset($configuration['group_by']) && $configuration['group_by'] === 'month') {
                $allData = $query->get();
                $groupedData = $allData->groupBy(function($item) {
                    $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                    $date = $data['disbursement_date'] ?? null;
                    if ($date) {
                        return date('Y-m', strtotime($date));
                    }
                    return date('Y-m', strtotime($item->created_at));
                });
                
                $results = $groupedData->map(function($group, $month) {
                    $total = $group->sum(function($item) {
                        $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                        return (float)($data['outstanding_balance'] ?? 0);
                    });
                    return (object)['x' => $month, 'y' => $total];
                })->sortBy('x')->values();
            } else {
                $allData = $query->get();
                $groupedData = $allData->groupBy(function($item) use ($xAxis) {
                    $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                    return $data[$xAxis] ?? 'Unknown';
                });
                
                $results = $groupedData->map(function($group, $xValue) {
                    $total = $group->sum(function($item) {
                        $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                        return (float)($data['outstanding_balance'] ?? 0);
                    });
                    return (object)['x' => $xValue, 'y' => $total];
                })->sortBy('x')->values();
            }
        } else {
            $results = $query->get()->groupBy(function($item) use ($xAxis) {
                $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                return $data[$xAxis] ?? 'Unknown';
            })->map(function($group) use ($yAxis) {
                return $this->calculateMetric($group->toBase(), $yAxis);
            })->map(function($value, $key) {
                return (object)['x' => $key, 'y' => (float)$value];
            })->values();
        }

        return [
            'data' => $results->map(function($item) {
                return [
                    'x' => $item->x ?? 'Unknown',
                    'y' => (float) ($item->y ?? 0)
                ];
            })->toArray(),
            'line_color' => $configuration['line_color'] ?? '#007bff',
            'show_points' => $configuration['show_points'] ?? true
        ];
    }

    
    protected function getPieChartData(array $configuration, array $dataSource, array $filters): array
    {
        // Support both old format (group_by, metric) and new format (x_axis, y_axis)
        $groupBy = $configuration['group_by'] ?? $configuration['x_axis'] ?? null;
        $metric = $configuration['metric'] ?? $configuration['y_axis'] ?? null;
        $aggregation = $configuration['aggregation'] ?? 'sum';
        
        if (!$groupBy || !$metric || !isset($dataSource['product_id'])) {
            return ['data' => [], 'colors' => $configuration['colors'] ?? []];
        }

        $query = $this->buildBaseQuery($dataSource['product_id'], $filters);
        
        // Use PHP-based processing for better reliability
        $records = $query->get();
        
        $groupedData = [];
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            $label = $data[$groupBy] ?? 'Unknown';
            $value = 0;
            
            // Handle different metric types
            if ($metric === 'outstanding_balance') {
                $value = $record->amount; // For Working Capital Loans, amount contains outstanding_balance
            } elseif (isset($data[$metric])) {
                $value = (float) ($data[$metric] ?? 0);
            } else {
                continue; // Skip records without the metric field
            }
            
            if (!isset($groupedData[$label])) {
                $groupedData[$label] = 0;
            }
            
            if ($aggregation === 'sum') {
                $groupedData[$label] += $value;
            } elseif ($aggregation === 'count') {
                $groupedData[$label] += 1;
            }
        }
        
        // Convert to array format
        $results = collect($groupedData)->map(function($value, $label) {
            return (object)[
                'label' => $label,
                'value' => (float) $value
            ];
        })->sortByDesc('value')->values();

        return [
            'data' => $results->map(function($item) {
                return [
                    'label' => $item->label,
                    'value' => (float) $item->value
                ];
            })->toArray(),
            'colors' => $configuration['colors'] ?? ['#007bff', '#28a745', '#ffc107', '#dc3545']
        ];
    }

    
    protected function getHeatmapData(array $configuration, array $dataSource, array $filters): array
    {
        $xAxis = $configuration['x_axis'] ?? null;
        $yAxis = $configuration['y_axis'] ?? null;
        $metric = $configuration['metric'] ?? null;
        
        if (!$xAxis || !$yAxis || !$metric || !isset($dataSource['product_id'])) {
            return ['data' => [], 'color_scale' => $configuration['color_scale'] ?? []];
        }

        $query = $this->buildBaseQuery($dataSource['product_id'], $filters);
        
        if ($metric === 'SUM(outstanding_balance)') {
            $results = $query->selectRaw("
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$xAxis}')) as x,
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$yAxis}')) as y,
                SUM(CAST(JSON_EXTRACT(data, '$.outstanding_balance') AS DECIMAL(15,2))) as value
            ")
            ->whereRaw("JSON_EXTRACT(data, '$.{$xAxis}') IS NOT NULL AND JSON_EXTRACT(data, '$.{$yAxis}') IS NOT NULL")
            ->groupBy('x', 'y')
            ->get();
        } else {
            $results = $query->get()->groupBy(function($item) use ($xAxis, $yAxis) {
                $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                return $data[$xAxis] ?? 'Unknown';
            })->map(function($group, $xValue) use ($yAxis, $metric) {
                $subGroups = $group->groupBy(function($item) use ($yAxis) {
                    $data = is_array($item->data) ? $item->data : json_decode($item->data, true);
                    return $data[$yAxis] ?? 'Unknown';
                });
                return $subGroups->map(function($subGroup, $yValue) use ($metric) {
                    return $this->calculateMetric($subGroup->toBase(), $metric);
                });
            })->flatten(1)->map(function($value, $key) {
                $parts = explode('.', $key);
                return (object)[
                    'x' => $parts[0] ?? 'Unknown',
                    'y' => $parts[1] ?? 'Unknown',
                    'value' => (float)$value
                ];
            })->values();
        }

        return [
            'data' => $results->map(function($item) {
                return [
                    'x' => $item->x ?? 'Unknown',
                    'y' => $item->y ?? 'Unknown',
                    'value' => (float) ($item->value ?? 0)
                ];
            })->toArray(),
            'color_scale' => $configuration['color_scale'] ?? 'blues'
        ];
    }

    
    protected function getTableData(array $configuration, array $dataSource, array $filters): array
    {
        $columns = $configuration['columns'] ?? [];
        $limit = $configuration['limit'] ?? 50;
        
        if (empty($columns) || !isset($dataSource['product_id'])) {
            return ['data' => [], 'columns' => [], 'total' => 0];
        }

        $query = $this->buildBaseQuery($dataSource['product_id'], $filters);
        
        if (isset($configuration['filter'])) {
        }
        
        $data = $query->limit($limit)->get();

        $results = [];
        foreach ($data as $item) {
            $itemData = is_array($item->data) ? $item->data : json_decode($item->data, true);
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = $itemData[$column] ?? '';
            }
            $results[] = $row;
        }

        return [
            'data' => $results,
            'columns' => $columns,
            'total' => $data->count()
        ];
    }

    
    protected function buildBaseQuery(int $productId, array $filters = [])
    {
        $query = ProductData::where('product_id', $productId);
        
        if (!empty($filters)) {
        }
        
        return $query;
    }

    
    public function calculateMetric($query, string $metric): float
    {
        switch (strtoupper(trim($metric))) {
            case 'COUNT':
            case 'COUNT(*)':
                return (float) $query->count();
            default:
                break;
        }
        
        try {
            $productId = $query->getQuery()->wheres[0]['value'] ?? null;
            
            if (!$productId) {
                $firstRecord = $query->first();
                if ($firstRecord) {
                    $productId = $firstRecord->product_id;
                }
            }
            
            if ($productId) {
                return $this->simpleEvaluator->evaluate($metric, $productId);
            }
            
            return $this->executeSqlFormula($query, $metric);
        } catch (\Exception $e) {
            \Log::error('Formula execution failed', [
                'metric' => $metric,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            try {
                $data = $query->get()->toArray();
                if (!$this->formulaEngine) {
                    $this->formulaEngine = new FormulaEngine();
                }
                return $this->formulaEngine->executeFormula(
                    $this->formulaEngine->parseExpression($metric),
                    $data
                );
            } catch (\Exception $e2) {
                \Log::error('Fallback formula execution also failed', [
                    'metric' => $metric,
                    'error' => $e2->getMessage()
                ]);
                return 0.0;
            }
        }
    }

    
    protected function executeSqlFormula($query, string $formula): float
    {
        \Log::info('ChartDataService: Executing SQL formula', [
            'formula' => $formula
        ]);
        
        $formula = trim($formula);
        
        if (preg_match('/^SUM\(([^)]+)\)$/', $formula, $matches)) {
            $field = trim($matches[1]);
            $sqlField = $this->convertFieldToSql($field);
            \Log::info('ChartDataService: Converted field to SQL', [
                'field' => $field,
                'sqlField' => $sqlField
            ]);
            $result = (float) $query->sum(DB::raw($sqlField));
            \Log::info('ChartDataService: SQL result', [
                'result' => $result
            ]);
            return $result;
        }
        
        if (preg_match('/^SUM\(([^)]+)\s+WHERE\s+([^)]+)\)$/', $formula, $matches)) {
            $field = trim($matches[1]);
            $condition = trim($matches[2]);
            $sqlField = $this->convertFieldToSql($field);
            $sqlCondition = $this->convertConditionToSql($condition);
            
            $filteredQuery = clone $query;
            $filteredQuery->whereRaw($sqlCondition);
            return (float) $filteredQuery->sum(DB::raw($sqlField));
        }
        
        if (strpos($formula, '(') !== false && strpos($formula, ')') !== false) {
            return $this->executeComplexFormula($query, $formula);
        }
        
        $sqlField = $this->convertFieldToSql($formula);
        return (float) $query->sum(DB::raw($sqlField));
    }

    
    protected function convertFieldToSql(string $field): string
    {
        $field = trim($field);
        
        if (strpos($field, '*') !== false || strpos($field, '+') !== false || strpos($field, '-') !== false || strpos($field, '/') !== false) {
            $field = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function($matches) {
                return "CAST(JSON_EXTRACT(data, '$.{$matches[1]}') AS DECIMAL(15,2))";
            }, $field);
            return $field;
        }
        
        return "CAST(JSON_EXTRACT(data, '$.{$field}') AS DECIMAL(15,2))";
    }

    
    protected function convertConditionToSql(string $condition): string
    {
        $condition = trim($condition);
        
        $condition = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*([><=!]+)\s*([0-9.]+)\b/', function($matches) {
            $field = $matches[1];
            $operator = $matches[2];
            $value = $matches[3];
            return "CAST(JSON_EXTRACT(data, '$.{$field}') AS DECIMAL(15,2)) {$operator} {$value}";
        }, $condition);
        
        return $condition;
    }

    
    protected function executeComplexFormula($query, string $formula): float
    {
        if (preg_match('/^\((.*?)\)\s*\/\s*\((.*?)\)\s*\*\s*(\d+)$/', $formula, $matches)) {
            $numeratorFormula = trim($matches[1]);
            $denominatorFormula = trim($matches[2]);
            $multiplier = (float) $matches[3];
            
            $numerator = $this->executeSqlFormula($query, $numeratorFormula);
            $denominator = $this->executeSqlFormula($query, $denominatorFormula);
            
            if ($denominator == 0) {
                return 0.0;
            }
            
            return ($numerator / $denominator) * $multiplier;
        }
        
        if (preg_match('/^\((.*?)\)\s*\*\s*(\d+)$/', $formula, $matches)) {
            $innerFormula = trim($matches[1]);
            $multiplier = (float) $matches[2];
            
            $result = $this->executeSqlFormula($query, $innerFormula);
            return $result * $multiplier;
        }
        
        try {
            $sqlFormula = $this->convertFieldToSql($formula);
            return (float) $query->selectRaw($sqlFormula)->first()->result ?? 0.0;
        } catch (\Exception $e) {
            \Log::error('Complex formula execution failed', [
                'formula' => $formula,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }
}


