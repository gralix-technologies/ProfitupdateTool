<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, Auditable, HasEncryptedAttributes;

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'phone',
        'demographics',
        'branch_code',
        'total_loans_outstanding',
        'total_deposits',
        'npl_exposure',
        'profitability',
        'risk_level',
        'is_active'
    ];

    protected $casts = [
        'demographics' => 'array',
        'total_loans_outstanding' => 'decimal:2',
        'total_deposits' => 'decimal:2',
        'npl_exposure' => 'decimal:2',
        'profitability' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    
    protected $encrypted = [
        'email',
        'phone',
    ];

    
    protected $excludeFromAudit = [
        'email',
        'phone',
    ];

    
    public function productData(): HasMany
    {
        return $this->hasMany(ProductData::class, 'customer_id', 'customer_id');
    }

    
    public function calculateProfitability(): float
    {
        return (float) $this->profitability;
    }

    
    public function getProductsByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return $this->productData()
            ->whereHas('product', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->with('product')
            ->get();
    }

    
    public function getTotalProductsAttribute(): int
    {
        return $this->productData()->count();
    }

    
    public function updateMetrics(): void
    {
        // Use the ProfitabilityService for accurate calculations
        $profitabilityService = app(\App\Services\ProfitabilityService::class);
        $profitabilityService->updateCustomerMetrics($this);
    }
}



