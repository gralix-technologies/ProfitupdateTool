<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DatabaseOptimizationService
{
    private array $slowQueries = [];
    private float $slowQueryThreshold = 1.0; // 1 second

    
    public function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            $executionTime = $query->time;
            
            if ($executionTime > $this->slowQueryThreshold * 1000) { // Convert to milliseconds
                $this->logSlowQuery($query);
            }
            
            $this->slowQueries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $executionTime,
                'timestamp' => now()
            ];
        });
    }

    
    public function getSlowQueries(): array
    {
        return array_filter($this->slowQueries, function ($query) {
            return $query['time'] > $this->slowQueryThreshold * 1000;
        });
    }

    
    public function getQueryStats(): array
    {
        if (empty($this->slowQueries)) {
            return [
                'total_queries' => 0,
                'slow_queries' => 0,
                'average_time' => 0,
                'max_time' => 0,
                'min_time' => 0
            ];
        }

        $times = array_column($this->slowQueries, 'time');
        
        return [
            'total_queries' => count($this->slowQueries),
            'slow_queries' => count($this->getSlowQueries()),
            'average_time' => round(array_sum($times) / count($times), 2),
            'max_time' => max($times),
            'min_time' => min($times)
        ];
    }

    
    public function analyzeTablePerformance(): array
    {
        $analysis = [];
        
        try {
            $analysis['product_data'] = $this->analyzeProductDataTable();
            
            $analysis['customers'] = $this->analyzeCustomersTable();
            
            $analysis['dashboards'] = $this->analyzeDashboardsTable();
            
            $analysis['missing_indexes'] = $this->suggestMissingIndexes();
            
        } catch (\Exception $e) {
            Log::error('Database analysis failed', ['error' => $e->getMessage()]);
            $analysis['error'] = 'Analysis failed: ' . $e->getMessage();
        }
        
        return $analysis;
    }

    
    public function createOptimizedIndexes(): array
    {
        $results = [];
        
        try {
            $indexes = [
                'product_data_customer_date_idx' => [
                    'table' => 'product_data',
                    'columns' => ['customer_id', 'effective_date', 'status'],
                    'description' => 'Optimize customer data queries with date filtering'
                ],
                'product_data_amount_idx' => [
                    'table' => 'product_data',
                    'columns' => ['product_id', 'amount', 'status'],
                    'description' => 'Optimize amount-based calculations'
                ],
                'customers_profitability_idx' => [
                    'table' => 'customers',
                    'columns' => ['branch_code', 'profitability', 'risk_level'],
                    'description' => 'Optimize profitability analysis queries'
                ],
                'dashboards_user_updated_idx' => [
                    'table' => 'dashboards',
                    'columns' => ['user_id', 'updated_at'],
                    'description' => 'Optimize dashboard listing queries'
                ]
            ];
            
            foreach ($indexes as $indexName => $config) {
                if (!$this->indexExists($config['table'], $indexName)) {
                    $this->createIndex($config['table'], $indexName, $config['columns']);
                    $results[] = "Created index: {$indexName} on {$config['table']}";
                } else {
                    $results[] = "Index already exists: {$indexName}";
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Index creation failed', ['error' => $e->getMessage()]);
            $results[] = 'Error creating indexes: ' . $e->getMessage();
        }
        
        return $results;
    }

    
    public function optimizeDatabaseConfig(): array
    {
        $recommendations = [];
        
        try {
            $variables = DB::select('SHOW VARIABLES LIKE ?', ['%buffer%']);
            
            foreach ($variables as $variable) {
                if ($variable->Variable_name === 'innodb_buffer_pool_size') {
                    $currentSize = $this->parseSize($variable->Value);
                    $recommendedSize = $this->getRecommendedBufferPoolSize();
                    
                    if ($currentSize < $recommendedSize) {
                        $recommendations[] = [
                            'setting' => 'innodb_buffer_pool_size',
                            'current' => $variable->Value,
                            'recommended' => $this->formatSize($recommendedSize),
                            'description' => 'Increase buffer pool size for better caching'
                        ];
                    }
                }
            }
            
            $queryCacheSize = DB::select('SHOW VARIABLES LIKE ?', ['query_cache_size']);
            if (!empty($queryCacheSize) && $queryCacheSize[0]->Value == 0) {
                $recommendations[] = [
                    'setting' => 'query_cache_size',
                    'current' => '0',
                    'recommended' => '64M',
                    'description' => 'Enable query cache for repeated queries'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Database config analysis failed', ['error' => $e->getMessage()]);
            $recommendations[] = [
                'error' => 'Unable to analyze database configuration: ' . $e->getMessage()
            ];
        }
        
        return $recommendations;
    }

    
    public function getTableSizes(): array
    {
        try {
            $query = "
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ";
            
            return DB::select($query);
        } catch (\Exception $e) {
            Log::error('Failed to get table sizes', ['error' => $e->getMessage()]);
            return [];
        }
    }

    
    private function logSlowQuery($query): void
    {
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
            'connection' => $query->connectionName
        ]);
    }

    
    private function analyzeProductDataTable(): array
    {
        $analysis = [];
        
        try {
            $rowCount = DB::table('product_data')->count();
            $analysis['row_count'] = $rowCount;
            
            $productDistribution = DB::table('product_data')
                ->select('product_id', DB::raw('COUNT(*) as count'))
                ->groupBy('product_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
            
            $analysis['product_distribution'] = $productDistribution;
            
            $dateRange = DB::table('product_data')
                ->selectRaw('MIN(effective_date) as min_date, MAX(effective_date) as max_date')
                ->first();
            
            $analysis['date_range'] = $dateRange;
            
        } catch (\Exception $e) {
            $analysis['error'] = $e->getMessage();
        }
        
        return $analysis;
    }

    
    private function analyzeCustomersTable(): array
    {
        $analysis = [];
        
        try {
            $rowCount = DB::table('customers')->count();
            $analysis['row_count'] = $rowCount;
            
            $branchDistribution = DB::table('customers')
                ->select('branch_code', DB::raw('COUNT(*) as count'))
                ->groupBy('branch_code')
                ->orderByDesc('count')
                ->get();
            
            $analysis['branch_distribution'] = $branchDistribution;
            
        } catch (\Exception $e) {
            $analysis['error'] = $e->getMessage();
        }
        
        return $analysis;
    }

    
    private function analyzeDashboardsTable(): array
    {
        $analysis = [];
        
        try {
            $rowCount = DB::table('dashboards')->count();
            $analysis['row_count'] = $rowCount;
            
            $userDistribution = DB::table('dashboards')
                ->select('user_id', DB::raw('COUNT(*) as count'))
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->get();
            
            $analysis['user_distribution'] = $userDistribution;
            
        } catch (\Exception $e) {
            $analysis['error'] = $e->getMessage();
        }
        
        return $analysis;
    }

    
    private function suggestMissingIndexes(): array
    {
        $suggestions = [];
        
        $patterns = [
            [
                'table' => 'product_data',
                'columns' => ['customer_id', 'effective_date'],
                'reason' => 'Customer timeline queries'
            ],
            [
                'table' => 'product_data',
                'columns' => ['product_id', 'amount'],
                'reason' => 'Product amount aggregations'
            ],
            [
                'table' => 'customers',
                'columns' => ['branch_code', 'profitability'],
                'reason' => 'Branch profitability analysis'
            ]
        ];
        
        foreach ($patterns as $pattern) {
            $indexName = $pattern['table'] . '_' . implode('_', $pattern['columns']) . '_idx';
            if (!$this->indexExists($pattern['table'], $indexName)) {
                $suggestions[] = $pattern;
            }
        }
        
        return $suggestions;
    }

    
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    
    private function createIndex(string $table, string $indexName, array $columns): void
    {
        $columnList = implode(', ', $columns);
        $sql = "CREATE INDEX {$indexName} ON {$table} ({$columnList})";
        DB::statement($sql);
        
        Log::info("Created database index", [
            'table' => $table,
            'index' => $indexName,
            'columns' => $columns
        ]);
    }

    
    private function parseSize(string $size): int
    {
        $units = ['B' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824];
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        
        return $value * ($units[$unit] ?? 1);
    }

    
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'K', 'M', 'G'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes) . $units[$unitIndex];
    }

    
    private function getRecommendedBufferPoolSize(): int
    {
        return 1073741824; // 1GB default recommendation
    }
}


