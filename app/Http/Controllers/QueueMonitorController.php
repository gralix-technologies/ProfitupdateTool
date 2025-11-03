<?php

namespace App\Http\Controllers;

use App\Services\QueueMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class QueueMonitorController extends Controller
{
    public function __construct(
        private QueueMonitoringService $queueMonitoringService
    ) {}

    
    public function index(): Response
    {
        $stats = $this->queueMonitoringService->getQueueStats();
        $health = $this->queueMonitoringService->getQueueHealth();
        $performance = $this->queueMonitoringService->getPerformanceMetrics();

        return Inertia::render('QueueMonitor/Index', [
            'stats' => $stats,
            'health' => $health,
            'performance' => $performance
        ]);
    }

    
    public function stats(): JsonResponse
    {
        $stats = $this->queueMonitoringService->getQueueStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    
    public function health(): JsonResponse
    {
        $health = $this->queueMonitoringService->getQueueHealth();
        
        return response()->json([
            'success' => true,
            'data' => $health
        ]);
    }

    
    public function throughput(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);
        $throughput = $this->queueMonitoringService->getJobThroughput($hours);
        
        return response()->json([
            'success' => true,
            'data' => $throughput
        ]);
    }

    
    public function failedJobs(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $failures = $this->queueMonitoringService->getRecentFailures($limit);
        
        return response()->json([
            'success' => true,
            'data' => $failures
        ]);
    }

    
    public function retryJob(Request $request): JsonResponse
    {
        $request->validate([
            'job_id' => 'required|string'
        ]);

        $success = $this->queueMonitoringService->retryFailedJob($request->job_id);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Job retried successfully' : 'Failed to retry job'
        ]);
    }

    
    public function retryAllJobs(): JsonResponse
    {
        $retried = $this->queueMonitoringService->retryAllFailedJobs();
        
        return response()->json([
            'success' => true,
            'message' => "Retried {$retried} failed jobs",
            'retried_count' => $retried
        ]);
    }

    
    public function clearFailedJobs(): JsonResponse
    {
        $cleared = $this->queueMonitoringService->clearFailedJobs();
        
        return response()->json([
            'success' => true,
            'message' => "Cleared {$cleared} failed jobs",
            'cleared_count' => $cleared
        ]);
    }

    
    public function performance(): JsonResponse
    {
        $metrics = $this->queueMonitoringService->getPerformanceMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);
    }

    
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days_old' => 'integer|min:1|max:365'
        ]);

        $daysOld = $request->get('days_old', 7);
        $cleaned = $this->queueMonitoringService->cleanupOldJobs($daysOld);
        
        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$cleaned} old jobs",
            'cleaned_count' => $cleaned
        ]);
    }

    
    public function testJob(Request $request): JsonResponse
    {
        $request->validate([
            'job_type' => 'required|in:profitability,dashboard,notification,dataset'
        ]);

        try {
            switch ($request->job_type) {
                case 'profitability':
                    \App\Jobs\CalculateProfitabilityJob::dispatch('TEST_CUSTOMER_001');
                    break;
                    
                case 'dashboard':
                    \App\Jobs\GenerateDashboardDataJob::dispatch(1, ['test' => true]);
                    break;
                    
                case 'notification':
                    \App\Jobs\SendNotificationJob::dispatch(
                        'system',
                        [['email' => 'test@example.com', 'name' => 'Test User']],
                        ['title' => 'Test Notification', 'message' => 'This is a test notification']
                    );
                    break;
                    
                case 'dataset':
                    \App\Jobs\ProcessLargeDatasetJob::dispatch(
                        \App\Models\ProductData::class,
                        [['where', 'status', 'active']],
                        'process',
                        [],
                        100
                    );
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Test job dispatched successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch test job: ' . $e->getMessage()
            ], 500);
        }
    }
}


