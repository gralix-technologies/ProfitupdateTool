<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Get the base currency configuration
     */
    public function getBaseCurrency(): ?Currency
    {
        return Currency::getBaseCurrency();
    }

    /**
     * Format amount using base currency
     */
    public function formatAmount($amount, ?Currency $currency = null): string
    {
        if ($amount === null || $amount === '' || $amount === 'invalid') {
            return 'N/A';
        }

        // Convert string to float if needed
        $amount = is_numeric($amount) ? (float) $amount : 0;

        $currency = $currency ?? $this->getBaseCurrency();
        
        if (!$currency) {
            Log::warning('No base currency configured, using default formatting');
            return number_format($amount, 2);
        }

        return $currency->formatAmount($amount);
    }

    /**
     * Format amount with currency code
     */
    public function formatAmountWithCode($amount, ?Currency $currency = null): string
    {
        if ($amount === null || $amount === '' || $amount === 'invalid') {
            return 'N/A';
        }

        // Convert string to float if needed
        $amount = is_numeric($amount) ? (float) $amount : 0;

        $currency = $currency ?? $this->getBaseCurrency();
        
        if (!$currency) {
            return number_format($amount, 2);
        }

        return $currency->formatAmountWithCode($amount);
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol(?Currency $currency = null): string
    {
        $currency = $currency ?? $this->getBaseCurrency();
        
        return $currency ? $currency->symbol : '';
    }

    /**
     * Get currency code
     */
    public function getCurrencyCode(?Currency $currency = null): string
    {
        $currency = $currency ?? $this->getBaseCurrency();
        
        return $currency ? $currency->code : '';
    }

    /**
     * Get currency display name
     */
    public function getCurrencyDisplayName(?Currency $currency = null): string
    {
        $currency = $currency ?? $this->getBaseCurrency();
        
        return $currency ? $currency->display_name : 'Currency';
    }

    /**
     * Convert amount from one currency to another
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $fromCurrencyModel = Currency::where('code', $fromCurrency)->first();
        if (!$fromCurrencyModel) {
            return $amount;
        }

        return $fromCurrencyModel->convertTo($amount, $toCurrency);
    }

    /**
     * Convert amount to base currency
     */
    public function convertToBaseCurrency(float $amount, string $fromCurrency): float
    {
        $baseCurrency = $this->getBaseCurrency();
        if (!$baseCurrency) {
            return $amount;
        }

        return $this->convertAmount($amount, $fromCurrency, $baseCurrency->code);
    }

    /**
     * Get all available currencies
     */
    public function getAvailableCurrencies()
    {
        return Currency::getActiveCurrencies();
    }

    /**
     * Set base currency
     */
    public function setBaseCurrency(string $currencyCode): bool
    {
        try {
            // Remove base currency flag from all currencies
            Currency::query()->update(['is_base_currency' => false]);
            
            // Set new base currency
            $currency = Currency::where('code', $currencyCode)->first();
            if ($currency) {
                $currency->update(['is_base_currency' => true]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to set base currency: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or update exchange rate (to base currency)
     */
    public function setExchangeRate(string $fromCurrency, string $toCurrency, float $rate): bool
    {
        try {
            $baseCurrency = $this->getBaseCurrency();
            if (!$baseCurrency) {
                return false;
            }

            // Only store rates to base currency in the existing table structure
            if ($toCurrency === $baseCurrency->code) {
                ExchangeRate::updateOrCreate(
                    [
                        'currency' => $fromCurrency,
                        'date' => now()->toDateString()
                    ],
                    [
                        'rate_to_base' => $rate
                    ]
                );
            } else if ($fromCurrency === $baseCurrency->code) {
                // For rates from base currency, store as inverse
                ExchangeRate::updateOrCreate(
                    [
                        'currency' => $toCurrency,
                        'date' => now()->toDateString()
                    ],
                    [
                        'rate_to_base' => 1.0 / $rate
                    ]
                );
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set exchange rate: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // Get base currency
        $baseCurrency = $this->getBaseCurrency();
        if (!$baseCurrency) {
            return 1.0;
        }

        // If converting to base currency, use direct rate
        if ($toCurrency === $baseCurrency->code) {
            $exchangeRate = ExchangeRate::forCurrency($fromCurrency)
                ->latest()
                ->first();
            return $exchangeRate ? $exchangeRate->rate_to_base : 1.0;
        }

        // If converting from base currency, use inverse rate
        if ($fromCurrency === $baseCurrency->code) {
            $exchangeRate = ExchangeRate::forCurrency($toCurrency)
                ->latest()
                ->first();
            return $exchangeRate ? (1.0 / $exchangeRate->rate_to_base) : 1.0;
        }

        // For cross-currency conversions, convert via base currency
        $fromToBase = $this->getExchangeRate($fromCurrency, $baseCurrency->code);
        $baseToTarget = $this->getExchangeRate($baseCurrency->code, $toCurrency);
        
        return $fromToBase * $baseToTarget;
    }

    /**
     * Initialize default currencies (ZMW as base, with common currencies)
     */
    public function initializeDefaultCurrencies(): void
    {
        $currencies = [
            [
                'code' => 'ZMW',
                'name' => 'Zambian Kwacha',
                'symbol' => 'K',
                'display_name' => 'Zambian Kwacha (ZMW) - K',
                'is_base_currency' => true,
                'is_active' => true,
                'decimal_places' => 2,
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'symbol_position' => 'before'
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'display_name' => 'US Dollar (USD) - $',
                'is_base_currency' => false,
                'is_active' => true,
                'decimal_places' => 2,
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'symbol_position' => 'before'
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'display_name' => 'Euro (EUR) - €',
                'is_base_currency' => false,
                'is_active' => true,
                'decimal_places' => 2,
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'symbol_position' => 'after'
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'display_name' => 'British Pound (GBP) - £',
                'is_base_currency' => false,
                'is_active' => true,
                'decimal_places' => 2,
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'symbol_position' => 'before'
            ]
        ];

        foreach ($currencies as $currencyData) {
            Currency::updateOrCreate(
                ['code' => $currencyData['code']],
                $currencyData
            );
        }

        // Set default exchange rates (rates to base currency ZMW)
        $this->setExchangeRate('USD', 'ZMW', 23.81); // Example: 1 USD = 23.81 ZMW
        $this->setExchangeRate('EUR', 'ZMW', 25.64); // Example: 1 EUR = 25.64 ZMW
        $this->setExchangeRate('GBP', 'ZMW', 30.30); // Example: 1 GBP = 30.30 ZMW
    }
}
