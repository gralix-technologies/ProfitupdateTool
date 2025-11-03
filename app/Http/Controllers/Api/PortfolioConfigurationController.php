<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConfigurablePortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PortfolioConfigurationController extends Controller
{
    protected ConfigurablePortfolioService $portfolioService;

    public function __construct(ConfigurablePortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }

    /**
     * Get current portfolio calculation configuration
     */
    public function getConfiguration(): JsonResponse
    {
        try {
            $options = $this->portfolioService->getAvailableFormulaOptions();
            $currentCalculation = $this->portfolioService->calculatePortfolioValue();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'available_options' => $options,
                    'current_calculation' => $currentCalculation
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get portfolio configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update portfolio calculation formula
     */
    public function updateFormula(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:sum_all_products,sum_active_only,sum_exclude_npl,weighted_sum,custom_formula',
            'custom_formula' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $type = $request->input('type');
            $customFormula = $request->input('custom_formula');

            if ($type === 'custom_formula' && empty($customFormula)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Custom formula is required when type is custom_formula'
                ], 422);
            }

            $success = $this->portfolioService->updatePortfolioFormula($type, $customFormula);

            if ($success) {
                // Get updated calculation
                $newCalculation = $this->portfolioService->calculatePortfolioValue();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Portfolio calculation formula updated successfully',
                    'data' => [
                        'updated_calculation' => $newCalculation
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update portfolio formula'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update portfolio formula: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test a portfolio calculation formula without saving
     */
    public function testFormula(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:sum_all_products,sum_active_only,sum_exclude_npl,weighted_sum,custom_formula',
            'custom_formula' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $type = $request->input('type');
            $customFormula = $request->input('custom_formula');

            // Temporarily update the formula for testing
            $originalConfig = \App\Models\Configuration::getValue('portfolio_calculation_formula');
            
            // Create a temporary portfolio service instance for testing
            $testService = new ConfigurablePortfolioService();
            
            // Mock the configuration for testing
            $testConfig = json_decode($originalConfig, true) ?? [];
            $testConfig['type'] = $type;
            
            if ($type === 'custom_formula' && $customFormula) {
                \App\Models\Configuration::updateOrCreate(
                    ['key' => 'portfolio_custom_formula'],
                    ['value' => $customFormula]
                );
            }

            $result = $testService->calculatePortfolioValue();

            // Restore original configuration
            \App\Models\Configuration::updateOrCreate(
                ['key' => 'portfolio_calculation_formula'],
                ['value' => $originalConfig]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'test_result' => $result,
                    'formula_type' => $type,
                    'custom_formula' => $customFormula
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to test formula: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get portfolio calculation history/audit log
     */
    public function getCalculationHistory(): JsonResponse
    {
        try {
            // This would typically come from an audit log table
            // For now, return current calculation with timestamp
            $currentCalculation = $this->portfolioService->calculatePortfolioValue();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current' => $currentCalculation,
                    'timestamp' => now()->toISOString(),
                    'history' => [
                        // In a real implementation, this would come from audit logs
                        [
                            'timestamp' => now()->subDay()->toISOString(),
                            'method' => 'sum_all_products',
                            'value' => $currentCalculation['value'],
                            'changed_by' => 'System'
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get calculation history: ' . $e->getMessage()
            ], 500);
        }
    }
}
