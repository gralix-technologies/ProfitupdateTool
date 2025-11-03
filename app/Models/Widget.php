<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class Widget extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'dashboard_id',
        'title',
        'type',
        'configuration',
        'position',
        'data_source',
        'is_active',
        'order_index'
    ];

    protected $casts = [
        'configuration' => 'array',
        'position' => 'array',
        'data_source' => 'array',
        'is_active' => 'boolean',
        'order_index' => 'integer'
    ];

    
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    
    public static function getTypes(): array
    {
        return ['KPI', 'Table', 'PieChart', 'BarChart', 'LineChart', 'Heatmap'];
    }

    
    public static function getDefaultConfiguration(string $type): array
    {
        $defaults = [
            'KPI' => [
                'metric' => null,
                'format' => 'number',
                'color' => '#007bff',
                'show_trend' => true
            ],
            'Table' => [
                'columns' => [],
                'sortable' => true,
                'paginated' => true,
                'page_size' => 10
            ],
            'PieChart' => [
                'data_field' => null,
                'label_field' => null,
                'colors' => ['#007bff', '#28a745', '#ffc107', '#dc3545']
            ],
            'BarChart' => [
                'x_axis' => null,
                'y_axis' => null,
                'orientation' => 'vertical',
                'color' => '#007bff'
            ],
            'LineChart' => [
                'x_axis' => null,
                'y_axis' => null,
                'line_color' => '#007bff',
                'show_points' => true
            ],
            'Heatmap' => [
                'x_axis' => null,
                'y_axis' => null,
                'value_field' => null,
                'color_scale' => ['#f8f9fa', '#007bff']
            ]
        ];

        return $defaults[$type] ?? [];
    }

    
    public static function getDefaultPosition(): array
    {
        return [
            'x' => 0,
            'y' => 0,
            'width' => 4,
            'height' => 3
        ];
    }

    
    public function updatePosition(array $position): void
    {
        $this->position = array_merge($this->position ?? [], $position);
        $this->save();
    }

    
    public function updateConfiguration(array $configuration): void
    {
        $this->configuration = array_merge($this->configuration ?? [], $configuration);
        $this->save();
    }

    
    public function isValidType(): bool
    {
        return in_array($this->type, self::getTypes());
    }
}



