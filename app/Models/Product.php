<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'category',
        'field_definitions',
        'portfolio_value_field',
        'description',
        'is_active'
    ];

    protected $casts = [
        'field_definitions' => 'array',
        'is_active' => 'boolean'
    ];

    
    public function productData(): HasMany
    {
        return $this->hasMany(ProductData::class);
    }

    
    public function formulas(): HasMany
    {
        return $this->hasMany(Formula::class);
    }

    
    public function dashboard(): HasOne
    {
        return $this->hasOne(Dashboard::class);
    }

    
    public function hasField(string $fieldName): bool
    {
        $fields = $this->field_definitions ?? [];
        return collect($fields)->contains('name', $fieldName);
    }

    
    public function validateFieldValue(string $fieldName, $value): bool
    {
        $fields = $this->field_definitions ?? [];
        $field = collect($fields)->firstWhere('name', $fieldName);
        
        if (!$field) {
            return false;
        }

        switch ($field['type']) {
            case 'Text':
                return is_string($value);
            case 'Numeric':
                return is_numeric($value);
            case 'Date':
                return strtotime($value) !== false;
            case 'Lookup':
                return in_array($value, $field['options'] ?? []);
            default:
                return true;
        }
    }

    
    public static function getCategories(): array
    {
        return ['Loan', 'Account', 'Deposit', 'Transaction', 'Other'];
    }

    
    public static function getFieldTypes(): array
    {
        return ['Text', 'Numeric', 'Date', 'Lookup'];
    }

    /**
     * Validate portfolio value field is numeric
     */
    public function validatePortfolioValueField(string $fieldName): bool
    {
        $fields = $this->field_definitions ?? [];
        $field = collect($fields)->firstWhere('name', $fieldName);
        
        if (!$field) {
            return false;
        }

        // Must be Numeric type
        return ($field['type'] ?? '') === 'Numeric';
    }

    /**
     * Get the designated portfolio value field name
     */
    public function getPortfolioValueField(): string
    {
        // If explicitly set, use that
        if ($this->portfolio_value_field) {
            return $this->portfolio_value_field;
        }

        // Auto-detect based on field names (fallback for backwards compatibility)
        $fields = $this->field_definitions ?? [];
        $priorityFields = ['outstanding_balance', 'loan_amount', 'balance', 'amount', 'principal_amount'];
        
        foreach ($priorityFields as $priorityField) {
            $field = collect($fields)->firstWhere('name', $priorityField);
            if ($field && ($field['type'] ?? '') === 'Numeric') {
                return $priorityField;
            }
        }

        // Ultimate fallback
        return 'amount';
    }

    /**
     * Get numeric fields only (for portfolio value field selection)
     */
    public function getNumericFields(): array
    {
        $fields = $this->field_definitions ?? [];
        return collect($fields)
            ->filter(fn($field) => ($field['type'] ?? '') === 'Numeric')
            ->pluck('name')
            ->toArray();
    }
}



