<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get the base currency configuration
     */
    public function getBaseCurrency(): JsonResponse
    {
        try {
            $baseCurrency = $this->currencyService->getBaseCurrency();
            
            if (!$baseCurrency) {
                return response()->json([
                    'success' => false,
                    'message' => 'No base currency configured'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $baseCurrency->code,
                    'name' => $baseCurrency->name,
                    'symbol' => $baseCurrency->symbol,
                    'display_name' => $baseCurrency->display_name,
                    'decimal_places' => $baseCurrency->decimal_places,
                    'thousands_separator' => $baseCurrency->thousands_separator,
                    'decimal_separator' => $baseCurrency->decimal_separator,
                    'symbol_position' => $baseCurrency->symbol_position
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get currency configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available currencies
     */
    public function getAvailableCurrencies(): JsonResponse
    {
        try {
            $currencies = $this->currencyService->getAvailableCurrencies();
            
            return response()->json([
                'success' => true,
                'data' => $currencies->map(function ($currency) {
                    return [
                        'code' => $currency->code,
                        'name' => $currency->name,
                        'symbol' => $currency->symbol,
                        'display_name' => $currency->display_name,
                        'is_base_currency' => $currency->is_base_currency
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get currencies: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format an amount using the base currency
     */
    public function formatAmount(float $amount): JsonResponse
    {
        try {
            $formattedAmount = $this->currencyService->formatAmount($amount);
            $currencyCode = $this->currencyService->getCurrencyCode();
            $currencySymbol = $this->currencyService->getCurrencySymbol();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => $amount,
                    'formatted_amount' => $formattedAmount,
                    'currency_code' => $currencyCode,
                    'currency_symbol' => $currencySymbol
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to format amount: ' . $e->getMessage()
            ], 500);
        }
    }
}
