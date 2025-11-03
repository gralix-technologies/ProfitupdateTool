<?php

namespace App\Repositories;

use App\Models\Formula;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FormulaRepository
{
    
    public function create(array $data): Formula
    {
        return Formula::create($data);
    }

    
    public function findById(int $id): ?Formula
    {
        return Formula::find($id);
    }

    
    public function findByIdOrFail(int $id): Formula
    {
        return Formula::findOrFail($id);
    }

    
    public function update(Formula $formula, array $data): bool
    {
        return $formula->update($data);
    }

    
    public function delete(Formula $formula): bool
    {
        return $formula->delete();
    }

    
    public function getAll(array $filters = []): Collection
    {
        $query = Formula::query();

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Remove created_by filter - show all formulas to all users
        // if (isset($filters['created_by'])) {
        //     $query->where('created_by', $filters['created_by']);
        // }

        if (isset($filters['return_type'])) {
            $query->where('return_type', $filters['return_type']);
        }

        return $query->with(['product', 'creator'])->get();
    }

    
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Formula::query();

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Remove created_by filter - show all formulas to all users
        // if (isset($filters['created_by'])) {
        //     $query->where('created_by', $filters['created_by']);
        // }

        if (isset($filters['return_type'])) {
            $query->where('return_type', $filters['return_type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('expression', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with(['product', 'creator'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    
    public function getByProduct(Product $product): Collection
    {
        return Formula::where(function ($query) use ($product) {
                         $query->where('product_id', $product->id)
                               ->orWhereNull('product_id'); // Global formulas
                     })
                     ->where('is_active', true)
                     ->with(['product', 'creator'])
                     ->get();
    }

    
    public function getGlobalFormulas(): Collection
    {
        return Formula::whereNull('product_id')
                     ->where('is_active', true)
                     ->with(['creator'])
                     ->get();
    }

    
    public function findByNamePattern(string $pattern): Collection
    {
        return Formula::where('name', 'like', '%' . $pattern . '%')
                     ->where('is_active', true)
                     ->with(['product', 'creator'])
                     ->get();
    }

    
    public function getFormulasUsingField(string $fieldName): Collection
    {
        return Formula::where('expression', 'like', '%' . $fieldName . '%')
                     ->where('is_active', true)
                     ->with(['product', 'creator'])
                     ->get();
    }

    
    public function getByReturnType(string $returnType): Collection
    {
        return Formula::where('return_type', $returnType)
                     ->where('is_active', true)
                     ->with(['product', 'creator'])
                     ->get();
    }

    
    public function nameExistsForProduct(string $name, ?int $productId = null, ?int $excludeId = null): bool
    {
        $query = Formula::where('name', $name);

        if ($productId) {
            $query->where('product_id', $productId);
        } else {
            $query->whereNull('product_id');
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    
    public function getUsageStatistics(Formula $formula): array
    {
        return [
            'dashboard_count' => 0,
            'widget_count' => 0,
            'last_used' => null
        ];
    }

    
    public function duplicate(Formula $formula, array $overrides = []): Formula
    {
        $data = $formula->toArray();
        
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        $data = array_merge($data, $overrides);
        
        if (!isset($overrides['name'])) {
            $data['name'] = $this->generateUniqueName($data['name'], $data['product_id'] ?? null);
        }

        return $this->create($data);
    }

    
    private function generateUniqueName(string $baseName, ?int $productId = null): string
    {
        $counter = 1;
        $newName = $baseName . ' (Copy)';

        while ($this->nameExistsForProduct($newName, $productId)) {
            $counter++;
            $newName = $baseName . ' (Copy ' . $counter . ')';
        }

        return $newName;
    }

    
    public function getRecent(int $limit = 10): Collection
    {
        return Formula::where('is_active', true)
                     ->with(['product', 'creator'])
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    
    public function getByCreator(int $userId): Collection
    {
        // Show all active formulas to all users regardless of creator
        return Formula::where('is_active', true)
                     ->with(['product'])
                     ->orderBy('created_at', 'desc')
                     ->get();
    }
}


