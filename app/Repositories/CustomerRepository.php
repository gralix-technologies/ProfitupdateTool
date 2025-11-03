<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository
{
    
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    
    public function findById(int $id): ?Customer
    {
        return Customer::find($id);
    }

    
    public function findByCustomerId(string $customerId): ?Customer
    {
        return Customer::where('customer_id', $customerId)->first();
    }

    
    public function update(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }

    
    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Customer::query();

        if (!empty($filters['branch_code'])) {
            $query->where('branch_code', $filters['branch_code']);
        }

        if (!empty($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_id', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    
    public function getByBranch(string $branchCode): Collection
    {
        return Customer::where('branch_code', $branchCode)
            ->orderBy('name')
            ->get();
    }

    
    public function getByRiskLevel(string $riskLevel): Collection
    {
        return Customer::where('risk_level', $riskLevel)
            ->orderBy('profitability', 'desc')
            ->get();
    }

    
    public function getTopProfitable(int $limit = 10): Collection
    {
        return Customer::where('is_active', true)
            ->orderBy('profitability', 'desc')
            ->limit($limit)
            ->get();
    }

    
    public function getHighNPLExposure(float $threshold = 10000): Collection
    {
        return Customer::where('npl_exposure', '>', $threshold)
            ->orderBy('npl_exposure', 'desc')
            ->get();
    }

    
    public function getBranchStatistics(string $branchCode): array
    {
        $baseQuery = Customer::where('branch_code', $branchCode);

        return [
            'total_customers' => (clone $baseQuery)->count(),
            'active_customers' => (clone $baseQuery)->where('is_active', true)->count(),
            'total_profitability' => (clone $baseQuery)->sum('profitability'),
            'total_loans' => (clone $baseQuery)->sum('total_loans_outstanding'),
            'total_deposits' => (clone $baseQuery)->sum('total_deposits'),
            'total_npl_exposure' => (clone $baseQuery)->sum('npl_exposure'),
            'risk_distribution' => [
                'low' => (clone $baseQuery)->where('risk_level', 'Low')->count(),
                'medium' => (clone $baseQuery)->where('risk_level', 'Medium')->count(),
                'high' => (clone $baseQuery)->where('risk_level', 'High')->count(),
            ]
        ];
    }

    
    public function updateAllMetrics(): int
    {
        $customers = Customer::all();
        $updated = 0;

        foreach ($customers as $customer) {
            $customer->updateMetrics();
            $updated++;
        }

        return $updated;
    }

    
    public function getWithProducts(array $filters = []): Collection
    {
        $query = Customer::with(['productData.product']);

        if (!empty($filters['branch_code'])) {
            $query->where('branch_code', $filters['branch_code']);
        }

        if (!empty($filters['has_products'])) {
            $query->has('productData');
        }

        return $query->get();
    }

    
    public function findOrCreate(string $customerId, array $data = []): Customer
    {
        return Customer::firstOrCreate(
            ['customer_id' => $customerId],
            array_merge($data, ['customer_id' => $customerId])
        );
    }
}


