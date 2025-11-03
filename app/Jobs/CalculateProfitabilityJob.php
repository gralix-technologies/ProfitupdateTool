<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\ProfitabilityService;
use App\Services\CacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateProfitabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 2;

    
    public function __construct(
        public string $customerId,
        public array $options = []
    ) {
        $this->onQueue('data-processing');
    }

    
    public function handle(ProfitabilityService $profitabilityService, CacheService $cacheService): void
    {
        try {
            Log::info("Starting profitability calculation for customer: {$this->customerId}");

            $customer = Customer::where('customer_id', $this->customerId)->first();
            
            if (!$customer) {
                Log::warning("Customer not found: {$this->customerId}");
                return;
            }

            $profitabilityData = $profitabilityService->calculateCustomerProfitability($customer);

            $customer->update([
                'profitability' => $profitabilityData['total_profitability'],
                'total_loans_outstanding' => $profitabilityData['total_loans'],
                'total_deposits' => $profitabilityData['total_deposits'],
                'npl_exposure' => $profitabilityData['npl_exposure'] ?? 0
            ]);

            $cacheService->cacheProfitability($this->customerId, $profitabilityData);

            $cacheService->invalidateCustomerCache($this->customerId);

            Log::info("Profitability calculation completed for customer: {$this->customerId}");

        } catch (\Exception $e) {
            Log::error("Profitability calculation failed for customer: {$this->customerId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    
    public function failed(\Throwable $exception): void
    {
        Log::error("Profitability calculation job failed permanently for customer: {$this->customerId}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

    }

    
    public function tags(): array
    {
        return ['profitability', 'customer:' . $this->customerId];
    }
}


