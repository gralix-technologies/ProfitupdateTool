<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductData;
use App\Models\Customer;
use App\Models\Configuration;
use App\Services\CurrencyService;
use App\Services\ConfigurablePortfolioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardStatsService
{
    protected CurrencyService $currencyService;
    protected ConfigurablePortfolioService $portfolioService;

    public function __construct(
        CurrencyService $currencyService,
        ConfigurablePortfolioService $portfolioService
    ) {
        $this->currencyService = $currencyService;
        $this->portfolioService = $portfolioService;
    }
    public function getDashboardStats(): array
    {
        $cacheTtl = (int) Configuration::getValue('dashboard_cache_ttl', 300);
        
        return Cache::remember('dashboard_stats', $cacheTtl, function () {
            return [
                'total_customers' => $this->getTotalCustomers(),
                'portfolio_value' => $this->getPortfolioValue(),
                'npl_metrics' => $this->getNplMetrics(),
                'growth_rate' => $this->getGrowthRate(),
                'risk_alerts_count' => $this->getRiskAlertsCount(),
                'risk_alerts' => $this->getRiskAlerts(),
                'recent_customers' => $this->getRecentCustomers(),
                'portfolio_performance' => $this->getPortfolioPerformance(),
                'product_breakdown' => $this->getProductBreakdown()
            ];
        });
    }

    private function getTotalCustomers(): array
    {
        $total = Customer::count();
        $previousMonth = Customer::where('created_at', '<', now()->subMonth())->count();
        $change = $previousMonth > 0 ? (($total - $previousMonth) / $previousMonth) * 100 : 0;

        return [
            'title' => 'Total Customers',
            'value' => number_format($total),
            'change' => $this->formatChange($change),
            'changeType' => $change >= 0 ? 'positive' : 'negative',
            'icon' => 'users',
            'color' => 'blue',
            'description' => 'Active customers in the system'
        ];
    }

    private function getPortfolioValue(): array
    {
        // Use configurable portfolio service
        $portfolioData = $this->portfolioService->calculatePortfolioValue();
        $totalPortfolioValue = $portfolioData['value'];
        
        // Calculate additional metrics for metadata
        $products = Product::with('productData')->get();
        $activePortfolioValue = 0;
        $nplAmount = 0;
        $previousMonthValue = 0;

        foreach ($products as $product) {
            // Calculate active value (active records only)
            $productActiveValue = $product->productData->where('status', 'active')->sum('amount');
            $activePortfolioValue += $productActiveValue;
            
            // Calculate NPL amount
            $productNplAmount = $product->productData->where('status', 'npl')->sum('amount');
            $nplAmount += $productNplAmount;
            
            // Calculate previous month value (all records for comparison)
            $previousValue = $product->productData
                ->filter(function($data) {
                    return $data->created_at->lt(now()->subMonth());
                })
                ->sum('amount');
            $previousMonthValue += $previousValue;
        }
        
        $change = $previousMonthValue > 0 ? (($totalPortfolioValue - $previousMonthValue) / $previousMonthValue) * 100 : 0;
        $nplPercentage = $totalPortfolioValue > 0 ? ($nplAmount / $totalPortfolioValue) * 100 : 0;

        return [
            'title' => 'Portfolio Value',
            'value' => $this->currencyService->formatAmount($totalPortfolioValue),
            'change' => $this->formatChange($change),
            'changeType' => $change >= 0 ? 'positive' : 'negative',
            'icon' => 'currency-dollar',
            'color' => 'green',
            'description' => $portfolioData['description'],
            'metadata' => [
                'raw_value' => $totalPortfolioValue,
                'active_value' => $activePortfolioValue,
                'npl_amount' => $nplAmount,
                'npl_percentage' => $nplPercentage,
                'previous_month_value' => $previousMonthValue,
                'calculation_method' => $portfolioData['method'],
                'total_accounts' => $portfolioData['accounts'],
                'breakdown' => [
                    'total' => $totalPortfolioValue,
                    'active' => $activePortfolioValue,
                    'npl' => $nplAmount,
                    'npl_percentage' => $nplPercentage
                ]
            ]
        ];
    }

    private function getNplMetrics(): array
    {
        $products = Product::with('productData')->get();
        
        $totalAmount = 0;
        $nplAmount = 0;
        $nplCount = 0;
        $totalCount = 0;

        foreach ($products as $product) {
            $productTotalAmount = $product->productData->sum('amount');
            $productNplAmount = $product->productData->where('status', 'npl')->sum('amount');
            $productNplCount = $product->productData->where('status', 'npl')->count();
            $productTotalCount = $product->productData->count();
            
            $totalAmount += $productTotalAmount;
            $nplAmount += $productNplAmount;
            $nplCount += $productNplCount;
            $totalCount += $productTotalCount;
        }
        
        $nplPercentage = $totalAmount > 0 ? ($nplAmount / $totalAmount) * 100 : 0;
        $nplCountPercentage = $totalCount > 0 ? ($nplCount / $totalCount) * 100 : 0;

        return [
            'title' => 'NPL Ratio',
            'value' => number_format($nplPercentage, 2) . '%',
            'change' => '0.0%', // TODO: Calculate NPL trend
            'changeType' => 'neutral',
            'icon' => 'alert-triangle',
            'color' => $nplPercentage > 5 ? 'red' : ($nplPercentage > 2 ? 'yellow' : 'green'),
            'description' => 'Non-performing loans as percentage of total portfolio',
            'metadata' => [
                'raw_value' => $nplPercentage,
                'npl_amount' => $nplAmount,
                'npl_count' => $nplCount,
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'npl_count_percentage' => $nplCountPercentage,
                'breakdown' => [
                    'npl_amount' => $nplAmount,
                    'npl_percentage' => $nplPercentage,
                    'npl_count' => $nplCount,
                    'total_amount' => $totalAmount
                ]
            ]
        ];
    }

    private function getGrowthRate(): array
    {
        $products = Product::with(['productData' => function($query) {
            $query->where('status', 'active');
        }])->get();

        $currentMonth = 0;
        $previousMonth = 0;

        foreach ($products as $product) {
            $currentValue = $product->productData
                ->filter(function($data) {
                    return $data->created_at->month === now()->month && 
                           $data->created_at->year === now()->year;
                })
                ->sum('amount');
            $currentMonth += $currentValue;
            
            $previousValue = $product->productData
                ->filter(function($data) {
                    return $data->created_at->month === now()->subMonth()->month && 
                           $data->created_at->year === now()->subMonth()->year;
                })
                ->sum('amount');
            $previousMonth += $previousValue;
        }
        
        $growthRate = $previousMonth > 0 ? (($currentMonth - $previousMonth) / $previousMonth) * 100 : 0;

        return [
            'title' => 'Growth Rate',
            'value' => number_format($growthRate, 1) . '%',
            'change' => $this->formatChange($growthRate),
            'changeType' => $growthRate >= 0 ? 'positive' : 'negative',
            'icon' => 'trending-up',
            'color' => 'yellow',
            'description' => 'Monthly growth rate',
            'metadata' => [
                'current_month' => $currentMonth,
                'previous_month' => $previousMonth
            ]
        ];
    }

    private function getRiskAlertsCount(): array
    {
        $riskAlerts = ProductData::where(function($query) {
            $query->where('status', 'npl')
                  ->orWhere('status', 'NPL')
                  ->orWhere('status', 'default')
                  ->orWhere('status', 'delinquent')
                  ->orWhereRaw('JSON_EXTRACT(data, "$.status") = "npl"')
                  ->orWhereRaw('JSON_EXTRACT(data, "$.status") = "NPL"')
                  ->orWhereRaw('JSON_EXTRACT(data, "$.days_past_due") > 90');
        })->count();

        return [
            'title' => 'Risk Alerts',
            'value' => number_format($riskAlerts),
            'change' => $riskAlerts > 0 ? '-' . number_format($riskAlerts) : '0',
            'changeType' => 'positive',
            'icon' => 'alert-triangle',
            'color' => 'red',
            'description' => 'Active risk alerts'
        ];
    }

    private function getPortfolioPerformance(): array
    {
        $performance = [];
        
        // Get all product data, not just active records
        $products = Product::with('productData')->get();

        foreach ($products as $product) {
            // Calculate total amount (all records)
            $totalAmount = $product->productData->sum('amount');
            
            // Calculate active amount and count
            $activeAmount = $product->productData->where('status', 'active')->sum('amount');
            $activeCount = $product->productData->where('status', 'active')->count();
            
            // Calculate NPL amount and count only for Loan products
            if ($product->category === 'Loan') {
                $nplAmount = $product->productData->where('status', 'npl')->sum('amount');
                $nplCount = $product->productData->where('status', 'npl')->count();
                $nplRate = $totalAmount > 0 ? ($nplAmount / $totalAmount) * 100 : 0;
            } else {
                $nplAmount = 0;
                $nplCount = 0;
                $nplRate = 0;
            }
            
            $performance[] = [
                'product_name' => $product->name,
                'product_id' => $product->id,
                'category' => $product->category,
                'total_value' => $totalAmount, // Use total amount (including NPL)
                'active_value' => $activeAmount,
                'active_accounts' => $activeCount,
                'npl_accounts' => $nplCount,
                'npl_rate' => $product->category === 'Loan' ? $nplRate : null,
                'average_value' => $activeCount > 0 ? $activeAmount / $activeCount : 0,
                'used_field' => 'amount'
            ];
        }
        
        return $performance;
    }

    private function getProductBreakdown(): array
    {
        return Product::withCount(['productData as total_accounts'])
        ->with(['productData'])
        ->get()
        ->map(function($product) {
            // Use the amount column directly instead of JSON fields
            $totalValue = $product->productData->sum('amount');
            $activeAccounts = $product->productData->where('status', 'active')->count();
            $activeValue = $product->productData->where('status', 'active')->sum('amount');
            
            $result = [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'total_accounts' => $product->total_accounts,
                'total_value' => $totalValue,
                'active_accounts' => $activeAccounts,
                'active_value' => $activeValue,
                'average_value' => $product->total_accounts > 0 ? ($totalValue / $product->total_accounts) : 0,
                'used_field' => 'amount'
            ];
            
            // Only calculate NPL metrics for Loan products
            if ($product->category === 'Loan') {
                $nplAmount = $product->productData->where('status', 'npl')->sum('amount');
                $nplCount = $product->productData->where('status', 'npl')->count();
                $nplRate = $totalValue > 0 ? ($nplAmount / $totalValue) * 100 : 0;
                
                $result['npl_amount'] = $nplAmount;
                $result['npl_count'] = $nplCount;
                $result['npl_rate'] = $nplRate;
                $result['npl_percentage'] = $nplRate;
            } else {
                $result['npl_amount'] = 0;
                $result['npl_count'] = 0;
                $result['npl_rate'] = null;
                $result['npl_percentage'] = null;
            }
            
            return $result;
        })
        ->toArray();
    }

    public function getRecentCustomers(): array
    {
        return Customer::latest()
            ->take(5)
            ->get()
            ->map(function ($customer) {

                $productData = ProductData::where('customer_id', $customer->customer_id)
                    ->where('status', 'active')
                    ->with('product')
                    ->get();
                
                $portfolioValue = 0;
                foreach ($productData as $data) {
                    $portfolioValue += $data->amount ?? 0;
                }
                
                return [
                    'id' => $customer->id, // Use the numeric primary key, not customer_id
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'portfolio_value' => $portfolioValue,
                    'segment' => $customer->demographics['segment'] ?? 'Standard',
                    'initials' => $this->getInitials($customer->name)
                ];
            })
            ->toArray();
    }

    public function getRiskAlerts(): array
    {
        $alerts = [];
        $nplThreshold = (float) Configuration::getValue('risk_threshold_npl', 5.0);
        $concentrationThreshold = (float) Configuration::getValue('portfolio_concentration_threshold', 30.0);
        

        $nplCustomers = ProductData::where(function($query) {
            $query->where('status', 'npl')
                  ->orWhere('status', 'NPL')
                  ->orWhereRaw('JSON_EXTRACT(data, "$.status") = "npl"')
                  ->orWhereRaw('JSON_EXTRACT(data, "$.status") = "NPL"')
                  ->orWhereRaw('JSON_EXTRACT(data, "$.days_past_due") > 90');
        })
        ->with('customer')
        ->take(3)
        ->get();
        
        foreach ($nplCustomers as $data) {
            $alerts[] = [
                'type' => 'High Risk Customer',
                'description' => "Customer ID: {$data->customer_id} - Account status: NPL",
                'customer_id' => $data->customer->id ?? null, // Use numeric ID for routing
                'customer_name' => $data->customer->name ?? 'Unknown',
                'severity' => 'high',
                'amount' => $data->amount
            ];
        }
        

        $sectors = ProductData::selectRaw('JSON_EXTRACT(data, "$.sector") as sector, COUNT(*) as count, SUM(amount) as total')
            ->where('status', 'active')
            ->groupBy('sector')
            ->havingRaw("total > (SELECT SUM(amount) * {$concentrationThreshold} / 100 FROM product_data WHERE status = 'active')")
            ->take(2)
            ->get();
        
        foreach ($sectors as $sector) {
            $totalPortfolio = ProductData::where('status', 'active')->sum('amount');
            $percentage = $totalPortfolio > 0 ? ($sector->total / $totalPortfolio) * 100 : 0;
            
            $alerts[] = [
                'type' => 'Portfolio Concentration',
                'description' => "High concentration in {$sector->sector} sector",
                'sector' => $sector->sector,
                'percentage' => number_format($percentage, 1),
                'severity' => 'medium'
            ];
        }
        
        return $alerts;
    }

    private function formatChange($change): string
    {
        $sign = $change > 0 ? '+' : '';
        return $sign . number_format($change, 1) . '%';
    }

    private function formatLargeNumber($number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    private function getInitials($name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }

    private function getBestAmountFieldForProduct(Product $product): string
    {
        $fieldDefinitions = $product->field_definitions ?? [];
        

        $amountFields = ['outstanding_balance', 'principal_amount', 'loan_amount', 'balance', 'credit_limit'];
        
        foreach ($amountFields as $field) {
            foreach ($fieldDefinitions as $def) {
                if ($def['name'] === $field) {
                    return $field;
                }
            }
        }
        

        return Configuration::getValue('portfolio_amount_field', 'outstanding_balance');
    }

    public function clearCache(): void
    {
        Cache::forget('dashboard_stats');
    }
}



