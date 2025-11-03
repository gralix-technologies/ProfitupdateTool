<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ProductData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ProfitabilityService
{
    
    public function calculateCustomerProfitability(Customer $customer): array
    {
        $productData = $customer->productData()->with('product')->get();
        
        $totalLoansOutstanding = $this->calculateTotalLoansOutstanding($productData);
        $totalDeposits = $this->calculateTotalDeposits($productData);
        $nplExposure = $this->calculateNPLExposure($productData);
        
        $interestEarned = $this->calculateInterestEarned($productData);
        $interestPaid = $this->calculateInterestPaid($productData);
        $operationalCosts = $this->calculateOperationalCosts($productData);
        
        $profitability = $interestEarned - $interestPaid - $operationalCosts;
        
        // Calculate total revenue (interest earned is the primary revenue)
        $totalRevenue = $interestEarned;
        
        return [
            'customer_id' => $customer->customer_id,
            'total_loans_outstanding' => $totalLoansOutstanding,
            'total_deposits' => $totalDeposits,
            'npl_exposure' => $nplExposure,
            'interest_earned' => $interestEarned,
            'interest_paid' => $interestPaid,
            'operational_costs' => $operationalCosts,
            'profitability' => $profitability,
            'profitability_margin' => $totalRevenue > 0 ? ($profitability / $totalRevenue) * 100 : 0,
            'npl_ratio' => $totalLoansOutstanding > 0 ? ($nplExposure / $totalLoansOutstanding) * 100 : 0,
            'risk_level' => $this->determineRiskLevel($nplExposure, $totalLoansOutstanding)
        ];
    }

    
    public function calculateTotalLoansOutstanding($productData): float
    {
        return $productData
            ->filter(function ($data) {
                return $data->product && $data->product->category === 'Loan';
            })
            ->sum(function ($data) {
                $amount = $data->data['amount'] ?? $data->data['outstanding_balance'] ?? 0;
                return (float) $amount;
            });
    }

    
    public function calculateTotalDeposits($productData): float
    {
        return $productData
            ->filter(function ($data) {
                return $data->product && in_array($data->product->category, ['Deposit', 'Account']);
            })
            ->sum(function ($data) {
                $amount = $data->data['amount'] ?? $data->data['balance'] ?? 0;
                return (float) $amount;
            });
    }

    
    public function calculateNPLExposure($productData): float
    {
        return $productData
            ->filter(function ($data) {
                return $data->product && 
                       $data->product->category === 'Loan' && 
                       (($data->data['status'] ?? '') === 'npl' || 
                        ($data->data['status'] ?? '') === 'non_performing' ||
                        ($data->data['days_past_due'] ?? 0) > 90);
            })
            ->sum(function ($data) {
                $amount = $data->data['amount'] ?? $data->data['outstanding_balance'] ?? 0;
                return (float) $amount;
            });
    }

    
    public function calculateInterestEarned($productData): float
    {
        return $productData
            ->filter(function ($data) {
                return $data->product && $data->product->category === 'Loan';
            })
            ->sum(function ($data) {
                $amount = (float) ($data->data['amount'] ?? $data->data['outstanding_balance'] ?? 0);
                $interestRate = (float) ($data->data['interest_rate'] ?? 8.0) / 100; // Default 8%
                
                // Calculate annual interest earned (not total over term)
                return $amount * $interestRate;
            });
    }

    
    public function calculateInterestPaid($productData): float
    {
        return $productData
            ->filter(function ($data) {
                return $data->product && in_array($data->product->category, ['Deposit', 'Account']);
            })
            ->sum(function ($data) {
                $amount = (float) ($data->data['amount'] ?? $data->data['balance'] ?? 0);
                $interestRate = (float) ($data->data['interest_rate'] ?? 2.0) / 100; // Default 2%
                
                // Calculate annual interest paid (not total over term)
                return $amount * $interestRate;
            });
    }

    
    public function calculateOperationalCosts($productData): float
    {
        $baseCostPerProduct = 50; // Base annual cost per product
        $loanProcessingCost = 150; // Annual cost for loan management
        $depositMaintenanceCost = 25; // Annual cost for deposit management
        
        $totalCost = $productData->count() * $baseCostPerProduct;
        
        $loanCount = $productData->filter(function ($data) {
            return $data->product && $data->product->category === 'Loan';
        })->count();
        
        $depositCount = $productData->filter(function ($data) {
            return $data->product && in_array($data->product->category, ['Deposit', 'Account']);
        })->count();
        
        $totalCost += ($loanCount * $loanProcessingCost) + ($depositCount * $depositMaintenanceCost);
        
        // Add risk-based costs for NPL exposure
        $nplExposure = $this->calculateNPLExposure($productData);
        $nplCostFactor = 0.001; // 0.1% of NPL exposure as additional operational cost
        $totalCost += $nplExposure * $nplCostFactor;
        
        return $totalCost;
    }

    
    public function getBranchProfitability(string $branchCode): array
    {
        $customers = Customer::where('branch_code', $branchCode)->get();
        
        $totalProfitability = 0;
        $totalLoans = 0;
        $totalDeposits = 0;
        $totalNPL = 0;
        $customerCount = $customers->count();
        
        $riskDistribution = ['Low' => 0, 'Medium' => 0, 'High' => 0];
        
        foreach ($customers as $customer) {
            $profitability = $this->calculateCustomerProfitability($customer);
            
            $totalProfitability += $profitability['profitability'];
            $totalLoans += $profitability['total_loans_outstanding'];
            $totalDeposits += $profitability['total_deposits'];
            $totalNPL += $profitability['npl_exposure'];
            
            $riskDistribution[$profitability['risk_level']]++;
        }
        
        return [
            'branch_code' => $branchCode,
            'customer_count' => $customerCount,
            'total_profitability' => $totalProfitability,
            'average_profitability' => $customerCount > 0 ? $totalProfitability / $customerCount : 0,
            'total_loans_outstanding' => $totalLoans,
            'total_deposits' => $totalDeposits,
            'total_npl_exposure' => $totalNPL,
            'npl_ratio' => $totalLoans > 0 ? ($totalNPL / $totalLoans) * 100 : 0,
            'loan_to_deposit_ratio' => $totalDeposits > 0 ? ($totalLoans / $totalDeposits) * 100 : 0,
            'risk_distribution' => $riskDistribution,
            'profitability_per_customer' => $customerCount > 0 ? $totalProfitability / $customerCount : 0
        ];
    }

    
    public function getNPLExposure(Customer $customer): array
    {
        $productData = $customer->productData()->with('product')->get();
        
        $totalLoans = $this->calculateTotalLoansOutstanding($productData);
        $nplExposure = $this->calculateNPLExposure($productData);
        
        $nplProducts = $productData->filter(function ($data) {
            return $data->product && 
                   $data->product->category === 'Loan' && 
                   (($data->data['status'] ?? '') === 'npl' || 
                    ($data->data['status'] ?? '') === 'non_performing' ||
                    ($data->data['days_past_due'] ?? 0) > 90);
        });
        
        return [
            'customer_id' => $customer->customer_id,
            'total_loans_outstanding' => $totalLoans,
            'npl_exposure' => $nplExposure,
            'npl_ratio' => $totalLoans > 0 ? ($nplExposure / $totalLoans) * 100 : 0,
            'npl_product_count' => $nplProducts->count(),
            'performing_loans' => $totalLoans - $nplExposure,
            'risk_level' => $this->determineRiskLevel($nplExposure, $totalLoans),
            'npl_products' => $nplProducts->map(function ($data) {
                return [
                    'product_name' => $data->product->name,
                    'amount' => $data->data['amount'] ?? $data->data['outstanding_balance'] ?? 0,
                    'days_past_due' => $data->data['days_past_due'] ?? 0,
                    'status' => $data->data['status'] ?? 'unknown'
                ];
            })->toArray()
        ];
    }

    
    private function determineRiskLevel(float $nplExposure, float $totalLoans): string
    {
        if ($totalLoans == 0) {
            return 'Low';
        }
        
        $nplRatio = ($nplExposure / $totalLoans) * 100;
        
        if ($nplRatio < 2) {
            return 'Low';
        } elseif ($nplRatio < 5) {
            return 'Medium';
        } else {
            return 'High';
        }
    }

    
    public function updateCustomerMetrics(Customer $customer): void
    {
        $profitability = $this->calculateCustomerProfitability($customer);
        
        $customer->update([
            'total_loans_outstanding' => $profitability['total_loans_outstanding'],
            'total_deposits' => $profitability['total_deposits'],
            'npl_exposure' => $profitability['npl_exposure'],
            'profitability' => $profitability['profitability'],
            'risk_level' => $profitability['risk_level']
        ]);
    }

    
    public function getTopProfitableCustomers(int $limit = 10, ?string $branchCode = null): array
    {
        $query = Customer::query();
        
        if ($branchCode) {
            $query->where('branch_code', $branchCode);
        }
        
        $customers = $query->get();
        
        $profitabilityData = [];
        
        foreach ($customers as $customer) {
            $profitability = $this->calculateCustomerProfitability($customer);
            $profitabilityData[] = array_merge($profitability, [
                'customer_name' => $customer->name,
                'branch_code' => $customer->branch_code
            ]);
        }
        
        usort($profitabilityData, function ($a, $b) {
            return $b['profitability'] <=> $a['profitability'];
        });
        
        return array_slice($profitabilityData, 0, $limit);
    }
}


