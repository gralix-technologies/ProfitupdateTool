<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ExchangeRate extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'currency',
        'rate_to_base',
        'date'
    ];

    protected $casts = [
        'rate_to_base' => 'decimal:8',
        'date' => 'date'
    ];

    /**
     * Get the currency relationship
     */
    public function currencyModel()
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    /**
     * Scope for exchange rates by currency
     */
    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope for latest exchange rates
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc');
    }
}