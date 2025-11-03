<?php

namespace App\Services;

use App\Models\Product;

class ProductFieldValidator
{
    /**
     * Ensure customer_id field exists and is mandatory for all products
     */
    public function ensureCustomerIdField(array $fieldDefinitions): array
    {
        $hasCustomerId = false;
        
        // Check if customer_id already exists
        foreach ($fieldDefinitions as &$field) {
            if ($field['name'] === 'customer_id') {
                $hasCustomerId = true;
                // Ensure it's required and has proper type
                $field['required'] = true;
                $field['type'] = $field['type'] ?? 'text';
                $field['label'] = $field['label'] ?? 'Customer ID';
                $field['description'] = 'Customer identifier (required for profitability calculations)';
                break;
            }
        }
        
        // If customer_id doesn't exist, add it at the beginning
        if (!$hasCustomerId) {
            array_unshift($fieldDefinitions, [
                'name' => 'customer_id',
                'label' => 'Customer ID',
                'type' => 'text',
                'required' => true,
                'description' => 'Customer identifier (required for profitability calculations)',
                'validation_rules' => 'required|string|max:255',
            ]);
        }
        
        return $fieldDefinitions;
    }
    
    /**
     * Validate portfolio value field exists and is numeric
     */
    public function validatePortfolioValueField(Product $product): bool
    {
        if (!$product->portfolio_value_field) {
            return false;
        }
        
        $fieldDefinitions = $product->field_definitions ?? [];
        
        foreach ($fieldDefinitions as $field) {
            if ($field['name'] === $product->portfolio_value_field) {
                // Check if field type is numeric (case-insensitive)
                $type = strtolower($field['type']);
                return in_array($type, ['numeric', 'number', 'decimal', 'integer', 'float']);
            }
        }
        
        return false;
    }
    
    /**
     * Get suggested portfolio value field for a product
     */
    public function suggestPortfolioValueField(array $fieldDefinitions): ?string
    {
        $numericFields = [];
        
        foreach ($fieldDefinitions as $field) {
            // Check if field type is numeric (case-insensitive)
            $type = strtolower($field['type']);
            if (in_array($type, ['numeric', 'number', 'decimal', 'integer', 'float'])) {
                $numericFields[] = $field['name'];
            }
        }
        
        // Priority order for common portfolio value fields
        $preferredNames = [
            'outstanding_balance',
            'balance',
            'amount',
            'principal_amount',
            'loan_amount',
            'deposit_amount',
            'investment_amount',
            'trade_amount',
            'premium_amount',
            'value',
        ];
        
        foreach ($preferredNames as $preferred) {
            if (in_array($preferred, $numericFields)) {
                return $preferred;
            }
        }
        
        // Return first numeric field if no preferred name found
        return $numericFields[0] ?? null;
    }
    
    /**
     * Ensure all required system fields exist
     */
    public function ensureSystemFields(array $fieldDefinitions): array
    {
        // Ensure customer_id is present and required
        $fieldDefinitions = $this->ensureCustomerIdField($fieldDefinitions);
        
        // Additional system fields can be added here in the future
        // For example: created_at, updated_at, etc.
        
        return $fieldDefinitions;
    }
    
    /**
     * Validate field definitions structure
     */
    public function validateFieldDefinitions(array $fieldDefinitions): array
    {
        $errors = [];
        
        if (empty($fieldDefinitions)) {
            $errors[] = 'Field definitions cannot be empty';
            return $errors;
        }
        
        $fieldNames = [];
        foreach ($fieldDefinitions as $index => $field) {
            // Check required properties
            if (!isset($field['name']) || empty($field['name'])) {
                $errors[] = "Field at index {$index} is missing 'name' property";
                continue;
            }
            
            // Check for duplicate names
            if (in_array($field['name'], $fieldNames)) {
                $errors[] = "Duplicate field name: {$field['name']}";
            }
            $fieldNames[] = $field['name'];
            
            // Check type
            if (!isset($field['type']) || empty($field['type'])) {
                $errors[] = "Field '{$field['name']}' is missing 'type' property";
            }
        }
        
        return $errors;
    }
}

