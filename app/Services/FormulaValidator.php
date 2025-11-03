<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Exceptions\FormulaException;

class FormulaValidator
{
    private FormulaEngine $formulaEngine;

    public function __construct(FormulaEngine $formulaEngine)
    {
        $this->formulaEngine = $formulaEngine;
    }

    
    public function validateFormula(string $expression, ?Product $product = null): ValidationResult
    {
        $result = new ValidationResult(true, []);

        try {
            $syntaxResult = $this->formulaEngine->validateSyntax($expression);
            
            if (!$syntaxResult->isValid()) {
                foreach ($syntaxResult->getErrors() as $error) {
                    $result->addError($error);
                }
            }

            $parsed = $this->formulaEngine->parseExpression($expression);
            
            if ($product) {
                $fieldValidation = $this->validateFieldReferences($parsed, $product);
                if (!$fieldValidation->isValid()) {
                    foreach ($fieldValidation->getErrors() as $error) {
                        $result->addError($error);
                    }
                }
            }

            $complexityValidation = $this->validateComplexity($parsed);
            if (!$complexityValidation->isValid()) {
                foreach ($complexityValidation->getErrors() as $error) {
                    $result->addError($error);
                }
                foreach ($complexityValidation->getWarnings() as $warning) {
                    $result->addWarning($warning);
                }
            }

            $functionValidation = $this->validateFunctionUsage($parsed);
            if (!$functionValidation->isValid()) {
                foreach ($functionValidation->getErrors() as $error) {
                    $result->addError($error);
                }
            }

        } catch (FormulaException $e) {
            $result->addError($e->getMessage());
        }

        return $result;
    }

    
    private function validateFieldReferences(ParsedFormula $parsed, Product $product): ValidationResult
    {
        $result = new ValidationResult(true);
        $fieldReferences = $parsed->getFieldReferences();
        $productFields = $this->getProductFieldNames($product);
        
        // If product has no field definitions, allow common fields
        if (empty($productFields)) {
            $commonFields = [
                'amount', 'status', 'interest_rate', 'days_past_due', 
                'probability_of_default', 'loss_given_default', 'customer_id',
                'created_at', 'effective_date', 'maturity_date', 'branch_code',
                'sector', 'risk_level', 'outstanding_balance'
            ];
            $productFields = $commonFields;
        }
        
        // Add field name mappings for common aliases
        $fieldMappings = [
            'amount' => ['outstanding_balance', 'balance'],
            'interest_rate' => ['interest_rate_annual', 'rate'],
            'probability_of_default' => ['pd'],
            'loss_given_default' => ['lgd'],
            'exposure_at_default' => ['ead']
        ];
        
        // Add mapped fields to allowed fields
        foreach ($fieldMappings as $alias => $actualFields) {
            foreach ($actualFields as $actualField) {
                if (in_array($actualField, $productFields) && !in_array($alias, $productFields)) {
                    $productFields[] = $alias;
                }
            }
        }

        foreach ($fieldReferences as $fieldName) {
            if (!in_array($fieldName, $productFields)) {
                $result->addError("Field '{$fieldName}' does not exist in product schema");
            }
        }

        return $result;
    }

    
    private function validateComplexity(ParsedFormula $parsed): ValidationResult
    {
        $result = new ValidationResult(true);
        $tokens = $parsed->getTokens();

        if (count($tokens) > 100) {
            $result->addError('Formula is too complex (maximum 100 tokens allowed)');
        } elseif (count($tokens) > 50) {
            $result->addWarning('Formula is complex and may impact performance');
        }

        $nestingDepth = $this->calculateNestingDepth($parsed->getOriginalExpression());
        if ($nestingDepth > 10) {
            $result->addError('Formula nesting is too deep (maximum 10 levels allowed)');
        } elseif ($nestingDepth > 5) {
            $result->addWarning('Formula has deep nesting which may impact readability');
        }

        $fieldCount = count($parsed->getFieldReferences());
        if ($fieldCount > 20) {
            $result->addError('Formula references too many fields (maximum 20 allowed)');
        } elseif ($fieldCount > 10) {
            $result->addWarning('Formula references many fields which may impact performance');
        }

        return $result;
    }

    
    private function validateFunctionUsage(ParsedFormula $parsed): ValidationResult
    {
        $result = new ValidationResult(true);
        $tokens = $parsed->getTokens();
        $functions = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'function') {
                $functions[] = $token['value'];
            }
        }

        $functionCounts = array_count_values($functions);
        foreach ($functionCounts as $function => $count) {
            if ($count > 5) {
                $result->addWarning("Function '{$function}' is used {$count} times, consider simplifying");
            }
        }

        foreach ($functions as $function) {
            $functionValidation = $this->validateSpecificFunction($function, $tokens);
            if (!$functionValidation->isValid()) {
                foreach ($functionValidation->getErrors() as $error) {
                    $result->addError($error);
                }
            }
        }

        return $result;
    }

    
    private function validateSpecificFunction(string $function, array $tokens): ValidationResult
    {
        $result = new ValidationResult(true);

        switch ($function) {
            case 'IF':
                if (!$this->hasValidParameterCount($tokens, $function, 3)) {
                    $result->addError("IF function requires exactly 3 parameters");
                }
                break;

            case 'CASE':
                if (!$this->hasMinimumParameterCount($tokens, $function, 3)) {
                    $result->addError("CASE function requires at least 3 parameters");
                }
                break;

            case 'MOVING_AVG':
                if (!$this->hasValidParameterCount($tokens, $function, 2)) {
                    $result->addError("MOVING_AVG function requires exactly 2 parameters");
                }
                break;

            case 'GROWTH_RATE':
                if (!$this->hasValidParameterCount($tokens, $function, 2)) {
                    $result->addError("GROWTH_RATE function requires exactly 2 parameters");
                }
                break;
        }

        return $result;
    }

    
    private function hasValidParameterCount(array $tokens, string $function, int $expectedCount): bool
    {
        return true; // Placeholder implementation
    }

    
    private function hasMinimumParameterCount(array $tokens, string $function, int $minimumCount): bool
    {
        return true; // Placeholder implementation
    }

    
    private function calculateNestingDepth(string $expression): int
    {
        $maxDepth = 0;
        $currentDepth = 0;

        for ($i = 0; $i < strlen($expression); $i++) {
            if ($expression[$i] === '(') {
                $currentDepth++;
                $maxDepth = max($maxDepth, $currentDepth);
            } elseif ($expression[$i] === ')') {
                $currentDepth--;
            }
        }

        return $maxDepth;
    }

    
    private function getProductFieldNames(Product $product): array
    {
        $fieldDefinitions = $product->getAttribute('field_definitions') ?? [];
        
        // Handle both indexed and associative arrays
        if (empty($fieldDefinitions)) {
            return [];
        }
        
        // If it's an indexed array (like from database), extract name fields
        if (is_array($fieldDefinitions) && isset($fieldDefinitions[0])) {
            return array_column($fieldDefinitions, 'name');
        }
        
        // If it's an associative array, return the keys
        return array_keys($fieldDefinitions);
    }

    
    public function validateWithSampleData(string $expression, array $sampleData): ValidationResult
    {
        $result = new ValidationResult(true);

        try {
            $parsed = $this->formulaEngine->parseExpression($expression);
            
            foreach ($parsed->getFieldReferences() as $field) {
                if (!array_key_exists($field, $sampleData)) {
                    $result->addError("Required field '{$field}' not found in sample data");
                }
            }

            if ($result->isValid()) {
                $this->formulaEngine->executeFormula($parsed, $sampleData);
            }

        } catch (\Exception $e) {
            $result->addError("Formula execution failed with sample data: " . $e->getMessage());
        }

        return $result;
    }
}


