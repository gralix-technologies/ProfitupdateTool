<?php

namespace App\Services;

class FormulaCalculationService
{
    public function calculateSum(array $data, string $field): float
    {
        $sum = 0;
        foreach ($data as $row) {
            if (isset($row[$field]) && is_numeric($row[$field])) {
                $sum += $row[$field];
            }
        }
        return $sum;
    }

    public function calculateAverage(array $data, string $field): float
    {
        if (empty($data)) {
            return 0;
        }

        $sum = $this->calculateSum($data, $field);
        $count = $this->countNumericValues($data, $field);
        
        return $count > 0 ? $sum / $count : 0;
    }

    public function calculateCount(array $data, string $field, $value = null): int
    {
        $count = 0;
        foreach ($data as $row) {
            if (isset($row[$field])) {
                if ($value === null || $row[$field] === $value) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function calculateMin(array $data, string $field): ?float
    {
        $values = $this->extractNumericValues($data, $field);
        return empty($values) ? null : min($values);
    }

    public function calculateMax(array $data, string $field): ?float
    {
        $values = $this->extractNumericValues($data, $field);
        return empty($values) ? null : max($values);
    }

    public function calculatePercentage(float $part, float $total): ?float
    {
        if ($total == 0) {
            return null;
        }
        return ($part / $total) * 100;
    }

    public function calculateRatio(float $numerator, float $denominator): ?float
    {
        if ($denominator == 0) {
            return null;
        }
        return $numerator / $denominator;
    }

    public function calculateMovingAverage(array $data, string $field, int $window): array
    {
        $values = $this->extractNumericValues($data, $field);
        $result = [];
        
        for ($i = $window - 1; $i < count($values); $i++) {
            $windowValues = array_slice($values, $i - $window + 1, $window);
            $result[] = array_sum($windowValues) / $window;
        }
        
        return $result;
    }

    public function calculateGrowthRate(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return 0;
        }
        return (($newValue - $oldValue) / $oldValue) * 100;
    }

    public function evaluateIf(string $condition, $trueValue, $falseValue, array $data): mixed
    {
        $result = $this->evaluateCondition($condition, $data);
        return $result ? $trueValue : $falseValue;
    }

    public function evaluateCase(array $cases, $defaultValue, array $data): mixed
    {
        foreach ($cases as $case) {
            if ($this->evaluateCondition($case['condition'], $data)) {
                return $case['value'];
            }
        }
        return $defaultValue;
    }

    public function calculateWeightedAverage(array $data, string $valueField, string $weightField): float
    {
        $weightedSum = 0;
        $totalWeight = 0;
        
        foreach ($data as $row) {
            if (isset($row[$valueField], $row[$weightField]) && 
                is_numeric($row[$valueField]) && is_numeric($row[$weightField])) {
                $weightedSum += $row[$valueField] * $row[$weightField];
                $totalWeight += $row[$weightField];
            }
        }
        
        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    public function calculateStandardDeviation(array $data, string $field): float
    {
        $values = $this->extractNumericValues($data, $field);
        
        if (count($values) < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        $variance = array_sum($squaredDifferences) / count($values);
        return sqrt($variance);
    }

    private function extractNumericValues(array $data, string $field): array
    {
        $values = [];
        foreach ($data as $row) {
            if (isset($row[$field]) && is_numeric($row[$field])) {
                $values[] = (float) $row[$field];
            }
        }
        return $values;
    }

    private function countNumericValues(array $data, string $field): int
    {
        $count = 0;
        foreach ($data as $row) {
            if (isset($row[$field]) && is_numeric($row[$field])) {
                $count++;
            }
        }
        return $count;
    }

    private function evaluateCondition(string $condition, array $data): bool
    {
        
        $expression = $condition;
        foreach ($data as $field => $value) {
            $expression = str_replace($field, $value, $expression);
        }
        
        if (preg_match('/(\d+(?:\.\d+)?)\s*([><=]+)\s*(\d+(?:\.\d+)?)/', $expression, $matches)) {
            $left = (float) $matches[1];
            $operator = $matches[2];
            $right = (float) $matches[3];
            
            return match($operator) {
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
                '==' => $left == $right,
                '!=' => $left != $right,
                default => false
            };
        }
        
        return false;
    }
}


