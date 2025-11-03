<?php

namespace Database\Factories;

use App\Models\Widget;
use App\Models\Dashboard;
use Illuminate\Database\Eloquent\Factories\Factory;


class WidgetFactory extends Factory
{
    protected $model = Widget::class;

    
    public function definition(): array
    {
        $types = ['KPI', 'Table', 'PieChart', 'BarChart', 'LineChart', 'Heatmap'];
        $type = $this->faker->randomElement($types);

        return [
            'dashboard_id' => Dashboard::factory(),
            'title' => $this->generateTitleForType($type),
            'type' => $type,
            'configuration' => $this->generateConfigurationForType($type),
            'position' => [
                'x' => $this->faker->numberBetween(0, 8),
                'y' => $this->faker->numberBetween(0, 6),
                'width' => $this->faker->numberBetween(2, 6),
                'height' => $this->faker->numberBetween(2, 4),
            ],
            'data_source' => $this->generateDataSourceForType($type),
            'is_active' => $this->faker->boolean(95), // 95% chance of being active
            'order_index' => $this->faker->numberBetween(1, 20),
        ];
    }

    
    private function generateTitleForType(string $type): string
    {
        $titles = [
            'KPI' => [
                'Total Portfolio Value',
                'Active Customers',
                'Average Profitability',
                'Risk Exposure',
                'Monthly Revenue',
                'Customer Acquisition'
            ],
            'Table' => [
                'Customer List',
                'Product Performance',
                'Top Performers',
                'Risk Analysis',
                'Branch Summary',
                'Recent Transactions'
            ],
            'PieChart' => [
                'Portfolio Distribution',
                'Risk Categories',
                'Product Mix',
                'Branch Performance',
                'Customer Segments',
                'Revenue Sources'
            ],
            'BarChart' => [
                'Monthly Performance',
                'Branch Comparison',
                'Product Categories',
                'Customer Growth',
                'Risk Levels',
                'Profitability Trends'
            ],
            'LineChart' => [
                'Growth Trend',
                'Performance Over Time',
                'Customer Acquisition',
                'Revenue Trend',
                'Risk Evolution',
                'Portfolio Growth'
            ],
            'Heatmap' => [
                'Regional Performance',
                'Product Utilization',
                'Risk Distribution',
                'Customer Activity',
                'Branch Efficiency',
                'Time-based Analysis'
            ]
        ];

        return $this->faker->randomElement($titles[$type] ?? ['Generic Widget']);
    }

    
    private function generateConfigurationForType(string $type): array
    {
        switch ($type) {
            case 'KPI':
                return [
                    'metric' => $this->faker->randomElement(['total_value', 'customer_count', 'avg_profitability', 'risk_score']),
                    'format' => $this->faker->randomElement(['number', 'currency', 'percentage']),
                    'color' => $this->faker->randomElement(['#007bff', '#28a745', '#ffc107', '#dc3545']),
                    'show_trend' => $this->faker->boolean(),
                    'trend_period' => $this->faker->randomElement(['daily', 'weekly', 'monthly'])
                ];

            case 'Table':
                return [
                    'columns' => [
                        ['field' => 'id', 'label' => 'ID', 'sortable' => true],
                        ['field' => 'name', 'label' => 'Name', 'sortable' => true],
                        ['field' => 'value', 'label' => 'Value', 'format' => 'currency', 'sortable' => true],
                        ['field' => 'status', 'label' => 'Status', 'sortable' => true]
                    ],
                    'sortable' => true,
                    'paginated' => $this->faker->boolean(),
                    'page_size' => $this->faker->randomElement([10, 25, 50])
                ];

            case 'PieChart':
                return [
                    'data_field' => $this->faker->randomElement(['amount', 'count', 'percentage']),
                    'label_field' => $this->faker->randomElement(['category', 'type', 'status']),
                    'colors' => ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1']
                ];

            case 'BarChart':
                return [
                    'x_axis' => $this->faker->randomElement(['month', 'category', 'branch']),
                    'y_axis' => $this->faker->randomElement(['amount', 'count', 'percentage']),
                    'orientation' => $this->faker->randomElement(['vertical', 'horizontal']),
                    'color' => $this->faker->randomElement(['#007bff', '#28a745', '#ffc107'])
                ];

            case 'LineChart':
                return [
                    'x_axis' => $this->faker->randomElement(['date', 'month', 'quarter']),
                    'y_axis' => $this->faker->randomElement(['value', 'count', 'growth']),
                    'line_color' => $this->faker->randomElement(['#007bff', '#28a745', '#dc3545']),
                    'show_points' => $this->faker->boolean()
                ];

            case 'Heatmap':
                return [
                    'x_axis' => $this->faker->randomElement(['branch', 'month', 'category']),
                    'y_axis' => $this->faker->randomElement(['product', 'customer_type', 'risk_level']),
                    'value_field' => $this->faker->randomElement(['amount', 'count', 'intensity']),
                    'color_scale' => ['#f8f9fa', '#007bff']
                ];

            default:
                return [];
        }
    }

    
    private function generateDataSourceForType(string $type): array
    {
        $queries = [
            'KPI' => [
                'SELECT COUNT(*) as value FROM customers WHERE is_active = 1',
                'SELECT SUM(amount) as value FROM product_data WHERE status = "active"',
                'SELECT AVG(profitability) as value FROM customers WHERE is_active = 1'
            ],
            'Table' => [
                'SELECT * FROM customers WHERE is_active = 1 ORDER BY profitability DESC LIMIT 10',
                'SELECT p.name, COUNT(*) as count, SUM(pd.amount) as total FROM products p JOIN product_data pd ON p.id = pd.product_id GROUP BY p.id'
            ],
            'PieChart' => [
                'SELECT category, SUM(amount) as total FROM products p JOIN product_data pd ON p.id = pd.product_id GROUP BY category',
                'SELECT risk_level, COUNT(*) as count FROM customers GROUP BY risk_level'
            ],
            'BarChart' => [
                'SELECT branch_code, SUM(profitability) as total FROM customers GROUP BY branch_code',
                'SELECT DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count FROM customers GROUP BY month'
            ],
            'LineChart' => [
                'SELECT DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count FROM customers GROUP BY month ORDER BY month',
                'SELECT DATE_FORMAT(effective_date, "%Y-%m") as month, SUM(amount) as total FROM product_data GROUP BY month ORDER BY month'
            ],
            'Heatmap' => [
                'SELECT c.branch_code, p.category, COUNT(*) as count FROM customers c JOIN product_data pd ON c.customer_id = pd.customer_id JOIN products p ON pd.product_id = p.id GROUP BY c.branch_code, p.category'
            ]
        ];

        $typeQueries = $queries[$type] ?? $queries['KPI'];

        return [
            'query' => $this->faker->randomElement($typeQueries),
            'refresh_interval' => $this->faker->randomElement([60, 300, 600, 1800]), // seconds
            'cache_duration' => $this->faker->randomElement([300, 600, 1800, 3600]) // seconds
        ];
    }

    
    public function kpi(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'KPI',
            'title' => $this->generateTitleForType('KPI'),
            'configuration' => $this->generateConfigurationForType('KPI'),
            'data_source' => $this->generateDataSourceForType('KPI'),
        ]);
    }

    
    public function table(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Table',
            'title' => $this->generateTitleForType('Table'),
            'configuration' => $this->generateConfigurationForType('Table'),
            'data_source' => $this->generateDataSourceForType('Table'),
        ]);
    }

    
    public function chart(string $chartType = null): static
    {
        $chartType = $chartType ?? $this->faker->randomElement(['PieChart', 'BarChart', 'LineChart']);
        
        return $this->state(fn (array $attributes) => [
            'type' => $chartType,
            'title' => $this->generateTitleForType($chartType),
            'configuration' => $this->generateConfigurationForType($chartType),
            'data_source' => $this->generateDataSourceForType($chartType),
        ]);
    }

    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    
    public function forDashboard(Dashboard $dashboard): static
    {
        return $this->state(fn (array $attributes) => [
            'dashboard_id' => $dashboard->id,
        ]);
    }

    
    public function atPosition(int $x, int $y, int $width = 4, int $height = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
            ],
        ]);
    }
}


