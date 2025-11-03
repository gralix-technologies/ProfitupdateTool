<?php

namespace App\Repositories;

use App\Models\Product;
use App\Services\CacheService;
use App\Services\PaginationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function __construct(
        private CacheService $cacheService,
        private PaginationService $paginationService
    ) {}

    
    public function all(): Collection
    {
        return $this->cacheService->remember(
            'products:all',
            CacheService::PRODUCT_CACHE_TTL,
            fn() => Product::all()
        );
    }

    
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()->orderBy('created_at', 'desc');
        return $this->paginationService->paginateQuery($query, $perPage);
    }

    
    public function find(int $id): ?Product
    {
        return $this->cacheService->remember(
            "product:{$id}",
            CacheService::PRODUCT_CACHE_TTL,
            fn() => Product::find($id)
        );
    }

    
    public function findOrFail(int $id): Product
    {
        $product = $this->find($id);
        if (!$product) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }
        return $product;
    }

    
    public function findByName(string $name): ?Product
    {
        return $this->cacheService->remember(
            "product:name:{$name}",
            CacheService::PRODUCT_CACHE_TTL,
            fn() => Product::where('name', $name)->first()
        );
    }

    
    public function create(array $data): Product
    {
        $product = Product::create($data);
        
        $this->invalidateProductCaches();
        
        return $product;
    }

    
    public function update(Product $product, array $data): bool
    {
        $result = $product->update($data);
        
        if ($result) {
            $this->cacheService->invalidateProductCache($product->id);
            $this->invalidateProductCaches();
        }
        
        return $result;
    }

    
    public function delete(Product $product): bool
    {
        $result = $product->delete();
        
        if ($result) {
            $this->cacheService->invalidateProductCache($product->id);
            $this->invalidateProductCaches();
        }
        
        return $result;
    }

    
    public function getByCategory(string $category): Collection
    {
        return $this->cacheService->remember(
            "products:category:{$category}",
            CacheService::PRODUCT_CACHE_TTL,
            fn() => Product::where('category', $category)->get()
        );
    }

    
    public function getActive(): Collection
    {
        return $this->cacheService->remember(
            'products:active',
            CacheService::PRODUCT_CACHE_TTL,
            fn() => Product::where('is_active', true)->get()
        );
    }

    
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = Product::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    
    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orderBy('name');
            
        return $this->paginationService->paginateQuery($query, $perPage);
    }

    
    public function getProductStats(): array
    {
        return $this->cacheService->remember(
            'products:stats',
            CacheService::PRODUCT_CACHE_TTL,
            function () {
                return [
                    'total' => Product::count(),
                    'active' => Product::where('is_active', true)->count(),
                    'by_category' => Product::selectRaw('category, COUNT(*) as count')
                        ->groupBy('category')
                        ->pluck('count', 'category')
                        ->toArray()
                ];
            }
        );
    }

    
    public function getProductsWithDataCount(): Collection
    {
        return $this->cacheService->remember(
            'products:with_data_count',
            CacheService::PRODUCT_CACHE_TTL,
            function () {
                return Product::withCount('productData')
                    ->orderBy('product_data_count', 'desc')
                    ->get();
            }
        );
    }

    
    public function getPaginatedWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('description', 'LIKE', "%{$filters['search']}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        return $this->paginationService->paginateQuery($query, $perPage);
    }

    
    private function invalidateProductCaches(): void
    {
        $cacheKeys = [
            'products:all',
            'products:active',
            'products:stats',
            'products:with_data_count'
        ];

        foreach ($cacheKeys as $key) {
            \Cache::forget($key);
        }

        $categories = ['Loan', 'Account', 'Deposit', 'Transaction', 'Other'];
        foreach ($categories as $category) {
            \Cache::forget("products:category:{$category}");
        }
    }
}


