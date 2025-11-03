<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $dashboard->name }} - Dashboard Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            margin: 0;
            color: #007bff;
            font-size: 24px;
        }
        
        .header .meta {
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .filters {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #495057;
        }
        
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }
        
        .filter-label {
            font-weight: bold;
            color: #495057;
        }
        
        .widget {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .widget-header {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px 5px 0 0;
            border-left: 4px solid #007bff;
        }
        
        .widget-title {
            margin: 0;
            font-size: 18px;
            color: #495057;
        }
        
        .widget-type {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .widget-content {
            border: 1px solid #e9ecef;
            border-top: none;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        
        .kpi-value {
            font-size: 36px;
            font-weight: bold;
            color: #007bff;
            text-align: center;
            margin: 20px 0;
        }
        
        .kpi-change {
            text-align: center;
            font-size: 14px;
        }
        
        .kpi-change.positive {
            color: #28a745;
        }
        
        .kpi-change.negative {
            color: #dc3545;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .chart-placeholder {
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
            padding: 40px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        
        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .widget {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $dashboard->name }}</h1>
        <div class="meta">
            <strong>Created by:</strong> {{ $dashboard->user->name ?? 'Unknown' }} |
            <strong>Dashboard created:</strong> {{ $dashboard->created_at->format('M j, Y g:i A') }} |
            <strong>Exported:</strong> {{ $exportedAt->format('M j, Y g:i A') }}
        </div>
    </div>

    @if(!empty($filters) && array_filter($filters))
    <div class="filters">
        <h3>Applied Filters</h3>
        @foreach($filters as $key => $value)
            @if($value !== null && $value !== '')
            <div class="filter-item">
                <span class="filter-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                @if(is_array($value))
                    {{ implode(', ', $value) }}
                @else
                    {{ $value }}
                @endif
            </div>
            @endif
        @endforeach
    </div>
    @endif

    @forelse($exportData as $widgetId => $data)
    <div class="widget">
        <div class="widget-header">
            <div class="widget-type">{{ $data['widget']->type }}</div>
            <h2 class="widget-title">{{ $data['widget']->title }}</h2>
        </div>
        
        <div class="widget-content">
            @if(isset($data['error']))
                <div class="error-message">
                    <strong>Error loading widget data:</strong> {{ $data['error'] }}
                </div>
            @else
                @switch($data['widget']->type)
                    @case('KPI')
                        <div class="kpi-value">
                            {{ number_format($data['data']['value'] ?? 0) }}
                        </div>
                        @if(isset($data['data']['change']))
                        <div class="kpi-change {{ ($data['data']['change'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                            {{ ($data['data']['change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($data['data']['change'] ?? 0, 2) }}
                            @if(isset($data['data']['changePercentage']))
                                ({{ number_format($data['data']['changePercentage'] ?? 0, 1) }}%)
                            @endif
                        </div>
                        @endif
                        @break

                    @case('Table')
                        @if(!empty($data['data']['rows']))
                        <table class="data-table">
                            @if(!empty($data['data']['columns']))
                            <thead>
                                <tr>
                                    @foreach($data['data']['columns'] as $column)
                                    <th>{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            @endif
                            <tbody>
                                @foreach(array_slice($data['data']['rows'], 0, 50) as $row)
                                <tr>
                                    @if(is_array($row))
                                        @foreach($row as $cell)
                                        <td>{{ $cell }}</td>
                                        @endforeach
                                    @else
                                        <td>{{ $row }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if(count($data['data']['rows']) > 50)
                        <p><em>Showing first 50 rows of {{ count($data['data']['rows']) }} total rows.</em></p>
                        @endif
                        @else
                        <div class="chart-placeholder">No table data available</div>
                        @endif
                        @break

                    @case('PieChart')
                    @case('BarChart')
                    @case('LineChart')
                        <div class="chart-placeholder">
                            {{ $data['widget']->type }} visualization<br>
                            <small>Charts are not rendered in PDF exports. Please view the dashboard online for interactive charts.</small>
                        </div>
                        
                        @if(!empty($data['data']['data']))
                        <div class="summary-stats">
                            <div class="stat-item">
                                <div class="stat-value">{{ count($data['data']['data']) }}</div>
                                <div class="stat-label">Data Points</div>
                            </div>
                            @if($data['widget']->type !== 'LineChart')
                            <div class="stat-item">
                                <div class="stat-value">{{ number_format(array_sum(array_column($data['data']['data'], 'value'))) }}</div>
                                <div class="stat-label">Total Value</div>
                            </div>
                            @endif
                        </div>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>{{ $data['widget']->type === 'LineChart' ? 'Date/Period' : 'Category' }}</th>
                                    <th>Value</th>
                                    @if($data['widget']->type !== 'LineChart')
                                    <th>Percentage</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($data['data']['data'], 0, 20) as $item)
                                <tr>
                                    <td>{{ $item['category'] ?? $item['name'] ?? $item['date'] ?? $item['x'] ?? 'N/A' }}</td>
                                    <td>{{ number_format($item['value'] ?? $item['y'] ?? 0, 2) }}</td>
                                    @if($data['widget']->type !== 'LineChart')
                                    <td>{{ number_format($item['percentage'] ?? 0, 1) }}%</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                        @break

                    @case('Heatmap')
                        <div class="chart-placeholder">
                            Heatmap visualization<br>
                            <small>Heatmaps are not rendered in PDF exports. Please view the dashboard online for interactive visualizations.</small>
                        </div>
                        
                        @if(!empty($data['data']['data']))
                        <div class="summary-stats">
                            <div class="stat-item">
                                <div class="stat-value">{{ count($data['data']['data']) }}</div>
                                <div class="stat-label">Data Points</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ count(array_unique(array_column($data['data']['data'], 'x'))) }}</div>
                                <div class="stat-label">X Categories</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ count(array_unique(array_column($data['data']['data'], 'y'))) }}</div>
                                <div class="stat-label">Y Categories</div>
                            </div>
                        </div>
                        @endif
                        @break

                    @default
                        <div class="chart-placeholder">
                            Widget type: {{ $data['widget']->type }}<br>
                            <small>This widget type is not supported in PDF exports.</small>
                        </div>
                @endswitch
            @endif
        </div>
    </div>
    @empty
    <div class="widget">
        <div class="widget-content">
            <div class="chart-placeholder">
                No widgets found in this dashboard.
            </div>
        </div>
    </div>
    @endforelse

    <div class="footer">
        <p>Generated by Portfolio Analytics Platform | {{ config('app.name') }}</p>
        <p>This report contains {{ count($exportData) }} widgets and was generated on {{ $exportedAt->format('M j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>


