<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductData;
use Illuminate\Support\Collection;

class CustomerInsightsService
{
    public function generateInsights(Customer $customer): array
    {
        return [
            'profitability_score' => $this->calculateProfitabilityScore($customer),
            'risk_assessment' => $this->calculateRiskIndicators($customer),
            'product_mix' => $this->analyzeProductMix($customer),
            'recommendations' => $this->generateRecommendations($customer),
            'lifetime_value' => $this->calculateCustomerLifetimeValue($customer),
            'cross_sell_opportunities' => $this->identifyCrossSellOpportunities($customer),
            'behavior_analysis' => $this->analyzeCustomerBehavior($customer)
        ];
    }

    public function calculateProfitabilityScore(Customer $customer): float
    {
        $profitability = $customer->profitability;
        $totalAssets = $customer->total_loans_outstanding + $customer->total_deposits;
        
        if ($totalAssets == 0) {
            return 0.0;
        }

        $roa = ($profitability / $totalAssets) * 100;
        
        return min(100.0, max(0.0, $roa * 10));
    }

    public function analyzeProductMix(Customer $customer): array
    {
        $productData = ProductData::where('customer_id', $customer->customer_id)
            ->with('product')
            ->get();

        $categories = $productData->groupBy('product.category');
        $totalProducts = $productData->count();

        $categoryBreakdown = [];
        foreach ($categories as $category => $products) {
            $categoryBreakdown[$category] = [
                'count' => $products->count(),
                'percentage' => $totalProducts > 0 ? ($products->count() / $totalProducts) * 100 : 0
            ];
        }

        $primaryCategory = $categories->sortByDesc(function ($products) {
            return $products->count();
        })->keys()->first();

        return [
            'categories' => $categoryBreakdown,
            'total_products' => $totalProducts,
            'primary_category' => $primaryCategory
        ];
    }

    public function generateRecommendations(Customer $customer): array
    {
        $recommendations = [];

        if ($customer->profitability < 1000) {
            $recommendations[] = [
                'type' => 'profitability',
                'message' => 'Consider offering premium products to increase profitability',
                'priority' => 'high'
            ];
        }

        if ($customer->total_deposits > 50000 && $customer->total_loans_outstanding < 10000) {
            $recommendations[] = [
                'type' => 'cross_sell',
                'message' => 'Customer has high deposits - consider loan products',
                'priority' => 'medium'
            ];
        }

        if ($customer->total_deposits > 75000 && $customer->profitability > 1000) {
            $recommendations[] = [
                'type' => 'retention',
                'message' => 'High-value customer - consider premium services and relationship management',
                'priority' => 'high'
            ];
        }

        if ($customer->risk_level === 'High') {
            $recommendations[] = [
                'type' => 'risk',
                'message' => 'Monitor customer closely due to high risk level',
                'priority' => 'high'
            ];
        }

        if ($customer->risk_level === 'Low' && $customer->profitability > 1500) {
            $recommendations[] = [
                'type' => 'growth',
                'message' => 'Low-risk profitable customer - explore additional product opportunities',
                'priority' => 'medium'
            ];
        }

        return $recommendations;
    }

    public function calculateCustomerLifetimeValue(Customer $customer): float
    {
        $annualProfitability = $customer->profitability;
        $estimatedYears = 5; // Default assumption
        
        $riskMultiplier = match($customer->risk_level) {
            'Low' => 1.2,
            'Medium' => 1.0,
            'High' => 0.8,
            default => 1.0
        };

        return $annualProfitability * $estimatedYears * $riskMultiplier;
    }

    public function identifyCrossSellOpportunities(Customer $customer): array
    {
        $existingCategories = ProductData::where('customer_id', $customer->customer_id)
            ->with('product')
            ->get()
            ->pluck('product.category')
            ->unique()
            ->toArray();

        $allCategories = ['Loan', 'Account', 'Deposit', 'Transaction'];
        $missingCategories = array_diff($allCategories, $existingCategories);

        $opportunities = [];
        foreach ($missingCategories as $category) {
            $opportunities[] = [
                'category' => $category,
                'reason' => $this->getCrossSellReason($category, $customer),
                'priority' => $this->getCrossSellPriority($category, $customer)
            ];
        }

        return $opportunities;
    }

    public function analyzeCustomerBehavior(Customer $customer): array
    {
        $productData = ProductData::where('customer_id', $customer->customer_id)->get();
        
        $activityLevel = $productData->count() > 3 ? 'High' : ($productData->count() > 1 ? 'Medium' : 'Low');
        
        return [
            'activity_level' => $activityLevel,
            'transaction_frequency' => $this->calculateTransactionFrequency($productData),
            'engagement_score' => $this->calculateEngagementScore($customer, $productData)
        ];
    }

    public function calculateRiskIndicators(Customer $customer): array
    {
        $nplRatio = $customer->total_loans_outstanding > 0 
            ? ($customer->npl_exposure / $customer->total_loans_outstanding) * 100 
            : 0;

        $debtToDepositRatio = $customer->total_deposits > 0 
            ? ($customer->total_loans_outstanding / $customer->total_deposits) * 100 
            : 0;

        $overallRiskScore = $this->calculateOverallRiskScore($customer, $nplRatio, $debtToDepositRatio);

        return [
            'npl_ratio' => round($nplRatio, 2),
            'debt_to_deposit_ratio' => round($debtToDepositRatio, 2),
            'overall_risk_score' => $overallRiskScore
        ];
    }

    public function formatInsightsForDisplay(array $insights): array
    {
        return [
            'summary' => [
                'profitability_score' => round($insights['profitability_score'], 1),
                'lifetime_value' => number_format($insights['lifetime_value'], 2),
                'risk_level' => $insights['risk_assessment']['overall_risk_score']
            ],
            'metrics' => [
                'product_count' => $insights['product_mix']['total_products'],
                'primary_category' => $insights['product_mix']['primary_category'],
                'recommendations_count' => count($insights['recommendations'])
            ],
            'charts' => [
                'product_mix' => $insights['product_mix']['categories'],
                'risk_indicators' => $insights['risk_assessment']
            ]
        ];
    }

    private function getCrossSellReason(string $category, Customer $customer): string
    {
        return match($category) {
            'Loan' => 'Customer has deposits but no loans - lending opportunity',
            'Account' => 'Basic account services could increase engagement',
            'Deposit' => 'Savings products could improve customer stickiness',
            'Transaction' => 'Transaction services could increase fee income',
            default => 'Additional product opportunity'
        };
    }

    private function getCrossSellPriority(string $category, Customer $customer): string
    {
        if ($customer->total_deposits > 50000 && $category === 'Loan') {
            return 'high';
        }
        
        return $customer->risk_level === 'Low' ? 'medium' : 'low';
    }

    private function calculateTransactionFrequency(Collection $productData): string
    {
        $count = $productData->count();
        
        return match(true) {
            $count > 10 => 'High',
            $count > 5 => 'Medium',
            default => 'Low'
        };
    }

    private function calculateEngagementScore(Customer $customer, Collection $productData): int
    {
        $score = 0;
        
        $score += min(50, $productData->count() * 10);
        
        if ($customer->profitability > 5000) $score += 20;
        elseif ($customer->profitability > 1000) $score += 10;
        
        if ($customer->risk_level === 'High') $score -= 20;
        elseif ($customer->risk_level === 'Medium') $score -= 10;
        
        return max(0, min(100, $score));
    }

    private function calculateOverallRiskScore(Customer $customer, float $nplRatio, float $debtToDepositRatio): int
    {
        $score = 0;
        
        if ($nplRatio > 10) $score += 40;
        elseif ($nplRatio > 5) $score += 20;
        elseif ($nplRatio > 2) $score += 10;
        
        if ($debtToDepositRatio > 200) $score += 30;
        elseif ($debtToDepositRatio > 100) $score += 15;
        
        $score += match($customer->risk_level) {
            'High' => 30,
            'Medium' => 15,
            'Low' => 0,
            default => 15
        };
        
        return min(100, $score);
    }
}


