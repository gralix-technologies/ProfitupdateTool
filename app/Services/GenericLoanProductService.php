<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductData;
use App\Models\Formula;
use App\Services\SimpleFormulaEvaluator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GenericLoanProductService
{
    protected SimpleFormulaEvaluator $formulaEvaluator;

    public function __construct(SimpleFormulaEvaluator $formulaEvaluator)
    {
        $this->formulaEvaluator = $formulaEvaluator;
    }

    
    public function createLoanProduct(array $productData): Product
    {
        $productData['category'] = 'Loan';
        
        $standardLoanFields = [
            'loan_id' => ['type' => 'text', 'required' => true, 'unique' => true],
            'customer_id' => ['type' => 'text', 'required' => true],
            'outstanding_balance' => ['type' => 'numeric', 'required' => true, 'precision' => 2],
            'original_principal' => ['type' => 'numeric', 'required' => true, 'precision' => 2],
            'interest_rate_annual' => ['type' => 'numeric', 'required' => true, 'precision' => 6],
            'disbursement_date' => ['type' => 'date', 'required' => true],
            'maturity_date' => ['type' => 'date', 'required' => true],
            'currency' => ['type' => 'text', 'required' => true, 'default' => 'ZMW'],
            'status' => ['type' => 'lookup', 'required' => true, 'options' => ['active', 'closed', 'defaulted']],
        ];

        $fieldDefinitions = array_merge($standardLoanFields, $productData['field_definitions'] ?? []);
        $productData['field_definitions'] = $fieldDefinitions;

        return Product::create($productData);
    }

    
    public function calculateLoanMetrics(ProductData $loanData): array
    {
        $product = $loanData->product;
        $data = array_merge($loanData->data ?? [], [
            'amount' => $loanData->amount,
            'effective_date' => $loanData->effective_date,
            'status' => $loanData->status
        ]);
        
        $formulas = Formula::where('product_id', $product->id)
            ->where('is_active', true)
            ->get();

        $metrics = [];
        
        foreach ($formulas as $formula) {
            try {
                $scope = $formula->parameters['scope'] ?? 'loan';
                
                if ($scope === 'loan') {
                    $result = $this->evaluateFormulaForLoan($formula, $data);
                    $precision = $formula->parameters['precision'] ?? 2;
                    $metrics[$formula->name] = round((float) $result, $precision);
                }
            } catch (\Exception $e) {
                \Log::error("Formula evaluation failed for {$formula->name}: " . $e->getMessage());
                $metrics[$formula->name] = 0.0;
            }
        }

        return $metrics;
    }

    
    public function calculatePortfolioMetrics(Product $loanProduct): array
    {
        $formulas = Formula::where('product_id', $loanProduct->id)
            ->where('is_active', true)
            ->whereJsonContains('parameters->scope', 'portfolio')
            ->get();

        $metrics = [];
        
        foreach ($formulas as $formula) {
            try {
                $result = $this->evaluatePortfolioFormula($formula, $loanProduct);
                $precision = $formula->parameters['precision'] ?? 2;
                $metrics[$formula->name] = round((float) $result, $precision);
            } catch (\Exception $e) {
                \Log::error("Portfolio formula evaluation failed for {$formula->name}: " . $e->getMessage());
                $metrics[$formula->name] = 0.0;
            }
        }

        return $metrics;
    }

    
    protected function evaluateFormulaForLoan(Formula $formula, array $loanData): float
    {
        try {
            $result = $this->formulaEvaluator->evaluate($formula->expression, $loanData);
            return (float) $result;
        } catch (\Exception $e) {
            \Log::error("Formula evaluation failed: " . $e->getMessage());
            return 0.0;
        }
    }

    
    protected function evaluatePortfolioFormula(Formula $formula, Product $product): float
    {
        try {
            $expression = $formula->expression;
            
            if (preg_match('/SUM\(([^)]+)\)/', $expression, $matches)) {
                $field = trim($matches[1]);
                if ($field === 'amount') {
                    return (float) ProductData::where('product_id', $product->id)->sum('amount');
                } else {
                    return $this->aggregateJsonField($product, $field, 'sum');
                }
            }
            
            if (preg_match('/AVG\(([^)]+)\)/', $expression, $matches)) {
                $field = trim($matches[1]);
                if ($field === 'amount') {
                    return (float) ProductData::where('product_id', $product->id)->avg('amount');
                } else {
                    return $this->aggregateJsonField($product, $field, 'avg');
                }
            }
            
            if (preg_match('/COUNT\(\*\)/', $expression)) {
                return (float) ProductData::where('product_id', $product->id)->count();
            }
            
            $records = ProductData::where('product_id', $product->id)->get();
            $total = 0;
            
            foreach ($records as $record) {
                $data = array_merge($record->data ?? [], [
                    'amount' => $record->amount,
                    'effective_date' => $record->effective_date,
                    'status' => $record->status
                ]);
                $total += $this->formulaEvaluator->evaluate($expression, $data);
            }
            
            return $total;
        } catch (\Exception $e) {
            \Log::error("Portfolio formula evaluation failed: " . $e->getMessage());
            return 0.0;
        }
    }

    
    protected function aggregateJsonField(Product $product, string $field, string $operation): float
    {
        $records = ProductData::where('product_id', $product->id)
            ->whereNotNull("data->{$field}")
            ->get();
        
        $values = $records->map(function ($record) use ($field) {
            return (float) $record->getFieldValue($field);
        })->filter(function ($value) {
            return is_numeric($value);
        });
        
        if ($values->isEmpty()) {
            return 0.0;
        }
        
        switch ($operation) {
            case 'sum':
                return $values->sum();
            case 'avg':
                return $values->avg();
            case 'min':
                return $values->min();
            case 'max':
                return $values->max();
            default:
                return 0.0;
        }
    }

    
    public function createDefaultLoanFormulas(Product $product, array $customFormulas = []): void
    {
        $defaultFormulas = [
            [
                'name' => 'Total Outstanding',
                'description' => 'Total outstanding balance across all loans',
                'expression' => 'SUM(outstanding_balance)',
                'return_type' => 'numeric',
                'parameters' => [
                    'precision' => 2,
                    'format' => 'currency',
                    'category' => 'Portfolio',
                    'scope' => 'portfolio'
                ]
            ],
            [
                'name' => 'Average Loan Size',
                'description' => 'Average outstanding balance per loan',
                'expression' => 'AVG(outstanding_balance)',
                'return_type' => 'numeric',
                'parameters' => [
                    'precision' => 2,
                    'format' => 'currency',
                    'category' => 'Portfolio',
                    'scope' => 'portfolio'
                ]
            ],
            [
                'name' => 'Loan Count',
                'description' => 'Total number of active loans',
                'expression' => 'COUNT(*)',
                'return_type' => 'numeric',
                'parameters' => [
                    'precision' => 0,
                    'format' => 'number',
                    'category' => 'Portfolio',
                    'scope' => 'portfolio'
                ]
            ],
            [
                'name' => 'Monthly Interest Income',
                'description' => 'Expected monthly interest income per loan',
                'expression' => 'outstanding_balance * (interest_rate_annual / 12)',
                'return_type' => 'numeric',
                'parameters' => [
                    'precision' => 2,
                    'format' => 'currency',
                    'category' => 'Revenue',
                    'scope' => 'loan'
                ]
            ]
        ];
        
        $allFormulas = array_merge($defaultFormulas, $customFormulas);
        
        $user = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'Admin');
        })->first() ?? \App\Models\User::first();
        
        foreach ($allFormulas as $formulaData) {
            Formula::create(array_merge($formulaData, [
                'product_id' => $product->id,
                'created_by' => $user->id ?? 1,
                'is_active' => true
            ]));
        }
    }

    
    public function migrateProductData(Product $product, string $sourceTable, array $fieldMapping = []): int
    {
        $migratedCount = 0;
        
        try {
            DB::beginTransaction();
            
            $sourceData = DB::table($sourceTable)->get();
            
            foreach ($sourceData as $record) {
                $recordArray = (array) $record;
                
                $commonFields = [
                    'customer_id' => $recordArray['customer_id'] ?? null,
                    'amount' => $recordArray['outstanding_balance'] ?? $recordArray['amount'] ?? null,
                    'effective_date' => $recordArray['disbursement_date'] ?? $recordArray['effective_date'] ?? null,
                    'status' => $recordArray['status'] ?? 'active'
                ];
                
                $jsonData = [];
                foreach ($recordArray as $key => $value) {
                    if (!in_array($key, ['id', 'created_at', 'updated_at', 'customer_id', 'outstanding_balance', 'amount', 'disbursement_date', 'effective_date', 'status'])) {
                        $mappedKey = $fieldMapping[$key] ?? $key;
                        $jsonData[$mappedKey] = $value;
                    }
                }
                
                ProductData::create([
                    'product_id' => $product->id,
                    'customer_id' => $commonFields['customer_id'],
                    'data' => $jsonData,
                    'amount' => $commonFields['amount'],
                    'effective_date' => $commonFields['effective_date'],
                    'status' => $commonFields['status']
                ]);
                
                $migratedCount++;
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return $migratedCount;
    }
}


