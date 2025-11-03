<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Jobs\ImportJob;
use App\Models\Product;
use App\Services\SampleFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DataIngestionController extends Controller
{
    protected SampleFileService $sampleFileService;

    public function __construct(SampleFileService $sampleFileService)
    {
        $this->sampleFileService = $sampleFileService;
    }

    
    public function index()
    {
        $products = Product::select('id', 'name', 'category', 'field_definitions')
            ->orderBy('name')
            ->get();


        $productsWithRequirements = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'field_requirements' => $this->sampleFileService->getFieldRequirements($product)
            ];
        });

        return Inertia::render('DataIngestion/Index', [
            'products' => $productsWithRequirements
        ]);
    }

    
    public function uploadFile(FileUploadRequest $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($request->product_id);
            $file = $request->file('file');
            $mode = $request->input('mode', 'append'); // append or overwrite
            

            $importId = Str::uuid()->toString();
            

            $filePath = $file->store('imports', 'local');
            

            ImportJob::dispatch(
                $filePath,
                $product->id,
                auth()->id(),
                $importId,
                $mode
            );


            $this->initializeProgress($importId, $product->id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'File upload started successfully',
                'import_id' => $importId,
                'product_id' => $product->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 400);
        }
    }

    
    public function getProgress(string $importId): JsonResponse
    {
        $progress = Cache::get("import_progress_{$importId}");

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Import progress not found'
            ], 404);
        }


        // Remove user restriction - allow all users to view import progress
        // if ($progress['user_id'] !== auth()->id()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized access to import progress'
        //     ], 403);
        // }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    
    public function getUserImports(): JsonResponse
    {
        // Show all imports to all users regardless of user
        $imports = [];

        try {



            

            if (config('cache.default') === 'redis' && Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $cacheKeys = Cache::getRedis()->keys("import_progress_*");
                
                foreach ($cacheKeys as $key) {
                    $progress = Cache::get(str_replace(config('cache.prefix') . ':', '', $key));
                    if ($progress && $progress['user_id'] === $userId) {
                        $imports[] = $progress;
                    }
                }
            } else {



                $imports = [];
            }


            usort($imports, function ($a, $b) {
                return strtotime($b['updated_at']) - strtotime($a['updated_at']);
            });

            return response()->json([
                'success' => true,
                'imports' => $imports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve import history',
                'imports' => []
            ], 500);
        }
    }

    
    public function cancelImport(string $importId): JsonResponse
    {
        $progress = Cache::get("import_progress_{$importId}");

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found'
            ], 404);
        }


        // Remove user restriction - allow all users to view import progress
        // if ($progress['user_id'] !== auth()->id()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized access to import'
        //     ], 403);
        // }


        $progress['status'] = 'cancelled';
        $progress['message'] = 'Import cancelled by user';
        $progress['updated_at'] = now()->toISOString();
        
        Cache::put("import_progress_{$importId}", $progress, 86400);

        return response()->json([
            'success' => true,
            'message' => 'Import cancelled successfully'
        ]);
    }

    
    public function validateFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:204800', // 200MB
            'product_id' => 'required|exists:products,id'
        ]);

        $file = $request->file('file');
        $product = Product::findOrFail($request->product_id);

        try {

            if (!in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'])) {
                throw new \InvalidArgumentException('File must be a CSV file');
            }


            $maxSize = 200 * 1024 * 1024; // 200MB in bytes
            if ($file->getSize() > $maxSize) {
                throw new \InvalidArgumentException('File size exceeds 200MB limit');
            }


            $handle = fopen($file->getPathname(), 'r');
            if ($handle === false) {
                throw new \RuntimeException('Unable to read CSV file');
            }

            $headers = fgetcsv($handle);
            fclose($handle);

            if (empty($headers)) {
                throw new \InvalidArgumentException('CSV file appears to be empty');
            }


            $fieldDefinitions = $product->field_definitions ?? [];
            
            // Ensure field_definitions is an array
            if (is_string($fieldDefinitions)) {
                $fieldDefinitions = json_decode($fieldDefinitions, true) ?? [];
            }
            
            $requiredFields = collect($fieldDefinitions)
                ->where('required', true)
                ->pluck('name')
                ->toArray();

            $missingFields = [];
            

            if (!in_array('customer_id', $headers)) {
                $missingFields[] = 'customer_id';
            }


            foreach ($requiredFields as $requiredField) {
                if (!in_array($requiredField, $headers)) {
                    $missingFields[] = $requiredField;
                }
            }

            if (!empty($missingFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields),
                    'missing_fields' => $missingFields
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'File validation passed',
                'headers' => $headers,
                'file_size' => $file->getSize(),
                'file_name' => $file->getClientOriginalName()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    
    public function downloadSampleFile(Request $request): Response
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rows' => 'integer|min:1|max:100'
        ]);

        $product = Product::findOrFail($request->product_id);
        $sampleRows = $request->input('rows', 5);

        try {
            $filePath = $this->sampleFileService->generateSampleFile($product, $sampleRows);
            $fileContent = Storage::disk('local')->get($filePath);
            
            $filename = "sample_{$product->name}_" . now()->format('Y-m-d') . ".csv";
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);


            Storage::disk('local')->delete($filePath);

            return response($fileContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate sample file: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function getFieldRequirements(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $product = Product::findOrFail($request->product_id);
        $requirements = $this->sampleFileService->getFieldRequirements($product);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category
            ],
            'field_requirements' => $requirements
        ]);
    }

    
    public function getFieldDetails(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $product = Product::findOrFail($request->product_id);
        $fieldDefinitions = $product->field_definitions ?? [];

        // Ensure field_definitions is an array
        if (is_string($fieldDefinitions)) {
            $fieldDefinitions = json_decode($fieldDefinitions, true) ?? [];
        }

        $fieldDetails = [];
        foreach ($fieldDefinitions as $field) {
            $fieldDetails[] = [
                'name' => $field['name'],
                'type' => $field['type'] ?? 'Text',
                'required' => $field['required'] ?? false,
                'description' => $this->sampleFileService->getFieldDescription($field),
                'options' => $field['options'] ?? null,
                'constraints' => $this->sampleFileService->getFieldConstraints($field),
                'sample_value' => $this->sampleFileService->generateSampleValue($field, 1)
            ];
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category
            ],
            'fields' => $fieldDetails
        ]);
    }

    
    protected function initializeProgress(string $importId, int $productId, int $userId): void
    {
        $progressData = [
            'import_id' => $importId,
            'product_id' => $productId,
            'user_id' => $userId,
            'percent' => 0,
            'message' => 'Import queued for processing...',
            'status' => 'queued',
            'updated_at' => now()->toISOString(),
            'data' => []
        ];

        Cache::put("import_progress_{$importId}", $progressData, 86400);
    }
}



