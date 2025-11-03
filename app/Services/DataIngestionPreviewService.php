<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced Data Ingestion Preview Service
 * Provides data preview and validation before actual import
 */
class DataIngestionPreviewService
{
    protected DataValidationService $dataValidationService;
    protected CsvProcessorService $csvProcessorService;

    public function __construct(
        DataValidationService $dataValidationService,
        CsvProcessorService $csvProcessorService
    ) {
        $this->dataValidationService = $dataValidationService;
        $this->csvProcessorService = $csvProcessorService;
    }

    /**
     * Preview CSV file before import
     */
    public function previewFile(UploadedFile $file, Product $product, int $previewRows = 10): array
    {
        try {
            // Parse CSV
            $csvData = $this->parseCsvFile($file);
            
            if (empty($csvData)) {
                return [
                    'success' => false,
                    'message' => 'CSV file is empty',
                    'errors' => ['File contains no data']
                ];
            }

            // Extract headers
            $headers = array_shift($csvData);
            
            // Validate headers
            $headerValidation = $this->dataValidationService->validateHeaders($headers, $product);
            
            // Get preview rows
            $previewData = array_slice($csvData, 0, $previewRows);
            
            // Validate preview rows
            $validatedRows = [];
            $rowErrors = [];
            $rowNumber = 2; // Start from 2 (after header)
            
            foreach ($previewData as $row) {
                if (count($row) !== count($headers)) {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'errors' => ["Column count mismatch. Expected " . count($headers) . ", got " . count($row)]
                    ];
                    $rowNumber++;
                    continue;
                }
                
                $rowData = array_combine($headers, $row);
                $validation = $this->dataValidationService->validateRow($rowData, $product);
                
                $validatedRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $rowData,
                    'valid' => $validation->isValid(),
                    'errors' => $validation->getErrors()
                ];
                
                if (!$validation->isValid()) {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'errors' => $validation->getErrors()
                    ];
                }
                
                $rowNumber++;
            }

            // Data quality analysis
            $qualityAnalysis = $this->analyzeDataQuality($validatedRows, $product);
            
            // Generate statistics
            $stats = $this->generateStatistics($csvData, $headers, $validatedRows);

            return [
                'success' => true,
                'total_rows' => count($csvData),
                'preview_rows' => count($previewData),
                'headers' => $headers,
                'header_validation' => [
                    'valid' => $headerValidation->isValid(),
                    'errors' => $headerValidation->getErrors()
                ],
                'preview_data' => $validatedRows,
                'row_errors' => $rowErrors,
                'quality_analysis' => $qualityAnalysis,
                'statistics' => $stats,
                'field_mapping' => $this->suggestFieldMapping($headers, $product),
                'recommendations' => $this->generateRecommendations($validatedRows, $qualityAnalysis)
            ];

        } catch (\Exception $e) {
            Log::error('Data preview error', [
                'file' => $file->getClientOriginalName(),
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Analyze data quality
     */
    private function analyzeDataQuality(array $validatedRows, Product $product): array
    {
        $totalRows = count($validatedRows);
        $validRows = 0;
        $invalidRows = 0;
        $nullCounts = [];
        $duplicates = [];
        $dataTypes = [];

        foreach ($validatedRows as $row) {
            if ($row['valid']) {
                $validRows++;
            } else {
                $invalidRows++;
            }

            // Count null values per field
            foreach ($row['data'] as $field => $value) {
                if (!isset($nullCounts[$field])) {
                    $nullCounts[$field] = 0;
                    $dataTypes[$field] = [];
                }
                
                if ($value === null || $value === '') {
                    $nullCounts[$field]++;
                }

                // Track data types
                $type = gettype($value);
                if (!isset($dataTypes[$field][$type])) {
                    $dataTypes[$field][$type] = 0;
                }
                $dataTypes[$field][$type]++;
            }
        }

        // Calculate quality scores
        $qualityScore = $totalRows > 0 ? ($validRows / $totalRows) * 100 : 0;
        $completenessScore = $this->calculateCompletenessScore($nullCounts, $totalRows);

        return [
            'quality_score' => round($qualityScore, 2),
            'completeness_score' => round($completenessScore, 2),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'null_counts' => $nullCounts,
            'data_type_consistency' => $this->analyzeTypeConsistency($dataTypes),
            'issues' => $this->identifyQualityIssues($validatedRows, $nullCounts, $totalRows)
        ];
    }

    /**
     * Calculate completeness score
     */
    private function calculateCompletenessScore(array $nullCounts, int $totalRows): float
    {
        if ($totalRows === 0 || empty($nullCounts)) {
            return 100;
        }

        $totalCells = count($nullCounts) * $totalRows;
        $totalNulls = array_sum($nullCounts);
        
        return (($totalCells - $totalNulls) / $totalCells) * 100;
    }

    /**
     * Analyze data type consistency
     */
    private function analyzeTypeConsistency(array $dataTypes): array
    {
        $consistency = [];
        
        foreach ($dataTypes as $field => $types) {
            $dominantType = array_search(max($types), $types);
            $consistencyPercent = (max($types) / array_sum($types)) * 100;
            
            $consistency[$field] = [
                'dominant_type' => $dominantType,
                'consistency_percent' => round($consistencyPercent, 2),
                'is_consistent' => $consistencyPercent >= 95
            ];
        }
        
        return $consistency;
    }

    /**
     * Identify quality issues
     */
    private function identifyQualityIssues(array $validatedRows, array $nullCounts, int $totalRows): array
    {
        $issues = [];

        // Check for high null rates
        foreach ($nullCounts as $field => $count) {
            $nullPercent = ($count / $totalRows) * 100;
            if ($nullPercent > 50) {
                $issues[] = [
                    'severity' => 'high',
                    'field' => $field,
                    'issue' => "Field '{$field}' has {$nullPercent}% null values"
                ];
            } elseif ($nullPercent > 20) {
                $issues[] = [
                    'severity' => 'medium',
                    'field' => $field,
                    'issue' => "Field '{$field}' has {$nullPercent}% null values"
                ];
            }
        }

        // Check for low valid row count
        $validCount = count(array_filter($validatedRows, fn($r) => $r['valid']));
        $validPercent = ($validCount / $totalRows) * 100;
        
        if ($validPercent < 50) {
            $issues[] = [
                'severity' => 'critical',
                'field' => 'overall',
                'issue' => "Only {$validPercent}% of rows are valid"
            ];
        } elseif ($validPercent < 80) {
            $issues[] = [
                'severity' => 'high',
                'field' => 'overall',
                'issue' => "Only {$validPercent}% of rows are valid"
            ];
        }

        return $issues;
    }

    /**
     * Generate statistics
     */
    private function generateStatistics(array $csvData, array $headers, array $validatedRows): array
    {
        $stats = [
            'total_rows' => count($csvData),
            'total_columns' => count($headers),
            'valid_rows_preview' => count(array_filter($validatedRows, fn($r) => $r['valid'])),
            'invalid_rows_preview' => count(array_filter($validatedRows, fn($r) => !$r['valid'])),
        ];

        // Field statistics
        $fieldStats = [];
        foreach ($headers as $header) {
            $values = array_column(array_column($validatedRows, 'data'), $header);
            $numericValues = array_filter($values, 'is_numeric');
            
            if (!empty($numericValues)) {
                $fieldStats[$header] = [
                    'min' => min($numericValues),
                    'max' => max($numericValues),
                    'avg' => array_sum($numericValues) / count($numericValues),
                    'type' => 'numeric'
                ];
            } else {
                $fieldStats[$header] = [
                    'unique_values' => count(array_unique($values)),
                    'most_common' => $this->getMostCommon($values),
                    'type' => 'text'
                ];
            }
        }

        $stats['field_statistics'] = $fieldStats;

        return $stats;
    }

    /**
     * Get most common value
     */
    private function getMostCommon(array $values): ?string
    {
        if (empty($values)) {
            return null;
        }

        $counts = array_count_values(array_filter($values));
        if (empty($counts)) {
            return null;
        }

        arsort($counts);
        return (string)array_key_first($counts);
    }

    /**
     * Suggest field mapping
     */
    private function suggestFieldMapping(array $csvHeaders, Product $product): array
    {
        $productFields = $this->getProductFields($product);
        $mapping = [];

        foreach ($csvHeaders as $csvHeader) {
            $bestMatch = $this->findBestFieldMatch($csvHeader, $productFields);
            $mapping[$csvHeader] = [
                'suggested_field' => $bestMatch,
                'confidence' => $this->calculateMatchConfidence($csvHeader, $bestMatch),
                'alternatives' => $this->findAlternativeMatches($csvHeader, $productFields, $bestMatch)
            ];
        }

        return $mapping;
    }

    /**
     * Get product fields
     */
    private function getProductFields(Product $product): array
    {
        $fieldDefinitions = $product->field_definitions ?? [];
        return array_column($fieldDefinitions, 'name');
    }

    /**
     * Find best field match
     */
    private function findBestFieldMatch(string $csvHeader, array $productFields): ?string
    {
        $normalized = strtolower(str_replace([' ', '_', '-'], '', $csvHeader));
        
        foreach ($productFields as $field) {
            $normalizedField = strtolower(str_replace([' ', '_', '-'], '', $field));
            if ($normalized === $normalizedField) {
                return $field;
            }
        }

        // Fuzzy matching
        $bestMatch = null;
        $bestScore = 0;

        foreach ($productFields as $field) {
            $score = similar_text(strtolower($csvHeader), strtolower($field));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $field;
            }
        }

        return $bestScore > 3 ? $bestMatch : null;
    }

    /**
     * Calculate match confidence
     */
    private function calculateMatchConfidence(string $csvHeader, ?string $match): string
    {
        if ($match === null) {
            return 'none';
        }

        $similarity = similar_text(strtolower($csvHeader), strtolower($match));
        
        if ($similarity >= strlen($csvHeader)) {
            return 'high';
        } elseif ($similarity >= strlen($csvHeader) * 0.7) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Find alternative matches
     */
    private function findAlternativeMatches(string $csvHeader, array $productFields, ?string $bestMatch): array
    {
        $alternatives = [];
        
        foreach ($productFields as $field) {
            if ($field === $bestMatch) {
                continue;
            }

            $similarity = similar_text(strtolower($csvHeader), strtolower($field));
            if ($similarity > 3) {
                $alternatives[] = $field;
            }
        }

        return array_slice($alternatives, 0, 3); // Max 3 alternatives
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(array $validatedRows, array $qualityAnalysis): array
    {
        $recommendations = [];

        // Quality score recommendations
        if ($qualityAnalysis['quality_score'] < 80) {
            $recommendations[] = [
                'type' => 'quality',
                'priority' => 'high',
                'message' => 'Data quality is below 80%. Review and fix validation errors before importing.'
            ];
        }

        // Completeness recommendations
        if ($qualityAnalysis['completeness_score'] < 90) {
            $recommendations[] = [
                'type' => 'completeness',
                'priority' => 'medium',
                'message' => 'Some fields have missing values. Consider filling in missing data.'
            ];
        }

        // Field-specific recommendations
        foreach ($qualityAnalysis['null_counts'] as $field => $count) {
            if ($count > 0) {
                $recommendations[] = [
                    'type' => 'field',
                    'priority' => 'low',
                    'field' => $field,
                    'message' => "Field '{$field}' has {$count} null values in preview"
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Parse CSV file
     */
    private function parseCsvFile(UploadedFile $file): array
    {
        $csvData = [];
        $handle = fopen($file->getPathname(), 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV file');
        }

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $csvData[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $csvData;
    }
}

