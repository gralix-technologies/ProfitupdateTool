<?php

namespace App\Exports;

use App\Models\Dashboard;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class DashboardCsvExport implements WithMultipleSheets
{
    public function __construct(
        private Dashboard $dashboard,
        private array $exportData,
        private array $filters
    ) {}

    public function sheets(): array
    {
        $sheets = [];
        
        $sheets[] = new DashboardSummarySheet($this->dashboard, $this->exportData, $this->filters);
        
        foreach ($this->exportData as $widgetId => $data) {
            if (!empty($data['data']) && !isset($data['error'])) {
                $sheets[] = new WidgetDataSheet($data['widget'], $data['data']);
            }
        }
        
        return $sheets;
    }
}

class DashboardSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Dashboard $dashboard,
        private array $exportData,
        private array $filters
    ) {}

    public function collection()
    {
        $summary = collect();
        
        $summary->push([
            'Dashboard Name' => $this->dashboard->name,
            'Created By' => $this->dashboard->user->name ?? 'Unknown',
            'Created At' => $this->dashboard->created_at->format('Y-m-d H:i:s'),
            'Exported At' => now()->format('Y-m-d H:i:s'),
            'Total Widgets' => count($this->exportData)
        ]);
        
        if (!empty($this->filters)) {
            $summary->push(['', '', '', '', '']);
            $summary->push(['Applied Filters:', '', '', '', '']);
            
            foreach ($this->filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    $summary->push([
                        ucfirst(str_replace('_', ' ', $key)),
                        is_array($value) ? implode(', ', $value) : $value,
                        '', '', ''
                    ]);
                }
            }
        }
        
        $summary->push(['', '', '', '', '']);
        $summary->push(['Widget Summary:', '', '', '', '']);
        $summary->push(['Widget Name', 'Type', 'Data Points', 'Status', 'Notes']);
        
        foreach ($this->exportData as $data) {
            $widget = $data['widget'];
            $summary->push([
                $widget->title,
                $widget->type,
                $data['summary']['data_points'] ?? $data['summary']['total_records'] ?? 0,
                isset($data['error']) ? 'Error' : 'Success',
                $data['error'] ?? ''
            ]);
        }
        
        return $summary;
    }

    public function headings(): array
    {
        return ['Field', 'Value', '', '', ''];
    }

    public function title(): string
    {
        return 'Dashboard Summary';
    }
}

class WidgetDataSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private $widget,
        private array $data
    ) {}

    public function collection()
    {
        return collect($this->formatDataForExport());
    }

    public function headings(): array
    {
        switch ($this->widget->type) {
            case 'KPI':
                return ['Metric', 'Value', 'Change', 'Change %'];
                
            case 'Table':
                return $this->data['columns'] ?? ['Data'];
                
            case 'PieChart':
            case 'BarChart':
                return ['Category', 'Value', 'Percentage'];
                
            case 'LineChart':
                return ['Date', 'Value', 'Series'];
                
            case 'Heatmap':
                return ['X Axis', 'Y Axis', 'Value', 'Intensity'];
                
            default:
                return ['Data'];
        }
    }

    public function title(): string
    {
        return \Str::limit($this->widget->title, 30);
    }

    private function formatDataForExport(): array
    {
        switch ($this->widget->type) {
            case 'KPI':
                return [[
                    $this->widget->title,
                    $this->data['value'] ?? 0,
                    $this->data['change'] ?? 0,
                    $this->data['changePercentage'] ?? 0
                ]];
                
            case 'Table':
                return $this->data['rows'] ?? [];
                
            case 'PieChart':
            case 'BarChart':
                return array_map(function ($item) {
                    return [
                        $item['category'] ?? $item['name'] ?? '',
                        $item['value'] ?? 0,
                        $item['percentage'] ?? 0
                    ];
                }, $this->data['data'] ?? []);
                
            case 'LineChart':
                return array_map(function ($item) {
                    return [
                        $item['date'] ?? $item['x'] ?? '',
                        $item['value'] ?? $item['y'] ?? 0,
                        $item['series'] ?? ''
                    ];
                }, $this->data['data'] ?? []);
                
            case 'Heatmap':
                return array_map(function ($item) {
                    return [
                        $item['x'] ?? '',
                        $item['y'] ?? '',
                        $item['value'] ?? 0,
                        $item['intensity'] ?? 0
                    ];
                }, $this->data['data'] ?? []);
                
            default:
                return [['No data available']];
        }
    }
}


