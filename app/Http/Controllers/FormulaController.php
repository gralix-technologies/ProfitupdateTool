<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormulaRequest;
use App\Http\Requests\UpdateFormulaRequest;
use App\Http\Requests\TestFormulaRequest;
use App\Models\Formula;
use App\Models\Product;
use App\Services\FormulaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormulaController extends Controller
{
    private FormulaService $formulaService;

    public function __construct(FormulaService $formulaService)
    {
        $this->formulaService = $formulaService;
    }

    
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'product_id', 'return_type', 'is_active']);
        $formulas = $this->formulaService->getPaginatedFormulas(15, $filters);

        return Inertia::render('Formulas/Index', [
            'formulas' => $formulas,
            'filters' => $filters,
            'products' => Product::select('id', 'name')->get()
        ]);
    }

    
    public function create(): Response
    {
        $formulaService = app(\App\Services\FormulaService::class);
        
        return Inertia::render('Formulas/Create', [
            'products' => Product::select('id', 'name', 'category', 'field_definitions')->get(),
            'returnTypes' => Formula::getReturnTypes(),
            'supportedOperations' => app(\App\Services\FormulaEngine::class)->getSupportedOperations(),
            'templates' => $formulaService->getFormulaTemplates(),
            'fieldSuggestions' => $formulaService->getFieldSuggestions(),
            'functionDocumentation' => $formulaService->getFunctionDocumentation()
        ]);
    }

    
    public function store(StoreFormulaRequest $request)
    {
        try {
            \Log::info('Formula creation started', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'data' => $request->validated()
            ]);

            $data = $request->validated();
            $data['created_by'] = auth()->id();

            $formula = $this->formulaService->createFormula($data);

            \Log::info('Formula created successfully', [
                'formula_id' => $formula->id,
                'formula_name' => $formula->name
            ]);

            // Check if this is an API request
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Formula created successfully',
                    'formula' => $formula->load(['product', 'creator'])
                ], 201);
            }

            // For web requests, return Inertia response
            return redirect()->route('formulas.index')
                ->with('success', 'Formula created successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Formula creation validation failed', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);

            // Check if this is an API request
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Formula creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            // Check if this is an API request
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create formula.',
                    'error' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null
                ], 422);
            }

            // For web requests, return redirect with error
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create formula: ' . $e->getMessage()]);
        }
    }

    
    public function show(Formula $formula): Response
    {
        $formula->load(['product', 'creator']);
        
        return Inertia::render('Formulas/Show', [
            'formula' => $formula,
            'usageStats' => $this->formulaService->getUsageStatistics($formula)
        ]);
    }

    
    public function edit(Formula $formula): Response
    {
        $formula->load(['product', 'creator']);
        $formulaService = app(\App\Services\FormulaService::class);

        return Inertia::render('Formulas/Edit', [
            'formula' => $formula,
            'products' => Product::select('id', 'name', 'category', 'field_definitions')->get(),
            'returnTypes' => Formula::getReturnTypes(),
            'supportedOperations' => app(\App\Services\FormulaEngine::class)->getSupportedOperations(),
            'templates' => $formulaService->getFormulaTemplates(),
            'fieldSuggestions' => $formulaService->getFieldSuggestions($formula->product),
            'functionDocumentation' => $formulaService->getFunctionDocumentation()
        ]);
    }

    /**
     * Get field suggestions for a specific product
     */
    public function getProductFields(Product $product): \Illuminate\Http\JsonResponse
    {
        $formulaService = app(\App\Services\FormulaService::class);
        
        return response()->json([
            'fields' => $formulaService->getFieldSuggestions($product),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'field_definitions' => $product->field_definitions
            ]
        ]);
    }

    
    public function update(UpdateFormulaRequest $request, Formula $formula): JsonResponse
    {
        try {
            $data = $request->validated();
            $updatedFormula = $this->formulaService->updateFormula($formula, $data);

            return response()->json([
                'success' => true,
                'message' => 'Formula updated successfully',
                'formula' => $updatedFormula->load(['product', 'creator'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update formula: ' . $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(Formula $formula): JsonResponse
    {
        try {
            $this->formulaService->deleteFormula($formula);

            return response()->json([
                'success' => true,
                'message' => 'Formula deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete formula: ' . $e->getMessage()
            ], 422);
        }
    }

    
    public function test(TestFormulaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;

        $result = $this->formulaService->testFormula(
            $data['expression'],
            $data['sample_data'] ?? [],
            $product
        );

        return response()->json($result);
    }

    
    public function duplicate(Formula $formula, Request $request): JsonResponse
    {
        try {
            $overrides = $request->only(['name', 'description']);
            $overrides['created_by'] = auth()->id();

            $duplicate = $this->formulaService->duplicateFormula($formula, $overrides);

            return response()->json([
                'success' => true,
                'message' => 'Formula duplicated successfully',
                'formula' => $duplicate->load(['product', 'creator'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate formula: ' . $e->getMessage()
            ], 422);
        }
    }

    
    public function byProduct(Product $product): JsonResponse
    {
        $formulas = $this->formulaService->getFormulasForProduct($product);

        return response()->json([
            'formulas' => $formulas->load(['creator'])
        ]);
    }

    
    public function global(): JsonResponse
    {
        $formulas = $this->formulaService->getGlobalFormulas();

        return response()->json([
            'formulas' => $formulas->load(['creator'])
        ]);
    }

    
    public function export(Formula $formula): JsonResponse
    {
        $exportData = $this->formulaService->exportFormula($formula);

        return response()->json([
            'success' => true,
            'data' => $exportData
        ]);
    }

    
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'formula_data' => 'required|array',
            'formula_data.name' => 'required|string|max:255',
            'formula_data.expression' => 'required|string',
            'formula_data.description' => 'nullable|string',
            'formula_data.return_type' => 'nullable|string|in:numeric,text,boolean,date',
            'product_id' => 'nullable|exists:products,id'
        ]);

        try {
            $formula = $this->formulaService->importFormula(
                $request->input('formula_data'),
                $request->input('product_id'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Formula imported successfully',
                'formula' => $formula->load(['product', 'creator'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import formula: ' . $e->getMessage()
            ], 422);
        }
    }

    
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'fields' => 'required|array',
            'fields.*' => 'string'
        ]);

        $suggestions = $this->formulaService->getFormulaSuggestions($request->input('fields'));

        return response()->json([
            'suggestions' => $suggestions->load(['product', 'creator'])
        ]);
    }

    
    public function templates(): JsonResponse
    {
        $templates = $this->formulaService->getFormulaTemplates();
        
        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Get product-specific examples
     */
    public function productExamples(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string'
        ]);

        $examples = $this->formulaService->getProductExamples($request->input('category'));
        
        return response()->json([
            'success' => true,
            'examples' => $examples
        ]);
    }

    /**
     * Get field suggestions
     */
    public function fieldSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id'
        ]);

        $product = $request->input('product_id') ? Product::find($request->input('product_id')) : null;
        $suggestions = $this->formulaService->getFieldSuggestions($product);
        
        return response()->json([
            'success' => true,
            'fields' => $suggestions
        ]);
    }
}


