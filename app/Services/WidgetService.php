<?php

namespace App\Services;

use App\Models\Widget;
use App\Services\ChartDataService;

class WidgetService
{
    private const WIDGET_TYPES = ['KPI', 'Table', 'PieChart', 'BarChart', 'LineChart', 'Heatmap'];
    
    private const WIDGET_TEMPLATES = [
        'KPI' => [
            'type' => 'KPI',
            'defaultConfig' => [
                'title' => 'New KPI',
                'metric' => '',
                'format' => 'number',
                'color' => 'primary'
            ],
            'requiredFields' => ['title']
        ],
        'BarChart' => [
            'type' => 'BarChart',
            'defaultConfig' => [
                'title' => 'New Bar Chart',
                'chart_type' => 'bar',
                'x_axis' => '',
                'y_axis' => '',
                'color_scheme' => 'default'
            ],
            'requiredFields' => ['title', 'x_axis', 'y_axis']
        ],
        'LineChart' => [
            'type' => 'LineChart',
            'defaultConfig' => [
                'title' => 'New Line Chart',
                'chart_type' => 'line',
                'x_axis' => '',
                'y_axis' => '',
                'show_points' => true
            ],
            'requiredFields' => ['title', 'x_axis', 'y_axis']
        ],
        'PieChart' => [
            'type' => 'PieChart',
            'defaultConfig' => [
                'title' => 'New Pie Chart',
                'label_field' => '',
                'value_field' => '',
                'show_legend' => true
            ],
            'requiredFields' => ['title', 'label_field', 'value_field']
        ],
        'Table' => [
            'type' => 'Table',
            'defaultConfig' => [
                'title' => 'New Table',
                'columns' => [],
                'sortable' => true,
                'paginated' => true,
                'limit' => 100
            ],
            'requiredFields' => ['title', 'columns']
        ],
        'Heatmap' => [
            'type' => 'Heatmap',
            'defaultConfig' => [
                'title' => 'New Heatmap',
                'x_axis' => '',
                'y_axis' => '',
                'value_field' => '',
                'color_scale' => 'blues'
            ],
            'requiredFields' => ['title', 'x_axis', 'y_axis', 'value_field']
        ]
    ];

    public function __construct(
        private ChartDataService $chartDataService
    ) {}

    public function createWidget(int $dashboardId, string $type, array $config): Widget
    {
        $template = self::WIDGET_TEMPLATES[$type] ?? null;
        if (!$template) {
            throw new \InvalidArgumentException("Invalid widget type: {$type}");
        }

        $finalConfig = array_merge($template['defaultConfig'], $config);

        return Widget::create([
            'dashboard_id' => $dashboardId,
            'title' => $finalConfig['title'] ?? 'New Widget',
            'type' => $type,
            'configuration' => $finalConfig,
            'position' => $config['position'] ?? ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 3],
            'order_index' => $config['order_index'] ?? 0
        ]);
    }

    public function validateWidgetConfig(string $type, array $config): ValidationResult
    {
        $errors = [];
        $template = self::WIDGET_TEMPLATES[$type] ?? null;

        if (!$template) {
            $errors[] = "Invalid widget type: {$type}";
            return new ValidationResult(false, $errors);
        }

        foreach ($template['requiredFields'] as $field) {
            if (empty($config[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        switch ($type) {
            case 'Table':
                if (isset($config['columns']) && !is_array($config['columns'])) {
                    $errors[] = 'Columns must be an array';
                }
                break;
                
            case 'BarChart':
            case 'LineChart':
                if (empty($config['x_axis']) || empty($config['y_axis'])) {
                    $errors[] = 'Both X and Y axes are required for chart widgets';
                }
                break;
        }

        return new ValidationResult(empty($errors), $errors);
    }

    public function getChartData(Widget $widget, array $filters = []): array
    {
        return $this->chartDataService->getChartData($widget, $filters);
    }

    public function updateWidget(Widget $widget, array $config): Widget
    {
        $currentConfig = $widget->configuration;
        $newConfig = array_merge($currentConfig, $config);
        
        $widget->update(['configuration' => $newConfig]);
        
        return $widget->fresh();
    }

    public function updateWidgetPosition(Widget $widget, array $position): Widget
    {
        $widget->update(['position' => $position]);
        return $widget->fresh();
    }

    public function duplicateWidget(Widget $originalWidget): Widget
    {
        $config = $originalWidget->configuration;
        $config['title'] = 'Copy of ' . $config['title'];

        $position = $originalWidget->position;
        $position['x'] += $position['w']; // Move to the right

        return Widget::create([
            'dashboard_id' => $originalWidget->dashboard_id,
            'title' => $config['title'],
            'type' => $originalWidget->type,
            'configuration' => $config,
            'position' => $position,
            'order_index' => $originalWidget->order_index + 1
        ]);
    }

    public function getAvailableWidgetTypes(): array
    {
        return self::WIDGET_TYPES;
    }

    public function getWidgetTemplate(string $type): array
    {
        return self::WIDGET_TEMPLATES[$type] ?? [];
    }

    public function calculateWidgetDataSize(Widget $widget): int
    {
        return $this->chartDataService->getDataSize($widget);
    }

    public function optimizeWidgetForPerformance(Widget $widget): Widget
    {
        $config = $widget->configuration;
        
        switch ($widget->type) {
            case 'Table':
                if (($config['limit'] ?? 0) > 1000) {
                    $config['limit'] = 1000;
                    $config['pagination'] = true;
                }
                break;
                
            case 'BarChart':
            case 'LineChart':
                if (!isset($config['data_limit'])) {
                    $config['data_limit'] = 500;
                }
                break;
        }

        $widget->update(['configuration' => $config]);
        return $widget->fresh();
    }

    public function exportWidgetData(Widget $widget, string $format = 'csv'): array
    {
        $data = $this->chartDataService->getChartData($widget, []);
        
        return [
            'data' => $data,
            'format' => $format,
            'widget_title' => $widget->configuration['title'] ?? 'Widget Data',
            'exported_at' => now()->toISOString()
        ];
    }
}


