<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductData;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class ConfigurablePortfolioService
{
    /**
     * Calculate portfolio value based on configurable formula
     */
    public function calculatePortfolioValue(): array
    {
        $formulaConfig = $this->getPortfolioFormulaConfig();
        $formulaType = $formulaConfig['type'];
        
        switch ($formulaType) {
            case 'sum_all_products':
                return $this->sumAllProducts();
                
            case 'sum_active_only':
                return $this->sumActiveOnly();
                
            case 'sum_exclude_npl':
                return $this->sumExcludeNpl();
                
            case 'weighted_sum':
                return $this->weightedSum();
                
            case 'custom_formula':
                return $this->executeCustomFormula($formulaConfig);
                
            default:
                return $this->sumAllProducts(); // Default fallback
        }
    }
    
    /**
     * Get portfolio formula configuration
     */
    private function getPortfolioFormulaConfig(): array
    {
        $config = Configuration::getValue('portfolio_calculation_formula', json_encode([
            'type' => 'sum_all_products',
            'description' => 'Sum all product amounts across all products'
        ]));
        
        return is_string($config) ? json_decode($config, true) : $config;
    }
    
    /**
     * Sum all products using designated portfolio_value_field (ENHANCED)
     */
    private function sumAllProducts(): array
    {
        $products = Product::where('is_active', true)->get();
        $totalValue = 0;
        $totalAccounts = 0;
        $breakdown = [];
        
        foreach ($products as $product) {
            $portfolioField = $product->getPortfolioValueField();
            
            // Sum the designated field for this product
            $productValue = $this->sumProductField($product, $portfolioField);
            $productCount = ProductData::where('product_id', $product->id)->count();
            
            $totalValue += $productValue;
            $totalAccounts += $productCount;
            
            // Track breakdown for reporting
            $breakdown[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'portfolio_field' => $portfolioField,
                'value' => $productValue,
                'accounts' => $productCount
            ];
        }
        
        return [
            'value' => round($totalValue, 2),
            'accounts' => $totalAccounts,
            'method' => 'sum_all_products',
            'description' => 'Sum of all product portfolio values using designated fields',
            'breakdown' => $breakdown
        ];
    }
    
    /**
     * Sum active accounts only (ENHANCED)
     */
    private function sumActiveOnly(): array
    {
        $products = Product::where('is_active', true)->get();
        $totalValue = 0;
        $totalAccounts = 0;
        
        foreach ($products as $product) {
            $portfolioField = $product->getPortfolioValueField();
            $productValue = $this->sumProductField($product, $portfolioField, ['status' => 'active']);
            $productCount = ProductData::where('product_id', $product->id)
                ->where('status', 'active')
                ->count();
            
            $totalValue += $productValue;
            $totalAccounts += $productCount;
        }
        
        return [
            'value' => round($totalValue, 2),
            'accounts' => $totalAccounts,
            'method' => 'sum_active_only',
            'description' => 'Sum of active accounts only using designated portfolio fields'
        ];
    }
    
    /**
     * Sum excluding NPL accounts (ENHANCED)
     */
    private function sumExcludeNpl(): array
    {
        $products = Product::where('is_active', true)->get();
        $totalValue = 0;
        $totalAccounts = 0;
        
        foreach ($products as $product) {
            $portfolioField = $product->getPortfolioValueField();
            
            // Sum all non-NPL records for this product
            $productValue = ProductData::where('product_id', $product->id)
                ->where('status', '!=', 'npl')
                ->get()
                ->sum(function($record) use ($portfolioField) {
                    return $this->getFieldValue($record, $portfolioField);
                });
            
            $productCount = ProductData::where('product_id', $product->id)
                ->where('status', '!=', 'npl')
                ->count();
            
            $totalValue += $productValue;
            $totalAccounts += $productCount;
        }
        
        return [
            'value' => round($totalValue, 2),
            'accounts' => $totalAccounts,
            'method' => 'sum_exclude_npl',
            'description' => 'Sum excluding NPL accounts using designated portfolio fields'
        ];
    }
    
    /**
     * Weighted sum by product category
     */
    private function weightedSum(): array
    {
        $products = Product::with('productData')->get();
        $totalValue = 0;
        $totalAccounts = 0;
        
        // Define category weights
        $categoryWeights = [
            'Loan' => 1.0,        // Full weight
            'Account' => 0.8,     // 80% weight
            'Deposit' => 0.6,     // 60% weight
            'Other' => 0.4        // 40% weight
        ];
        
        foreach ($products as $product) {
            $categoryWeight = $categoryWeights[$product->category] ?? 0.5;
            $productValue = $product->productData->sum('amount') * $categoryWeight;
            $productAccounts = $product->productData->count();
            
            $totalValue += $productValue;
            $totalAccounts += $productAccounts;
        }
        
        return [
            'value' => $totalValue,
            'accounts' => $totalAccounts,
            'method' => 'weighted_sum',
            'description' => 'Weighted sum by product category (Loans: 100%, Accounts: 80%, Deposits: 60%, Other: 40%)'
        ];
    }
    
    /**
     * Execute custom formula
     */
    private function executeCustomFormula(array $formulaConfig): array
    {
        $customFormula = Configuration::getValue('portfolio_custom_formula', 'SUM(amount) WHERE status = "active"');
        
        try {
            // Parse and execute the custom formula
            // This is a simplified implementation - in production, you'd want a more robust formula parser
            if (strpos($customFormula, 'SUM(amount)') !== false) {
                $query = ProductData::query();
                
                // Handle WHERE conditions
                if (strpos($customFormula, 'WHERE') !== false) {
                    $whereClause = substr($customFormula, strpos($customFormula, 'WHERE') + 5);
                    $whereClause = trim($whereClause);
                    
                    if (strpos($whereClause, 'status = "active"') !== false) {
                        $query->where('status', 'active');
                    } elseif (strpos($whereClause, 'status != "npl"') !== false) {
                        $query->where('status', '!=', 'npl');
                    }
                }
                
                $totalValue = $query->sum('amount');
                $totalAccounts = $query->count();
                
                return [
                    'value' => $totalValue,
                    'accounts' => $totalAccounts,
                    'method' => 'custom_formula',
                    'description' => "Custom formula: $customFormula"
                ];
            }
            
            // Fallback to sum all products if formula parsing fails
            return $this->sumAllProducts();
            
        } catch (\Exception $e) {
            // Fallback to sum all products if custom formula fails
            return $this->sumAllProducts();
        }
    }
    
    /**
     * Get available formula options
     */
    public function getAvailableFormulaOptions(): array
    {
        $config = $this->getPortfolioFormulaConfig();
        return $config['options'] ?? [];
    }
    
    /**
     * Update portfolio calculation formula
     */
    public function updatePortfolioFormula(string $type, string $customFormula = null): bool
    {
        try {
            $config = $this->getPortfolioFormulaConfig();
            $config['type'] = $type;
            
            Configuration::updateOrCreate(
                ['key' => 'portfolio_calculation_formula'],
                [
                    'value' => json_encode($config),
                    'description' => 'Formula configuration for calculating total portfolio value'
                ]
            );
            
            if ($customFormula) {
                Configuration::updateOrCreate(
                    ['key' => 'portfolio_custom_formula'],
                    [
                        'value' => $customFormula,
                        'description' => 'Custom formula for portfolio calculation'
                    ]
                );
            }
            
            // Clear dashboard cache to apply new formula
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sum a specific field for a product (HELPER METHOD)
     */
    private function sumProductField(Product $product, string $fieldName, array $filters = []): float
    {
        $query = ProductData::where('product_id', $product->id);
        
        // Apply filters if provided
        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }
        
        $records = $query->get();
        
        $total = $records->sum(function($record) use ($fieldName) {
            return $this->getFieldValue($record, $fieldName);
        });
        
        return (float) $total;
    }

    /**
     * Get field value from ProductData record (HELPER METHOD)
     */
    private function getFieldValue(ProductData $record, string $fieldName): float
    {
        // Check if field is in main columns (amount, effective_date, status)
        if ($fieldName === 'amount' && $record->amount !== null) {
            return (float) $record->amount;
        }
        
        // Otherwise, look in JSON data column
        $data = $record->data ?? [];
        $value = $data[$fieldName] ?? 0;
        
        return is_numeric($value) ? (float) $value : 0;
    }
}
