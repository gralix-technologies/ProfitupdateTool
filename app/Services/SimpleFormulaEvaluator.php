<?php

namespace App\Services;

use App\Models\ProductData;
use Illuminate\Support\Facades\DB;

class SimpleFormulaEvaluator
{
    
    public function evaluate(string $expression, int $productId, array $filters = []): float
    {
        \Log::info('SimpleFormulaEvaluator: Evaluating expression', [
            'expression' => $expression,
            'productId' => $productId
        ]);
        
        $query = ProductData::where('product_id', $productId);
        
        if (!empty($filters)) {
        }
        
        if (preg_match('/^SUM\([a-zA-Z_][a-zA-Z0-9_]*\)$/', $expression)) {
            return $this->evaluateSumExpression($query, $expression);
        }
        
        if (preg_match('/^SUM\([a-zA-Z_][a-zA-Z0-9_]*\s+WHERE\s+[^)]+\)$/', $expression)) {
            return $this->evaluateSumExpression($query, $expression);
        }
        
        if (strpos($expression, 'AVG(') !== false) {
            return $this->evaluateAvgExpression($query, $expression);
        }
        
        if (strpos($expression, 'COUNT(') !== false) {
            // Check if it's a complex arithmetic expression with COUNT operations
            if (preg_match('/[+\-*\/\(\)]/', $expression) && preg_match('/COUNT\([^)]+\)/', $expression)) {
                return $this->evaluateCountArithmeticExpression($query, $expression);
            }
            
            // Otherwise, it's a simple COUNT expression
            return $this->evaluateCountExpression($query, $expression);
        }
        
        
        return $this->evaluateArithmeticExpression($query, $expression);
    }
    
    
    private function evaluateSumExpression($query, string $expression): float
    {
        if (preg_match('/^SUM\(([a-zA-Z_][a-zA-Z0-9_]*)\s+WHERE\s+([^)]+)\)$/', $expression, $matches)) {
            $field = trim($matches[1]);
            $condition = trim($matches[2]);
            return $this->sumFieldWithCondition($query, $field, $condition);
        }
        
        if (preg_match('/^SUM\(([a-zA-Z_][a-zA-Z0-9_]*)\)$/', $expression, $matches)) {
            $field = trim($matches[1]);
            return $this->sumField($query, $field);
        }
        
        \Log::error('SimpleFormulaEvaluator: Could not parse SUM expression', [
            'expression' => $expression
        ]);
        
        return 0.0;
    }
    
    
    private function evaluateAvgExpression($query, string $expression): float
    {
        if (preg_match('/^AVG\(([^)]+)\)$/', $expression, $matches)) {
            $field = trim($matches[1]);
            return $this->avgField($query, $field);
        }
        
        return 0.0;
    }
    
    
    private function evaluateCountExpression($query, string $expression): float
    {
        // Handle COUNT(WHERE condition) syntax
        if (preg_match('/^COUNT\(WHERE\s+(.+)\)$/', $expression, $matches)) {
            $condition = trim($matches[1]);
            return $this->countFieldWithCondition($query, $condition);
        }
        
        // Handle regular COUNT(*) or COUNT(field)
        if (preg_match('/^COUNT\(([^)]*)\)$/', $expression, $matches)) {
            $field = trim($matches[1]);
            if (empty($field) || $field === '*') {
                return (float) $query->count();
            }
            return $this->countField($query, $field);
        }
        
        return 0.0;
    }
    
    
    private function evaluateArithmeticExpression($query, string $expression): float
    {
        // Handle COUNT operations in arithmetic expressions
        if (strpos($expression, 'COUNT(') !== false) {
            return $this->evaluateCountArithmeticExpression($query, $expression);
        }
        
        if (strpos($expression, 'SUM(') !== false) {
            if (preg_match('/^SUM\(([^)]+)\)$/', $expression, $matches)) {
                $innerExpression = trim($matches[1]);
                return $this->evaluateExpressionWithSums($query, $innerExpression);
            }
            return $this->evaluateExpressionWithSums($query, $expression);
        }
        
        $data = $query->get();
        
        if ($data->isEmpty()) {
            return 0.0;
        }
        
        $total = 0.0;
        $count = 0;
        
        foreach ($data as $record) {
            $recordData = is_array($record->data) ? $record->data : json_decode($record->data, true);
            $value = $this->evaluateExpressionForRecord($expression, $recordData);
            $total += $value;
            $count++;
        }
        
        return $total;
    }
    
    
    private function evaluateCountArithmeticExpression($query, string $expression): float
    {
        // Handle expressions like (COUNT(WHERE status = "npl") / COUNT(*)) * 100
        
        // Replace COUNT operations with their values
        $evaluatedExpression = $expression;
        
        // Handle COUNT(WHERE condition)
        while (preg_match('/COUNT\(WHERE\s+([^)]+)\)/', $evaluatedExpression, $matches)) {
            $condition = trim($matches[1]);
            $count = $this->countFieldWithCondition($query, $condition);
            $evaluatedExpression = str_replace($matches[0], $count, $evaluatedExpression);
        }
        
        // Handle COUNT(*)
        while (preg_match('/COUNT\(\*\)/', $evaluatedExpression, $matches)) {
            $count = (float) $query->count();
            $evaluatedExpression = str_replace($matches[0], $count, $evaluatedExpression);
        }
        
        // Handle COUNT(field)
        while (preg_match('/COUNT\(([^)]+)\)/', $evaluatedExpression, $matches)) {
            $field = trim($matches[1]);
            if ($field !== '*' && !str_contains($field, 'WHERE')) {
                $count = $this->countField($query, $field);
                $evaluatedExpression = str_replace($matches[0], $count, $evaluatedExpression);
            }
        }
        
        // Now evaluate the arithmetic expression
        return $this->evaluateMathematicalExpression($evaluatedExpression);
    }
    
    
    private function evaluateExpressionWithSums($query, string $expression): float
    {
        $evaluatedExpression = $expression;
        
        preg_match_all('/SUM\([^)]+\)/', $expression, $matches);
        
        foreach ($matches[0] as $sumExpression) {
            $value = $this->evaluateSumExpression($query, $sumExpression);
            $evaluatedExpression = str_replace($sumExpression, $value, $evaluatedExpression);
        }
        
        try {
            return $this->safeEvaluate($evaluatedExpression);
        } catch (\Exception $e) {
            return 0.0;
        }
    }
    
    
    private function evaluateExpressionForRecord(string $expression, array $data): float
    {
        if (strpos($expression, 'IF(') !== false) {
            return $this->evaluateIfStatement($expression, $data);
        }
        
        $evaluatedExpression = $expression;
        
        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', $expression, $matches);
        
        foreach ($matches[1] as $field) {
            $value = $data[$field] ?? 0;
            if (is_numeric($value)) {
                $evaluatedExpression = str_replace($field, $value, $evaluatedExpression);
            } else {
                $evaluatedExpression = str_replace($field, '0', $evaluatedExpression);
            }
        }
        
        try {
            return $this->safeEvaluate($evaluatedExpression);
        } catch (\Exception $e) {
            return 0.0;
        }
    }
    
    
    private function evaluateIfStatement(string $expression, array $data): float
    {
        if (preg_match('/IF\(([^,]+),\s*([^,]+),\s*([^)]+)\)/', $expression, $matches)) {
            $condition = trim($matches[1]);
            $trueValue = trim($matches[2]);
            $falseValue = trim($matches[3]);
            
            $conditionResult = $this->evaluateCondition($condition, $data);
            
            if ($conditionResult) {
                return $this->evaluateExpressionForRecord($trueValue, $data);
            } else {
                return $this->evaluateExpressionForRecord($falseValue, $data);
            }
        }
        
        return 0.0;
    }
    
    
    private function evaluateCondition(string $condition, array $data): bool
    {
        $evaluatedCondition = $condition;
        
        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', $condition, $matches);
        
        foreach ($matches[1] as $field) {
            $value = $data[$field] ?? 0;
            if (is_numeric($value)) {
                $evaluatedCondition = str_replace($field, $value, $evaluatedCondition);
            } else {
                $evaluatedCondition = str_replace($field, '0', $evaluatedCondition);
            }
        }
        
        if (preg_match('/([0-9.]+)\s*([><=!]+)\s*([0-9.]+)/', $evaluatedCondition, $matches)) {
            $left = (float) $matches[1];
            $operator = $matches[2];
            $right = (float) $matches[3];
            
            switch ($operator) {
                case '>':
                    return $left > $right;
                case '<':
                    return $left < $right;
                case '>=':
                    return $left >= $right;
                case '<=':
                    return $left <= $right;
                case '==':
                case '=':
                    return $left == $right;
                case '!=':
                    return $left != $right;
                default:
                    return false;
            }
        }
        
        try {
            $result = $this->safeEvaluate($evaluatedCondition);
            return (bool) $result;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    
    private function safeEvaluate(string $expression): float
    {
        $expression = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expression);
        
        if (empty($expression)) {
            return 0.0;
        }
        
        try {
            // Check for division by zero before evaluation
            if (strpos($expression, '/') !== false && strpos($expression, '/ 0') !== false) {
                return 0.0;
            }
            
            $result = eval("return $expression;");
            
            // Handle division by zero result
            if (!is_finite($result) || is_nan($result)) {
                return 0.0;
            }
            
            return is_numeric($result) ? (float) $result : 0.0;
        } catch (\DivisionByZeroError $e) {
            return 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }
    
    
    private function sumField($query, string $field): float
    {
        // For Working Capital Loans, check both amount field and JSON data
        if ($field === 'outstanding_balance') {
            // First try the amount field (which contains outstanding_balance for Working Capital Loans)
            $amountSum = (float) $query->sum('amount');
            if ($amountSum > 0) {
                return $amountSum;
            }
        }
        
        // Use PHP-based processing for all JSON fields to avoid SQL JSON extraction issues
        $records = $query->get();
        $total = 0.0;
        
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            
            if ($field === 'outstanding_balance') {
                // Use the amount field which contains outstanding_balance for Working Capital Loans
                $total += (float) $record->amount;
            } else {
                // Extract from JSON data
                $value = $data[$field] ?? 0;
                if (is_numeric($value)) {
                    $total += (float) $value;
                }
            }
        }
        
        return $total;
    }
    
    
    private function sumFieldWithCondition($query, string $field, string $condition): float
    {
        $data = $query->get();
        $total = 0.0;
        $matchedCount = 0;
        
        foreach ($data as $record) {
            $recordData = is_array($record->data) ? $record->data : json_decode($record->data, true);
            
            // For Working Capital Loans, also check the direct field values
            if ($field === 'outstanding_balance' && isset($record->amount)) {
                $recordData['outstanding_balance'] = $record->amount;
            }
            if (isset($record->status)) {
                $recordData['status'] = $record->status;
            }
            
            if ($this->evaluateConditionForRecord($condition, $recordData)) {
                $value = $recordData[$field] ?? 0;
                if (is_numeric($value)) {
                    $total += (float) $value;
                    $matchedCount++;
                }
            }
        }
        
        \Log::info('SimpleFormulaEvaluator: sumFieldWithCondition result', [
            'field' => $field,
            'condition' => $condition,
            'total' => $total,
            'matchedCount' => $matchedCount
        ]);
        
        return $total;
    }
    
    
    private function evaluateConditionForRecord(string $condition, array $data): bool
    {
        // Handle string comparisons (e.g., status = "active")
        if (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*"([^"]+)"/', $condition, $matches)) {
            $field = $matches[1];
            $value = $matches[2];
            
            $fieldValue = $data[$field] ?? null;
            return $fieldValue === $value;
        }
        
        // Handle numeric comparisons
        if (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s*([><=!]+)\s*([0-9.]+)/', $condition, $matches)) {
            $field = $matches[1];
            $operator = $matches[2];
            $value = (float) $matches[3];
            
            $fieldValue = $data[$field] ?? 0;
            if (!is_numeric($fieldValue)) {
                return false;
            }
            
            $fieldValue = (float) $fieldValue;
            
            switch ($operator) {
                case '>':
                    return $fieldValue > $value;
                case '<':
                    return $fieldValue < $value;
                case '>=':
                    return $fieldValue >= $value;
                case '<=':
                    return $fieldValue <= $value;
                case '==':
                case '=':
                    return $fieldValue == $value;
                case '!=':
                    return $fieldValue != $value;
                default:
                    return false;
            }
        }
        
        return false;
    }
    
    
    private function avgField($query, string $field): float
    {
        // For Working Capital Loans, check both amount field and JSON data
        if ($field === 'outstanding_balance') {
            // First try the amount field (which contains outstanding_balance for Working Capital Loans)
            $amountAvg = (float) $query->avg('amount');
            if ($amountAvg > 0) {
                return $amountAvg;
            }
        }
        
        // Use PHP-based processing for all JSON fields to avoid SQL JSON extraction issues
        $records = $query->get();
        $total = 0.0;
        $count = 0;
        
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            
            if ($field === 'outstanding_balance') {
                // Use the amount field which contains outstanding_balance for Working Capital Loans
                $total += (float) $record->amount;
                $count++;
            } else {
                // Extract from JSON data
                $value = $data[$field] ?? 0;
                if (is_numeric($value)) {
                    $total += (float) $value;
                    $count++;
                }
            }
        }
        
        return $count > 0 ? $total / $count : 0.0;
    }
    
    
    private function countField($query, string $field): float
    {
        // For simple COUNT operations, just count all records
        // since we're counting the existence of records, not the field values
        return (float) $query->count();
    }
    
    
    private function countFieldWithCondition($query, string $condition): float
    {
        $records = $query->get();
        $count = 0;
        
        foreach ($records as $record) {
            $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
            
            // For Working Capital Loans, also check the direct field values
            if (isset($record->status)) {
                $data['status'] = $record->status;
            }
            
            if ($this->evaluateConditionForRecord($condition, $data)) {
                $count++;
            }
        }
        
        return (float) $count;
    }
    
    
    private function evaluateMathematicalExpression(string $expression): float
    {
        try {
            // Clean the expression and ensure it's safe
            $expression = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expression);
            
            if (empty($expression)) {
                return 0.0;
            }
            
            // Check for division by zero before evaluation
            if (strpos($expression, '/') !== false && strpos($expression, '/ 0') !== false) {
                return 0.0;
            }
            
            $result = eval("return $expression;");
            
            // Handle division by zero result
            if (!is_finite($result) || is_nan($result)) {
                return 0.0;
            }
            
            return is_numeric($result) ? (float) $result : 0.0;
        } catch (\DivisionByZeroError $e) {
            return 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }
    
    
    private function convertConditionToSql(string $condition): string
    {
        $condition = trim($condition);
        
        $condition = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*([><=!]+)\s*([0-9.]+)\b/', function($matches) {
            $field = $matches[1];
            $operator = $matches[2];
            $value = $matches[3];
            return "CAST(JSON_EXTRACT(data, '$.{$field}') AS DECIMAL(15,2)) {$operator} {$value}";
        }, $condition);
        
        return $condition;
    }
}


