<?php

namespace App\Services;

use App\Models\Formula;
use App\Models\Product;
use App\Repositories\FormulaRepository;
use App\Services\FormulaEngine;
use App\Services\FormulaValidator;
use App\Services\Exceptions\FormulaException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FormulaService
{
    private FormulaRepository $repository;
    private FormulaEngine $engine;
    private FormulaValidator $validator;

    public function __construct(
        FormulaRepository $repository,
        FormulaEngine $engine,
        FormulaValidator $validator
    ) {
        $this->repository = $repository;
        $this->engine = $engine;
        $this->validator = $validator;
    }

    
    public function createFormula(array $data): Formula
    {
        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;
        $validationResult = $this->validator->validateFormula($data['expression'], $product);

        if (!$validationResult->isValid()) {
            throw new FormulaException(
                'Formula validation failed: ' . implode(', ', $validationResult->getErrors()),
                $data['expression']
            );
        }

        if ($this->repository->nameExistsForProduct($data['name'], $data['product_id'] ?? null)) {
            throw new FormulaException('Formula name already exists for this product');
        }

        $parsed = $this->engine->parseExpression($data['expression']);
        
        $data['parameters'] = array_merge([
            'field_references' => $parsed->getFieldReferences(),
            'validation_warnings' => $validationResult->getWarnings()
        ], $data['parameters'] ?? []);

        return $this->repository->create($data);
    }

    
    public function updateFormula(Formula $formula, array $data): Formula
    {
        if (isset($data['expression'])) {
            $product = $formula->product;
            $validationResult = $this->validator->validateFormula($data['expression'], $product);

            if (!$validationResult->isValid()) {
                throw new FormulaException(
                    'Formula validation failed: ' . implode(', ', $validationResult->getErrors()),
                    $data['expression']
                );
            }

            $parsed = $this->engine->parseExpression($data['expression']);
            
            $currentParams = $formula->parameters ?? [];
            $data['parameters'] = array_merge($currentParams, [
                'field_references' => $parsed->getFieldReferences(),
                'validation_warnings' => $validationResult->getWarnings()
            ]);
        }

        if (isset($data['name']) && $data['name'] !== $formula->name) {
            if ($this->repository->nameExistsForProduct($data['name'], $formula->product_id, $formula->id)) {
                throw new FormulaException('Formula name already exists for this product');
            }
        }

        $this->repository->update($formula, $data);
        return $formula->fresh();
    }

    
    public function executeFormula(Formula $formula, array $data): mixed
    {
        try {
            $parsed = $this->engine->parseExpression($formula->expression);
            return $this->engine->executeFormula($parsed, $data);
        } catch (\Exception $e) {
            throw new FormulaException(
                'Formula execution failed: ' . $e->getMessage(),
                $formula->expression,
                ['formula_id' => $formula->id, 'data_keys' => array_keys($data)]
            );
        }
    }

    
    public function executeFormulaById(int $formulaId, array $data): mixed
    {
        $formula = $this->repository->findByIdOrFail($formulaId);
        return $this->executeFormula($formula, $data);
    }

    
    public function testFormula(string $expression, array $sampleData, ?Product $product = null): array
    {
        $validationResult = $this->validator->validateFormula($expression, $product);
        
        $result = [
            'valid' => $validationResult->isValid(),
            'errors' => $validationResult->getErrors(),
            'warnings' => $validationResult->getWarnings(),
            'execution_result' => null,
            'execution_error' => null,
            'message' => $validationResult->isValid() ? 'Formula is valid' : 'Formula validation failed'
        ];

        if ($validationResult->isValid()) {
            try {
                $parsed = $this->engine->parseExpression($expression);
                // Only execute if sample data is provided
                if (!empty($sampleData)) {
                    $result['execution_result'] = $this->engine->executeFormula($parsed, $sampleData);
                } else {
                    $result['message'] = 'Formula is valid. No sample data provided for execution.';
                }
            } catch (\Exception $e) {
                $result['execution_error'] = $e->getMessage();
                $result['valid'] = false;
            }
        }

        return $result;
    }

    
    public function getFormulasForProduct(Product $product): Collection
    {
        return $this->repository->getByProduct($product);
    }

    
    public function getGlobalFormulas(): Collection
    {
        return $this->repository->getGlobalFormulas();
    }

    
    public function getPaginatedFormulas(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getPaginated($perPage, $filters);
    }

    
    public function duplicateFormula(Formula $formula, array $overrides = []): Formula
    {
        return $this->repository->duplicate($formula, $overrides);
    }

    
    public function deleteFormula(Formula $formula): bool
    {
        $usage = $this->repository->getUsageStatistics($formula);
        
        if ($usage['dashboard_count'] > 0 || $usage['widget_count'] > 0) {
            throw new FormulaException('Cannot delete formula that is currently in use');
        }

        return $this->repository->delete($formula);
    }

    
    public function getUsageStatistics(Formula $formula): array
    {
        return $this->repository->getUsageStatistics($formula);
    }

    
    public function getFormulasUsingField(string $fieldName): Collection
    {
        return $this->repository->getFormulasUsingField($fieldName);
    }

    
    public function validateFormulaCompatibility(Formula $formula, Product $product): ValidationResult
    {
        return $this->validator->validateFormula($formula->expression, $product);
    }

    
    public function getExecutionContext(Formula $formula, array $data): array
    {
        $parsed = $this->engine->parseExpression($formula->expression);
        
        return [
            'formula_id' => $formula->id,
            'formula_name' => $formula->name,
            'expression' => $formula->expression,
            'field_references' => $parsed->getFieldReferences(),
            'available_fields' => array_keys($data),
            'missing_fields' => array_diff($parsed->getFieldReferences(), array_keys($data)),
            'parameters' => $formula->getParametersWithDefaults()
        ];
    }

    
    public function batchExecuteFormulas(array $formulaIds, array $data): array
    {
        $results = [];
        
        foreach ($formulaIds as $formulaId) {
            try {
                $results[$formulaId] = [
                    'success' => true,
                    'result' => $this->executeFormulaById($formulaId, $data),
                    'error' => null
                ];
            } catch (\Exception $e) {
                $results[$formulaId] = [
                    'success' => false,
                    'result' => null,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    
    public function getFormulaSuggestions(array $fieldNames): Collection
    {
        $suggestions = collect();
        
        foreach ($fieldNames as $fieldName) {
            $formulas = $this->repository->getFormulasUsingField($fieldName);
            $suggestions = $suggestions->merge($formulas);
        }
        
        return $suggestions->unique('id');
    }

    
    public function exportFormula(Formula $formula): array
    {
        return [
            'name' => $formula->name,
            'description' => $formula->description,
            'expression' => $formula->expression,
            'return_type' => $formula->return_type,
            'parameters' => $formula->parameters,
            'product_name' => $formula->product?->name,
            'created_at' => $formula->created_at->toISOString()
        ];
    }

    
    public function importFormula(array $formulaData, ?int $productId = null, ?int $createdBy = null): Formula
    {
        $data = [
            'name' => $formulaData['name'],
            'description' => $formulaData['description'] ?? null,
            'expression' => $formulaData['expression'],
            'return_type' => $formulaData['return_type'] ?? 'numeric',
            'parameters' => $formulaData['parameters'] ?? [],
            'product_id' => $productId,
            'created_by' => $createdBy,
            'is_active' => true
        ];

        return $this->createFormula($data);
    }

    /**
     * Get comprehensive formula templates for different use cases
     */
    public function getFormulaTemplates(): array
    {
        return [
            'portfolio' => [
                'name' => 'Portfolio Metrics',
                'templates' => [
                    [
                        'name' => 'Total Portfolio Value',
                        'description' => 'Sum of all outstanding balances across products',
                        'expression' => 'SUM(amount)',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'currency',
                            'category' => 'Portfolio',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 1000000,
                            'status' => 'active'
                        ]
                    ],
                    [
                        'name' => 'Active Portfolio Value',
                        'description' => 'Sum of active accounts only',
                        'expression' => 'SUM(amount WHERE status = "active")',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'currency',
                            'category' => 'Portfolio',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 800000,
                            'status' => 'active'
                        ]
                    ],
                    [
                        'name' => 'NPL Amount',
                        'description' => 'Sum of non-performing loans',
                        'expression' => 'SUM(amount WHERE status = "npl")',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'currency',
                            'category' => 'Risk',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 200000,
                            'status' => 'npl'
                        ]
                    ],
                    [
                        'name' => 'NPL Ratio',
                        'description' => 'Percentage of portfolio that is non-performing',
                        'expression' => '(SUM(amount WHERE status = "npl") / SUM(amount)) * 100',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'percentage',
                            'category' => 'Risk',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 200000,
                            'status' => 'npl'
                        ]
                    ]
                ]
            ],
            'profitability' => [
                'name' => 'Profitability Metrics',
                'templates' => [
                    [
                        'name' => 'Interest Income',
                        'description' => 'Total interest earned from loans',
                        'expression' => 'SUM(amount * (interest_rate / 100))',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'currency',
                            'category' => 'Revenue',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 1000000,
                            'interest_rate' => 12.5
                        ]
                    ],
                    [
                        'name' => 'Average Interest Rate',
                        'description' => 'Weighted average interest rate',
                        'expression' => 'AVG(interest_rate)',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'percentage',
                            'category' => 'Revenue',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'interest_rate' => 12.5
                        ]
                    ],
                    [
                        'name' => 'Net Interest Margin',
                        'description' => 'Interest income minus interest expense',
                        'expression' => 'SUM(amount * (interest_rate / 100)) - SUM(amount * (cost_of_funds / 100))',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'currency',
                            'category' => 'Profitability',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 1000000,
                            'interest_rate' => 12.5,
                            'cost_of_funds' => 8.0
                        ]
                    ]
                ]
            ],
            'risk' => [
                'name' => 'Risk Metrics',
                'templates' => [
                    [
                        'name' => 'Portfolio at Risk (PAR) 30',
                        'description' => 'Percentage of portfolio 30+ days past due',
                        'expression' => '(SUM(amount WHERE days_past_due >= 30) / SUM(amount)) * 100',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'percentage',
                            'category' => 'Risk',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 150000,
                            'days_past_due' => 45
                        ]
                    ],
                    [
                        'name' => 'Portfolio at Risk (PAR) 90',
                        'description' => 'Percentage of portfolio 90+ days past due',
                        'expression' => '(SUM(amount WHERE days_past_due >= 90) / SUM(amount)) * 100',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'percentage',
                            'category' => 'Risk',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 75000,
                            'days_past_due' => 120
                        ]
                    ],
                    [
                        'name' => 'Expected Loss',
                        'description' => 'Expected loss based on PD and LGD',
                        'expression' => 'SUM(amount * (probability_of_default / 100) * (loss_given_default / 100))',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'currency',
                            'category' => 'Risk',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 1000000,
                            'probability_of_default' => 2.5,
                            'loss_given_default' => 40
                        ]
                    ]
                ]
            ],
            'growth' => [
                'name' => 'Growth Metrics',
                'templates' => [
                    [
                        'name' => 'Portfolio Growth Rate',
                        'description' => 'Month-over-month portfolio growth',
                        'expression' => '((SUM(amount WHERE created_at >= CURDATE() - INTERVAL 1 MONTH) - SUM(amount WHERE created_at >= CURDATE() - INTERVAL 2 MONTH AND created_at < CURDATE() - INTERVAL 1 MONTH)) / SUM(amount WHERE created_at >= CURDATE() - INTERVAL 2 MONTH AND created_at < CURDATE() - INTERVAL 1 MONTH)) * 100',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 2,
                            'format' => 'percentage',
                            'category' => 'Growth',
                            'scope' => 'portfolio'
                        ],
                        'example_data' => [
                            'amount' => 1000000,
                            'created_at' => '2024-01-15'
                        ]
                    ],
                    [
                        'name' => 'Customer Growth Rate',
                        'description' => 'New customer acquisition rate',
                        'expression' => 'COUNT(DISTINCT customer_id WHERE created_at >= CURDATE() - INTERVAL 1 MONTH)',
                        'return_type' => 'numeric',
                        'parameters' => [
                            'precision' => 0,
                            'format' => 'number',
                            'category' => 'Growth',
                            'scope' => 'customers'
                        ],
                        'example_data' => [
                            'customer_id' => 'CUST001',
                            'created_at' => '2024-01-15'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get example formulas for specific products
     */
    public function getProductExamples(string $productCategory): array
    {
        $examples = [
            'Loan' => [
                [
                    'name' => 'Average Loan Size',
                    'description' => 'Average outstanding balance per loan',
                    'expression' => 'AVG(amount)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Portfolio',
                        'scope' => 'loan'
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
                        'scope' => 'loan'
                    ]
                ],
                [
                    'name' => 'Monthly Interest Income',
                    'description' => 'Expected monthly interest income',
                    'expression' => 'SUM(amount * (interest_rate / 100) / 12)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Revenue',
                        'scope' => 'loan'
                    ]
                ]
            ],
            'Account' => [
                [
                    'name' => 'Average Account Balance',
                    'description' => 'Average balance per account',
                    'expression' => 'AVG(amount)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Portfolio',
                        'scope' => 'account'
                    ]
                ],
                [
                    'name' => 'Total Deposits',
                    'description' => 'Sum of all deposit balances',
                    'expression' => 'SUM(amount)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Portfolio',
                        'scope' => 'account'
                    ]
                ]
            ],
            'Deposit' => [
                [
                    'name' => 'Average Deposit Size',
                    'description' => 'Average fixed deposit amount',
                    'expression' => 'AVG(amount)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Portfolio',
                        'scope' => 'deposit'
                    ]
                ],
                [
                    'name' => 'Interest Paid on Deposits',
                    'description' => 'Total interest paid to depositors',
                    'expression' => 'SUM(amount * (interest_rate / 100))',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Cost',
                        'scope' => 'deposit'
                    ]
                ]
            ],
            'Other' => [
                [
                    'name' => 'Total Outstanding Balance',
                    'description' => 'Total outstanding balance for other products',
                    'expression' => 'SUM(amount)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Portfolio',
                        'scope' => 'other'
                    ]
                ],
                [
                    'name' => 'Average Balance',
                    'description' => 'Average balance per account',
                    'expression' => 'AVG(amount)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 2,
                        'format' => 'currency',
                        'category' => 'Portfolio',
                        'scope' => 'other'
                    ]
                ],
                [
                    'name' => 'Account Count',
                    'description' => 'Total number of accounts',
                    'expression' => 'COUNT(*)',
                    'return_type' => 'numeric',
                    'parameters' => [
                        'precision' => 0,
                        'format' => 'number',
                        'category' => 'Portfolio',
                        'scope' => 'other'
                    ]
                ]
            ]
        ];

        return $examples[$productCategory] ?? [];
    }

    /**
     * Get field suggestions based on product data
     */
    public function getFieldSuggestions(?Product $product = null): array
    {
        $commonFields = [
            'amount' => 'Outstanding balance or principal amount',
            'status' => 'Account status (active, npl, closed)',
            'interest_rate' => 'Annual interest rate (%)',
            'days_past_due' => 'Days past due',
            'probability_of_default' => 'Probability of default (%)',
            'loss_given_default' => 'Loss given default (%)',
            'customer_id' => 'Customer identifier',
            'created_at' => 'Account creation date',
            'effective_date' => 'Account effective date',
            'maturity_date' => 'Account maturity date',
            'branch_code' => 'Branch location code',
            'sector' => 'Customer sector',
            'risk_level' => 'Risk level (Low, Medium, High)'
        ];

        if ($product) {
            $productSpecific = [];
            // Add product-specific fields based on field definitions
            if ($product->field_definitions) {
                foreach ($product->field_definitions as $field) {
                    $productSpecific[$field['name']] = $field['description'] ?? $field['name'];
                }
            }
            
            // Only return product-specific fields, not common fields
            return $productSpecific;
        }

        return $commonFields;
    }

    /**
     * Get function documentation with examples
     */
    public function getFunctionDocumentation(): array
    {
        return [
            'SUM' => [
                'description' => 'Calculates the sum of numeric values in a field',
                'syntax' => 'SUM(field)',
                'examples' => [
                    'SUM(amount)' => 'Sum of all amounts',
                    'SUM(outstanding_balance)' => 'Total outstanding balance',
                    'SUM(interest_earned)' => 'Total interest earned'
                ],
                'use_cases' => [
                    'Calculate total portfolio value',
                    'Sum up all outstanding loans',
                    'Total revenue calculation',
                    'Aggregate financial metrics'
                ],
                'return_type' => 'numeric',
                'category' => 'aggregation'
            ],
            'AVG' => [
                'description' => 'Calculates the average of numeric values in a field',
                'syntax' => 'AVG(field)',
                'examples' => [
                    'AVG(interest_rate)' => 'Average interest rate',
                    'AVG(days_past_due)' => 'Average days past due',
                    'AVG(loan_amount)' => 'Average loan size'
                ],
                'use_cases' => [
                    'Calculate average interest rates',
                    'Determine average loan size',
                    'Compute mean days past due',
                    'Portfolio performance metrics'
                ],
                'return_type' => 'numeric',
                'category' => 'aggregation'
            ],
            'COUNT' => [
                'description' => 'Counts the number of records or non-null values',
                'syntax' => 'COUNT(field) or COUNT(*)',
                'examples' => [
                    'COUNT(customer_id)' => 'Number of customers',
                    'COUNT(*)' => 'Total number of records',
                    'COUNT(status)' => 'Number of records with status'
                ],
                'use_cases' => [
                    'Count total customers',
                    'Count active loans',
                    'Count NPL accounts',
                    'Record volume metrics'
                ],
                'return_type' => 'numeric',
                'category' => 'aggregation'
            ],
            'MIN' => [
                'description' => 'Finds the minimum value in a field',
                'syntax' => 'MIN(field)',
                'examples' => [
                    'MIN(amount)' => 'Smallest amount',
                    'MIN(interest_rate)' => 'Lowest interest rate',
                    'MIN(created_at)' => 'Earliest creation date'
                ],
                'use_cases' => [
                    'Find smallest loan amount',
                    'Identify lowest interest rate',
                    'Get earliest account creation',
                    'Minimum value analysis'
                ],
                'return_type' => 'numeric',
                'category' => 'aggregation'
            ],
            'MAX' => [
                'description' => 'Finds the maximum value in a field',
                'syntax' => 'MAX(field)',
                'examples' => [
                    'MAX(amount)' => 'Largest amount',
                    'MAX(interest_rate)' => 'Highest interest rate',
                    'MAX(maturity_date)' => 'Latest maturity date'
                ],
                'use_cases' => [
                    'Find largest loan amount',
                    'Identify highest interest rate',
                    'Get latest maturity date',
                    'Maximum value analysis'
                ],
                'return_type' => 'numeric',
                'category' => 'aggregation'
            ],
            'IF' => [
                'description' => 'Conditional logic - returns one value if condition is true, another if false',
                'syntax' => 'IF(condition, true_value, false_value)',
                'examples' => [
                    'IF(status = "active", amount, 0)' => 'Amount if active, 0 otherwise',
                    'IF(days_past_due > 90, "NPL", "Performing")' => 'NPL classification',
                    'IF(risk_level = "High", amount * 0.1, 0)' => 'High risk provisioning'
                ],
                'use_cases' => [
                    'Conditional calculations',
                    'Risk classification',
                    'Status-based logic',
                    'Conditional provisioning'
                ],
                'return_type' => 'mixed',
                'category' => 'conditional'
            ],
            'RATIO' => [
                'description' => 'Calculates ratio between two values',
                'syntax' => 'RATIO(numerator, denominator)',
                'examples' => [
                    'RATIO(npl_amount, total_amount)' => 'NPL ratio',
                    'RATIO(interest_earned, outstanding_balance)' => 'Interest rate ratio',
                    'RATIO(active_loans, total_loans)' => 'Active loan ratio'
                ],
                'use_cases' => [
                    'NPL ratio calculation',
                    'Interest rate analysis',
                    'Portfolio composition',
                    'Performance ratios'
                ],
                'return_type' => 'numeric',
                'category' => 'calculation'
            ],
            'PERCENTAGE' => [
                'description' => 'Calculates percentage of one value relative to another',
                'syntax' => 'PERCENTAGE(part, whole)',
                'examples' => [
                    'PERCENTAGE(npl_amount, total_amount)' => 'NPL percentage',
                    'PERCENTAGE(active_loans, total_loans)' => 'Active loan percentage',
                    'PERCENTAGE(interest_earned, total_income)' => 'Interest income percentage'
                ],
                'use_cases' => [
                    'NPL percentage calculation',
                    'Market share analysis',
                    'Income composition',
                    'Portfolio distribution'
                ],
                'return_type' => 'numeric',
                'category' => 'calculation'
            ],
            'MOVING_AVG' => [
                'description' => 'Calculates moving average over a specified period',
                'syntax' => 'MOVING_AVG(field, period)',
                'examples' => [
                    'MOVING_AVG(amount, 12)' => '12-month moving average',
                    'MOVING_AVG(growth_rate, 6)' => '6-month moving average growth',
                    'MOVING_AVG(npl_rate, 3)' => '3-month moving average NPL rate'
                ],
                'use_cases' => [
                    'Trend analysis',
                    'Smoothing volatile data',
                    'Seasonal adjustment',
                    'Performance trending'
                ],
                'return_type' => 'numeric',
                'category' => 'statistical'
            ],
            'GROWTH_RATE' => [
                'description' => 'Calculates growth rate between two periods',
                'syntax' => 'GROWTH_RATE(current_value, previous_value)',
                'examples' => [
                    'GROWTH_RATE(current_amount, previous_amount)' => 'Amount growth rate',
                    'GROWTH_RATE(this_month, last_month)' => 'Monthly growth rate',
                    'GROWTH_RATE(current_portfolio, previous_portfolio)' => 'Portfolio growth rate'
                ],
                'use_cases' => [
                    'Portfolio growth analysis',
                    'Revenue growth calculation',
                    'Customer growth metrics',
                    'Performance benchmarking'
                ],
                'return_type' => 'numeric',
                'category' => 'statistical'
            ],
            // Additional useful functions that could be implemented
            'SUMIF' => [
                'description' => 'Sums values based on a condition (conceptual - use IF with SUM)',
                'syntax' => 'SUM(IF(condition, field, 0))',
                'examples' => [
                    'SUM(IF(status = "active", amount, 0))' => 'Sum of active amounts',
                    'SUM(IF(risk_level = "High", outstanding_balance, 0))' => 'Sum of high-risk balances'
                ],
                'use_cases' => [
                    'Conditional summation',
                    'Risk-based aggregation',
                    'Status-filtered totals',
                    'Segmented analysis'
                ],
                'return_type' => 'numeric',
                'category' => 'conditional_aggregation',
                'note' => 'Use combination of SUM and IF functions'
            ],
            'COUNTIF' => [
                'description' => 'Counts records based on a condition (conceptual - use IF with COUNT)',
                'syntax' => 'SUM(IF(condition, 1, 0))',
                'examples' => [
                    'SUM(IF(status = "NPL", 1, 0))' => 'Count of NPL accounts',
                    'SUM(IF(days_past_due > 30, 1, 0))' => 'Count of overdue accounts'
                ],
                'use_cases' => [
                    'Conditional counting',
                    'Risk classification counts',
                    'Status-based metrics',
                    'Threshold analysis'
                ],
                'return_type' => 'numeric',
                'category' => 'conditional_aggregation',
                'note' => 'Use combination of SUM and IF functions'
            ],
            'AVGIF' => [
                'description' => 'Averages values based on a condition (conceptual - use complex formula)',
                'syntax' => 'RATIO(SUM(IF(condition, field, 0)), SUM(IF(condition, 1, 0)))',
                'examples' => [
                    'RATIO(SUM(IF(status = "active", amount, 0)), SUM(IF(status = "active", 1, 0)))' => 'Average of active amounts'
                ],
                'use_cases' => [
                    'Conditional averaging',
                    'Segmented averages',
                    'Filtered mean calculations',
                    'Risk-based averages'
                ],
                'return_type' => 'numeric',
                'category' => 'conditional_aggregation',
                'note' => 'Use combination of RATIO, SUM and IF functions'
            ]
        ];
    }
}


