<?php

namespace App\Services;

use App\Models\ProductData;
use App\Services\FormulaEngine;
use App\Services\SimpleFormulaEvaluator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizedChartDataService
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
        // Generate cache key based on parameters
        $cacheKey = $this->generateCacheKey($chartType, $configuration, $dataSource, $filters);
        
        // Check cache first (cache for 5 minutes)
        return Cache::remember($cacheKey, 300, function () use ($chartType, $configuration, $dataSource, $filters) {
            return $this->executeChartDataGeneration($chartType, $configuration, $dataSource, $filters);
        });
    }

    protected function executeChartDataGeneration(string $chartType, array $configuration, array $dataSource, array $filters): array
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

        // Use database aggregation instead of loading all data
        $value = $this->calculateMetricOptimized($dataSource['product_id'], $metric, $filters);
        
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

        // Use database aggregation for better performance
        $results = $this->getAggregatedData($dataSource['product_id'], $xAxis, $yAxis, $filters);

        return [
            'data' => $results,
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

        // Use database aggregation for better performance
        $results = $this->getAggregatedData($dataSource['product_id'], $xAxis, $yAxis, $filters);

        return [
            'data' => $results,
            'line_color' => $configuration['line_color'] ?? '#007bff',
            'show_points' => $configuration['show_points'] ?? true
        ];
    }

    protected function getPieChartData(array $configuration, array $dataSource, array $filters): array
    {
        $groupBy = $configuration['group_by'] ?? $configuration['x_axis'] ?? null;
        $metric = $configuration['metric'] ?? $configuration['y_axis'] ?? null;
        $aggregation = $configuration['aggregation'] ?? 'sum';
        
        if (!$groupBy || !$metric || !isset($dataSource['product_id'])) {
            return ['data' => [], 'colors' => $configuration['colors'] ?? []];
        }

        // Use database aggregation for better performance
        $results = $this->getAggregatedData($dataSource['product_id'], $groupBy, $metric, $filters, $aggregation);

        return [
            'data' => $results,
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

        // Use optimized SQL query for heatmap data
        $results = DB::table('product_data')
            ->selectRaw("
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$xAxis}')) as x,
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$yAxis}')) as y,
                SUM(CAST(JSON_EXTRACT(data, '$.{$metric}') AS DECIMAL(15,2))) as value
            ")
            ->where('product_id', $dataSource['product_id'])
            ->whereRaw("JSON_EXTRACT(data, '$.{$xAxis}') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(data, '$.{$yAxis}') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(data, '$.{$metric}') IS NOT NULL")
            ->groupBy('x', 'y')
            ->orderBy('x')
            ->orderBy('y')
            ->get()
            ->map(function($item) {
                return [
                    'x' => $item->x ?? 'Unknown',
                    'y' => $item->y ?? 'Unknown',
                    'value' => (float) ($item->value ?? 0)
                ];
            })
            ->toArray();

        return [
            'data' => $results,
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

        // Use optimized query with proper indexing
        $query = DB::table('product_data')
            ->where('product_id', $dataSource['product_id']);

        // Apply filters if any
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if (isset($filter['field']) && isset($filter['operator']) && isset($filter['value'])) {
                    $query->whereRaw("JSON_EXTRACT(data, '$.{$filter['field']}') {$filter['operator']} ?", [$filter['value']]);
                }
            }
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();

        $results = [];
        foreach ($data as $item) {
            $itemData = json_decode($item->data, true);
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = $itemData[$column] ?? '';
            }
            $results[] = $row;
        }

        return [
            'data' => $results,
            'columns' => $columns,
            'total' => $total
        ];
    }

    protected function getAggregatedData(int $productId, string $groupBy, string $metric, array $filters = [], string $aggregation = 'sum'): array
    {
        $query = DB::table('product_data')
            ->where('product_id', $productId);

        // Apply filters if any
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if (isset($filter['field']) && isset($filter['operator']) && isset($filter['value'])) {
                    $query->whereRaw("JSON_EXTRACT(data, '$.{$filter['field']}') {$filter['operator']} ?", [$filter['value']]);
                }
            }
        }

        // Handle COUNT(*) special case
        if ($metric === 'COUNT(*)') {
            $results = $query->selectRaw("
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$groupBy}')) as x,
                COUNT(*) as y
            ")
            ->whereRaw("JSON_EXTRACT(data, '$.{$groupBy}') IS NOT NULL")
            ->groupBy('x')
            ->orderBy('y', 'desc')
            ->get();
        } else {
            // Handle other aggregations
            $aggregationFunction = strtoupper($aggregation);
            $results = $query->selectRaw("
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$groupBy}')) as x,
                {$aggregationFunction}(CAST(JSON_EXTRACT(data, '$.{$metric}') AS DECIMAL(15,2))) as y
            ")
            ->whereRaw("JSON_EXTRACT(data, '$.{$groupBy}') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(data, '$.{$metric}') IS NOT NULL")
            ->groupBy('x')
            ->orderBy('x')
            ->get();
        }

        return $results->map(function($item) {
            return [
                'x' => $item->x ?? 'Unknown',
                'y' => (float) ($item->y ?? 0)
            ];
        })->toArray();
    }

    protected function calculateMetricOptimized(int $productId, string $metric, array $filters = []): float
    {
        $query = DB::table('product_data')->where('product_id', $productId);

        // Apply filters if any
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if (isset($filter['field']) && isset($filter['operator']) && isset($filter['value'])) {
                    $query->whereRaw("JSON_EXTRACT(data, '$.{$filter['field']}') {$filter['operator']} ?", [$filter['value']]);
                }
            }
        }

        switch (strtoupper(trim($metric))) {
            case 'COUNT':
            case 'COUNT(*)':
                return (float) $query->count();
            default:
                break;
        }

        try {
            return $this->simpleEvaluator->evaluate($metric, $productId);
        } catch (\Exception $e) {
            \Log::error('Formula execution failed', [
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }

    protected function generateCacheKey(string $chartType, array $configuration, array $dataSource, array $filters): string
    {
        return 'chart_data_' . md5(serialize([
            'chart_type' => $chartType,
            'configuration' => $configuration,
            'data_source' => $dataSource,
            'filters' => $filters
        ]));
    }

    public function clearCache(): void
    {
        Cache::flush();
    }
}
