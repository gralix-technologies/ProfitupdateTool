<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    const PRODUCT_CACHE_TTL = 3600; // 1 hour
    const CUSTOMER_CACHE_TTL = 1800; // 30 minutes
    const DASHBOARD_CACHE_TTL = 900; // 15 minutes
    const FORMULA_CACHE_TTL = 3600; // 1 hour
    const CHART_DATA_CACHE_TTL = 600; // 10 minutes
    const PROFITABILITY_CACHE_TTL = 1800; // 30 minutes

    
    public function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning("Cache operation failed for key: {$key}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $callback();
        }
    }

    
    public function cacheProduct(int $productId, $data): void
    {
        $key = $this->getProductCacheKey($productId);
        Cache::put($key, $data, self::PRODUCT_CACHE_TTL);
    }

    
    public function getCachedProduct(int $productId)
    {
        $key = $this->getProductCacheKey($productId);
        return Cache::get($key);
    }

    
    public function cacheProfitability(string $customerId, array $data): void
    {
        $key = $this->getProfitabilityCacheKey($customerId);
        Cache::put($key, $data, self::PROFITABILITY_CACHE_TTL);
    }

    
    public function getCachedProfitability(string $customerId): ?array
    {
        $key = $this->getProfitabilityCacheKey($customerId);
        return Cache::get($key);
    }

    
    public function cacheDashboard(int $dashboardId, array $filters, $data): void
    {
        $key = $this->getDashboardCacheKey($dashboardId, $filters);
        Cache::put($key, $data, self::DASHBOARD_CACHE_TTL);
    }

    
    public function getCachedDashboard(int $dashboardId, array $filters)
    {
        $key = $this->getDashboardCacheKey($dashboardId, $filters);
        return Cache::get($key);
    }

    
    public function cacheChartData(string $chartType, array $params, $data): void
    {
        $key = $this->getChartDataCacheKey($chartType, $params);
        Cache::put($key, $data, self::CHART_DATA_CACHE_TTL);
    }

    
    public function getCachedChartData(string $chartType, array $params)
    {
        $key = $this->getChartDataCacheKey($chartType, $params);
        return Cache::get($key);
    }

    
    public function cacheFormulaResult(int $formulaId, array $context, $result): void
    {
        $key = $this->getFormulaCacheKey($formulaId, $context);
        Cache::put($key, $result, self::FORMULA_CACHE_TTL);
    }

    
    public function getCachedFormulaResult(int $formulaId, array $context)
    {
        $key = $this->getFormulaCacheKey($formulaId, $context);
        return Cache::get($key);
    }

    
    public function invalidateProductCache(int $productId): void
    {
        $patterns = [
            $this->getProductCacheKey($productId),
            "dashboard:*:product:{$productId}",
            "chart:*:product:{$productId}",
            "formula:*:product:{$productId}"
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $this->invalidateByPattern($pattern);
            } else {
                Cache::forget($pattern);
            }
        }
    }

    
    public function invalidateCustomerCache(string $customerId): void
    {
        $patterns = [
            $this->getProfitabilityCacheKey($customerId),
            "dashboard:*:customer:{$customerId}",
            "chart:*:customer:{$customerId}"
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $this->invalidateByPattern($pattern);
            } else {
                Cache::forget($pattern);
            }
        }
    }

    
    public function invalidateDashboardCache(int $dashboardId): void
    {
        $pattern = "dashboard:{$dashboardId}:*";
        $this->invalidateByPattern($pattern);
    }

    
    public function clearAllCache(): void
    {
        Cache::flush();
        Log::info('All application caches cleared');
    }

    
    public function getCacheStats(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();
            
            return [
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 'N/A',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A',
                'keyspace_hits' => $info['keyspace_hits'] ?? 'N/A',
                'keyspace_misses' => $info['keyspace_misses'] ?? 'N/A',
                'hit_rate' => $this->calculateHitRate($info)
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get cache statistics', ['error' => $e->getMessage()]);
            return ['error' => 'Unable to retrieve cache statistics'];
        }
    }

    
    private function getProductCacheKey(int $productId): string
    {
        return "product:{$productId}";
    }

    
    private function getProfitabilityCacheKey(string $customerId): string
    {
        return "profitability:{$customerId}";
    }

    
    private function getDashboardCacheKey(int $dashboardId, array $filters): string
    {
        $filterHash = md5(serialize($filters));
        return "dashboard:{$dashboardId}:{$filterHash}";
    }

    
    private function getChartDataCacheKey(string $chartType, array $params): string
    {
        $paramHash = md5(serialize($params));
        return "chart:{$chartType}:{$paramHash}";
    }

    
    private function getFormulaCacheKey(int $formulaId, array $context): string
    {
        $contextHash = md5(serialize($context));
        return "formula:{$formulaId}:{$contextHash}";
    }

    
    private function invalidateByPattern(string $pattern): void
    {
        try {
            if (config('cache.default') !== 'redis' || app()->environment('testing')) {
                Cache::flush();
                Log::info("Flushed all cache (non-Redis environment or testing)");
                return;
            }

            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
                Log::info("Invalidated cache keys matching pattern: {$pattern}", [
                    'keys_count' => count($keys)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to invalidate cache pattern: {$pattern}", [
                'error' => $e->getMessage()
            ]);
            try {
                Cache::flush();
            } catch (\Exception $fallbackException) {
                Log::error("Failed to flush cache as fallback", [
                    'error' => $fallbackException->getMessage()
                ]);
            }
        }
    }

    
    private function calculateHitRate(array $info): string
    {
        $hits = (int) ($info['keyspace_hits'] ?? 0);
        $misses = (int) ($info['keyspace_misses'] ?? 0);
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        $hitRate = ($hits / $total) * 100;
        return number_format($hitRate, 2) . '%';
    }
}


