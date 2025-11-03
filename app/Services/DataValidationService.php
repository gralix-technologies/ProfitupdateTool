<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;

class DataValidationService
{
    
    public function validateRow(array $rowData, Product $product): ValidationResult
    {
        $errors = [];
        $fieldDefinitions = $product->field_definitions ?? [];


        if (empty($rowData['customer_id'])) {
            $errors[] = 'customer_id is required';
        }


        foreach ($fieldDefinitions as $field) {
            $fieldName = $field['name'];
            $value = $rowData[$fieldName] ?? null;


            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                $errors[] = "Field '{$fieldName}' is required but is empty";
                continue;
            }


            if ($value === null || $value === '') {
                continue;
            }


            $fieldValidation = $this->validateFieldValue($fieldName, $value, $field);
            if (!$fieldValidation->isValid()) {
                $errors = array_merge($errors, $fieldValidation->getErrors());
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    
    public function validateHeaders(array $headers, Product $product): ValidationResult
    {
        $errors = [];
        $fieldDefinitions = $product->field_definitions ?? [];
        

        if (!in_array('customer_id', $headers)) {
            $errors[] = 'customer_id column is required';
        }
        

        $requiredFields = collect($fieldDefinitions)
            ->where('required', true)
            ->pluck('name')
            ->toArray();
            
        $missingFields = [];
        foreach ($requiredFields as $requiredField) {
            if (!in_array($requiredField, $headers)) {
                $missingFields[] = $requiredField;
            }
        }
        
        if (!empty($missingFields)) {
            $errors[] = 'Missing required columns: ' . implode(', ', $missingFields);
        }
        

        $expectedFields = array_merge(['customer_id'], array_column($fieldDefinitions, 'name'));
        $unexpectedFields = array_diff($headers, $expectedFields);
        
        if (!empty($unexpectedFields)) {
            $errors[] = 'Unexpected columns found (will be ignored): ' . implode(', ', $unexpectedFields);
        }
        
        return new ValidationResult(empty($errors), $errors);
    }

    
    public function validateFieldValue(string $fieldName, $value, array $fieldDefinition): ValidationResult
    {
        $errors = [];
        $type = $fieldDefinition['type'] ?? 'Text';

        switch ($type) {
            case 'Text':
                if (!$this->validateTextValue($value, $fieldDefinition)) {
                    $errors[] = "Field '{$fieldName}' must be a valid text value";
                }
                break;

            case 'Numeric':
                if (!$this->validateNumericValue($value, $fieldDefinition)) {
                    $errors[] = "Field '{$fieldName}' must be a valid numeric value";
                }
                break;

            case 'Date':
                if (!$this->validateDateValue($value, $fieldDefinition)) {
                    $errors[] = "Field '{$fieldName}' must be a valid date";
                }
                break;

            case 'Lookup':
                if (!$this->validateLookupValue($value, $fieldDefinition)) {
                    $allowedValues = implode(', ', $fieldDefinition['options'] ?? []);
                    $errors[] = "Field '{$fieldName}' must be one of: {$allowedValues}";
                }
                break;

            default:
                $errors[] = "Field '{$fieldName}' has unknown type '{$type}'";
        }

        return new ValidationResult(empty($errors), $errors);
    }

    
    protected function validateTextValue($value, array $fieldDefinition): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $stringValue = (string) $value;


        if (isset($fieldDefinition['min_length']) && strlen($stringValue) < $fieldDefinition['min_length']) {
            return false;
        }


        if (isset($fieldDefinition['max_length']) && strlen($stringValue) > $fieldDefinition['max_length']) {
            return false;
        }


        if (isset($fieldDefinition['pattern']) && !preg_match($fieldDefinition['pattern'], $stringValue)) {
            return false;
        }

        return true;
    }

    
    protected function validateNumericValue($value, array $fieldDefinition): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $numericValue = (float) $value;


        if (isset($fieldDefinition['min_value']) && $numericValue < $fieldDefinition['min_value']) {
            return false;
        }


        if (isset($fieldDefinition['max_value']) && $numericValue > $fieldDefinition['max_value']) {
            return false;
        }


        if (isset($fieldDefinition['decimal_places'])) {
            $decimalPlaces = $fieldDefinition['decimal_places'];
            $valueString = (string) $value;
            
            if (strpos($valueString, '.') !== false) {
                $actualDecimalPlaces = strlen(substr(strrchr($valueString, '.'), 1));
                if ($actualDecimalPlaces > $decimalPlaces) {
                    return false;
                }
            }
        }

        return true;
    }

    
    protected function validateDateValue($value, array $fieldDefinition): bool
    {
        try {

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && !isset($fieldDefinition['date_format'])) {

                $date = Carbon::parse($value);
                

                $reformatted = $date->format('Y-m-d');
                if ($reformatted !== $value) {
                    return false;
                }
            } else {

                $parts = explode('-', $value);
                if (count($parts) !== 3) {
                    return false;
                }
                
                $year = (int) $parts[0];
                $month = (int) $parts[1];
                $day = (int) $parts[2];
                

                if (!checkdate($month, $day, $year)) {
                    return false;
                }
                
                $date = Carbon::createFromDate($year, $month, $day);
            }


            if (isset($fieldDefinition['date_format'])) {
                $expectedFormat = $fieldDefinition['date_format'];
                

                $strictDate = Carbon::createFromFormat($expectedFormat, $value);
                if (!$strictDate || $strictDate->format($expectedFormat) !== $value) {
                    return false;
                }
                
                $date = $strictDate;
            }


            if (isset($fieldDefinition['min_date'])) {
                $minDate = Carbon::parse($fieldDefinition['min_date']);
                if ($date->lt($minDate)) {
                    return false;
                }
            }


            if (isset($fieldDefinition['max_date'])) {
                $maxDate = Carbon::parse($fieldDefinition['max_date']);
                if ($date->gt($maxDate)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    
    protected function validateLookupValue($value, array $fieldDefinition): bool
    {
        $options = $fieldDefinition['options'] ?? [];
        
        if (empty($options)) {
            return true; // No options defined, accept any value
        }

        return in_array($value, $options, true);
    }

    
    public function validateRows(array $rows, Product $product): array
    {
        $results = [];
        
        foreach ($rows as $index => $row) {
            $results[$index] = $this->validateRow($row, $product);
        }

        return $results;
    }

    
    public function getValidationSummary(array $validationResults): array
    {
        $totalRows = count($validationResults);
        $validRows = 0;
        $invalidRows = 0;
        $allErrors = [];

        foreach ($validationResults as $index => $result) {
            if ($result->isValid()) {
                $validRows++;
            } else {
                $invalidRows++;
                foreach ($result->getErrors() as $error) {
                    $allErrors[] = "Row " . ($index + 1) . ": " . $error;
                }
            }
        }

        return [
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'errors' => $allErrors
        ];
    }
}



