<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class ProductData extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'product_id',
        'customer_id',
        'data',
        'amount',
        'effective_date',
        'status'
    ];

    protected $casts = [
        'data' => 'array',
        'amount' => 'decimal:2',
        'effective_date' => 'date'
    ];

    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    
    public function getFieldValue(string $fieldName)
    {
        return $this->data[$fieldName] ?? null;
    }

    
    public function setFieldValue(string $fieldName, $value): void
    {
        $data = $this->data ?? [];
        $data[$fieldName] = $value;
        $this->data = $data;
    }

    
    public function validateData(): array
    {
        $errors = [];
        $product = $this->product;
        
        if (!$product) {
            return ['Product not found'];
        }

        $fieldDefinitions = $product->field_definitions ?? [];
        
        foreach ($fieldDefinitions as $field) {
            $fieldName = $field['name'];
            $value = $this->getFieldValue($fieldName);
            
            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                $errors[] = "Field '{$fieldName}' is required";
                continue;
            }
            
            if ($value !== null && $value !== '' && !$product->validateFieldValue($fieldName, $value)) {
                $errors[] = "Invalid value for field '{$fieldName}'";
            }
        }
        
        return $errors;
    }

    
    public static function existsForCustomer(int $productId, string $customerId): bool
    {
        return static::where('product_id', $productId)
            ->where('customer_id', $customerId)
            ->exists();
    }

    
    public static function getForCustomer(int $productId, string $customerId): ?self
    {
        return static::where('product_id', $productId)
            ->where('customer_id', $customerId)
            ->first();
    }

    
    public static function updateOrCreateForCustomer(
        int $productId,
        string $customerId,
        array $data,
        ?float $amount = null,
        ?string $effectiveDate = null,
        string $status = 'active'
    ): self {
        return static::updateOrCreate(
            [
                'product_id' => $productId,
                'customer_id' => $customerId
            ],
            [
                'data' => $data,
                'amount' => $amount,
                'effective_date' => $effectiveDate,
                'status' => $status
            ]
        );
    }

    
    public static function clearForProduct(int $productId): int
    {
        return static::where('product_id', $productId)->delete();
    }

    
    public static function getSummaryForProduct(int $productId): array
    {
        $baseQuery = static::where('product_id', $productId);
        
        return [
            'total_records' => $baseQuery->count(),
            'unique_customers' => static::where('product_id', $productId)->distinct('customer_id')->count(),
            'total_amount' => static::where('product_id', $productId)->sum('amount'),
            'active_records' => static::where('product_id', $productId)->where('status', 'active')->count(),
            'latest_update' => static::where('product_id', $productId)->max('updated_at')
        ];
    }
}



