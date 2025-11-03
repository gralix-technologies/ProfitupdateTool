<?php

namespace App\Jobs;

use App\Models\Dashboard;
use App\Services\ChartDataService;
use App\Services\CacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDashboardDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;
    public $maxExceptions = 1;

    
    public function __construct(
        public int $dashboardId,
        public array $filters = [],
        public bool $forceRefresh = false
    ) {
        $this->onQueue('data-processing');
    }

    
    public function handle(ChartDataService $chartDataService, CacheService $cacheService): void
    {
        try {
            Log::info("Starting dashboard data generation for dashboard: {$this->dashboardId}");

            $dashboard = Dashboard::with('widgets')->find($this->dashboardId);
            
            if (!$dashboard) {
                Log::warning("Dashboard not found: {$this->dashboardId}");
                return;
            }

            $dashboardData = [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'widgets' => [],
                'generated_at' => now()->toISOString(),
                'filters_applied' => $this->filters
            ];

            foreach ($dashboard->widgets as $widget) {
                try {
                    $widgetData = $this->generateWidgetData($widget, $chartDataService);
                    $dashboardData['widgets'][] = $widgetData;
                    
                    Log::debug("Generated data for widget: {$widget->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to generate data for widget: {$widget->id}", [
                        'error' => $e->getMessage()
                    ]);
                    
                    $dashboardData['widgets'][] = [
                        'id' => $widget->id,
                        'type' => $widget->type,
                        'error' => 'Failed to generate data: ' . $e->getMessage()
                    ];
                }
            }

            $cacheService->cacheDashboard($this->dashboardId, $this->filters, $dashboardData);

            Log::info("Dashboard data generation completed for dashboard: {$this->dashboardId}");

        } catch (\Exception $e) {
            Log::error("Dashboard data generation failed for dashboard: {$this->dashboardId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    
    private function generateWidgetData($widget, ChartDataService $chartDataService): array
    {
        $config = $widget->config ?? [];
        $mergedFilters = array_merge($config, $this->filters);

        switch ($widget->type) {
            case 'kpi':
                return [
                    'id' => $widget->id,
                    'type' => 'kpi',
                    'title' => $widget->title,
                    'data' => $chartDataService->generateKPIData($mergedFilters)
                ];

            case 'bar_chart':
                return [
                    'id' => $widget->id,
                    'type' => 'bar_chart',
                    'title' => $widget->title,
                    'data' => $chartDataService->generateBarChartData($mergedFilters)
                ];

            case 'line_chart':
                return [
                    'id' => $widget->id,
                    'type' => 'line_chart',
                    'title' => $widget->title,
                    'data' => $chartDataService->generateLineChartData($mergedFilters)
                ];

            case 'pie_chart':
                return [
                    'id' => $widget->id,
                    'type' => 'pie_chart',
                    'title' => $widget->title,
                    'data' => $chartDataService->generatePieChartData($mergedFilters)
                ];

            case 'table':
                return [
                    'id' => $widget->id,
                    'type' => 'table',
                    'title' => $widget->title,
                    'data' => $chartDataService->generateTableData($mergedFilters)
                ];

            case 'heatmap':
                return [
                    'id' => $widget->id,
                    'type' => 'heatmap',
                    'title' => $widget->title,
                    'data' => $chartDataService->generateHeatmapData($mergedFilters)
                ];

            default:
                throw new \InvalidArgumentException("Unsupported widget type: {$widget->type}");
        }
    }

    
    public function failed(\Throwable $exception): void
    {
        Log::error("Dashboard data generation job failed permanently for dashboard: {$this->dashboardId}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'filters' => $this->filters
        ]);
    }

    
    public function tags(): array
    {
        return ['dashboard', 'dashboard:' . $this->dashboardId, 'data-generation'];
    }
}


