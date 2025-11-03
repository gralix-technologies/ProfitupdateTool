<?php

namespace App\Console\Commands;

use App\Services\SampleFileService;
use Illuminate\Console\Command;

class CleanupSampleFilesCommand extends Command
{
    
    protected $signature = 'data:cleanup-sample-files {--hours=24 : Number of hours after which files should be deleted}';

    
    protected $description = 'Clean up old sample CSV files';

    
    public function handle(SampleFileService $sampleFileService): int
    {
        $hours = (int) $this->option('hours');
        
        $this->info("Cleaning up sample files older than {$hours} hours...");
        
        $deletedCount = $sampleFileService->cleanupOldFiles($hours);
        
        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old sample files.");
        } else {
            $this->info("No old sample files found to delete.");
        }
        
        return Command::SUCCESS;
    }
}



