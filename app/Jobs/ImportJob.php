<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductData;
use App\Models\Customer;
use App\Services\CsvProcessorService;
use App\Services\ImportResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected int $productId;
    protected int $userId;
    protected string $importId;
    protected string $mode; // 'append' or 'overwrite'

    
    public int $timeout = 3600; // 1 hour

    
    public int $tries = 3;

    
    public function __construct(string $filePath, int $productId, int $userId, string $importId, string $mode = 'append')
    {
        $this->filePath = $filePath;
        $this->productId = $productId;
        $this->userId = $userId;
        $this->importId = $importId;
        $this->mode = $mode;
    }

    
    public function handle(CsvProcessorService $csvProcessor): void
    {
        try {
            $this->updateProgress(0, 'Starting import...');

            $product = Product::findOrFail($this->productId);
            
            if (!Storage::exists($this->filePath)) {
                throw new \Exception('Import file not found');
            }

            $tempFile = $this->createTempUploadedFile();
            
            $this->updateProgress(10, 'Validating file...');

            $result = $csvProcessor->processFile($tempFile, $product);

            if (!$result->isSuccess()) {
                throw new \Exception($result->getMessage());
            }

            $this->updateProgress(30, 'Processing data rows...');

            if ($this->mode === 'overwrite') {
                ProductData::where('product_id', $this->productId)->delete();
                $this->updateProgress(40, 'Cleared existing data...');
            }

            $validRows = $result->getValidRows();
            $totalRows = count($validRows);
            $batchSize = 1000;
            $processed = 0;

            foreach (array_chunk($validRows, $batchSize) as $batch) {
                $dataToInsert = [];
                $createdCustomers = [];
                
                foreach ($batch as $row) {
                    $customerId = $row['customer_id'];
                    
                    // Check if customer exists, create if not
                    $customer = Customer::where('customer_id', $customerId)->first();
                    if (!$customer) {
                        $customer = Customer::create([
                            'customer_id' => $customerId,
                            'name' => "Customer {$customerId} (Auto-created from data import)",
                            'email' => "{$customerId}@imported.example.com",
                            'phone' => '000-000-0000',
                            'address' => 'Address not provided during import',
                            'city' => 'Unknown',
                            'country' => 'Zambia',
                            'date_of_birth' => '1980-01-01',
                            'gender' => 'Unknown',
                            'marital_status' => 'Unknown',
                            'occupation' => 'Unknown',
                            'income_level' => 'Unknown',
                            'credit_score' => 500,
                            'risk_level' => 'Medium',
                            'customer_segment' => 'Imported',
                            'external_id' => \Illuminate\Support\Str::uuid(),
                            'created_by' => $this->userId,
                            'updated_by' => $this->userId,
                        ]);
                        
                        $createdCustomers[] = $customerId;
                        Log::info("Auto-created missing customer during import", [
                            'customer_id' => $customerId,
                            'import_id' => $this->importId,
                            'user_id' => $this->userId
                        ]);
                    }
                    
                    $dataToInsert[] = [
                        'product_id' => $this->productId,
                        'customer_id' => $customer->customer_id,
                        'data' => json_encode($row),
                        'amount' => $row['loan_amount'] ?? $row['outstanding_balance'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                ProductData::insert($dataToInsert);
                $processed += count($batch);
                
                $progressPercent = 40 + (($processed / $totalRows) * 50);
                $message = "Stored {$processed}/{$totalRows} rows...";
                if (!empty($createdCustomers)) {
                    $message .= " (Created " . count($createdCustomers) . " missing customers)";
                }
                $this->updateProgress($progressPercent, $message);
            }

            unlink($tempFile->getPathname());

    // Count total auto-created customers for this import
    $totalAutoCreatedCustomers = Customer::where('name', 'LIKE', '%(Auto-created from data import)%')
        ->count();

            $this->updateProgress(100, 'Import completed successfully', [
                'total_rows' => $totalRows,
                'errors' => $result->getErrors(),
                'error_count' => count($result->getErrors()),
                'auto_created_customers' => $totalAutoCreatedCustomers
            ]);

            Log::info('Import job completed successfully', [
                'import_id' => $this->importId,
                'product_id' => $this->productId,
                'user_id' => $this->userId,
                'total_rows' => $totalRows,
                'error_count' => count($result->getErrors())
            ]);

        } catch (\Exception $e) {
            Log::error('Import job failed', [
                'import_id' => $this->importId,
                'product_id' => $this->productId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateProgress(0, 'Import failed: ' . $e->getMessage(), [], 'failed');
            
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }

            throw $e;
        } finally {
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
        }
    }

    
    public function failed(\Throwable $exception): void
    {
        Log::error('Import job failed permanently', [
            'import_id' => $this->importId,
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);

        $this->updateProgress(0, 'Import failed permanently: ' . $exception->getMessage(), [], 'failed');
    }

    
    protected function updateProgress(float $percent, string $message, array $data = [], string $status = 'processing'): void
    {
        $progressData = [
            'import_id' => $this->importId,
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'percent' => $percent,
            'message' => $message,
            'status' => $status,
            'updated_at' => now()->toISOString(),
            'data' => $data
        ];

        Cache::put("import_progress_{$this->importId}", $progressData, 86400);
    }

    
    protected function createTempUploadedFile(): \Illuminate\Http\UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'import_');
        $content = Storage::get($this->filePath);
        file_put_contents($tempPath, $content);

        return new \Illuminate\Http\UploadedFile(
            $tempPath,
            basename($this->filePath),
            'text/csv',
            null,
            true
        );
    }
}


