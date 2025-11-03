<?php

namespace App\Services;

use App\Models\Formula;
use App\Services\Exceptions\FormulaException;
use Illuminate\Support\Collection;

class FormulaEngine
{
    
    private const SUPPORTED_OPERATIONS = [
        'SUM', 'AVG', 'COUNT', 'MIN', 'MAX', 'IF', 'CASE', 
        'RATIO', 'PERCENTAGE', 'MOVING_AVG', 'GROWTH_RATE'
    ];

    
    private const ALLOWED_OPERATORS = ['+', '-', '*', '/', '(', ')', ',', '.', '>', '<', '='];

    
    public function parseExpression(string $expression): ParsedFormula
    {
        $expression = trim($expression);
        
        if (empty($expression)) {
            throw new FormulaException('Expression cannot be empty');
        }

        $this->validateSecurityConstraints($expression);

        $tokens = $this->tokenizeExpression($expression);
        
        $ast = $this->buildAST($tokens);
        
        $fieldReferences = $this->extractFieldReferences($tokens);
        
        return new ParsedFormula($expression, $ast, $fieldReferences, $tokens);
    }

    
    public function validateSyntax(string $expression): ValidationResult
    {
        try {
            $parsed = $this->parseExpression($expression);
            
            $errors = [];
            
            if (!$this->hasBalancedParentheses($expression)) {
                $errors[] = 'Unbalanced parentheses in expression';
            }
            
            $invalidFunctions = $this->findInvalidFunctions($parsed->getTokens());
            if (!empty($invalidFunctions)) {
                $errors[] = 'Invalid functions: ' . implode(', ', $invalidFunctions);
            }
            
            $securityIssues = $this->checkSecurityIssues($expression);
            if (!empty($securityIssues)) {
                $errors = array_merge($errors, $securityIssues);
            }
            
            return new ValidationResult(empty($errors), $errors);
            
        } catch (FormulaException $e) {
            return new ValidationResult(false, [$e->getMessage()]);
        }
    }

    
    public function executeFormula(ParsedFormula $formula, array $data): mixed
    {
        try {
            // Validate input data
            if (!is_array($data)) {
                throw new FormulaException('Data must be an array');
            }

            // Check if this is a simple numeric literal or field reference
            $tokens = $formula->getTokens();
            if (count($tokens) === 1 && $tokens[0]['type'] === 'number') {
                return $tokens[0]['value'];
            }

            // Check if this is a single field reference
            if (count($tokens) === 1 && $tokens[0]['type'] === 'field') {
                return $data[$tokens[0]['value']] ?? 0;
            }

            // For complex expressions, validate data structure
            if (!empty($data)) {
                $this->validateDataStructure($data);
            }

            return $this->evaluateAST($formula->getAST(), $data);
        } catch (FormulaException $e) {
            // Re-throw FormulaException as-is
            throw $e;
        } catch (\DivisionByZeroError $e) {
            throw new FormulaException(
                'Division by zero error in formula execution',
                $formula->getExpression(),
                $data,
                0,
                new \Exception($e->getMessage(), $e->getCode(), $e)
            );
        } catch (\TypeError $e) {
            throw new FormulaException(
                "Type error in formula execution: {$e->getMessage()}",
                $formula->getExpression(),
                $data,
                0,
                new \Exception($e->getMessage(), $e->getCode(), $e)
            );
        } catch (\Exception $e) {
            throw new FormulaException(
                "Formula execution failed: {$e->getMessage()}",
                $formula->getExpression(),
                $data,
                0,
                $e
            );
        }
    }

    /**
     * Validate data structure for formula execution
     */
    private function validateDataStructure(array $data): void
    {
        // Check if data is an array of records
        if (isset($data[0]) && is_array($data[0])) {
            // Validate each record has required structure
            foreach ($data as $index => $record) {
                if (!is_array($record)) {
                    throw new FormulaException("Record at index {$index} is not an array");
                }
            }
        } else {
            // Single record or direct data access
            if (!is_array($data)) {
                throw new FormulaException('Data must be an array or array of records');
            }
        }
    }

    
    public function getSupportedOperations(): array
    {
        return self::SUPPORTED_OPERATIONS;
    }

    
    private function validateSecurityConstraints(string $expression): void
    {
        $dangerousPatterns = [
            '/\b(exec|system|shell_exec|passthru|eval|file_get_contents|file_put_contents|fopen|fwrite)\s*\(/i',
            '/\$\w+\s*\(/i', // Variable functions
            '/\b(include|require|include_once|require_once)\b/i',
            '/\b(__construct|__destruct|__call|__get|__set)\b/i',
            '/\b(new\s+\w+|class\s+\w+)\b/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $expression)) {
                throw new FormulaException('Expression contains potentially dangerous code');
            }
        }

        // Allow WHERE clause in expressions
        if (!preg_match('/^[a-zA-Z0-9\s\+\-\*\/\(\)\,\.\>\<\=\_\"\"]+$/', $expression)) {
            throw new FormulaException('Expression contains invalid characters');
        }
    }

    
    private function tokenizeExpression(string $expression): array
    {
        $tokens = [];
        $currentToken = '';
        $insideString = false;
        $stringDelimiter = '';
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            // Handle string literals
            if ($char === '"' || $char === "'") {
                if ($insideString && $char === $stringDelimiter) {
                    // End of string
                    $tokens[] = ['type' => 'string', 'value' => $currentToken];
                    $currentToken = '';
                    $insideString = false;
                    $stringDelimiter = '';
                    continue;
                } elseif (!$insideString) {
                    // Start of string
                    if ($currentToken !== '') {
                        $tokens[] = $this->createToken($currentToken);
                        $currentToken = '';
                    }
                    $insideString = true;
                    $stringDelimiter = $char;
                    continue;
                }
            }
            
            if ($insideString) {
                $currentToken .= $char;
                continue;
            }
            
            if (ctype_space($char)) {
                if ($currentToken !== '') {
                    $tokens[] = $this->createToken($currentToken);
                    $currentToken = '';
                }
                continue;
            }
            
            if (in_array($char, self::ALLOWED_OPERATORS)) {
                // Special handling for decimal point in numbers
                if ($char === '.' && $currentToken !== '' && is_numeric($currentToken)) {
                    $currentToken .= $char;
                    continue;
                }
                
                // Special handling for decimal point at start of number
                if ($char === '.' && $currentToken === '') {
                    $currentToken = '0.';
                    continue;
                }
                
                if ($currentToken !== '') {
                    $tokens[] = $this->createToken($currentToken);
                    $currentToken = '';
                }
                
                // Don't tokenize decimal point as operator when it's part of a number
                if ($char !== '.' || $currentToken !== '') {
                    $tokens[] = $this->createToken($char);
                }
            } else {
                $currentToken .= $char;
            }
        }
        
        if ($currentToken !== '') {
            $tokens[] = $this->createToken($currentToken);
        }
        
        return $tokens;
    }

    
    private function createToken(string $value): array
    {
        $value = trim($value);
        
        if ($value === '') {
            return ['type' => 'empty', 'value' => ''];
        }
        
        if (in_array(strtoupper($value), self::SUPPORTED_OPERATIONS)) {
            return ['type' => 'function', 'value' => strtoupper($value)];
        }
        
        if (is_numeric($value)) {
            return ['type' => 'number', 'value' => (float)$value];
        }
        
        if (in_array($value, self::ALLOWED_OPERATORS)) {
            return ['type' => 'operator', 'value' => $value];
        }
        
        return ['type' => 'field', 'value' => $value];
    }

    
    private function buildAST(array $tokens): array
    {
        return [
            'type' => 'expression',
            'tokens' => $tokens
        ];
    }

    
    private function extractFieldReferences(array $tokens): array
    {
        $fields = [];
        $keywords = ['WHERE'];
        
        foreach ($tokens as $token) {
            if ($token['type'] === 'field' && !empty($token['value'])) {
                $value = $token['value'];
                // Skip keywords like WHERE
                if (!in_array(strtoupper($value), $keywords)) {
                    $fields[] = $value;
                }
            }
        }
        
        return array_unique($fields);
    }

    
    private function hasBalancedParentheses(string $expression): bool
    {
        $count = 0;
        
        for ($i = 0; $i < strlen($expression); $i++) {
            if ($expression[$i] === '(') {
                $count++;
            } elseif ($expression[$i] === ')') {
                $count--;
                if ($count < 0) {
                    return false;
                }
            }
        }
        
        return $count === 0;
    }

    
    private function findInvalidFunctions(array $tokens): array
    {
        $invalid = [];
        
        for ($i = 0; $i < count($tokens) - 1; $i++) {
            $currentToken = $tokens[$i];
            $nextToken = $tokens[$i + 1];
            
            if (($currentToken['type'] === 'field' || $currentToken['type'] === 'function') 
                && $nextToken['type'] === 'operator' && $nextToken['value'] === '(') {
                
                $functionName = strtoupper($currentToken['value']);
                
                if (!in_array($functionName, self::SUPPORTED_OPERATIONS)) {
                    $invalid[] = $functionName;
                }
                
                // Check for empty function calls (allow * for COUNT)
                $args = $this->extractFunctionArguments($tokens, $i);
                // Check if it's a COUNT(*) call
                $isCountStar = false;
                if ($functionName === 'COUNT') {
                    $parenCount = 0;
                    $j = $i + 1;
                    while ($j < count($tokens)) {
                        if ($tokens[$j]['value'] === '(') $parenCount++;
                        elseif ($tokens[$j]['value'] === ')') $parenCount--;
                        if ($parenCount === 1 && isset($tokens[$j + 1]) && $tokens[$j + 1]['value'] === '*' && isset($tokens[$j + 2]) && $tokens[$j + 2]['value'] === ')') {
                            $isCountStar = true;
                            break;
                        }
                        $j++;
                    }
                }
                if (empty($args) && !$isCountStar) {
                    $invalid[] = "Empty function call: {$functionName}()";
                }
                
                // Check for leading comma and incomplete arithmetic
                $parenCount = 0;
                $j = $i + 1;
                $inFunction = false;
                $lastTokenType = null;
                $lastWasOperator = false;
                while ($j < count($tokens)) {
                    $token = $tokens[$j];
                    if ($token['value'] === '(') {
                        $parenCount++;
                        $inFunction = true;
                        $lastWasOperator = false;
                        // Check if there's a comma right after opening parenthesis
                        if ($j + 1 < count($tokens) && $tokens[$j + 1]['value'] === ',') {
                            $invalid[] = "Leading comma in function call: {$functionName}()";
                        }
                    } elseif ($token['value'] === ')') {
                        $parenCount--;
                        $lastWasOperator = false;
                        if ($parenCount === 0) {
                            // Check if there's a comma just before the closing parenthesis
                            if ($j > 0 && $tokens[$j - 1]['value'] === ',') {
                                $invalid[] = "Trailing comma in function call: {$functionName}()";
                            }
                            // Check if last token before closing parenthesis is an operator (but allow where clauses)
                            if ($lastTokenType === 'operator' && $lastWasOperator) {
                                $invalid[] = "Incomplete arithmetic in function call: {$functionName}()";
                            }
                            break;
                        }
                    } else {
                        $lastTokenType = $token['type'];
                        // Check for double operators (allow = after fields for WHERE clauses)
                        $isEquals = ($token['type'] === 'operator' && $token['value'] === '=');
                        if ($token['type'] === 'operator' && $lastWasOperator && !$isEquals) {
                            $invalid[] = "Double operator in function call: {$functionName}()";
                        }
                        $lastWasOperator = ($token['type'] === 'operator');
                    }
                    $j++;
                }
            }
        }
        
        return array_unique($invalid);
    }

    
    private function checkSecurityIssues(string $expression): array
    {
        $issues = [];
        
        if (substr_count($expression, '(') > 10) {
            $issues[] = 'Expression has excessive nesting (max 10 levels)';
        }
        
        if (strlen($expression) > 1000) {
            $issues[] = 'Expression is too long (max 1000 characters)';
        }
        
        return $issues;
    }

    
    private function evaluateAST(array $ast, array $data): mixed
    {
        
        if ($ast['type'] === 'expression') {
            return $this->evaluateTokens($ast['tokens'], $data);
        }
        
        throw new FormulaException('Invalid AST structure');
    }

    
    private function evaluateTokens(array $tokens, array $data): mixed
    {
        
        if (count($tokens) === 1) {
            $token = $tokens[0];
            
            if ($token['type'] === 'number') {
                return $token['value'];
            }
            
            if ($token['type'] === 'field') {
                return $data[$token['value']] ?? 0;
            }
        }
        
        return $this->evaluateFunctions($tokens, $data);
    }

    
    private function evaluateFunctions(array $tokens, array $data): mixed
    {
        // First, evaluate all functions and replace them with their results
        $evaluatedTokens = [];
        $i = 0;
        
        while ($i < count($tokens)) {
            $token = $tokens[$i];
            
            if ($token['type'] === 'function') {
                $functionName = $token['value'];
                $args = $this->extractFunctionArguments($tokens, $i);
                $result = $this->executeFunction($functionName, $args, $data);
                
                // Replace function call with result
                $evaluatedTokens[] = ['type' => 'number', 'value' => $result];
                
                // Skip the function arguments
                $parenCount = 0;
                $j = $i + 1;
                while ($j < count($tokens)) {
                    if ($tokens[$j]['value'] === '(') {
                        $parenCount++;
                    } elseif ($tokens[$j]['value'] === ')') {
                        $parenCount--;
                        if ($parenCount === 0) {
                            $i = $j;
                            break;
                        }
                    }
                    $j++;
                }
                $i = $j + 1;
            } elseif ($token['type'] === 'field' && 
                     isset($tokens[$i + 1]) && 
                     $tokens[$i + 1]['type'] === 'operator' && 
                     $tokens[$i + 1]['value'] === '(') {
                
                $functionName = strtoupper($token['value']);
                $args = $this->extractFunctionArguments($tokens, $i);
                $result = $this->executeFunction($functionName, $args, $data);
                
                // Replace function call with result
                $evaluatedTokens[] = ['type' => 'number', 'value' => $result];
                
                // Skip the function arguments
                $parenCount = 0;
                $j = $i + 2;
                while ($j < count($tokens)) {
                    if ($tokens[$j]['value'] === '(') {
                        $parenCount++;
                    } elseif ($tokens[$j]['value'] === ')') {
                        $parenCount--;
                        if ($parenCount === 0) {
                            $i = $j;
                            break;
                        }
                    }
                    $j++;
                }
                $i = $j + 1;
            } else {
                $evaluatedTokens[] = $token;
                $i++;
            }
        }
        
        // Now evaluate arithmetic with the function results
        return $this->evaluateArithmetic($evaluatedTokens, $data);
    }

    
    private function extractFunctionArguments(array $tokens, int $functionIndex): array
    {
        $args = [];
        $parenCount = 0;
        $currentArgTokens = [];
        $inFunction = false;
        
        // Find the opening parenthesis
        for ($i = $functionIndex + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]['value'] === '(') {
                $inFunction = true;
                break;
            }
        }
        
        if (!$inFunction) {
            return $args;
        }
        
        // Parse arguments from the opening parenthesis
        for ($i = $i + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            
            if ($token['value'] === '(') {
                $parenCount++;
                $currentArgTokens[] = $token;
            } elseif ($token['value'] === ')') {
                if ($parenCount === 0) {
                    // Add the last argument if it exists
                    if (!empty($currentArgTokens)) {
                        $args[] = $currentArgTokens;
                    }
                    break;
                } else {
                    $parenCount--;
                    $currentArgTokens[] = $token;
                }
            } elseif ($token['value'] === ',' && $parenCount === 0) {
                if (!empty($currentArgTokens)) {
                    $args[] = $currentArgTokens;
                }
                $currentArgTokens = [];
            } else {
                $currentArgTokens[] = $token;
            }
        }
        
        return $args;
    }

    
    private function executeFunction(string $functionName, array $args, array $data): mixed
    {
        switch ($functionName) {
            case 'SUM':
                return $this->executeSum($args, $data);
            case 'AVG':
                return $this->executeAvg($args, $data);
            case 'COUNT':
                return $this->executeCount($args, $data);
            case 'MIN':
                return $this->executeMin($args, $data);
            case 'MAX':
                return $this->executeMax($args, $data);
            case 'IF':
                return $this->executeIf($args, $data);
            case 'RATIO':
                return $this->executeRatio($args, $data);
            case 'PERCENTAGE':
                return $this->executePercentage($args, $data);
            case 'MOVING_AVG':
                return $this->executeMovingAvg($args, $data);
            case 'GROWTH_RATE':
                return $this->executeGrowthRate($args, $data);
            default:
                throw new FormulaException("Unsupported function: {$functionName}");
        }
    }

    
    private function executeSum(array $args, array $data): float
    {
        if (empty($args)) {
            return 0;
        }
        
        // Handle both old string format and new token array format
        if (is_string($args[0])) {
            $fieldName = $args[0];
        } else {
            // New format: args[0] is an array of tokens
            $fieldToken = $args[0][0] ?? null;
            if (!$fieldToken || $fieldToken['type'] !== 'field') {
                return 0;
            }
            $fieldName = $fieldToken['value'];
        }
        
        // Handle array of records (our current data structure)
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $sum = 0;
            foreach ($data as $record) {
                $value = $record[$fieldName] ?? 0;
                $sum += is_numeric($value) ? (float)$value : 0;
            }
            return $sum;
        }
        
        // Handle single record or direct field access
        $values = $data[$fieldName] ?? [];
        
        if (is_array($values)) {
            return array_sum($values);
        }
        
        return (float)$values;
    }

    
    private function executeAvg(array $args, array $data): float
    {
        if (empty($args)) {
            return 0;
        }
        
        // Handle both old string format and new token array format
        if (is_string($args[0])) {
            $fieldName = $args[0];
        } else {
            // New format: args[0] is an array of tokens
            $fieldToken = $args[0][0] ?? null;
            if (!$fieldToken || $fieldToken['type'] !== 'field') {
                return 0;
            }
            $fieldName = $fieldToken['value'];
        }
        
        // Handle array of records (our current data structure)
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $sum = 0;
            $count = 0;
            foreach ($data as $record) {
                $value = $record[$fieldName] ?? 0;
                if (is_numeric($value) && $value !== null) {
                    $sum += (float)$value;
                    $count++;
                }
            }
            return $count > 0 ? $sum / $count : 0;
        }
        
        // Handle single record or direct field access
        $values = $data[$fieldName] ?? [];
        
        if (is_array($values) && !empty($values)) {
            return array_sum($values) / count($values);
        }
        
        return (float)$values;
    }

    
    private function executeCount(array $args, array $data): int
    {
        // Handle COUNT(*) - count all records
        if (empty($args)) {
            if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                return count($data);
            }
            return is_array($data) ? count($data) : 1;
        }
        
        // Handle both old string format and new token array format
        if (is_string($args[0])) {
            $fieldName = $args[0];
        } else {
            // New format: args[0] is an array of tokens
            $fieldToken = $args[0][0] ?? null;
            if (!$fieldToken) {
                return 0;
            }
            
            // Handle COUNT(*) special case
            if ($fieldToken['type'] === 'operator' && $fieldToken['value'] === '*') {
                if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                    return count($data);
                }
                return is_array($data) ? count($data) : 1;
            }
            
            if ($fieldToken['type'] !== 'field') {
                return 0;
            }
            $fieldName = $fieldToken['value'];
        }
        
        // Handle COUNT(*) special case (for string format)
        if ($fieldName === '*') {
            if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                return count($data);
            }
            return is_array($data) ? count($data) : 1;
        }
        
        // Handle array of records (our current data structure)
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $count = 0;
            foreach ($data as $record) {
                if (isset($record[$fieldName]) && $record[$fieldName] !== null) {
                    $count++;
                }
            }
            return $count;
        }
        
        // Handle single record or direct field access
        $values = $data[$fieldName] ?? [];
        
        if (is_array($values)) {
            return count($values);
        }
        
        return $values ? 1 : 0;
    }

    
    private function executeMin(array $args, array $data): float
    {
        if (empty($args)) {
            return 0;
        }
        
        // Handle both old string format and new token array format
        if (is_string($args[0])) {
            $fieldName = $args[0];
        } else {
            // New format: args[0] is an array of tokens
            $fieldToken = $args[0][0] ?? null;
            if (!$fieldToken || $fieldToken['type'] !== 'field') {
                return 0;
            }
            $fieldName = $fieldToken['value'];
        }
        
        // Handle array of records (our current data structure)
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $values = [];
            foreach ($data as $record) {
                $value = $record[$fieldName] ?? null;
                if (is_numeric($value)) {
                    $values[] = (float)$value;
                }
            }
            return !empty($values) ? min($values) : 0;
        }
        
        // Handle single record or direct field access
        $values = $data[$fieldName] ?? [];
        
        if (is_array($values) && !empty($values)) {
            return min($values);
        }
        
        return (float)$values;
    }

    
    private function executeMax(array $args, array $data): float
    {
        if (empty($args)) {
            return 0;
        }
        
        // Handle both old string format and new token array format
        if (is_string($args[0])) {
            $fieldName = $args[0];
        } else {
            // New format: args[0] is an array of tokens
            $fieldToken = $args[0][0] ?? null;
            if (!$fieldToken || $fieldToken['type'] !== 'field') {
                return 0;
            }
            $fieldName = $fieldToken['value'];
        }
        
        // Handle array of records (our current data structure)
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $values = [];
            foreach ($data as $record) {
                $value = $record[$fieldName] ?? null;
                if (is_numeric($value)) {
                    $values[] = (float)$value;
                }
            }
            return !empty($values) ? max($values) : 0;
        }
        
        // Handle single record or direct field access
        $values = $data[$fieldName] ?? [];
        
        if (is_array($values) && !empty($values)) {
            return max($values);
        }
        
        return (float)$values;
    }

    
    private function executeIf(array $args, array $data): mixed
    {
        if (count($args) < 3) {
            throw new FormulaException('IF function requires 3 arguments: condition, true_value, false_value');
        }
        
        $condition = $this->evaluateCondition($args[0], $data);
        $trueValue = $this->evaluateTokens($args[1], $data);
        $falseValue = $this->evaluateTokens($args[2], $data);
        
        return $condition ? $trueValue : $falseValue;
    }

    
    private function executeRatio(array $args, array $data): float
    {
        if (count($args) < 2) {
            throw new FormulaException('RATIO function requires 2 arguments: numerator, denominator');
        }
        
        $numerator = $this->evaluateTokens($args[0], $data);
        $denominator = $this->evaluateTokens($args[1], $data);
        
        if ($denominator == 0) {
            return 0;
        }
        
        return $numerator / $denominator;
    }

    
    private function executePercentage(array $args, array $data): float
    {
        if (count($args) < 2) {
            throw new FormulaException('PERCENTAGE function requires 2 arguments: part, whole');
        }
        
        $part = $this->evaluateTokens($args[0], $data);
        $whole = $this->evaluateTokens($args[1], $data);
        
        if ($whole == 0) {
            return 0;
        }
        
        return ($part / $whole) * 100;
    }

    
    private function executeMovingAvg(array $args, array $data): float
    {
        if (count($args) < 2) {
            throw new FormulaException('MOVING_AVG function requires 2 arguments: field, period');
        }
        
        // Handle both old string format and new token array format
        if (is_string($args[0])) {
            $fieldName = $args[0];
        } else {
            // New format: args[0] is an array of tokens
            $fieldToken = $args[0][0] ?? null;
            if (!$fieldToken || $fieldToken['type'] !== 'field') {
                return 0;
            }
            $fieldName = $fieldToken['value'];
        }
        
        $period = is_string($args[1]) ? (int)$args[1] : $this->evaluateTokens($args[1], $data);
        $values = $data[$fieldName] ?? [];
        
        if (!is_array($values) || count($values) < $period) {
            return 0;
        }
        
        $lastValues = array_slice($values, -$period);
        return array_sum($lastValues) / count($lastValues);
    }

    
    private function executeGrowthRate(array $args, array $data): float
    {
        if (count($args) < 2) {
            throw new FormulaException('GROWTH_RATE function requires 2 arguments: current_value, previous_value');
        }
        
        $current = $this->evaluateTokens($args[0], $data);
        $previous = $this->evaluateTokens($args[1], $data);
        
        if ($previous == 0) {
            return 0;
        }
        
        return (($current - $previous) / $previous) * 100;
    }

    
    private function evaluateCondition(array $conditionTokens, array $data): bool
    {
        // Look for comparison operators in the condition tokens
        for ($i = 0; $i < count($conditionTokens); $i++) {
            $token = $conditionTokens[$i];
            if ($token['type'] === 'operator' && in_array($token['value'], ['>', '<', '>=', '<=', '=', '!='])) {
                $leftTokens = array_slice($conditionTokens, 0, $i);
                $rightTokens = array_slice($conditionTokens, $i + 1);
                
                $left = $this->evaluateTokens($leftTokens, $data);
                $right = $this->evaluateTokens($rightTokens, $data);
                
                switch ($token['value']) {
                    case '>': return $left > $right;
                    case '<': return $left < $right;
                    case '>=': return $left >= $right;
                    case '<=': return $left <= $right;
                    case '=': return $left == $right;
                    case '!=': return $left != $right;
                }
            }
        }
        
        // If no comparison operator found, evaluate as boolean
        $result = $this->evaluateTokens($conditionTokens, $data);
        return (bool)$result;
    }

    
    private function evaluateArithmetic(array $tokens, array $data): mixed
    {
        // Handle simple cases first
        if (count($tokens) === 1) {
            $token = $tokens[0];
            if ($token['type'] === 'number') {
                return $token['value'];
            } elseif ($token['type'] === 'field') {
                return $data[$token['value']] ?? 0;
            }
        }
        
        // Convert tokens to values and operators
        $values = [];
        $operators = [];
        
        foreach ($tokens as $token) {
            if ($token['type'] === 'number') {
                $values[] = (float)$token['value'];
            } elseif ($token['type'] === 'field') {
                $value = $data[$token['value']] ?? 0;
                $values[] = is_numeric($value) ? (float)$value : 0;
            } elseif ($token['type'] === 'operator' && in_array($token['value'], ['+', '-', '*', '/'])) {
                $operators[] = $token['value'];
            }
        }
        
        // If no operators, return the single value
        if (empty($operators)) {
            return !empty($values) ? $values[0] : 0;
        }
        
        // Use proper operator precedence evaluation
        // Process operations in order of precedence: *, / then +, -
        // Within same precedence, process left-to-right
        
        $precedence = ['*' => 2, '/' => 2, '+' => 1, '-' => 1];
        
        while (count($operators) > 0) {
            $highestPrecedence = 0;
            $operatorIndex = 0;
            
            // Find the operator with highest precedence
            for ($i = 0; $i < count($operators); $i++) {
                if ($precedence[$operators[$i]] > $highestPrecedence) {
                    $highestPrecedence = $precedence[$operators[$i]];
                    $operatorIndex = $i;
                }
            }
            
            // Execute the operation
            $left = $values[$operatorIndex];
            $right = $values[$operatorIndex + 1];
            $op = $operators[$operatorIndex];
            
            $result = $this->applyOperator($left, $right, $op);
            
            // Replace the two values and one operator with the result
            array_splice($values, $operatorIndex, 2, [$result]);
            array_splice($operators, $operatorIndex, 1);
        }
        
        return $values[0];
    }

    
    private function applyOperator(float $left, float $right, string $operator): float
    {
        switch ($operator) {
            case '+':
                return $left + $right;
            case '-':
                return $left - $right;
            case '*':
                return $left * $right;
            case '/':
                if ($right == 0) {
                    throw new \DivisionByZeroError('Division by zero');
                }
                return $left / $right;
            default:
                return $right;
        }
    }
}


