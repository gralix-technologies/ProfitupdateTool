<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * ENHANCED Sample File Service
 * Generates realistic sample CSV files dynamically based on product field definitions
 * Respects ALL field constraints: min/max values, decimal places, options, date formats
 */
class SampleFileService
{
    /**
     * Generate sample CSV file for a product
     */
    public function generateSampleFile(Product $product, int $sampleRows = 5): string
    {
        $fieldDefinitions = $product->field_definitions ?? [];
        
        // Ensure field_definitions is an array
        if (is_string($fieldDefinitions)) {
            $fieldDefinitions = json_decode($fieldDefinitions, true) ?? [];
        }
        
        if (empty($fieldDefinitions)) {
            throw new \InvalidArgumentException('Product has no field definitions');
        }

        $csvContent = $this->generateCsvContent($fieldDefinitions, $sampleRows);
        
        $filename = $this->generateFilename($product);
        
        $filePath = "sample_files/{$filename}";
        Storage::disk('local')->put($filePath, $csvContent);
        
        return $filePath;
    }

    /**
     * Generate CSV content with realistic sample data
     */
    protected function generateCsvContent(array $fieldDefinitions, int $sampleRows): string
    {
        $lines = [];
        
        // Build headers - always include customer_id first
        $headers = ['customer_id'];
        foreach ($fieldDefinitions as $field) {
            $headers[] = $field['name'];
        }
        $lines[] = implode(',', $headers);
        
        // Generate sample rows with realistic, constraint-respecting data
        for ($i = 1; $i <= $sampleRows; $i++) {
            $row = [];
            
            // Generate customer_id
            $row[] = "CUST" . str_pad($i, 6, '0', STR_PAD_LEFT);
            
            // Generate value for each field based on its definition
            foreach ($fieldDefinitions as $field) {
                $row[] = $this->generateSampleValue($field, $i);
            }
            
            $lines[] = implode(',', $row);
        }
        
        return implode("\n", $lines);
    }

    /**
     * Generate sample value - ENHANCED to respect field definitions
     */
    public function generateSampleValue(array $field, int $rowIndex): string
    {
        $fieldName = $field['name'];
        $fieldType = $field['type'] ?? 'Text';
        $isRequired = $field['required'] ?? false;
        
        // For optional fields, occasionally return empty to demonstrate they can be blank
        if (!$isRequired && $rowIndex % 4 === 0) {
            return '';
        }
        
        switch ($fieldType) {
            case 'Text':
                return $this->generateTextValue($field, $rowIndex);
                
            case 'Numeric':
                return $this->generateNumericValue($field, $rowIndex);
                
            case 'Date':
                return $this->generateDateValue($field, $rowIndex);
                
            case 'Lookup':
                return $this->generateLookupValue($field, $rowIndex);
                
            default:
                return "Sample_{$fieldName}_{$rowIndex}";
        }
    }

    /**
     * Generate text value - Enhanced with constraints
     */
    protected function generateTextValue(array $field, int $rowIndex): string
    {
        $fieldName = $field['name'];
        $minLength = $field['min_length'] ?? null;
        $maxLength = $field['max_length'] ?? null;
        
        // Pattern-based realistic values
        $sampleData = [
            'customer_id' => "CUST" . str_pad($rowIndex, 6, '0', STR_PAD_LEFT),
            'loan_reference' => "SME-" . date('Y') . "-" . str_pad($rowIndex, 5, '0', STR_PAD_LEFT),
            'reference' => "REF-" . str_pad($rowIndex, 6, '0', STR_PAD_LEFT),
            'account_number' => "ACC" . str_pad($rowIndex, 8, '0', STR_PAD_LEFT),
            'name' => ['John Smith', 'Jane Doe', 'Bob Johnson', 'Alice Brown', 'Charlie Wilson'][($rowIndex - 1) % 5],
            'branch_code' => ['BR001', 'BR002', 'BR003', 'BR004', 'BR005'][($rowIndex - 1) % 5],
            'description' => "Sample description for {$fieldName} row {$rowIndex}",
            'notes' => "Sample notes for row {$rowIndex}",
        ];
        
        // Check for pattern match
        foreach ($sampleData as $pattern => $value) {
            if (stripos($fieldName, $pattern) !== false) {
                // Respect length constraints
                if ($minLength !== null && strlen($value) < $minLength) {
                    $value = str_pad($value, $minLength, 'X');
                }
                if ($maxLength !== null && strlen($value) > $maxLength) {
                    $value = substr($value, 0, $maxLength);
                }
                return $value;
            }
        }
        
        // Default text value
        $value = "Sample_{$fieldName}_{$rowIndex}";
        
        // Respect length constraints
        if ($minLength !== null && strlen($value) < $minLength) {
            $value = str_pad($value, $minLength, 'X');
        }
        if ($maxLength !== null && strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }
        
        return $value;
    }

    /**
     * Generate numeric value - COMPLETELY REWRITTEN to respect all constraints
     */
    protected function generateNumericValue(array $field, int $rowIndex): string
    {
        $fieldName = $field['name'];
        $minValue = $field['min_value'] ?? null;
        $maxValue = $field['max_value'] ?? null;
        $decimalPlaces = $field['decimal_places'] ?? 2;
        
        // PRIORITY 1: If both min and max are defined, generate within range
        if ($minValue !== null && $maxValue !== null) {
            $range = $maxValue - $minValue;
            
            // Generate varied values across the range
            if ($rowIndex === 1) {
                $value = $minValue + ($range * 0.2); // 20% of range
            } elseif ($rowIndex === 2) {
                $value = $minValue + ($range * 0.5); // 50% of range (middle)
            } elseif ($rowIndex === 3) {
                $value = $minValue + ($range * 0.75); // 75% of range
            } elseif ($rowIndex === 4) {
                $value = $minValue + ($range * 0.3); // 30% of range
            } else {
                // Distribute remaining values
                $step = $range / 10;
                $value = $minValue + (($rowIndex % 10) * $step);
            }
            
            // Ensure within bounds
            $value = min($maxValue, max($minValue, $value));
            return number_format($value, $decimalPlaces, '.', '');
        }
        
        // PRIORITY 2: Only min defined
        if ($minValue !== null) {
            $value = $minValue * (1 + ($rowIndex * 0.5));
            return number_format($value, $decimalPlaces, '.', '');
        }
        
        // PRIORITY 3: Only max defined
        if ($maxValue !== null) {
            $value = $maxValue * (0.1 * $rowIndex);
            $value = min($maxValue, $value);
            return number_format($value, $decimalPlaces, '.', '');
        }
        
        // PRIORITY 4: No constraints - use realistic defaults based on field name patterns
        $defaultValues = [
            'loan_amount' => 500000 + ($rowIndex * 250000),
            'outstanding_balance' => 400000 + ($rowIndex * 200000),
            'principal_disbursed' => 500000 + ($rowIndex * 250000),
            'principal_amount' => 500000 + ($rowIndex * 250000),
            'amount' => 100000 + ($rowIndex * 50000),
            'balance' => 150000 + ($rowIndex * 75000),
            'interest_rate' => 12.00 + ($rowIndex * 2.50),
            'rate' => 8.00 + ($rowIndex * 1.50),
            'interest_earned' => 50000 + ($rowIndex * 25000),
            'loan_term_months' => 12 + ($rowIndex * 6),
            'term_months' => 12 + ($rowIndex * 6),
            'term_years' => 1 + $rowIndex,
            'days_past_due' => $rowIndex * 10,
            'probability_of_default' => 2.00 + ($rowIndex * 1.00),
            'loss_given_default' => 30.00 + ($rowIndex * 5.00),
            'expected_credit_loss' => 25000 + ($rowIndex * 10000),
            'collateral_value' => 600000 + ($rowIndex * 300000),
            'loan_to_value_ratio' => 75.00 + ($rowIndex * 3.00),
            'credit_limit' => 50000 + ($rowIndex * 25000),
            'current_balance' => 30000 + ($rowIndex * 15000),
            'minimum_balance' => 5000 + ($rowIndex * 2500),
            'maximum_amount' => 1000000 + ($rowIndex * 500000),
            'fee' => 500 + ($rowIndex * 250),
            'charge' => 250 + ($rowIndex * 125),
            'penalty' => 1000 + ($rowIndex * 500),
        ];
        
        // Match field name pattern
        foreach ($defaultValues as $pattern => $baseValue) {
            if (stripos($fieldName, $pattern) !== false) {
                return number_format($baseValue, $decimalPlaces, '.', '');
            }
        }
        
        // Ultimate fallback
        return number_format(1000 + ($rowIndex * 500), $decimalPlaces, '.', '');
    }

    /**
     * Generate date value - Enhanced with date format support
     */
    protected function generateDateValue(array $field, int $rowIndex): string
    {
        $fieldName = $field['name'];
        $dateFormat = $field['date_format'] ?? 'Y-m-d';
        $minDate = $field['min_date'] ?? null;
        $maxDate = $field['max_date'] ?? null;
        
        // If min and max dates are defined, generate within range
        if ($minDate && $maxDate) {
            $start = Carbon::parse($minDate);
            $end = Carbon::parse($maxDate);
            $daysDiff = $end->diffInDays($start);
            $daysToAdd = ($daysDiff / 10) * $rowIndex;
            return $start->addDays($daysToAdd)->format($dateFormat);
        }
        
        // Pattern-based date generation
        $baseDate = Carbon::now();
        
        if (stripos($fieldName, 'disbursement') !== false || stripos($fieldName, 'start') !== false) {
            // Past dates for disbursement/start
            return $baseDate->subMonths($rowIndex * 2)->format($dateFormat);
        }
        
        if (stripos($fieldName, 'maturity') !== false || stripos($fieldName, 'end') !== false || stripos($fieldName, 'due') !== false) {
            // Future dates for maturity/end/due
            return $baseDate->addMonths($rowIndex * 6)->format($dateFormat);
        }
        
        if (stripos($fieldName, 'birth') !== false) {
            // Birth dates - 25-45 years ago
            return $baseDate->subYears(25 + ($rowIndex * 2))->format($dateFormat);
        }
        
        // Default: recent past dates
        return $baseDate->subDays($rowIndex * 30)->format($dateFormat);
    }

    /**
     * Generate lookup value - Enhanced to use actual options
     */
    protected function generateLookupValue(array $field, int $rowIndex): string
    {
        $options = $field['options'] ?? [];
        
        if (empty($options)) {
            return "Option_{$rowIndex}";
        }
        
        // Cycle through available options
        $index = ($rowIndex - 1) % count($options);
        return $options[$index];
    }

    /**
     * Generate filename for sample file
     */
    protected function generateFilename(Product $product): string
    {
        $productName = str_replace(' ', '_', $product->name);
        $productName = preg_replace('/[^a-zA-Z0-9_-]/', '', $productName);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "sample_{$productName}_{$timestamp}.csv";
    }

    /**
     * Get field requirements for a product
     */
    public function getFieldRequirements(Product $product): array
    {
        $fieldDefinitions = $product->field_definitions ?? [];
        
        // Ensure field_definitions is an array
        if (is_string($fieldDefinitions)) {
            $fieldDefinitions = json_decode($fieldDefinitions, true) ?? [];
        }
        
        $requirements = [];
        
        // Always include customer_id first
        $requirements[] = [
            'name' => 'customer_id',
            'type' => 'Text',
            'required' => true,
            'description' => 'Unique customer identifier',
            'example' => 'CUST000001'
        ];
        
        // Add all defined fields
        foreach ($fieldDefinitions as $field) {
            $requirements[] = [
                'name' => $field['name'],
                'type' => $field['type'] ?? 'Text',
                'required' => $field['required'] ?? false,
                'description' => $this->getFieldDescription($field),
                'options' => $field['options'] ?? null,
                'constraints' => $this->getFieldConstraints($field),
                'example' => $this->generateSampleValue($field, 1)
            ];
        }
        
        return $requirements;
    }

    /**
     * Get human-readable field description
     */
    public function getFieldDescription(array $field): string
    {
        $fieldName = $field['name'];
        $fieldType = $field['type'] ?? 'Text';
        
        // Check for custom label first
        if (isset($field['label']) && !empty($field['label'])) {
            return $field['label'];
        }
        
        // Enhanced descriptions library
        $descriptions = [
            'customer_id' => 'Unique customer identifier',
            'loan_reference' => 'Unique loan reference number',
            'amount' => 'Monetary amount',
            'balance' => 'Current account balance',
            'loan_amount' => 'Total loan amount disbursed',
            'outstanding_balance' => 'Current outstanding loan balance',
            'principal_disbursed' => 'Principal amount disbursed',
            'principal_amount' => 'Principal amount',
            'credit_limit' => 'Maximum credit limit',
            'current_balance' => 'Current outstanding balance',
            'interest_rate' => 'Annual interest rate percentage',
            'interest_earned' => 'Total interest income earned',
            'rate' => 'Rate percentage',
            'term_months' => 'Loan or account term in months',
            'loan_term_months' => 'Loan term in months',
            'term_years' => 'Term in years',
            'days_past_due' => 'Number of days payment is overdue',
            'probability_of_default' => 'Probability of Default (PD) percentage',
            'loss_given_default' => 'Loss Given Default (LGD) percentage',
            'expected_credit_loss' => 'Expected Credit Loss (ECL) amount',
            'collateral_value' => 'Value of collateral pledged',
            'loan_to_value_ratio' => 'Loan-to-Value (LTV) ratio percentage',
            'minimum_balance' => 'Minimum required balance',
            'maximum_amount' => 'Maximum allowed amount',
            'fee' => 'Fee amount',
            'charge' => 'Charge amount',
            'penalty' => 'Penalty amount',
            'disbursement_date' => 'Date when funds were disbursed',
            'maturity_date' => 'Date when loan/account matures',
            'approval_date' => 'Date of approval',
            'effective_date' => 'Effective date',
            'start_date' => 'Start date',
            'end_date' => 'End date',
            'created_date' => 'Creation date',
            'updated_date' => 'Last update date',
            'transaction_date' => 'Transaction date',
            'due_date' => 'Payment due date',
            'birth_date' => 'Date of birth',
            'status' => 'Current status',
            'ifrs9_stage' => 'IFRS 9 classification stage',
            'type' => 'Type or category',
            'category' => 'Product category',
            'industry_sector' => 'Industry or business sector',
            'business_type' => 'Business legal structure type',
            'branch_code' => 'Branch or location code',
        ];
        
        if (isset($descriptions[$fieldName])) {
            return $descriptions[$fieldName];
        }
        
        // Generate description from field name
        $readable = ucfirst(str_replace('_', ' ', $fieldName));
        return "{$readable} ({$fieldType} field)";
    }

    /**
     * Get field constraints for display
     */
    public function getFieldConstraints(array $field): ?array
    {
        $constraints = [];
        
        // Numeric constraints
        if (isset($field['min_value'])) {
            $constraints['min_value'] = $field['min_value'];
        }
        if (isset($field['max_value'])) {
            $constraints['max_value'] = $field['max_value'];
        }
        if (isset($field['decimal_places'])) {
            $constraints['decimal_places'] = $field['decimal_places'];
        }
        
        // Text constraints
        if (isset($field['min_length'])) {
            $constraints['min_length'] = $field['min_length'];
        }
        if (isset($field['max_length'])) {
            $constraints['max_length'] = $field['max_length'];
        }
        if (isset($field['pattern'])) {
            $constraints['pattern'] = $field['pattern'];
        }
        
        // Date constraints
        if (isset($field['date_format'])) {
            $constraints['date_format'] = $field['date_format'];
        }
        if (isset($field['min_date'])) {
            $constraints['min_date'] = $field['min_date'];
        }
        if (isset($field['max_date'])) {
            $constraints['max_date'] = $field['max_date'];
        }
        
        return empty($constraints) ? null : $constraints;
    }

    /**
     * Cleanup old sample files
     */
    public function cleanupOldFiles(int $hoursOld = 24): int
    {
        $files = Storage::disk('local')->files('sample_files');
        $deletedCount = 0;
        $cutoffTime = now()->subHours($hoursOld);
        
        foreach ($files as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);
            if ($lastModified < $cutoffTime->timestamp) {
                Storage::disk('local')->delete($file);
                $deletedCount++;
            }
        }
        
        return $deletedCount;
    }
}
