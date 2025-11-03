<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Formula extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'expression',
        'product_id',
        'parameters',
        'description',
        'return_type',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean'
    ];

    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    
    public static function getReturnTypes(): array
    {
        return ['numeric', 'text', 'boolean', 'date'];
    }

    
    public function isValidForProduct(Product $product): bool
    {
        return $this->product_id === null || $this->product_id === $product->id;
    }

    
    public function getParametersWithDefaults(): array
    {
        $defaults = [
            'precision' => 2,
            'currency' => 'ZMW',
            'date_format' => 'Y-m-d'
        ];

        return array_merge($defaults, $this->parameters ?? []);
    }
}



