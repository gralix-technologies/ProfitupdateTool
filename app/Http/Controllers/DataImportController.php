<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class DataImportController extends Controller
{
    public function index(): Response
    {
        $products = Product::select('id', 'name', 'category', 'field_definitions')
            ->orderBy('name')
            ->get();
            
        $templates = [
            [
                'id' => 1,
                'name' => 'Customer Data Template',
                'description' => 'Template for importing customer information',
                'download_url' => '/templates/customer_data_template.csv'
            ],
            [
                'id' => 2,
                'name' => 'Product Data Template',
                'description' => 'Template for importing product-specific data',
                'download_url' => '/templates/product_data_template.csv'
            ]
        ];

        return Inertia::render('DataImport', [
            'products' => $products,
            'templates' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'field_mapping' => 'required|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $product = Product::findOrFail($request->product_id);
            $csvFile = $request->file('csv_file');
            $fieldMapping = $request->field_mapping;

            // Read and process CSV file
            $csvData = $this->processCsvFile($csvFile, $fieldMapping, $product);
            
            // Store data in database
            $importedCount = $this->storeProductData($csvData, $product->id);

            return redirect()->route('data-import')
                ->with('success', "Successfully imported {$importedCount} records for {$product->name}");

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    private function processCsvFile($file, $fieldMapping, $product)
    {
        $csvData = [];
        $handle = fopen($file->getPathname(), 'r');
        
        if (!$handle) {
            throw new \Exception('Could not read CSV file');
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new \Exception('CSV file is empty or invalid');
        }

        // Process data rows
        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            
            foreach ($row as $index => $value) {
                if (isset($headers[$index])) {
                    $csvField = $headers[$index];
                    if (isset($fieldMapping[$csvField])) {
                        $productField = $fieldMapping[$csvField];
                        $data[$productField] = $this->formatValue($value, $product->field_definitions, $productField);
                    }
                }
            }
            
            if (!empty($data)) {
                $csvData[] = $data;
            }
        }

        fclose($handle);
        return $csvData;
    }

    private function formatValue($value, $fieldDefinitions, $fieldName)
    {
        // Find field definition
        $fieldDef = collect($fieldDefinitions)->firstWhere('name', $fieldName);
        
        if (!$fieldDef) {
            return $value;
        }

        // Format based on field type
        switch ($fieldDef['type']) {
            case 'number':
            case 'currency':
                return is_numeric($value) ? (float) $value : 0;
            case 'integer':
                return is_numeric($value) ? (int) $value : 0;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'date':
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            default:
                return trim($value);
        }
    }

    private function storeProductData($csvData, $productId)
    {
        $importedCount = 0;
        
        foreach ($csvData as $data) {
            // Add metadata
            $data['imported_at'] = now();
            $data['product_id'] = $productId;
            
            // Create or update product data
            ProductData::updateOrCreate(
                [
                    'product_id' => $productId,
                    'customer_id' => $data['customer_id'] ?? null
                ],
                [
                    'data' => $data,
                    'updated_at' => now()
                ]
            );
            
            $importedCount++;
        }
        
        return $importedCount;
    }
}
