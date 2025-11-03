<?php

namespace App\Services;

use App\Models\Formula;
use App\Models\Product;
use App\Services\Exceptions\FormulaException;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced Formula Preview Service
 * Provides formula validation with dummy data preview before committing
 */
class FormulaPreviewService
{
    private FormulaEngine $formulaEngine;
    private FormulaValidator $formulaValidator;

    public function __construct(
        FormulaEngine $formulaEngine,
        FormulaValidator $formulaValidator
    ) {
        $this->formulaEngine = $formulaEngine;
        $this->formulaValidator = $formulaValidator;
    }

    /**
     * Validate formula with comprehensive checks and preview
     */
    public function validateWithPreview(
        string $expression,
        Product $product,
        array $dummyData = null
    ): array {
        $result = [
            'valid' => false,
            'syntax_validation' => [],
            'field_validation' => [],
            'preview_result' => null,
            'sample_calculation' => null,
            'warnings' => [],
            'errors' => [],
            'field_references' => []
        ];

        try {
            // Step 1: Validate syntax
            $syntaxValidation = $this->formulaEngine->validateSyntax($expression);
            $result['syntax_validation'] = [
                'valid' => $syntaxValidation->isValid(),
                'errors' => $syntaxValidation->getErrors()
            ];

            if (!$syntaxValidation->isValid()) {
                $result['errors'] = array_merge($result['errors'], $syntaxValidation->getErrors());
                return $result;
            }

            // Step 2: Parse formula
            $parsed = $this->formulaEngine->parseExpression($expression);
            $result['field_references'] = $parsed->getFieldReferences();

            // Step 3: Validate against product fields
            $formulaValidation = $this->formulaValidator->validateFormula($expression, $product);
            $result['field_validation'] = [
                'valid' => $formulaValidation->isValid(),
                'errors' => $formulaValidation->getErrors(),
                'warnings' => $formulaValidation->getWarnings() ?? []
            ];

            if (!$formulaValidation->isValid()) {
                $result['errors'] = array_merge($result['errors'], $formulaValidation->getErrors());
                return $result;
            }

            $result['warnings'] = $formulaValidation->getWarnings() ?? [];

            // Step 4: Generate dummy data if not provided
            if ($dummyData === null) {
                $dummyData = $this->generateDummyData($product, $parsed->getFieldReferences());
            }

            // Step 5: Execute formula with dummy data
            try {
                $previewResult = $this->formulaEngine->executeFormula($parsed, $dummyData);
                $result['preview_result'] = $previewResult;
                $result['sample_calculation'] = $this->formatPreviewResult($previewResult, $expression);
                $result['dummy_data_used'] = $dummyData;
                $result['valid'] = true;
            } catch (\Exception $e) {
                $result['errors'][] = "Formula execution error: " . $e->getMessage();
                $result['preview_result'] = null;
            }

        } catch (FormulaException $e) {
            $result['errors'][] = $e->getMessage();
        } catch (\Exception $e) {
            $result['errors'][] = "Unexpected error: " . $e->getMessage();
            Log::error('Formula preview error', [
                'expression' => $expression,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Generate realistic dummy data for formula testing
     */
    public function generateDummyData(Product $product, array $fieldReferences): array
    {
        $dummyRecords = [];
        $fieldDefinitions = $product->field_definitions ?? [];

        // Generate 10 dummy records for testing
        for ($i = 0; $i < 10; $i++) {
            $record = [];
            
            foreach ($fieldReferences as $fieldName) {
                $record[$fieldName] = $this->generateFieldValue($fieldName, $fieldDefinitions);
            }
            
            $dummyRecords[] = $record;
        }

        return $dummyRecords;
    }

    /**
     * Generate appropriate dummy value for a field
     */
    private function generateFieldValue(string $fieldName, array $fieldDefinitions): mixed
    {
        // Find field definition
        $fieldDef = null;
        foreach ($fieldDefinitions as $def) {
            if (isset($def['name']) && $def['name'] === $fieldName) {
                $fieldDef = $def;
                break;
            }
        }

        // Common field patterns
        $numericFields = ['amount', 'balance', 'loan_amount', 'principal', 'interest', 'outstanding_balance'];
        $rateFields = ['interest_rate', 'rate', 'margin', 'probability_of_default', 'pd', 'lgd', 'loss_given_default'];
        $statusFields = ['status', 'loan_status', 'account_status'];
        $dateFields = ['date', 'effective_date', 'maturity_date', 'created_at', 'disbursement_date'];

        // Generate based on field definition or pattern matching
        if ($fieldDef && isset($fieldDef['type'])) {
            switch ($fieldDef['type']) {
                case 'Numeric':
                    $min = $fieldDef['min_value'] ?? 1000;
                    $max = $fieldDef['max_value'] ?? 1000000;
                    return rand($min, $max);
                
                case 'Date':
                    return date('Y-m-d', strtotime('-' . rand(1, 365) . ' days'));
                
                case 'Lookup':
                    $options = $fieldDef['options'] ?? ['active', 'inactive'];
                    return $options[array_rand($options)];
                
                case 'Text':
                default:
                    return 'SAMPLE_' . strtoupper($fieldName);
            }
        }

        // Fallback to pattern matching
        foreach ($numericFields as $pattern) {
            if (stripos($fieldName, $pattern) !== false) {
                return rand(10000, 500000);
            }
        }

        foreach ($rateFields as $pattern) {
            if (stripos($fieldName, $pattern) !== false) {
                return rand(5, 25) + (rand(0, 99) / 100); // 5.00% to 25.99%
            }
        }

        foreach ($statusFields as $pattern) {
            if (stripos($fieldName, $pattern) !== false) {
                $statuses = ['active', 'closed', 'default', 'npl', 'performing'];
                return $statuses[array_rand($statuses)];
            }
        }

        foreach ($dateFields as $pattern) {
            if (stripos($fieldName, $pattern) !== false) {
                return date('Y-m-d', strtotime('-' . rand(1, 365) . ' days'));
            }
        }

        // Default: return field name as sample
        return 'SAMPLE_' . strtoupper($fieldName);
    }

    /**
     * Format preview result for display
     */
    private function formatPreviewResult($result, string $expression): array
    {
        $formatted = [
            'raw_result' => $result,
            'formatted_result' => $this->formatValue($result),
            'expression' => $expression,
            'result_type' => gettype($result),
            'interpretation' => $this->interpretResult($result, $expression)
        ];

        return $formatted;
    }

    /**
     * Format value based on type
     */
    private function formatValue($value): string
    {
        if (is_numeric($value)) {
            // Check if it looks like a percentage
            if ($value >= 0 && $value <= 100 && strpos(strtolower($value), 'percent') !== false) {
                return number_format($value, 2) . '%';
            }
            // Check if it's likely currency
            if ($value > 1000) {
                return 'ZMW ' . number_format($value, 2);
            }
            // Regular number
            return number_format($value, 2);
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return (string)$value;
    }

    /**
     * Provide interpretation of the result
     */
    private function interpretResult($result, string $expression): string
    {
        if (!is_numeric($result)) {
            return "Result: " . $result;
        }

        $expr = strtoupper($expression);

        // Identify result type based on formula
        if (strpos($expr, 'COUNT') !== false) {
            return "Count: " . number_format($result, 0) . " records";
        }

        if (strpos($expr, 'PERCENT') !== false || strpos($expr, 'RATIO') !== false) {
            return "Percentage/Ratio: " . number_format($result, 2) . "%";
        }

        if (strpos($expr, 'AVG') !== false) {
            return "Average: ZMW " . number_format($result, 2);
        }

        if (strpos($expr, 'SUM') !== false) {
            return "Total Sum: ZMW " . number_format($result, 2);
        }

        if (strpos($expr, 'MIN') !== false) {
            return "Minimum: ZMW " . number_format($result, 2);
        }

        if (strpos($expr, 'MAX') !== false) {
            return "Maximum: ZMW " . number_format($result, 2);
        }

        // Default
        if ($result > 1000) {
            return "Value: ZMW " . number_format($result, 2);
        }

        return "Value: " . number_format($result, 2);
    }

    /**
     * Test formula with multiple scenarios
     */
    public function testFormulaScenarios(string $expression, Product $product): array
    {
        $scenarios = [
            'normal' => $this->generateDummyData($product, []),
            'minimal' => $this->generateMinimalData($product),
            'maximum' => $this->generateMaximalData($product),
            'edge_cases' => $this->generateEdgeCaseData($product)
        ];

        $results = [];
        
        foreach ($scenarios as $scenarioName => $scenarioData) {
            try {
                $preview = $this->validateWithPreview($expression, $product, $scenarioData);
                $results[$scenarioName] = [
                    'result' => $preview['preview_result'] ?? null,
                    'formatted' => $preview['sample_calculation'] ?? null,
                    'valid' => $preview['valid'],
                    'errors' => $preview['errors'] ?? []
                ];
            } catch (\Exception $e) {
                $results[$scenarioName] = [
                    'result' => null,
                    'valid' => false,
                    'errors' => [$e->getMessage()]
                ];
            }
        }

        return $results;
    }

    /**
     * Generate minimal test data
     */
    private function generateMinimalData(Product $product): array
    {
        return [
            ['amount' => 1000, 'status' => 'active', 'interest_rate' => 5.0]
        ];
    }

    /**
     * Generate maximal test data
     */
    private function generateMaximalData(Product $product): array
    {
        $records = [];
        for ($i = 0; $i < 100; $i++) {
            $records[] = [
                'amount' => rand(100000, 1000000),
                'status' => 'active',
                'interest_rate' => rand(15, 25)
            ];
        }
        return $records;
    }

    /**
     * Generate edge case test data
     */
    private function generateEdgeCaseData(Product $product): array
    {
        return [
            ['amount' => 0, 'status' => 'closed', 'interest_rate' => 0],
            ['amount' => -1000, 'status' => 'default', 'interest_rate' => 50],
            ['amount' => PHP_INT_MAX, 'status' => 'active', 'interest_rate' => 100]
        ];
    }
}

