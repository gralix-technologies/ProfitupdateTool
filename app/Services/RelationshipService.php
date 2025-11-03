<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductData;
use Illuminate\Support\Collection;

class RelationshipService
{
    public function linkProductToCustomer(string $customerId, int $productId, array $data): ProductData
    {
        return ProductData::create([
            'customer_id' => $customerId,
            'product_id' => $productId,
            'data' => $data
        ]);
    }

    public function getCustomerProducts(string $customerId): Collection
    {
        return ProductData::where('customer_id', $customerId)
            ->with('product')
            ->get();
    }

    public function getCustomerProductsByCategory(string $customerId, string $category): Collection
    {
        return ProductData::where('customer_id', $customerId)
            ->whereHas('product', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->with('product')
            ->get();
    }

    public function calculateRelationshipStrength(string $customerId): float
    {
        $products = $this->getCustomerProducts($customerId);
        
        if ($products->isEmpty()) {
            return 0.0;
        }

        $score = 0;
        
        $productCount = $products->count();
        $score += min(40, $productCount * 10);
        
        $categories = $products->pluck('product.category')->unique();
        $score += min(30, $categories->count() * 10);
        
        $totalValue = $this->calculateCustomerPortfolioValue($customerId);
        if ($totalValue > 100000) $score += 30;
        elseif ($totalValue > 50000) $score += 20;
        elseif ($totalValue > 10000) $score += 10;
        
        return min(100.0, $score);
    }

    public function identifyPrimaryRelationship(string $customerId): ?ProductData
    {
        return ProductData::where('customer_id', $customerId)
            ->with('product')
            ->get()
            ->sortByDesc(function ($productData) {
                return $productData->data['amount'] ?? 0;
            })
            ->first();
    }

    public function getRelationshipTimeline(string $customerId): array
    {
        $products = ProductData::where('customer_id', $customerId)
            ->with('product')
            ->orderBy('created_at')
            ->get();

        return $products->map(function ($productData) {
            return [
                'date' => $productData->created_at->toDateString(),
                'product_name' => $productData->product->name,
                'product_category' => $productData->product->category,
                'event' => 'Product Added',
                'value' => $productData->data['amount'] ?? 0
            ];
        })->toArray();
    }

    public function calculateCustomerPortfolioValue(string $customerId): float
    {
        return ProductData::where('customer_id', $customerId)
            ->get()
            ->sum(function ($productData) {
                return $productData->data['amount'] ?? 0;
            });
    }

    public function identifyDormantRelationships(string $customerId, int $monthsThreshold = 12): Collection
    {
        $cutoffDate = now()->subMonths($monthsThreshold);
        
        return ProductData::where('customer_id', $customerId)
            ->where('updated_at', '<', $cutoffDate)
            ->with('product')
            ->get();
    }

    public function getCrossSellCandidates(string $customerId): Collection
    {
        $existingCategories = ProductData::where('customer_id', $customerId)
            ->with('product')
            ->get()
            ->pluck('product.category')
            ->unique()
            ->toArray();

        return Product::whereNotIn('category', $existingCategories)
            ->where('is_active', true)
            ->get();
    }

    public function updateRelationshipStatus(string $customerId, int $productId, string $status): bool
    {
        $productData = ProductData::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        if (!$productData) {
            return false;
        }

        $data = $productData->data;
        $data['status'] = $status;
        
        return $productData->update(['data' => $data]);
    }

    public function removeCustomerProductRelationship(string $customerId, int $productId): bool
    {
        return ProductData::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    public function getRelationshipSummary(string $customerId): array
    {
        $products = $this->getCustomerProducts($customerId);
        
        $categories = $products->groupBy('product.category');
        $totalValue = $this->calculateCustomerPortfolioValue($customerId);
        $relationshipStrength = $this->calculateRelationshipStrength($customerId);

        $categoryBreakdown = [];
        foreach ($categories as $category => $categoryProducts) {
            $categoryValue = $categoryProducts->sum(function ($productData) {
                return $productData->data['amount'] ?? 0;
            });
            
            $categoryBreakdown[$category] = [
                'count' => $categoryProducts->count(),
                'value' => $categoryValue,
                'percentage' => $totalValue > 0 ? ($categoryValue / $totalValue) * 100 : 0
            ];
        }

        return [
            'total_products' => $products->count(),
            'total_value' => $totalValue,
            'categories' => $categoryBreakdown,
            'relationship_strength' => $relationshipStrength,
            'primary_relationship' => $this->identifyPrimaryRelationship($customerId)?->product->name,
            'dormant_count' => $this->identifyDormantRelationships($customerId)->count(),
            'cross_sell_opportunities' => $this->getCrossSellCandidates($customerId)->count()
        ];
    }

    public function bulkUpdateRelationships(string $customerId, array $updates): array
    {
        $results = [];
        
        foreach ($updates as $update) {
            $productId = $update['product_id'];
            $data = $update['data'];
            
            $productData = ProductData::where('customer_id', $customerId)
                ->where('product_id', $productId)
                ->first();
                
            if ($productData) {
                $currentData = $productData->data;
                $newData = array_merge($currentData, $data);
                
                $success = $productData->update(['data' => $newData]);
                $results[$productId] = $success;
            } else {
                $results[$productId] = false;
            }
        }
        
        return $results;
    }

    public function getRelationshipMetrics(string $customerId): array
    {
        $products = $this->getCustomerProducts($customerId);
        
        if ($products->isEmpty()) {
            return [
                'total_relationships' => 0,
                'active_relationships' => 0,
                'average_relationship_age' => 0,
                'most_valuable_category' => null,
                'relationship_diversity_score' => 0
            ];
        }

        $activeCount = $products->filter(function ($productData) {
            return ($productData->data['status'] ?? 'Active') === 'Active';
        })->count();

        $averageAge = $products->avg(function ($productData) {
            return $productData->created_at->diffInDays(now());
        });

        $categoryValues = $products->groupBy('product.category')
            ->map(function ($categoryProducts) {
                return $categoryProducts->sum(function ($productData) {
                    return $productData->data['amount'] ?? 0;
                });
            });

        $mostValuableCategory = $categoryValues->sortDesc()->keys()->first();
        
        $diversityScore = min(100, $products->pluck('product.category')->unique()->count() * 25);

        return [
            'total_relationships' => $products->count(),
            'active_relationships' => $activeCount,
            'average_relationship_age' => round($averageAge),
            'most_valuable_category' => $mostValuableCategory,
            'relationship_diversity_score' => $diversityScore
        ];
    }
}


