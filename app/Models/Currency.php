<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\Auditable;

class Currency extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'display_name',
        'is_base_currency',
        'is_active',
        'decimal_places',
        'thousands_separator',
        'decimal_separator',
        'symbol_position'
    ];

    protected $casts = [
        'is_base_currency' => 'boolean',
        'is_active' => 'boolean',
        'decimal_places' => 'integer'
    ];

    /**
     * Get the base currency for the application
     */
    public static function getBaseCurrency(): ?self
    {
        return Cache::remember('base_currency', 3600, function () {
            return static::where('is_base_currency', true)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get all active currencies
     */
    public static function getActiveCurrencies()
    {
        return Cache::remember('active_currencies', 3600, function () {
            return static::where('is_active', true)->orderBy('code')->get();
        });
    }

    /**
     * Format a number according to this currency's rules
     */
    public function formatAmount(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            $this->decimal_separator,
            $this->thousands_separator
        );

        if ($this->symbol_position === 'before') {
            return $this->symbol . $formatted;
        }

        return $formatted . ' ' . $this->symbol;
    }

    /**
     * Format a number with currency code
     */
    public function formatAmountWithCode(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            $this->decimal_separator,
            $this->thousands_separator
        );

        return $this->code . ' ' . $formatted;
    }

    /**
     * Get the exchange rate to another currency
     */
    public function getExchangeRateTo(string $toCurrencyCode): float
    {
        if ($this->code === $toCurrencyCode) {
            return 1.0;
        }

        // Use CurrencyService for exchange rate calculations
        $currencyService = app(\App\Services\CurrencyService::class);
        return $currencyService->getExchangeRate($this->code, $toCurrencyCode);
    }

    /**
     * Convert amount to another currency
     */
    public function convertTo(float $amount, string $toCurrencyCode): float
    {
        if ($this->code === $toCurrencyCode) {
            return $amount;
        }

        $rate = $this->getExchangeRateTo($toCurrencyCode);
        return $amount * $rate;
    }

    /**
     * Boot method to clear cache when model changes
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('base_currency');
            Cache::forget('active_currencies');
        });

        static::deleted(function () {
            Cache::forget('base_currency');
            Cache::forget('active_currencies');
        });
    }

    /**
     * Relationship with exchange rates
     */
    public function exchangeRatesFrom()
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency', 'code');
    }

    public function exchangeRatesTo()
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency', 'code');
    }
}