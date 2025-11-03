<?php

namespace App\Services;

use App\Models\ProductData;
use Carbon\Carbon;

class DashboardFilterService
{
    
    public function getFilterOptions(int $productId): array
    {
        $records = ProductData::where('product_id', $productId)->get();
        
        if ($records->isEmpty()) {
            return [];
        }

        $dates = $records->pluck('data.disbursement_date')->filter()->sort()->values();
        $minDate = $dates->first();
        $maxDate = $dates->last();

        $outstandingBalances = $records->pluck('data.outstanding_balance')->filter();
        $minBalance = $outstandingBalances->min();
        $maxBalance = $outstandingBalances->max();

        $daysPastDue = $records->pluck('data.days_past_due')->filter();
        $minDays = $daysPastDue->min();
        $maxDays = $daysPastDue->max();

        return [
            'date_range' => [
                'type' => 'date_range',
                'label' => 'Date Range',
                'options' => [
                    'last_30_days',
                    'last_90_days', 
                    'last_6_months',
                    'last_12_months',
                    'last_24_months',
                    'custom'
                ],
                'min_date' => $minDate,
                'max_date' => $maxDate
            ],
            'branch' => [
                'type' => 'select',
                'label' => 'Branch',
                'options' => $this->formatOptions($records->pluck('data.branch_code')->unique()->values()->toArray()),
                'multiple' => true
            ],
            'sector' => [
                'type' => 'select', 
                'label' => 'Sector',
                'options' => $this->formatOptions($records->pluck('data.sector')->unique()->values()->toArray()),
                'multiple' => true
            ],
            'credit_rating' => [
                'type' => 'select',
                'label' => 'Credit Rating',
                'options' => $this->formatOptions($records->pluck('data.credit_rating')->unique()->values()->toArray()),
                'multiple' => true
            ],
            'currency' => [
                'type' => 'select',
                'label' => 'Currency',
                'options' => $this->formatOptions($records->pluck('data.currency')->unique()->values()->toArray()),
                'multiple' => false
            ],
            'collateral_type' => [
                'type' => 'select',
                'label' => 'Collateral Type',
                'options' => $this->formatOptions($records->pluck('data.collateral_type')->unique()->values()->toArray()),
                'multiple' => true
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => $this->formatOptions($records->pluck('data.status')->unique()->filter()->values()->toArray()),
                'multiple' => true
            ],
            'account_officer' => [
                'type' => 'select',
                'label' => 'Account Officer',
                'options' => $this->formatOptions($records->pluck('data.account_officer')->unique()->values()->toArray()),
                'multiple' => true
            ],
            'outstanding_balance_range' => [
                'type' => 'range',
                'label' => 'Outstanding Balance Range',
                'min' => round($minBalance),
                'max' => round($maxBalance),
                'step' => 1000,
                'unit' => 'ZMW'
            ],
            'days_past_due_range' => [
                'type' => 'range',
                'label' => 'Days Past Due Range',
                'min' => $minDays,
                'max' => $maxDays,
                'step' => 1,
                'unit' => 'days'
            ]
        ];
    }

    
    public function applyFilters($query, array $filters): void
    {
        foreach ($filters as $filterKey => $filterValue) {
            if (empty($filterValue)) {
                continue;
            }

            switch ($filterKey) {
                case 'date_range':
                    $this->applyDateRangeFilter($query, $filterValue);
                    break;
                case 'branch':
                    $this->applySelectFilter($query, 'branch_code', $filterValue);
                    break;
                case 'sector':
                    $this->applySelectFilter($query, 'sector', $filterValue);
                    break;
                case 'credit_rating':
                    $this->applySelectFilter($query, 'credit_rating', $filterValue);
                    break;
                case 'currency':
                    $this->applySelectFilter($query, 'currency', $filterValue);
                    break;
                case 'collateral_type':
                    $this->applySelectFilter($query, 'collateral_type', $filterValue);
                    break;
                case 'status':
                    $this->applySelectFilter($query, 'status', $filterValue);
                    break;
                case 'account_officer':
                    $this->applySelectFilter($query, 'account_officer', $filterValue);
                    break;
                case 'outstanding_balance_range':
                    $this->applyRangeFilter($query, 'outstanding_balance', $filterValue);
                    break;
                case 'days_past_due_range':
                    $this->applyRangeFilter($query, 'days_past_due', $filterValue);
                    break;
            }
        }
    }

    
    private function applyDateRangeFilter($query, $filterValue): void
    {
        if (is_array($filterValue) && isset($filterValue['start']) && isset($filterValue['end'])) {
            $startDate = Carbon::parse($filterValue['start'])->startOfDay();
            $endDate = Carbon::parse($filterValue['end'])->endOfDay();
            
            $query->whereRaw("JSON_EXTRACT(data, '$.disbursement_date') >= ?", [$startDate->toDateString()])
                  ->whereRaw("JSON_EXTRACT(data, '$.disbursement_date') <= ?", [$endDate->toDateString()]);
        } elseif (is_string($filterValue)) {
            $endDate = Carbon::now();
            
            switch ($filterValue) {
                case 'last_30_days':
                    $startDate = $endDate->copy()->subDays(30);
                    break;
                case 'last_90_days':
                    $startDate = $endDate->copy()->subDays(90);
                    break;
                case 'last_6_months':
                    $startDate = $endDate->copy()->subMonths(6);
                    break;
                case 'last_12_months':
                    $startDate = $endDate->copy()->subMonths(12);
                    break;
                case 'last_24_months':
                    $startDate = $endDate->copy()->subMonths(24);
                    break;
                default:
                    return; // No filter applied
            }
            
            $query->whereRaw("JSON_EXTRACT(data, '$.disbursement_date') >= ?", [$startDate->toDateString()])
                  ->whereRaw("JSON_EXTRACT(data, '$.disbursement_date') <= ?", [$endDate->toDateString()]);
        }
    }

    
    private function applySelectFilter($query, string $field, $filterValue): void
    {
        if (is_array($filterValue)) {
            $query->where(function($q) use ($field, $filterValue) {
                foreach ($filterValue as $value) {
                    if (!empty($value)) {
                        $q->orWhereRaw("JSON_EXTRACT(data, '$.{$field}') = ?", [$value]);
                    }
                }
            });
        } else {
            if (!empty($filterValue)) {
                $query->whereRaw("JSON_EXTRACT(data, '$.{$field}') = ?", [$filterValue]);
            }
        }
    }

    
    private function applyRangeFilter($query, string $field, array $range): void
    {
        if (isset($range['min']) && !empty($range['min'])) {
            $query->whereRaw("CAST(JSON_EXTRACT(data, '$.{$field}') AS DECIMAL(15,2)) >= ?", [$range['min']]);
        }
        
        if (isset($range['max']) && !empty($range['max'])) {
            $query->whereRaw("CAST(JSON_EXTRACT(data, '$.{$field}') AS DECIMAL(15,2)) <= ?", [$range['max']]);
        }
    }

    
    private function formatOptions(array $options): array
    {
        return array_map(function($option) {
            return [
                'value' => $option,
                'label' => $option ?: 'N/A'
            ];
        }, array_filter($options));
    }

    
    public function getFilterSummary(array $filters): array
    {
        $summary = [];
        
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }
            
            switch ($key) {
                case 'date_range':
                    if (is_array($value) && isset($value['start']) && isset($value['end'])) {
                        $summary[] = "Date: {$value['start']} to {$value['end']}";
                    } elseif (is_string($value)) {
                        $summary[] = "Date: " . ucwords(str_replace('_', ' ', $value));
                    }
                    break;
                case 'branch':
                    $summary[] = "Branch: " . (is_array($value) ? implode(', ', $value) : $value);
                    break;
                case 'sector':
                    $summary[] = "Sector: " . (is_array($value) ? implode(', ', $value) : $value);
                    break;
                case 'credit_rating':
                    $summary[] = "Credit Rating: " . (is_array($value) ? implode(', ', $value) : $value);
                    break;
                case 'currency':
                    $summary[] = "Currency: " . $value;
                    break;
                case 'status':
                    $summary[] = "Status: " . (is_array($value) ? implode(', ', $value) : $value);
                    break;
                case 'outstanding_balance_range':
                    if (isset($value['min']) && isset($value['max'])) {
                        $summary[] = "Balance: ZMW" . number_format($value['min']) . " - ZMW" . number_format($value['max']);
                    }
                    break;
                case 'days_past_due_range':
                    if (isset($value['min']) && isset($value['max'])) {
                        $summary[] = "Days Past Due: {$value['min']} - {$value['max']} days";
                    }
                    break;
            }
        }
        
        return $summary;
    }
}



