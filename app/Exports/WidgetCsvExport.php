<?php

namespace App\Exports;

use App\Models\Widget;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class WidgetCsvExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Widget $widget,
        private array $data,
        private array $filters = []
    ) {}

    public function collection()
    {
        return collect($this->formatDataForExport());
    }

    public function headings(): array
    {
        $baseHeadings = $this->getBaseHeadings();
        
        if (!empty($this->filters)) {
            $filterInfo = [];
            foreach ($this->filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    $filterInfo[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . 
                        (is_array($value) ? implode(', ', $value) : $value);
                }
            }
            
            if (!empty($filterInfo)) {
                return array_merge(['Filters Applied: ' . implode(' | ', $filterInfo)], $baseHeadings);
            }
        }
        
        return $baseHeadings;
    }

    public function title(): string
    {
        return \Str::limit($this->widget->title, 30);
    }

    private function getBaseHeadings(): array
    {
        switch ($this->widget->type) {
            case 'KPI':
                return ['Metric', 'Value', 'Change', 'Change %', 'Period'];
                
            case 'Table':
                return $this->data['columns'] ?? ['Data'];
                
            case 'PieChart':
            case 'BarChart':
                return ['Category', 'Value', 'Percentage', 'Color'];
                
            case 'LineChart':
                return ['Date/Period', 'Value', 'Series', 'Trend'];
                
            case 'Heatmap':
                return ['X Axis', 'Y Axis', 'Value', 'Intensity', 'Color'];
                
            default:
                return ['Data', 'Value'];
        }
    }

    private function formatDataForExport(): array
    {
        $exportData = [];
        
        if (!empty($this->filters)) {
            $exportData[] = array_fill(0, count($this->getBaseHeadings()), '');
        }
        
        switch ($this->widget->type) {
            case 'KPI':
                $exportData[] = [
                    $this->widget->title,
                    $this->data['value'] ?? 0,
                    $this->data['change'] ?? 0,
                    $this->data['changePercentage'] ?? 0,
                    $this->data['period'] ?? 'Current'
                ];
                break;
                
            case 'Table':
                foreach ($this->data['rows'] ?? [] as $row) {
                    $exportData[] = is_array($row) ? array_values($row) : [$row];
                }
                break;
                
            case 'PieChart':
            case 'BarChart':
                foreach ($this->data['data'] ?? [] as $item) {
                    $exportData[] = [
                        $item['category'] ?? $item['name'] ?? '',
                        $item['value'] ?? 0,
                        $item['percentage'] ?? 0,
                        $item['color'] ?? ''
                    ];
                }
                break;
                
            case 'LineChart':
                foreach ($this->data['data'] ?? [] as $item) {
                    $exportData[] = [
                        $item['date'] ?? $item['x'] ?? '',
                        $item['value'] ?? $item['y'] ?? 0,
                        $item['series'] ?? 'Default',
                        $item['trend'] ?? ''
                    ];
                }
                break;
                
            case 'Heatmap':
                foreach ($this->data['data'] ?? [] as $item) {
                    $exportData[] = [
                        $item['x'] ?? '',
                        $item['y'] ?? '',
                        $item['value'] ?? 0,
                        $item['intensity'] ?? 0,
                        $item['color'] ?? ''
                    ];
                }
                break;
                
            default:
                $exportData[] = ['No data available', ''];
        }
        
        return $exportData;
    }
}


