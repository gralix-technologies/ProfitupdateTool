<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ProductData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizedProfitabilityService
{
    public function calculateCustomerProfitability(Customer $customer): array
    {
        // Use database aggregation instead of loading all data
        $profitabilityData = $this->getCustomerProfitabilityFromDB($customer->customer_id);
        
        return [
            'customer_id' => $customer->customer_id,
            'total_loans_outstanding' => $profitabilityData['total_loans_outstanding'],
            'total_deposits' => $profitabilityData['total_deposits'],
            'npl_exposure' => $profitabilityData['npl_exposure'],
            'interest_earned' => $profitabilityData['interest_earned'],
            'interest_paid' => $profitabilityData['interest_paid'],
            'operational_costs' => $profitabilityData['operational_costs'],
            'profitability' => $profitabilityData['profitability'],
            'profitability_margin' => $profitabilityData['profitability_margin'],
            'npl_ratio' => $profitabilityData['npl_ratio'],
            'risk_level' => $profitabilityData['risk_level']
        ];
    }

    protected function getCustomerProfitabilityFromDB(string $customerId): array
    {
        // Use database aggregation for better performance
        $data = DB::table('product_data as pd')
            ->join('products as p', 'pd.product_id', '=', 'p.id')
            ->where('pd.customer_id', $customerId)
            ->selectRaw("
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_loans_outstanding,
                SUM(CASE 
                    WHEN p.category IN ('Deposit', 'Account') THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_deposits,
                SUM(CASE 
                    WHEN p.category = 'Loan' AND (
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'npl' OR
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'non_performing' OR
                        CAST(JSON_EXTRACT(pd.data, '$.days_past_due') AS UNSIGNED) > 90
                    ) THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as npl_exposure,
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN 
                        CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2)) * 
                        COALESCE(CAST(JSON_EXTRACT(pd.data, '$.interest_rate') AS DECIMAL(5,2)), 8.0) / 100
                    ELSE 0 
                END) as interest_earned,
                SUM(CASE 
                    WHEN p.category IN ('Deposit', 'Account') THEN 
                        CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2)) * 
                        COALESCE(CAST(JSON_EXTRACT(pd.data, '$.interest_rate') AS DECIMAL(5,2)), 2.0) / 100
                    ELSE 0 
                END) as interest_paid,
                COUNT(*) as product_count,
                SUM(CASE WHEN p.category = 'Loan' THEN 1 ELSE 0 END) as loan_count,
                SUM(CASE WHEN p.category IN ('Deposit', 'Account') THEN 1 ELSE 0 END) as deposit_count
            ")
            ->first();

        $totalLoansOutstanding = (float) ($data->total_loans_outstanding ?? 0);
        $totalDeposits = (float) ($data->total_deposits ?? 0);
        $nplExposure = (float) ($data->npl_exposure ?? 0);
        $interestEarned = (float) ($data->interest_earned ?? 0);
        $interestPaid = (float) ($data->interest_paid ?? 0);
        $productCount = (int) ($data->product_count ?? 0);
        $loanCount = (int) ($data->loan_count ?? 0);
        $depositCount = (int) ($data->deposit_count ?? 0);

        // Calculate operational costs
        $baseCostPerProduct = 50;
        $loanProcessingCost = 150;
        $depositMaintenanceCost = 25;
        $nplCostFactor = 0.001;

        $operationalCosts = ($productCount * $baseCostPerProduct) + 
                          ($loanCount * $loanProcessingCost) + 
                          ($depositCount * $depositMaintenanceCost) + 
                          ($nplExposure * $nplCostFactor);

        $profitability = $interestEarned - $interestPaid - $operationalCosts;
        $totalRevenue = $interestEarned;

        return [
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

    public function getBranchProfitability(string $branchCode): array
    {
        // Use database aggregation for better performance
        $data = DB::table('customers as c')
            ->join('product_data as pd', 'c.customer_id', '=', 'pd.customer_id')
            ->join('products as p', 'pd.product_id', '=', 'p.id')
            ->where('c.branch_code', $branchCode)
            ->selectRaw("
                COUNT(DISTINCT c.customer_id) as customer_count,
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_loans_outstanding,
                SUM(CASE 
                    WHEN p.category IN ('Deposit', 'Account') THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_deposits,
                SUM(CASE 
                    WHEN p.category = 'Loan' AND (
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'npl' OR
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'non_performing' OR
                        CAST(JSON_EXTRACT(pd.data, '$.days_past_due') AS UNSIGNED) > 90
                    ) THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_npl_exposure,
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN 
                        CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2)) * 
                        COALESCE(CAST(JSON_EXTRACT(pd.data, '$.interest_rate') AS DECIMAL(5,2)), 8.0) / 100
                    ELSE 0 
                END) as total_interest_earned,
                SUM(CASE 
                    WHEN p.category IN ('Deposit', 'Account') THEN 
                        CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2)) * 
                        COALESCE(CAST(JSON_EXTRACT(pd.data, '$.interest_rate') AS DECIMAL(5,2)), 2.0) / 100
                    ELSE 0 
                END) as total_interest_paid,
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN 1 ELSE 0 
                END) as loan_count,
                SUM(CASE 
                    WHEN p.category IN ('Deposit', 'Account') THEN 1 ELSE 0 
                END) as deposit_count,
                COUNT(*) as total_product_count
            ")
            ->first();

        $customerCount = (int) ($data->customer_count ?? 0);
        $totalLoansOutstanding = (float) ($data->total_loans_outstanding ?? 0);
        $totalDeposits = (float) ($data->total_deposits ?? 0);
        $totalNplExposure = (float) ($data->total_npl_exposure ?? 0);
        $totalInterestEarned = (float) ($data->total_interest_earned ?? 0);
        $totalInterestPaid = (float) ($data->total_interest_paid ?? 0);
        $loanCount = (int) ($data->loan_count ?? 0);
        $depositCount = (int) ($data->deposit_count ?? 0);
        $totalProductCount = (int) ($data->total_product_count ?? 0);

        // Calculate operational costs
        $baseCostPerProduct = 50;
        $loanProcessingCost = 150;
        $depositMaintenanceCost = 25;
        $nplCostFactor = 0.001;

        $totalOperationalCosts = ($totalProductCount * $baseCostPerProduct) + 
                                ($loanCount * $loanProcessingCost) + 
                                ($depositCount * $depositMaintenanceCost) + 
                                ($totalNplExposure * $nplCostFactor);

        $totalProfitability = $totalInterestEarned - $totalInterestPaid - $totalOperationalCosts;

        // Get risk distribution
        $riskDistribution = $this->getRiskDistribution($branchCode);

        return [
            'branch_code' => $branchCode,
            'customer_count' => $customerCount,
            'total_profitability' => $totalProfitability,
            'average_profitability' => $customerCount > 0 ? $totalProfitability / $customerCount : 0,
            'total_loans_outstanding' => $totalLoansOutstanding,
            'total_deposits' => $totalDeposits,
            'total_npl_exposure' => $totalNplExposure,
            'npl_ratio' => $totalLoansOutstanding > 0 ? ($totalNplExposure / $totalLoansOutstanding) * 100 : 0,
            'loan_to_deposit_ratio' => $totalDeposits > 0 ? ($totalLoansOutstanding / $totalDeposits) * 100 : 0,
            'risk_distribution' => $riskDistribution,
            'profitability_per_customer' => $customerCount > 0 ? $totalProfitability / $customerCount : 0
        ];
    }

    protected function getRiskDistribution(string $branchCode): array
    {
        $riskData = DB::table('customers as c')
            ->join('product_data as pd', 'c.customer_id', '=', 'pd.customer_id')
            ->join('products as p', 'pd.product_id', '=', 'p.id')
            ->where('c.branch_code', $branchCode)
            ->selectRaw("
                c.customer_id,
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_loans_outstanding,
                SUM(CASE 
                    WHEN p.category = 'Loan' AND (
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'npl' OR
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'non_performing' OR
                        CAST(JSON_EXTRACT(pd.data, '$.days_past_due') AS UNSIGNED) > 90
                    ) THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as npl_exposure
            ")
            ->groupBy('c.customer_id')
            ->get();

        $riskDistribution = ['Low' => 0, 'Medium' => 0, 'High' => 0];

        foreach ($riskData as $customer) {
            $riskLevel = $this->determineRiskLevel(
                (float) $customer->npl_exposure,
                (float) $customer->total_loans_outstanding
            );
            $riskDistribution[$riskLevel]++;
        }

        return $riskDistribution;
    }

    public function getTopProfitableCustomers(int $limit = 10, ?string $branchCode = null): array
    {
        // Use database aggregation for better performance
        $query = DB::table('customers as c')
            ->join('product_data as pd', 'c.customer_id', '=', 'pd.customer_id')
            ->join('products as p', 'pd.product_id', '=', 'p.id');

        if ($branchCode) {
            $query->where('c.branch_code', $branchCode);
        }

        $results = $query->selectRaw("
                c.customer_id,
                c.name,
                c.branch_code,
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_loans_outstanding,
                SUM(CASE 
                    WHEN p.category IN ('Deposit', 'Account') THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as total_deposits,
                SUM(CASE 
                    WHEN p.category = 'Loan' AND (
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'npl' OR
                        JSON_UNQUOTE(JSON_EXTRACT(pd.data, '$.status')) = 'non_performing' OR
                        CAST(JSON_EXTRACT(pd.data, '$.days_past_due') AS UNSIGNED) > 90
                    ) THEN CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2))
                    ELSE 0 
                END) as npl_exposure,
                SUM(CASE 
                    WHEN p.category = 'Loan' THEN 
                        CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2)) * 
                        COALESCE(CAST(JSON_EXTRACT(pd.data, '$.interest_rate') AS DECIMAL(5,2)), 8.0) / 100
                    ELSE 0 
                END) as interest_earned,
                SUM(CASE 
                    WHEN p.category IN ('Deposit', 'Account') THEN 
                        CAST(JSON_EXTRACT(pd.data, '$.amount') AS DECIMAL(15,2)) * 
                        COALESCE(CAST(JSON_EXTRACT(pd.data, '$.interest_rate') AS DECIMAL(5,2)), 2.0) / 100
                    ELSE 0 
                END) as interest_paid,
                COUNT(*) as product_count,
                SUM(CASE WHEN p.category = 'Loan' THEN 1 ELSE 0 END) as loan_count,
                SUM(CASE WHEN p.category IN ('Deposit', 'Account') THEN 1 ELSE 0 END) as deposit_count
            ")
            ->groupBy('c.customer_id', 'c.name', 'c.branch_code')
            ->orderByRaw('(SUM(CASE WHEN p.category = "Loan" THEN CAST(JSON_EXTRACT(pd.data, "$.amount") AS DECIMAL(15,2)) * COALESCE(CAST(JSON_EXTRACT(pd.data, "$.interest_rate") AS DECIMAL(5,2)), 8.0) / 100 ELSE 0 END) - SUM(CASE WHEN p.category IN ("Deposit", "Account") THEN CAST(JSON_EXTRACT(pd.data, "$.amount") AS DECIMAL(15,2)) * COALESCE(CAST(JSON_EXTRACT(pd.data, "$.interest_rate") AS DECIMAL(5,2)), 2.0) / 100 ELSE 0 END) - ((COUNT(*) * 50) + (SUM(CASE WHEN p.category = "Loan" THEN 1 ELSE 0 END) * 150) + (SUM(CASE WHEN p.category IN ("Deposit", "Account") THEN 1 ELSE 0 END) * 25) + (SUM(CASE WHEN p.category = "Loan" AND (JSON_UNQUOTE(JSON_EXTRACT(pd.data, "$.status")) = "npl" OR JSON_UNQUOTE(JSON_EXTRACT(pd.data, "$.status")) = "non_performing" OR CAST(JSON_EXTRACT(pd.data, "$.days_past_due") AS UNSIGNED) > 90) THEN CAST(JSON_EXTRACT(pd.data, "$.amount") AS DECIMAL(15,2)) ELSE 0 END) * 0.001))) DESC')
            ->limit($limit)
            ->get();

        $profitabilityData = [];
        foreach ($results as $result) {
            $totalLoansOutstanding = (float) $result->total_loans_outstanding;
            $totalDeposits = (float) $result->total_deposits;
            $nplExposure = (float) $result->npl_exposure;
            $interestEarned = (float) $result->interest_earned;
            $interestPaid = (float) $result->interest_paid;
            $productCount = (int) $result->product_count;
            $loanCount = (int) $result->loan_count;
            $depositCount = (int) $result->deposit_count;

            // Calculate operational costs
            $baseCostPerProduct = 50;
            $loanProcessingCost = 150;
            $depositMaintenanceCost = 25;
            $nplCostFactor = 0.001;

            $operationalCosts = ($productCount * $baseCostPerProduct) + 
                              ($loanCount * $loanProcessingCost) + 
                              ($depositCount * $depositMaintenanceCost) + 
                              ($nplExposure * $nplCostFactor);

            $profitability = $interestEarned - $interestPaid - $operationalCosts;
            $totalRevenue = $interestEarned;

            $profitabilityData[] = [
                'customer_id' => $result->customer_id,
                'customer_name' => $result->name,
                'branch_code' => $result->branch_code,
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

        return $profitabilityData;
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
}
