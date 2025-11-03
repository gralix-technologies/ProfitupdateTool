<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\Widget;
use App\Exports\DashboardCsvExport;
use App\Exports\WidgetCsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DashboardExportService
{
    public function __construct(
        private ChartDataService $chartDataService
    ) {}

    
    public function exportToPdf(Dashboard $dashboard, array $filters = []): string
    {
        $widgets = $dashboard->widgets()->where('is_active', true)->get();
        $exportData = $this->prepareExportData($widgets, $filters);
        
        $pdf = Pdf::loadView('exports.dashboard-pdf', [
            'dashboard' => $dashboard,
            'widgets' => $widgets,
            'exportData' => $exportData,
            'filters' => $filters,
            'exportedAt' => now()
        ]);
        
        $filename = $this->generateFilename($dashboard, 'pdf');
        $path = "exports/dashboards/{$filename}";
        
        Storage::put($path, $pdf->output());
        
        return $path;
    }

    
    public function exportToCsv(Dashboard $dashboard, array $filters = []): string
    {
        $widgets = $dashboard->widgets()->where('is_active', true)->get();
        $exportData = $this->prepareExportData($widgets, $filters);
        
        $filename = $this->generateFilename($dashboard, 'csv');
        $path = "exports/dashboards/{$filename}";
        
        Excel::store(new DashboardCsvExport($dashboard, $exportData, $filters), $path);
        
        return $path;
    }

    
    public function exportWidgetToCsv(Widget $widget, array $filters = []): string
    {
        $data = $this->chartDataService->getChartData(
            $widget->type,
            $widget->configuration ?? [],
            $widget->data_source ?? [],
            $filters
        );
        
        $filename = $this->generateWidgetFilename($widget, 'csv');
        $path = "exports/widgets/{$filename}";
        
        Excel::store(new WidgetCsvExport($widget, $data, $filters), $path);
        
        return $path;
    }

    
    public function getAvailableFormats(): array
    {
        return [
            'pdf' => [
                'name' => 'PDF',
                'description' => 'Portable Document Format with charts and visualizations',
                'mime_type' => 'application/pdf'
            ],
            'csv' => [
                'name' => 'CSV',
                'description' => 'Comma-separated values with raw data',
                'mime_type' => 'text/csv'
            ]
        ];
    }

    
    private function prepareExportData(Collection $widgets, array $filters): array
    {
        $exportData = [];
        
        foreach ($widgets as $widget) {
            try {
                $data = $this->chartDataService->getChartData(
                    $widget->type,
                    $widget->configuration ?? [],
                    $widget->data_source ?? [],
                    $filters
                );
                $exportData[$widget->id] = [
                    'widget' => $widget,
                    'data' => $data,
                    'summary' => $this->generateWidgetSummary($widget, $data)
                ];
            } catch (\Exception $e) {
                $exportData[$widget->id] = [
                    'widget' => $widget,
                    'data' => [],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $exportData;
    }

    
    private function generateWidgetSummary(Widget $widget, array $data): array
    {
        $summary = [
            'total_records' => 0,
            'data_points' => 0
        ];
        
        switch ($widget->type) {
            case 'KPI':
                $summary['value'] = $data['value'] ?? 0;
                $summary['change'] = $data['change'] ?? 0;
                break;
                
            case 'Table':
                $summary['total_records'] = count($data['rows'] ?? []);
                $summary['columns'] = count($data['columns'] ?? []);
                break;
                
            case 'PieChart':
            case 'BarChart':
            case 'LineChart':
                $summary['data_points'] = count($data['data'] ?? []);
                $summary['categories'] = count(array_unique(array_column($data['data'] ?? [], 'category')));
                break;
                
            case 'Heatmap':
                $summary['data_points'] = count($data['data'] ?? []);
                $summary['x_axis_values'] = count(array_unique(array_column($data['data'] ?? [], 'x')));
                $summary['y_axis_values'] = count(array_unique(array_column($data['data'] ?? [], 'y')));
                break;
        }
        
        return $summary;
    }

    
    private function generateFilename(Dashboard $dashboard, string $format): string
    {
        $slug = \Str::slug($dashboard->name);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "{$slug}_{$timestamp}.{$format}";
    }

    
    private function generateWidgetFilename(Widget $widget, string $format): string
    {
        $slug = \Str::slug($widget->title);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "{$slug}_{$timestamp}.{$format}";
    }

    
    public function cleanupOldExports(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $deleted = 0;
        
        $files = Storage::allFiles('exports/dashboards');
        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffDate->timestamp) {
                Storage::delete($file);
                $deleted++;
            }
        }
        
        $widgetFiles = Storage::allFiles('exports/widgets');
        foreach ($widgetFiles as $file) {
            if (Storage::lastModified($file) < $cutoffDate->timestamp) {
                Storage::delete($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}


