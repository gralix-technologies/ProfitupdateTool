<?php

namespace App\Services;

class FieldDefinitionService
{
    private const SUPPORTED_TYPES = ['Text', 'Numeric', 'Date', 'Lookup'];

    public function validateFieldDefinition(array $definition): ValidationResult
    {
        $errors = [];

        if (empty($definition['name'])) {
            $errors[] = 'Field name is required';
        }

        if (empty($definition['type'])) {
            $errors[] = 'Field type is required';
        } elseif (!in_array($definition['type'], self::SUPPORTED_TYPES)) {
            $errors[] = 'Invalid field type';
        }

        if ($definition['type'] === 'Lookup' && empty($definition['options'])) {
            $errors[] = 'Lookup fields must have options';
        }

        return new ValidationResult(empty($errors), $errors);
    }

    public function validateFieldValue(array $fieldDefinition, $value): bool
    {
        if ($fieldDefinition['required'] ?? false) {
            if ($value === null || $value === '') {
                return false;
            }
        }

        if (($value === null || $value === '') && !($fieldDefinition['required'] ?? false)) {
            return true;
        }

        switch ($fieldDefinition['type']) {
            case 'Numeric':
                return is_numeric($value);
            
            case 'Date':
                return $this->isValidDate($value);
            
            case 'Lookup':
                return in_array($value, $fieldDefinition['options'] ?? []);
            
            case 'Text':
            default:
                return is_string($value) || is_numeric($value);
        }
    }

    public function getSupportedFieldTypes(): array
    {
        return self::SUPPORTED_TYPES;
    }

    public function formatForStorage(array $definition): array
    {
        return [
            'name' => $definition['name'],
            'type' => $definition['type'],
            'required' => $definition['required'] ?? false,
            'metadata' => [
                'label' => $definition['label'] ?? $definition['name'],
                'description' => $definition['description'] ?? null,
                'options' => $definition['options'] ?? null,
            ]
        ];
    }

    private function isValidDate($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $timestamp = strtotime($value);
        return $timestamp !== false;
    }
}


