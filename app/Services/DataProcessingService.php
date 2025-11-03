<?php

namespace App\Services;

use App\Models\Product;

class DataProcessingService
{
    public function processCsvData(array $csvData, Product $product): DataProcessingResult
    {
        $processedData = [];
        $errors = [];
        $fieldDefinitions = $product->field_definitions;

        foreach ($csvData as $rowIndex => $row) {
            $processedRow = [];
            $rowErrors = [];

            foreach ($fieldDefinitions as $fieldDef) {
                $fieldName = $fieldDef['name'];
                $fieldType = $fieldDef['type'];
                $isRequired = $fieldDef['required'] ?? false;
                $value = $row[$fieldName] ?? null;

                if ($isRequired && ($value === null || $value === '')) {
                    $rowErrors[] = "Field '{$fieldName}' is required";
                    continue;
                }

                if (!$isRequired && ($value === null || $value === '')) {
                    $processedRow[$fieldName] = null;
                    continue;
                }

                $validationResult = $this->validateAndTransformField($value, $fieldDef);
                
                if ($validationResult['valid']) {
                    $processedRow[$fieldName] = $validationResult['value'];
                } else {
                    $rowErrors[] = "Field '{$fieldName}': {$validationResult['error']}";
                }
            }

            if (empty($rowErrors)) {
                $processedRow = $this->sanitizeData($processedRow);
                $processedData[] = $processedRow;
            } else {
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'errors' => $rowErrors,
                    'data' => $row
                ];
            }
        }

        return new DataProcessingResult($processedData, $errors, count($csvData));
    }

    public function processCsvDataInBatches(array $csvData, Product $product, int $batchSize = 100): DataProcessingResult
    {
        $allProcessedData = [];
        $allErrors = [];
        $totalRows = count($csvData);

        $batches = array_chunk($csvData, $batchSize);
        $processedRowCount = 0;

        foreach ($batches as $batchIndex => $batch) {
            $batchResult = $this->processCsvData($batch, $product);
            
            $allProcessedData = array_merge($allProcessedData, $batchResult->getProcessedData());
            
            $batchErrors = $batchResult->getErrors();
            foreach ($batchErrors as &$error) {
                $error['row'] += $processedRowCount;
            }
            $allErrors = array_merge($allErrors, $batchErrors);
            
            $processedRowCount += count($batch);
        }

        return new DataProcessingResult($allProcessedData, $allErrors, $totalRows);
    }

    public function processCsvDataWithDuplicateDetection(array $csvData, Product $product, string $uniqueField): DataProcessingResult
    {
        $seenValues = [];
        $filteredData = [];
        $duplicateErrors = [];

        foreach ($csvData as $rowIndex => $row) {
            $uniqueValue = $row[$uniqueField] ?? null;
            
            if ($uniqueValue && in_array($uniqueValue, $seenValues)) {
                $duplicateErrors[] = [
                    'row' => $rowIndex + 1,
                    'errors' => ["Duplicate value '{$uniqueValue}' in field '{$uniqueField}'"],
                    'data' => $row
                ];
            } else {
                if ($uniqueValue) {
                    $seenValues[] = $uniqueValue;
                }
                $filteredData[] = $row;
            }
        }

        $result = $this->processCsvData($filteredData, $product);
        
        $allErrors = array_merge($duplicateErrors, $result->getErrors());
        
        return new DataProcessingResult($result->getProcessedData(), $allErrors, count($csvData));
    }

    public function validateCsvHeaders(array $headers, array $requiredHeaders): HeaderValidationResult
    {
        $missingHeaders = array_diff($requiredHeaders, $headers);
        $isValid = empty($missingHeaders);
        
        return new HeaderValidationResult($isValid, $missingHeaders, $headers);
    }

    private function validateAndTransformField($value, array $fieldDefinition): array
    {
        $fieldType = $fieldDefinition['type'];
        
        switch ($fieldType) {
            case 'Text':
                return ['valid' => true, 'value' => (string) $value];
                
            case 'Numeric':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'error' => 'Must be a number'];
                }
                return ['valid' => true, 'value' => (float) $value];
                
            case 'Date':
                $timestamp = strtotime($value);
                if ($timestamp === false) {
                    return ['valid' => false, 'error' => 'Invalid date format'];
                }
                return ['valid' => true, 'value' => date('Y-m-d', $timestamp)];
                
            case 'Lookup':
                $options = $fieldDefinition['options'] ?? [];
                if (!in_array($value, $options)) {
                    return ['valid' => false, 'error' => 'Value not in allowed options'];
                }
                return ['valid' => true, 'value' => $value];
                
            default:
                return ['valid' => true, 'value' => $value];
        }
    }

    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
}

class DataProcessingResult
{
    public function __construct(
        private array $processedData,
        private array $errors,
        private int $totalRows
    ) {}

    public function isSuccessful(): bool
    {
        return empty($this->errors);
    }

    public function getProcessedData(): array
    {
        return $this->processedData;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSummary(): array
    {
        $processedRows = count($this->processedData);
        $errorRows = count($this->errors);
        $successRate = $this->totalRows > 0 ? ($processedRows / $this->totalRows) * 100 : 0;

        return [
            'total_rows' => $this->totalRows,
            'processed_rows' => $processedRows,
            'error_rows' => $errorRows,
            'success_rate' => round($successRate, 2)
        ];
    }
}

class HeaderValidationResult
{
    public function __construct(
        private bool $isValid,
        private array $missingHeaders,
        private array $providedHeaders
    ) {}

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getMissingHeaders(): array
    {
        return $this->missingHeaders;
    }

    public function getProvidedHeaders(): array
    {
        return $this->providedHeaders;
    }
}


