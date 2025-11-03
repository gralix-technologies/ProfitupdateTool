<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductData;
use App\Models\ImportError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataStorageService
{
    
    public function storeData(
        array $validRows,
        array $errors,
        Product $product,
        string $mode = 'append',
        ?string $importSessionId = null
    ): DataStorageResult {
        $importSessionId = $importSessionId ?? Str::uuid()->toString();
        
        DB::beginTransaction();
        
        try {
            $storedCount = 0;
            $errorCount = 0;

            if ($mode === 'overwrite') {
                $this->clearExistingData($product);
            }

            foreach ($validRows as $rowData) {
                try {
                    $this->storeProductData($product, $rowData);
                    $storedCount++;
                } catch (\Exception $e) {
                    $this->logError(
                        $importSessionId,
                        $product->id,
                        0, // Row number not available at this point
                        ImportError::TYPE_SYSTEM,
                        "Failed to store data: {$e->getMessage()}",
                        $rowData
                    );
                    $errorCount++;
                }
            }

            $this->logImportErrors($errors, $importSessionId, $product->id);
            $errorCount += count($errors);

            DB::commit();

            return new DataStorageResult(
                true,
                "Successfully stored {$storedCount} records",
                $storedCount,
                $errorCount,
                $importSessionId
            );

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Data storage failed', [
                'product_id' => $product->id,
                'import_session_id' => $importSessionId,
                'error' => $e->getMessage()
            ]);

            return new DataStorageResult(
                false,
                "Data storage failed: {$e->getMessage()}",
                0,
                0,
                $importSessionId
            );
        }
    }

    
    protected function storeProductData(Product $product, array $rowData): ProductData
    {
        $customerId = $rowData['customer_id'] ?? null;
        unset($rowData['customer_id']);

        $amount = $rowData['amount'] ?? null;
        $effectiveDate = $rowData['effective_date'] ?? null;
        $status = $rowData['status'] ?? 'active';

        unset($rowData['amount'], $rowData['effective_date'], $rowData['status']);

        return ProductData::create([
            'product_id' => $product->id,
            'customer_id' => $customerId,
            'data' => $rowData,
            'amount' => $amount,
            'effective_date' => $effectiveDate,
            'status' => $status
        ]);
    }

    
    protected function clearExistingData(Product $product): void
    {
        ProductData::where('product_id', $product->id)->delete();
    }

    
    protected function logImportErrors(array $errors, string $importSessionId, int $productId): void
    {
        foreach ($errors as $error) {
            $rowNumber = $this->extractRowNumber($error);
            $errorType = $this->determineErrorType($error);
            
            $this->logError(
                $importSessionId,
                $productId,
                $rowNumber,
                $errorType,
                $error
            );
        }
    }

    
    protected function logError(
        string $importSessionId,
        int $productId,
        int $rowNumber,
        string $errorType,
        string $errorMessage,
        ?array $rowData = null,
        ?array $context = null
    ): void {
        ImportError::create([
            'import_session_id' => $importSessionId,
            'product_id' => $productId,
            'row_number' => $rowNumber,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'row_data' => $rowData,
            'context' => $context
        ]);
    }

    
    protected function extractRowNumber(string $error): int
    {
        if (preg_match('/Row (\d+):/', $error, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    
    protected function determineErrorType(string $error): string
    {
        $lowerError = strtolower($error);
        
        if (str_contains($lowerError, 'validation') || 
            str_contains($lowerError, 'required') || 
            str_contains($lowerError, 'invalid') ||
            str_contains($lowerError, 'missing')) {
            return ImportError::TYPE_VALIDATION;
        }
        
        if (str_contains($lowerError, 'processing') || 
            str_contains($lowerError, 'format') ||
            str_contains($lowerError, 'column count') ||
            str_contains($lowerError, 'mismatch')) {
            return ImportError::TYPE_PROCESSING;
        }
        
        return ImportError::TYPE_SYSTEM;
    }

    
    public function getImportErrors(string $importSessionId): array
    {
        return ImportError::forSession($importSessionId)
            ->with('product')
            ->orderBy('row_number')
            ->get()
            ->map(function ($error) {
                return [
                    'id' => $error->id,
                    'row_number' => $error->row_number,
                    'error_type' => $error->error_type,
                    'error_message' => $error->error_message,
                    'formatted_message' => $error->getFormattedMessage(),
                    'row_data' => $error->row_data,
                    'context' => $error->context,
                    'created_at' => $error->created_at
                ];
            })
            ->toArray();
    }

    
    public function getErrorSummary(string $importSessionId): array
    {
        $errors = ImportError::forSession($importSessionId)->get();
        
        return [
            'total_errors' => $errors->count(),
            'validation_errors' => $errors->where('error_type', ImportError::TYPE_VALIDATION)->count(),
            'processing_errors' => $errors->where('error_type', ImportError::TYPE_PROCESSING)->count(),
            'system_errors' => $errors->where('error_type', ImportError::TYPE_SYSTEM)->count(),
            'error_types' => $errors->groupBy('error_type')->map->count()->toArray()
        ];
    }

    
    protected function ensureCustomerExists(string $customerId): void
    {
    }
}


