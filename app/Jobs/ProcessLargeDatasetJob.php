<?php

namespace App\Jobs;

use App\Services\PaginationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class ProcessLargeDatasetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes
    public $tries = 2;
    public $maxExceptions = 1;

    
    public function __construct(
        public string $modelClass,
        public array $queryConditions = [],
        public string $processingMethod = 'process',
        public array $processingOptions = [],
        public int $chunkSize = 1000
    ) {
        $this->onQueue('data-processing');
    }

    
    public function handle(PaginationService $paginationService): void
    {
        try {
            Log::info("Starting large dataset processing", [
                'model' => $this->modelClass,
                'conditions' => $this->queryConditions,
                'chunk_size' => $this->chunkSize
            ]);

            $query = $this->buildQuery();
            
            $totalRecords = $query->count();
            Log::info("Processing {$totalRecords} records in chunks of {$this->chunkSize}");

            $result = $paginationService->processInChunks(
                $query,
                [$this, 'processChunk'],
                $this->chunkSize
            );

            Log::info("Large dataset processing completed", [
                'processed' => $result['processed'],
                'errors' => count($result['errors']),
                'total_records' => $totalRecords
            ]);

            if (!empty($result['errors'])) {
                Log::warning("Some chunks failed during processing", [
                    'errors' => $result['errors']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Large dataset processing failed", [
                'model' => $this->modelClass,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    
    public function processChunk($items): void
    {
        $method = $this->processingMethod;
        
        foreach ($items as $item) {
            try {
                if (method_exists($this, $method)) {
                    $this->$method($item);
                } elseif (method_exists($item, $method)) {
                    $item->$method($this->processingOptions);
                } else {
                    Log::debug("Processing item", ['id' => $item->id ?? 'unknown']);
                }
            } catch (\Exception $e) {
                Log::error("Failed to process item", [
                    'item_id' => $item->id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                
                continue;
            }
        }
    }

    
    public function process($item): void
    {
        Log::debug("Default processing for item", ['id' => $item->id ?? 'unknown']);
    }

    
    private function buildQuery(): Builder
    {
        if (!class_exists($this->modelClass)) {
            throw new \InvalidArgumentException("Model class does not exist: {$this->modelClass}");
        }

        $query = $this->modelClass::query();

        foreach ($this->queryConditions as $condition) {
            if (is_array($condition) && count($condition) >= 2) {
                $method = $condition[0] ?? 'where';
                $parameters = array_slice($condition, 1);
                
                $query = $query->$method(...$parameters);
            }
        }

        return $query;
    }

    
    public function failed(\Throwable $exception): void
    {
        Log::error("Large dataset processing job failed permanently", [
            'model' => $this->modelClass,
            'conditions' => $this->queryConditions,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    
    public function tags(): array
    {
        return ['large-dataset', 'processing', class_basename($this->modelClass)];
    }
}


