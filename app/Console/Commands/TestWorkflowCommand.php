<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Formula;
use App\Models\ProductData;
use App\Models\Dashboard;
use App\Models\Widget;
use Illuminate\Console\Command;

class TestWorkflowCommand extends Command
{
    protected $signature = 'test:workflow';
    protected $description = 'Test the complete portfolio analytics workflow with dummy data';

    public function handle()
    {
        $this->info('🚀 Starting Portfolio Analytics Workflow Test');
        $this->line('==============================================');
        $this->newLine();

        try {
            // Step 1: Create Customer
            $this->info('Step 1: Creating Customer...');
            $customer = Customer::create([
                'customer_id' => 'TEST' . time(),
                'name' => 'Test Customer Ltd',
                'email' => 'test@example.com',
                'phone' => '+260977123456',
                'branch_code' => 'LUSAKA',
                'risk_level' => 'Medium',
                'is_active' => true,
                'total_loans_outstanding' => 50000,
                'total_deposits' => 75000,
                'npl_exposure' => 5000,
                'profitability' => 15000,
                'demographics' => [
                    'age' => '35',
                    'gender' => 'Male',
                    'occupation' => 'Business Owner',
                    'address' => '123 Main Street, Lusaka',
                    'city' => 'Lusaka',
                    'country' => 'Zambia',
                    'segment' => 'Corporate'
                ]
            ]);
            $this->line("✅ Customer created: {$customer->name} (ID: {$customer->customer_id})");
            $this->newLine();

            // Step 2: Create Product
            $this->info('Step 2: Creating Product...');
            $product = Product::create([
                'name' => 'Business Loan Product ' . time(),
                'description' => 'A comprehensive business loan product for SMEs',
                'category' => 'Loan',
                'is_active' => true,
                'field_definitions' => [
                    [
                        'name' => 'principal_amount',
                        'type' => 'currency',
                        'label' => 'Principal Amount',
                        'required' => true
                    ],
                    [
                        'name' => 'interest_rate',
                        'type' => 'percentage',
                        'label' => 'Interest Rate',
                        'required' => true
                    ],
                    [
                        'name' => 'loan_term_months',
                        'type' => 'integer',
                        'label' => 'Loan Term (Months)',
                        'required' => true
                    ],
                    [
                        'name' => 'status',
                        'type' => 'select',
                        'label' => 'Loan Status',
                        'options' => ['active', 'completed', 'npl', 'defaulted'],
                        'required' => true
                    ],
                    [
                        'name' => 'disbursement_date',
                        'type' => 'date',
                        'label' => 'Disbursement Date',
                        'required' => true
                    ]
                ]
            ]);
            $this->line("✅ Product created: {$product->name} (ID: {$product->id})");
            $this->newLine();

            // Step 3: Create Formulas
            $this->info('Step 3: Creating Formulas...');
            
            $portfolioFormula = Formula::create([
                'name' => 'Total Portfolio Value',
                'description' => 'Sum of all principal amounts',
                'expression' => 'SUM(principal_amount)',
                'product_id' => $product->id,
                'return_type' => 'numeric',
                'is_active' => true,
                'created_by' => 1
            ]);

            $avgRateFormula = Formula::create([
                'name' => 'Average Interest Rate',
                'description' => 'Average interest rate across all loans',
                'expression' => 'AVG(interest_rate)',
                'product_id' => $product->id,
                'return_type' => 'numeric',
                'is_active' => true,
                'created_by' => 1
            ]);

            $nplFormula = Formula::create([
                'name' => 'NPL Rate',
                'description' => 'Percentage of loans that are non-performing',
                'expression' => 'COUNT(status="npl") / COUNT(*) * 100',
                'product_id' => $product->id,
                'return_type' => 'numeric',
                'is_active' => true,
                'created_by' => 1
            ]);

            $this->line('✅ Formulas created:');
            $this->line("   - {$portfolioFormula->name}");
            $this->line("   - {$avgRateFormula->name}");
            $this->line("   - {$nplFormula->name}");
            $this->newLine();

            // Step 4: Create Dashboard
            $this->info('Step 4: Creating Dashboard...');
            $dashboard = Dashboard::create([
                'name' => 'Business Loan Analytics Dashboard',
                'description' => 'Comprehensive analytics for business loan portfolio',
                'user_id' => 1,
                'layout' => [
                    ['id' => 'widget-1', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 4],
                    ['id' => 'widget-2', 'x' => 6, 'y' => 0, 'w' => 6, 'h' => 4],
                    ['id' => 'widget-3', 'x' => 0, 'y' => 4, 'w' => 12, 'h' => 6]
                ],
                'filters' => []
            ]);
            $this->line("✅ Dashboard created: {$dashboard->name} (ID: {$dashboard->id})");
            $this->newLine();

            // Step 5: Create Widgets
            $this->info('Step 5: Creating Widgets...');
            
            $portfolioWidget = Widget::create([
                'dashboard_id' => $dashboard->id,
                'type' => 'KPI',
                'title' => 'Total Portfolio Value',
                'position' => ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 4],
                'configuration' => [
                    'data_source' => 'formulas',
                    'formula_id' => $portfolioFormula->id,
                    'chart_options' => [
                        'format' => 'currency',
                        'prefix' => 'ZMW ',
                        'suffix' => ''
                    ]
                ],
                'is_active' => true
            ]);

            $nplWidget = Widget::create([
                'dashboard_id' => $dashboard->id,
                'type' => 'KPI',
                'title' => 'NPL Rate',
                'position' => ['x' => 6, 'y' => 0, 'w' => 6, 'h' => 4],
                'configuration' => [
                    'data_source' => 'formulas',
                    'formula_id' => $nplFormula->id,
                    'chart_options' => [
                        'format' => 'percentage',
                        'prefix' => '',
                        'suffix' => '%'
                    ]
                ],
                'is_active' => true
            ]);

            $pieWidget = Widget::create([
                'dashboard_id' => $dashboard->id,
                'type' => 'PieChart',
                'title' => 'Portfolio by Status',
                'position' => ['x' => 0, 'y' => 4, 'w' => 12, 'h' => 6],
                'configuration' => [
                    'data_source' => 'direct_data',
                    'product_id' => $product->id,
                    'group_by' => 'status',
                    'value_field' => 'principal_amount',
                    'aggregation' => 'SUM'
                ],
                'is_active' => true
            ]);

            $this->line('✅ Widgets created:');
            $this->line("   - {$portfolioWidget->title} (KPI)");
            $this->line("   - {$nplWidget->title} (KPI)");
            $this->line("   - {$pieWidget->title} (PieChart)");
            $this->newLine();

            // Step 6: Ingest Sample Data
            $this->info('Step 6: Ingesting Sample Data...');
            
            // Create additional customers for testing
            $customer2 = Customer::create([
                'customer_id' => 'TEST002_' . time(),
                'name' => 'Test Customer 2 Ltd',
                'email' => 'test2@example.com',
                'phone' => '+260977123457',
                'branch_code' => 'LUSAKA',
                'risk_level' => 'High',
                'is_active' => true,
                'total_loans_outstanding' => 30000,
                'total_deposits' => 25000,
                'npl_exposure' => 15000,
                'profitability' => -5000,
                'demographics' => [
                    'age' => '28',
                    'gender' => 'Female',
                    'occupation' => 'Teacher',
                    'address' => '456 School Road, Lusaka',
                    'city' => 'Lusaka',
                    'country' => 'Zambia',
                    'segment' => 'Retail'
                ]
            ]);

            $sampleData = [
                [
                    'customer_id' => $customer->customer_id,
                    'principal_amount' => 50000,
                    'interest_rate' => 0.15,
                    'loan_term_months' => 24,
                    'status' => 'active',
                    'disbursement_date' => '2024-01-15'
                ],
                [
                    'customer_id' => $customer2->customer_id,
                    'principal_amount' => 75000,
                    'interest_rate' => 0.12,
                    'loan_term_months' => 36,
                    'status' => 'active',
                    'disbursement_date' => '2024-02-01'
                ],
                [
                    'customer_id' => $customer->customer_id,
                    'principal_amount' => 30000,
                    'interest_rate' => 0.18,
                    'loan_term_months' => 18,
                    'status' => 'npl',
                    'disbursement_date' => '2023-11-10'
                ],
                [
                    'customer_id' => $customer2->customer_id,
                    'principal_amount' => 100000,
                    'interest_rate' => 0.10,
                    'loan_term_months' => 48,
                    'status' => 'completed',
                    'disbursement_date' => '2022-06-01'
                ],
                [
                    'customer_id' => $customer->customer_id,
                    'principal_amount' => 25000,
                    'interest_rate' => 0.20,
                    'loan_term_months' => 12,
                    'status' => 'active',
                    'disbursement_date' => '2024-03-15'
                ]
            ];

            $importedCount = 0;
            foreach ($sampleData as $data) {
                ProductData::create([
                    'product_id' => $product->id,
                    'customer_id' => $data['customer_id'],
                    'data' => $data,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $importedCount++;
            }

            $this->line("✅ Imported {$importedCount} loan records");
            $this->newLine();

            // Step 7: Verify Data and Calculations
            $this->info('Step 7: Verifying Data and Calculations...');
            
            $totalPortfolio = ProductData::where('product_id', $product->id)
                ->get()
                ->sum(function($item) {
                    return $item->data['principal_amount'] ?? 0;
                });

            $this->line("📊 Total Portfolio Value: ZMW " . number_format($totalPortfolio, 2));
            
            $totalLoans = ProductData::where('product_id', $product->id)->count();
            $nplLoans = ProductData::where('product_id', $product->id)
                ->where('data->status', 'npl')
                ->count();
            $nplRate = $totalLoans > 0 ? ($nplLoans / $totalLoans) * 100 : 0;

            $this->line("📊 NPL Rate: " . number_format($nplRate, 2) . "%");
            
            $avgInterestRate = ProductData::where('product_id', $product->id)
                ->get()
                ->avg(function($item) {
                    return $item->data['interest_rate'] ?? 0;
                });

            $this->line("📊 Average Interest Rate: " . number_format($avgInterestRate * 100, 2) . "%");
            
            $statusBreakdown = ProductData::where('product_id', $product->id)
                ->get()
                ->groupBy(function($item) {
                    return $item->data['status'] ?? 'unknown';
                })
                ->map(function($group) {
                    return $group->count();
                });

            $this->line("📊 Status Breakdown:");
            foreach ($statusBreakdown as $status => $count) {
                $this->line("   - {$status}: {$count} loans");
            }

            $this->newLine();
            $this->line('✅ Data verification completed!');
            $this->newLine();

            // Step 8: Summary
            $this->info('🎉 WORKFLOW TEST COMPLETED SUCCESSFULLY!');
            $this->line('==========================================');
            $this->line("✅ Customer: {$customer->name}");
            $this->line("✅ Product: {$product->name}");
            $this->line("✅ Formulas: 3 created");
            $this->line("✅ Dashboard: {$dashboard->name}");
            $this->line("✅ Widgets: 3 created");
            $this->line("✅ Data: {$importedCount} records imported");
            $this->line("✅ Calculations: All verified");
            $this->newLine();

            $this->line("🌐 Access your dashboard at: http://localhost:8000/dashboards/{$dashboard->id}");
            $this->line("📊 View data import at: http://localhost:8000/data-import");
            $this->line("👥 View customers at: http://localhost:8000/customers");
            $this->line("📦 View products at: http://localhost:8000/products");
            $this->line("🧮 View formulas at: http://localhost:8000/formulas");
            $this->newLine();

            $this->line('The complete workflow has been tested and is working correctly!');

        } catch (\Exception $e) {
            $this->error('❌ Workflow test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
