<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductDataRequest;
use App\Http\Requests\UpdateProductDataRequest;
use App\Models\ProductData;
use App\Models\Product;
use App\Models\Customer;
use App\Services\DataValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductDataController extends Controller
{
    public function __construct(
        private DataValidationService $validationService
    ) {}

    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $productId = $request->get('product_id');
        $customerId = $request->get('customer_id');
        $status = $request->get('status');

        $query = ProductData::with(['product', 'customer']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $productData = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $productData
            ]);
        }

        return Inertia::render('ProductData/Index', [
            'productData' => $productData,
            'products' => Product::select('id', 'name')->get(),
            'filters' => [
                'product_id' => $productId,
                'customer_id' => $customerId,
                'status' => $status
            ]
        ]);
    }

    
    public function create(Request $request): Response
    {
        $productId = $request->get('product_id');
        $product = $productId ? Product::findOrFail($productId) : null;

        return Inertia::render('ProductData/Create', [
            'products' => Product::select('id', 'name', 'category', 'field_definitions')->get(),
            'customers' => Customer::select('customer_id', 'name')->get(),
            'selectedProduct' => $product
        ]);
    }

    
    public function store(StoreProductDataRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $product = Product::findOrFail($validated['product_id']);
            $validationResult = $this->validationService->validateProductData($validated['data'], $product);
            
            if (!$validationResult->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data validation failed',
                    'errors' => $validationResult->getErrors()
                ], 422);
            }

            $productData = ProductData::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product data created successfully.',
                'data' => $productData->load(['product', 'customer'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product data.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function show(ProductData $productData, Request $request): Response|JsonResponse
    {
        $productData->load(['product', 'customer']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $productData
            ]);
        }

        return Inertia::render('ProductData/Show', [
            'productData' => $productData
        ]);
    }

    
    public function edit(ProductData $productData): Response
    {
        $productData->load(['product', 'customer']);

        return Inertia::render('ProductData/Edit', [
            'productData' => $productData,
            'products' => Product::select('id', 'name', 'category', 'field_definitions')->get(),
            'customers' => Customer::select('customer_id', 'name')->get()
        ]);
    }

    
    public function update(UpdateProductDataRequest $request, ProductData $productData): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $product = $productData->product;
            $validationResult = $this->validationService->validateProductData($validated['data'], $product);
            
            if (!$validationResult->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data validation failed',
                    'errors' => $validationResult->getErrors()
                ], 422);
            }

            $productData->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product data updated successfully.',
                'data' => $productData->load(['product', 'customer'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product data.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(ProductData $productData): JsonResponse
    {
        try {
            $productData->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product data deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product data.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'data' => 'required|array',
            'data.*.customer_id' => 'required|string',
            'data.*.data' => 'required|array',
            'data.*.amount' => 'nullable|numeric',
            'data.*.effective_date' => 'nullable|date',
            'data.*.status' => 'nullable|string'
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $created = [];
            $errors = [];

            foreach ($request->data as $index => $item) {
                try {
                    $validationResult = $this->validationService->validateProductData($item['data'], $product);
                    
                    if (!$validationResult->isValid()) {
                        $errors[] = [
                            'row' => $index + 1,
                            'errors' => $validationResult->getErrors()
                        ];
                        continue;
                    }

                    $productData = ProductData::create([
                        'product_id' => $request->product_id,
                        'customer_id' => $item['customer_id'],
                        'data' => $item['data'],
                        'amount' => $item['amount'] ?? null,
                        'effective_date' => $item['effective_date'] ?? null,
                        'status' => $item['status'] ?? 'active'
                    ]);

                    $created[] = $productData;

                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk operation completed.',
                'created_count' => count($created),
                'error_count' => count($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk data.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function summary(Request $request): JsonResponse
    {
        $productId = $request->get('product_id');
        $customerId = $request->get('customer_id');

        $query = ProductData::query();

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $summary = [
            'total_records' => $query->count(),
            'unique_customers' => $query->distinct('customer_id')->count(),
            'total_amount' => $query->sum('amount'),
            'active_records' => $query->where('status', 'active')->count(),
            'latest_update' => $query->max('updated_at')
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    
    public function validateData(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'data' => 'required|array'
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $validationResult = $this->validationService->validateProductData($request->data, $product);

            return response()->json([
                'success' => true,
                'valid' => $validationResult->isValid(),
                'errors' => $validationResult->getErrors()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 422);
        }
    }
}



