<?php

namespace App\Http\Controllers;

use App\Models\ImportError;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportErrorController extends Controller
{
    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $errorType = $request->get('error_type');
        $productId = $request->get('product_id');
        $importSessionId = $request->get('import_session_id');

        $query = ImportError::with('product');

        if ($search) {
            $query->where('error_message', 'like', "%{$search}%");
        }

        if ($errorType) {
            $query->where('error_type', $errorType);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($importSessionId) {
            $query->where('import_session_id', $importSessionId);
        }

        $importErrors = $query->latest()->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $importErrors
            ]);
        }

        return Inertia::render('ImportErrors/Index', [
            'importErrors' => $importErrors,
            'products' => Product::select('id', 'name')->get(),
            'filters' => [
                'search' => $search,
                'error_type' => $errorType,
                'product_id' => $productId,
                'import_session_id' => $importSessionId
            ]
        ]);
    }

    
    public function show(ImportError $importError, Request $request): Response|JsonResponse
    {
        $importError->load('product');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $importError
            ]);
        }

        return Inertia::render('ImportErrors/Show', [
            'importError' => $importError
        ]);
    }

    
    public function getBySession(string $sessionId): JsonResponse
    {
        try {
            $importErrors = ImportError::where('import_session_id', $sessionId)
                ->with('product')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $importErrors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import errors for session.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getByProduct(Product $product): JsonResponse
    {
        try {
            $importErrors = ImportError::where('product_id', $product->id)
                ->with('product')
                ->latest()
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $importErrors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import errors for product.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function statistics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30));
            $dateTo = $request->get('date_to', now());

            $query = ImportError::whereBetween('created_at', [$dateFrom, $dateTo]);

            $stats = [
                'total_errors' => $query->count(),
                'errors_by_type' => $query->selectRaw('error_type, COUNT(*) as count')
                    ->groupBy('error_type')
                    ->pluck('count', 'error_type'),
                'errors_by_product' => $query->with('product')
                    ->selectRaw('product_id, COUNT(*) as count')
                    ->groupBy('product_id')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->product->name ?? 'Unknown' => $item->count];
                    }),
                'errors_by_session' => $query->selectRaw('import_session_id, COUNT(*) as count')
                    ->groupBy('import_session_id')
                    ->orderByDesc('count')
                    ->take(10)
                    ->get(),
                'recent_errors' => $query->with('product')
                    ->latest()
                    ->take(10)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import error statistics.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getErrorTypes(): JsonResponse
    {
        try {
            $errorTypes = [
                ImportError::TYPE_VALIDATION,
                ImportError::TYPE_PROCESSING,
                ImportError::TYPE_SYSTEM
            ];

            return response()->json([
                'success' => true,
                'data' => $errorTypes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get error types.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getSessions(): JsonResponse
    {
        try {
            $sessions = ImportError::select('import_session_id')
                ->selectRaw('COUNT(*) as error_count')
                ->selectRaw('MIN(created_at) as first_error')
                ->selectRaw('MAX(created_at) as last_error')
                ->groupBy('import_session_id')
                ->orderByDesc('error_count')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sessions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import sessions.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function clearSession(string $sessionId): JsonResponse
    {
        try {
            $deletedCount = ImportError::where('import_session_id', $sessionId)->delete();

            return response()->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} errors for session {$sessionId}."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear session errors.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function clearProduct(Product $product): JsonResponse
    {
        try {
            $deletedCount = ImportError::where('product_id', $product->id)->delete();

            return response()->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} errors for product {$product->name}."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear product errors.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'session_id' => 'nullable|string',
            'product_id' => 'nullable|integer|exists:products,id'
        ]);

        try {
            $query = ImportError::with('product');

            if ($request->session_id) {
                $query->where('import_session_id', $request->session_id);
            }

            if ($request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            $importErrors = $query->latest()->get();

            if ($request->format === 'csv') {
                $csvData = $importErrors->map(function ($error) {
                    return [
                        'id' => $error->id,
                        'import_session_id' => $error->import_session_id,
                        'product' => $error->product->name ?? 'Unknown',
                        'row_number' => $error->row_number,
                        'error_type' => $error->error_type,
                        'error_message' => $error->error_message,
                        'created_at' => $error->created_at->format('Y-m-d H:i:s')
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data' => $csvData,
                    'format' => 'csv'
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => $importErrors,
                    'format' => 'json'
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export import errors.',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}



