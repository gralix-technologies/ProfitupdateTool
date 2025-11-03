<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $category = $request->get('category');

        if ($search) {
            $products = $this->productService->searchProducts($search);
        } elseif ($category) {
            $products = $this->productService->getProductsByCategory($category);
        } else {
            $products = $this->productService->getPaginatedProducts($perPage);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products,
                'categories' => Product::getCategories(),
                'field_types' => Product::getFieldTypes()
            ]);
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => Product::getCategories(),
            'field_types' => Product::getFieldTypes(),
            'filters' => [
                'search' => $search,
                'category' => $category
            ]
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'categories' => Product::getCategories(),
            'field_types' => Product::getFieldTypes()
        ]);
    }

    
    public function store(StoreProductRequest $request)
    {
        try {
            \Log::info('Product creation started', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'data' => $request->validated()
            ]);

            $product = $this->productService->createProduct($request->validated());

            \Log::info('Product created successfully', [
                'product_id' => $product->id,
                'product_name' => $product->name
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully.',
                    'data' => $product
                ], 201);
            }

            return redirect()->route('products.index')
                ->with('success', 'Product created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Product creation validation failed', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);

            if ($request->expectsJson()) {
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
            \Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create product.',
                    'error' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    
    public function show(Product $product, Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $product->load(['productData', 'formulas'])
            ]);
        }

        return Inertia::render('Products/Show', [
            'product' => $product->load(['productData', 'formulas']),
            'categories' => Product::getCategories(),
            'field_types' => Product::getFieldTypes()
        ]);
    }

    
    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => Product::getCategories(),
            'field_types' => Product::getFieldTypes()
        ]);
    }

    
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $updatedProduct = $this->productService->updateProduct($product, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data' => $updatedProduct
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->deleteProduct($product);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Copy/Clone an existing product
     */
    public function copy(Product $product): JsonResponse
    {
        try {
            // Create a copy of the product
            $newProduct = $product->replicate();
            $newProduct->name = $product->name . ' (Copy)';
            $newProduct->is_active = false; // Set to inactive by default
            $newProduct->save();

            return response()->json([
                'success' => true,
                'message' => 'Product copied successfully.',
                'data' => $newProduct,
                'redirect' => route('products.edit', $newProduct)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy product.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function active(Request $request): JsonResponse
    {
        $products = $this->productService->getActiveProducts();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    
    public function validateSchema(Request $request): JsonResponse
    {
        try {
            $isValid = $this->productService->validateProductSchema($request->all());

            return response()->json([
                'success' => true,
                'valid' => $isValid,
                'message' => 'Schema is valid.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Schema validation failed.',
                'errors' => $e->getMessage()
            ], 422);
        }
    }
}


