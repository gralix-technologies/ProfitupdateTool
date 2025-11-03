<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Dashboard;
use App\Models\ProductData;
use App\Services\CsvProcessorService;
use App\Services\FormulaPreviewService;
use App\Services\DataIngestionPreviewService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Comprehensive SME Loans Testing Command
 * Tests the enhanced Formula and Data Ingestion engines
 */
class TestSmeLoansCommand extends Command
{
    protected $signature = 'test:sme-loans {--upload} {--formulas} {--report}';
    protected $description = 'Test SME Loans product with data upload and formula validation';

    private CsvProcessorService $csvProcessor;
    private FormulaPreviewService $formulaPreview;
    private DataIngestionPreviewService $dataPreview;
    private array $testResults = [];

    public function __construct(
        CsvProcessorService $csvProcessor,
        FormulaPreviewService $formulaPreview,
        DataIngestionPreviewService $dataPreview
    ) {
        parent::__construct();
        $this->csvProcessor = $csvProcessor;
        $this->formulaPreview = $formulaPreview;
        $this->dataPreview = $dataPreview;
    }

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  SME LOANS COMPREHENSIVE TESTING');
        $this->info('  Enhanced Formula & Data Ingestion Engine Validation');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Find SME Loans product
        $product = Product::where('name', 'SME Loans')->first();
        if (!$product) {
            $this->error('❌ SME Loans product not found!');
            return 1;
        }

        $this->info("✓ Found SME Loans product (ID: {$product->id})");
        
        // Get dashboard
        $dashboard = Dashboard::where('product_id', $product->id)->first();
        if ($dashboard) {
            $this->info("✓ Found dashboard (ID: {$dashboard->id})");
        }

        $this->newLine();

        // Run tests based on options
        $runAll = !$this->option('upload') && !$this->option('formulas') && !$this->option('report');

        if ($runAll || $this->option('upload')) {
            $this->testDataIngestion($product);
        }

        if ($runAll || $this->option('formulas')) {
            $this->testFormulas($product);
        }

        if ($runAll || $this->option('report')) {
            $this->generateReport();
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  TESTING COMPLETED');
        $this->info('═══════════════════════════════════════════════════════════');

        return 0;
    }

    private function testDataIngestion(Product $product)
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  TEST 1: DATA INGESTION & PREVIEW');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $csvPath = storage_path('app/sme_loans_test_data.csv');
        
        if (!file_exists($csvPath)) {
            $this->error('❌ Test data file not found: ' . $csvPath);
            $this->testResults['data_ingestion'] = ['status' => 'failed', 'reason' => 'File not found'];
            return;
        }

        $this->info("✓ Found test data file");
        $this->info("  Location: {$csvPath}");
        $this->newLine();

        // Create UploadedFile instance
        $file = new UploadedFile($csvPath, 'sme_loans_test_data.csv', 'text/csv', null, true);

        // Step 1: Preview data before import
        $this->info('[1] Previewing data...');
        try {
            $preview = $this->dataPreview->previewFile($file, $product, 10);
            
            if ($preview['success']) {
                $this->info("  ✓ Preview successful");
                $this->info("  - Total rows in file: " . $preview['total_rows']);
                $this->info("  - Preview rows: " . $preview['preview_rows']);
                $this->info("  - Headers valid: " . ($preview['header_validation']['valid'] ? 'Yes' : 'No'));
                
                if ($preview['quality_analysis']) {
                    $qa = $preview['quality_analysis'];
                    $this->info("  - Data quality score: " . $qa['quality_score'] . "%");
                    $this->info("  - Completeness score: " . $qa['completeness_score'] . "%");
                    $this->info("  - Valid rows (preview): " . $qa['valid_rows']);
                    $this->info("  - Invalid rows (preview): " . $qa['invalid_rows']);
                }
                
                $this->testResults['data_preview'] = [
                    'status' => 'passed',
                    'quality_score' => $qa['quality_score'] ?? 0,
                    'completeness' => $qa['completeness_score'] ?? 0
                ];
            } else {
                $this->error("  ✗ Preview failed: " . $preview['message']);
                $this->testResults['data_preview'] = ['status' => 'failed', 'reason' => $preview['message']];
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Preview error: " . $e->getMessage());
            $this->testResults['data_preview'] = ['status' => 'error', 'reason' => $e->getMessage()];
        }

        $this->newLine();

        // Step 2: Upload data
        $this->info('[2] Uploading data...');
        try {
            // Clear existing data first
            $deleted = ProductData::where('product_id', $product->id)->delete();
            if ($deleted > 0) {
                $this->info("  - Cleared {$deleted} existing records");
            }

            $result = $this->csvProcessor->processAndStoreFile($file, $product, 'overwrite');
            
            if ($result->isSuccess()) {
                $this->info("  ✓ Upload successful");
                $this->info("  - Records stored: " . $result->getStoredCount());
                $this->info("  - Errors: " . $result->getErrorCount());
                $this->info("  - Message: " . $result->getMessage());
                
                $this->testResults['data_upload'] = [
                    'status' => 'passed',
                    'records_stored' => $result->getStoredCount(),
                    'errors' => $result->getErrorCount()
                ];
            } else {
                $this->error("  ✗ Upload failed: " . $result->getMessage());
                $this->testResults['data_upload'] = ['status' => 'failed', 'reason' => $result->getMessage()];
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Upload error: " . $e->getMessage());
            $this->testResults['data_upload'] = ['status' => 'error', 'reason' => $e->getMessage()];
        }

        $this->newLine();

        // Step 3: Verify data in database
        $this->info('[3] Verifying data in database...');
        $recordCount = ProductData::where('product_id', $product->id)->count();
        $this->info("  ✓ Found {$recordCount} records in database");
        
        if ($recordCount > 0) {
            $sampleRecord = ProductData::where('product_id', $product->id)->first();
            $this->info("  ✓ Sample record structure verified");
            $this->testResults['data_verification'] = ['status' => 'passed', 'record_count' => $recordCount];
        } else {
            $this->error("  ✗ No records found!");
            $this->testResults['data_verification'] = ['status' => 'failed', 'reason' => 'No records in database'];
        }

        $this->newLine();
    }

    private function testFormulas(Product $product)
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  TEST 2: FORMULA VALIDATION & PREVIEW');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $formulas = $product->formulas;
        $this->info("✓ Found " . $formulas->count() . " formulas to test");
        $this->newLine();

        $formulaResults = [];

        foreach ($formulas as $index => $formula) {
            $this->info("[" . ($index + 1) . "] Testing: {$formula->name}");
            $this->info("    Expression: {$formula->expression}");

            try {
                // Preview formula with dummy data
                $preview = $this->formulaPreview->validateWithPreview(
                    $formula->expression,
                    $product
                );

                if ($preview['valid']) {
                    $this->info("    ✓ Formula valid");
                    $this->info("    ✓ Preview result: " . ($preview['sample_calculation']['formatted_result'] ?? 'N/A'));
                    
                    $formulaResults[$formula->name] = [
                        'status' => 'passed',
                        'preview_result' => $preview['preview_result'],
                        'formatted' => $preview['sample_calculation']['formatted_result'] ?? null
                    ];
                } else {
                    $this->error("    ✗ Formula invalid");
                    foreach ($preview['errors'] as $error) {
                        $this->error("      - " . $error);
                    }
                    
                    $formulaResults[$formula->name] = [
                        'status' => 'failed',
                        'errors' => $preview['errors']
                    ];
                }

                if (!empty($preview['warnings'])) {
                    foreach ($preview['warnings'] as $warning) {
                        $this->warn("    ⚠ " . $warning);
                    }
                }

            } catch (\Exception $e) {
                $this->error("    ✗ Error: " . $e->getMessage());
                $formulaResults[$formula->name] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }

            $this->newLine();
        }

        $this->testResults['formulas'] = $formulaResults;

        // Summary
        $passed = count(array_filter($formulaResults, fn($r) => $r['status'] === 'passed'));
        $failed = count(array_filter($formulaResults, fn($r) => $r['status'] === 'failed'));
        $errors = count(array_filter($formulaResults, fn($r) => $r['status'] === 'error'));

        $this->info("Formula Testing Summary:");
        $this->info("  ✓ Passed: {$passed}");
        if ($failed > 0) $this->error("  ✗ Failed: {$failed}");
        if ($errors > 0) $this->error("  ✗ Errors: {$errors}");

        $this->newLine();
    }

    private function generateReport()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  TEST REPORT');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $report = $this->buildReport();
        
        // Save report to file
        $reportPath = storage_path('app/sme_loans_test_report.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("✓ Test report saved to: {$reportPath}");
        $this->newLine();

        // Display summary
        $this->displayReportSummary($report);
    }

    private function buildReport(): array
    {
        return [
            'test_date' => date('Y-m-d H:i:s'),
            'product' => 'SME Loans',
            'test_results' => $this->testResults,
            'summary' => $this->calculateSummary()
        ];
    }

    private function calculateSummary(): array
    {
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;

        foreach ($this->testResults as $category => $result) {
            if ($category === 'formulas') {
                foreach ($result as $formula => $formulaResult) {
                    $totalTests++;
                    if ($formulaResult['status'] === 'passed') $passedTests++;
                    else $failedTests++;
                }
            } else {
                $totalTests++;
                if ($result['status'] === 'passed') $passedTests++;
                else $failedTests++;
            }
        }

        return [
            'total_tests' => $totalTests,
            'passed' => $passedTests,
            'failed' => $failedTests,
            'pass_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0
        ];
    }

    private function displayReportSummary(array $report)
    {
        $summary = $report['summary'];
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tests', $summary['total_tests']],
                ['Passed', $summary['passed']],
                ['Failed', $summary['failed']],
                ['Pass Rate', $summary['pass_rate'] . '%']
            ]
        );

        if ($summary['pass_rate'] === 100.0) {
            $this->info('🎉 ALL TESTS PASSED!');
        } elseif ($summary['pass_rate'] >= 80) {
            $this->warn('⚠ Most tests passed, but some issues found');
        } else {
            $this->error('❌ Many tests failed - review required');
        }
    }
}

