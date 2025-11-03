<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\ProductData;
use App\Models\Formula;
use App\Models\Dashboard;
use App\Models\Widget;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Configuration;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FinalWorkingCapitalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting Final Working Capital Product Seeding...');
        
        // Initialize currency system
        $this->initializeCurrencySystem();
        
        // Get admin user
        $admin = User::where('email', 'admin@gralix.co')->first();
        
        if (!$admin) {
            $this->command->error('Admin user not found. Please run BasicSystemSeeder first.');
            return;
        }
        
        // Create Working Capital Product
        $workingCapitalProduct = $this->createWorkingCapitalProduct();
        
        // Create customers
        $customers = $this->createCustomers();
        
        // Create sample data for Working Capital Product
        $this->createWorkingCapitalData($workingCapitalProduct, $customers);
        
        // Create comprehensive formulas
        $this->createWorkingCapitalFormulas($workingCapitalProduct, $admin);
        
        // Create dashboard and widgets
        $this->createWorkingCapitalDashboard($workingCapitalProduct, $admin);
        
        // Create configurations
        $this->createConfigurations();
        
        $this->command->info('✅ Final Working Capital Product seeding completed successfully!');
    }

    private function initializeCurrencySystem(): void
    {
        $this->command->info('💰 Initializing Currency System...');
        
        $currencyService = app(CurrencyService::class);
        $currencyService->initializeDefaultCurrencies();
        
        $this->command->info('✅ Currency system initialized');
    }

    private function createWorkingCapitalProduct(): Product
    {
        $this->command->info('📊 Creating Working Capital Product...');
        
        $product = Product::firstOrCreate(
            ['name' => 'Working Capital Loans'],
            [
                'description' => 'Short-term loans for business operations and cash flow management',
                'category' => 'Loan',
                'is_active' => true,
                'field_definitions' => [
                    // Basic Loan Information
                    ['name' => 'loan_id', 'type' => 'Text', 'required' => true, 'description' => 'Unique loan identifier'],
                    ['name' => 'customer_id', 'type' => 'Text', 'required' => true, 'description' => 'Customer identifier'],
                    ['name' => 'principal_amount', 'type' => 'Numeric', 'required' => true, 'description' => 'Original principal loan amount'],
                    ['name' => 'outstanding_balance', 'type' => 'Numeric', 'required' => true, 'description' => 'Current outstanding balance'],
                    ['name' => 'interest_rate', 'type' => 'Numeric', 'required' => true, 'description' => 'Annual interest rate (%)'],
                    ['name' => 'term_months', 'type' => 'Numeric', 'required' => true, 'description' => 'Loan term in months'],
                    ['name' => 'disbursement_date', 'type' => 'Date', 'required' => true, 'description' => 'Loan disbursement date'],
                    ['name' => 'maturity_date', 'type' => 'Date', 'required' => true, 'description' => 'Loan maturity date'],
                    ['name' => 'status', 'type' => 'Text', 'required' => true, 'description' => 'Loan status (active, npl, default, repaid)'],
                    
                    // Risk Assessment Fields
                    ['name' => 'days_past_due', 'type' => 'Numeric', 'required' => false, 'description' => 'Days past due'],
                    ['name' => 'probability_default', 'type' => 'Numeric', 'required' => false, 'description' => 'Probability of Default (PD)'],
                    ['name' => 'loss_given_default', 'type' => 'Numeric', 'required' => false, 'description' => 'Loss Given Default (LGD)'],
                    ['name' => 'exposure_at_default', 'type' => 'Numeric', 'required' => false, 'description' => 'Exposure at Default (EAD)'],
                    ['name' => 'expected_credit_loss', 'type' => 'Numeric', 'required' => false, 'description' => 'Expected Credit Loss (ECL)'],
                    ['name' => 'stage', 'type' => 'Text', 'required' => false, 'description' => 'IFRS 9 Stage (1, 2, 3)'],
                    ['name' => 'rating_grade', 'type' => 'Text', 'required' => false, 'description' => 'Credit Rating (AAA, AA, A, BBB, BB, B, CCC, CC, C, D)'],
                    
                    // Financial Performance Fields
                    ['name' => 'repaid_amount', 'type' => 'Numeric', 'required' => false, 'description' => 'Total amount repaid'],
                    ['name' => 'interest_earned', 'type' => 'Numeric', 'required' => false, 'description' => 'Interest income earned'],
                    ['name' => 'loan_loss_provision', 'type' => 'Numeric', 'required' => false, 'description' => 'Loan loss provision amount'],
                    ['name' => 'recovery_amount', 'type' => 'Numeric', 'required' => false, 'description' => 'Recovery amount for defaulted loans'],
                    
                    // Operational Fields
                    ['name' => 'branch_code', 'type' => 'Text', 'required' => false, 'description' => 'Branch code'],
                    ['name' => 'sector', 'type' => 'Text', 'required' => false, 'description' => 'Business sector'],
                    ['name' => 'loan_officer', 'type' => 'Text', 'required' => false, 'description' => 'Assigned loan officer'],
                    ['name' => 'collateral_value', 'type' => 'Numeric', 'required' => false, 'description' => 'Collateral value'],
                    ['name' => 'currency', 'type' => 'Lookup', 'required' => true, 'description' => 'Currency code']
                ],
                'portfolio_value_field' => 'outstanding_balance'
            ]
        );
        
        $this->command->info('✅ Working Capital Product created');
        return $product;
    }

    private function createCustomers(): array
    {
        $this->command->info('👥 Creating Customers...');
        
        $customers = [];
        $branches = ['BR001', 'BR002', 'BR003', 'BR004', 'BR005'];
        $sectors = ['Agriculture', 'Manufacturing', 'Retail', 'Services', 'Technology', 'Healthcare', 'Education', 'Construction'];
        
        // Zambian names
        $firstNames = [
            'John', 'Mary', 'Peter', 'Grace', 'David', 'Ruth', 'James', 'Esther', 'Michael', 'Sarah',
            'Paul', 'Rachel', 'Joseph', 'Hannah', 'Samuel', 'Rebecca', 'Daniel', 'Miriam', 'Andrew', 'Deborah',
            'Simon', 'Elizabeth', 'Mark', 'Naomi', 'Luke', 'Martha', 'Matthew', 'Priscilla', 'Thomas', 'Lydia',
            'Philip', 'Phoebe', 'Bartholomew', 'Dorcas', 'Thaddeus', 'Susanna', 'Stephen', 'Joanna', 'Barnabas', 'Mary Magdalene',
            'Silas', 'Salome', 'Timothy', 'Tabitha', 'Titus', 'Rhoda', 'Philemon', 'Lois', 'Onesimus', 'Eunice'
        ];
        
        $lastNames = [
            'Mwamba', 'Banda', 'Phiri', 'Mwanza', 'Chisenga', 'Kabwe', 'Mukuka', 'Ngoma', 'Sichone', 'Mbewe',
            'Kunda', 'Mwanawasa', 'Chiluba', 'Kaunda', 'Tembo', 'Mumba', 'Kapwepwe', 'Mwanakatwe', 'Mukuka', 'Mubanga',
            'Chanda', 'Mwale', 'Sampa', 'Mwanza', 'Kashoki', 'Chibwe', 'Mukuka', 'Mwamba', 'Phiri', 'Banda',
            'Chisenga', 'Kabwe', 'Mwanza', 'Ngoma', 'Sichone', 'Mbewe', 'Kunda', 'Mwanawasa', 'Chiluba', 'Kaunda',
            'Tembo', 'Mumba', 'Kapwepwe', 'Mwanakatwe', 'Mubanga', 'Chanda', 'Mwale', 'Sampa', 'Kashoki', 'Chibwe'
        ];
        
        for ($i = 1; $i <= 50; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $fullName = $firstName . ' ' . $lastName;
            
            $customers[] = Customer::firstOrCreate(
                ['customer_id' => "CUST" . str_pad($i, 6, '0', STR_PAD_LEFT)],
                [
                    'name' => $fullName,
                    'email' => "customer{$i}@example.com",
                    'phone' => "+260977" . str_pad($i, 7, '0', STR_PAD_LEFT),
                    'branch_code' => $branches[array_rand($branches)],
                    'demographics' => [
                        'age' => rand(25, 65),
                        'income_bracket' => ['Low', 'Medium', 'High'][array_rand(['Low', 'Medium', 'High'])],
                        'segment' => ['Retail', 'SME', 'Corporate'][array_rand(['Retail', 'SME', 'Corporate'])],
                        'sector' => $sectors[array_rand($sectors)]
                    ],
                    'total_loans_outstanding' => rand(0, 5000000),
                    'total_deposits' => rand(1000, 2000000),
                    'npl_exposure' => rand(0, 500000),
                    'profitability' => rand(-5, 25),
                    'risk_level' => ['Low', 'Medium', 'High'][array_rand(['Low', 'Medium', 'High'])],
                    'is_active' => true
                ]
            );
        }
        
        $this->command->info('✅ 50 customers created');
        return $customers;
    }

    private function createWorkingCapitalData(Product $product, array $customers): void
    {
        $this->command->info('📊 Creating Working Capital Loan Data...');
        
        $recordCount = 100; // Create 100 working capital loans
        for ($i = 1; $i <= $recordCount; $i++) {
            $customer = $customers[array_rand($customers)];
            $data = $this->generateWorkingCapitalData($customer);
            
            ProductData::create([
                'product_id' => $product->id,
                'customer_id' => $customer->customer_id,
                'data' => $data,
                'amount' => $data['outstanding_balance'],
                'status' => $data['status'] ?? 'active'
            ]);
        }
        
        $this->command->info('✅ 100 Working Capital loan records created');
    }

    private function generateWorkingCapitalData(Customer $customer): array
    {
        $principalAmount = rand(50000, 2000000);
        $outstandingBalance = rand(10000, $principalAmount);
        $repaidAmount = $principalAmount - $outstandingBalance;
        $interestRate = rand(12, 25);
        $daysPastDue = rand(0, 120);
        $status = $daysPastDue > 90 ? 'npl' : ($daysPastDue > 30 ? 'default' : 'active');
        $probabilityDefault = $daysPastDue > 90 ? rand(15, 50) : rand(1, 10);
        $lossGivenDefault = rand(20, 60);
        $exposureAtDefault = $outstandingBalance;
        $expectedCreditLoss = ($probabilityDefault / 100) * ($lossGivenDefault / 100) * $exposureAtDefault;
        $stage = $daysPastDue > 90 ? '3' : ($daysPastDue > 30 ? '2' : '1');
        $ratingGrades = ['AAA', 'AA', 'A', 'BBB', 'BB', 'B', 'CCC', 'CC', 'C', 'D'];
        $ratingGrade = $daysPastDue > 90 ? $ratingGrades[array_rand(array_slice($ratingGrades, 6))] : $ratingGrades[array_rand(array_slice($ratingGrades, 0, 6))];
        $interestEarned = $repaidAmount * ($interestRate / 100) * (rand(1, 24) / 12);
        $loanLossProvision = $status === 'npl' ? $expectedCreditLoss * 0.8 : $expectedCreditLoss * 0.2;
        $recoveryAmount = $status === 'default' ? rand(0, $outstandingBalance * 0.3) : 0;
        
        return [
            'loan_id' => 'WCL' . str_pad(rand(1, 1000), 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->customer_id,
            'principal_amount' => $principalAmount,
            'outstanding_balance' => $outstandingBalance,
            'interest_rate' => $interestRate,
            'term_months' => rand(3, 24),
            'disbursement_date' => now()->subMonths(rand(1, 24))->toDateString(),
            'maturity_date' => now()->addMonths(rand(1, 12))->toDateString(),
            'status' => $status,
            'days_past_due' => $daysPastDue,
            'probability_default' => $probabilityDefault,
            'loss_given_default' => $lossGivenDefault,
            'exposure_at_default' => $exposureAtDefault,
            'expected_credit_loss' => round($expectedCreditLoss, 2),
            'stage' => $stage,
            'rating_grade' => $ratingGrade,
            'repaid_amount' => $repaidAmount,
            'interest_earned' => round($interestEarned, 2),
            'loan_loss_provision' => round($loanLossProvision, 2),
            'recovery_amount' => $recoveryAmount,
            'branch_code' => $customer->branch_code,
            'sector' => $customer->demographics['sector'],
            'loan_officer' => 'LO' . str_pad(rand(1, 50), 3, '0', STR_PAD_LEFT),
            'collateral_value' => rand(0, $principalAmount * 1.2),
            'currency' => 'ZMW'
        ];
    }

    private function createWorkingCapitalFormulas(Product $product, User $admin): void
    {
        $this->command->info('🧮 Creating Working Capital Formulas...');
        
        $formulas = $this->getWorkingCapitalFormulas();
        
        foreach ($formulas as $formulaData) {
            Formula::updateOrCreate(
                [
                    'name' => $formulaData['name'],
                    'product_id' => $product->id
                ],
                array_merge($formulaData, [
                    'created_by' => $admin->id,
                    'is_active' => true
                ])
            );
        }
        
        $this->command->info('✅ Working Capital formulas created');
    }

    private function getWorkingCapitalFormulas(): array
    {
        return [
            // Basic Portfolio Metrics
            [
                'name' => 'Total Portfolio Value',
                'description' => 'Total value of all working capital loans',
                'expression' => 'SUM(outstanding_balance)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Portfolio']
            ],
            [
                'name' => 'Active Portfolio Value',
                'description' => 'Value of active loans only',
                'expression' => 'SUM(outstanding_balance WHERE status = "active")',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Portfolio']
            ],
            [
                'name' => 'Loan Count',
                'description' => 'Total number of working capital loans',
                'expression' => 'COUNT(*)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 0, 'format' => 'number', 'category' => 'Portfolio']
            ],
            [
                'name' => 'Average Loan Size',
                'description' => 'Average size per loan',
                'expression' => 'AVG(outstanding_balance)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Portfolio']
            ],
            [
                'name' => 'Total Principal Disbursed',
                'description' => 'Total principal amount disbursed',
                'expression' => 'SUM(principal_amount)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Portfolio']
            ],
            
            // Risk Metrics
            [
                'name' => 'NPL Ratio',
                'description' => 'Non-performing loans as percentage of total',
                'expression' => 'SUM(outstanding_balance WHERE status = "npl") / SUM(outstanding_balance) * 100',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Risk']
            ],
            [
                'name' => 'NPL Amount',
                'description' => 'Total amount in non-performing loans',
                'expression' => 'SUM(outstanding_balance WHERE status = "npl")',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Risk']
            ],
            [
                'name' => 'Capital Adequacy Ratio',
                'description' => 'Capital adequacy ratio calculation',
                'expression' => '(SUM(collateral_value) / SUM(outstanding_balance)) * 100',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Risk']
            ],
            [
                'name' => 'Loan Loss Provision Coverage',
                'description' => 'LLP coverage ratio',
                'expression' => '(SUM(loan_loss_provision) / SUM(outstanding_balance WHERE status = "npl")) * 100',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Risk']
            ],
            [
                'name' => 'Probability of Default',
                'description' => 'Average probability of default',
                'expression' => 'AVG(probability_default)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Risk']
            ],
            [
                'name' => 'Loss Given Default',
                'description' => 'Average loss given default',
                'expression' => 'AVG(loss_given_default)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Risk']
            ],
            [
                'name' => 'Expected Credit Loss',
                'description' => 'Total expected credit loss',
                'expression' => 'SUM(expected_credit_loss)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Risk']
            ],
            
            // Stage-wise ECL
            [
                'name' => 'Stage 1 ECL (12-month)',
                'description' => 'Expected Credit Loss for Stage 1 loans',
                'expression' => 'SUM(expected_credit_loss WHERE stage = "1")',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Risk']
            ],
            [
                'name' => 'Stage 2 ECL (Lifetime)',
                'description' => 'Expected Credit Loss for Stage 2 loans',
                'expression' => 'SUM(expected_credit_loss WHERE stage = "2")',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Risk']
            ],
            [
                'name' => 'Stage 3 ECL (Credit Impaired)',
                'description' => 'Expected Credit Loss for Stage 3 loans',
                'expression' => 'SUM(expected_credit_loss WHERE stage = "3")',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Risk']
            ],
            
            // Profitability Metrics
            [
                'name' => 'Return on Assets',
                'description' => 'Return on Assets calculation',
                'expression' => '(SUM(interest_earned) / SUM(outstanding_balance)) * 100',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Profitability']
            ],
            [
                'name' => 'Return on Equity',
                'description' => 'Return on Equity calculation',
                'expression' => '(SUM(interest_earned) / SUM(principal_amount)) * 100',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Profitability']
            ],
            [
                'name' => 'Total Interest Earned',
                'description' => 'Total interest income earned',
                'expression' => 'SUM(interest_earned)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Profitability']
            ],
            [
                'name' => 'Average Interest Rate',
                'description' => 'Average interest rate across all loans',
                'expression' => 'AVG(interest_rate)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'percentage', 'category' => 'Profitability']
            ],
            
            // Distribution Analysis
            [
                'name' => 'Loan Distribution by Branch',
                'description' => 'Loan count by branch',
                'expression' => 'COUNT(*)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 0, 'format' => 'number', 'category' => 'Distribution']
            ],
            [
                'name' => 'Loan Exposure by Sector',
                'description' => 'Loan amounts by industry sector',
                'expression' => 'SUM(outstanding_balance)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Distribution']
            ],
            [
                'name' => 'Loan Exposure by Rating Grade',
                'description' => 'Exposure by credit rating grade',
                'expression' => 'SUM(outstanding_balance)',
                'return_type' => 'numeric',
                'parameters' => ['precision' => 2, 'format' => 'currency', 'category' => 'Distribution']
            ]
        ];
    }

    private function createWorkingCapitalDashboard(Product $product, User $admin): void
    {
        $this->command->info('📊 Creating Working Capital Dashboard...');
        
        $dashboard = Dashboard::firstOrCreate(
            ['name' => 'Working Capital Loans Dashboard'],
            [
                'description' => 'Comprehensive dashboard for Working Capital Loans with all KPIs and analytics',
                'is_active' => true,
                'user_id' => $admin->id,
                'product_id' => $product->id
            ]
        );

        $this->createWorkingCapitalWidgets($dashboard, $product, $admin);
        
        $this->command->info('✅ Working Capital Dashboard created');
    }

    private function createWorkingCapitalWidgets(Dashboard $dashboard, Product $product, User $admin): void
    {
        // Get all formulas for this product
        $formulas = Formula::where('product_id', $product->id)->get();
        
        $widgetIndex = 0;
        
        // Create KPI widgets for ALL formulas
        foreach ($formulas as $formula) {
            $parameters = is_string($formula->parameters) ? json_decode($formula->parameters, true) : $formula->parameters;
            $category = $parameters['category'] ?? 'General';
            
            Widget::firstOrCreate(
                [
                    'dashboard_id' => $dashboard->id,
                    'title' => $formula->name,
                    'type' => 'KPI'
                ],
                [
                    'data_source' => json_encode(['product_id' => $product->id]),
                    'configuration' => [
                        'formula_name' => $formula->name,
                        'metric' => $formula->expression,
                        'format' => $parameters['format'] ?? 'currency',
                        'precision' => $parameters['precision'] ?? 2,
                        'category' => $category
                    ],
                    'position' => $widgetIndex,
                    'order_index' => $widgetIndex,
                    'is_active' => true
                ]
            );
            $widgetIndex++;
        }

        // Create additional chart widgets
        
        // Stage Distribution Pie Chart
        Widget::firstOrCreate(
            [
                'dashboard_id' => $dashboard->id,
                'title' => 'Loan Distribution by Stage',
                'type' => 'PieChart'
            ],
            [
                'data_source' => json_encode(['product_id' => $product->id]),
                'configuration' => [
                    'chart_type' => 'pie',
                    'x_axis' => 'stage',
                    'y_axis' => 'outstanding_balance',
                    'title' => 'Loans by IFRS 9 Stage',
                    'aggregation' => 'sum'
                ],
                'position' => $widgetIndex++,
                'order_index' => $widgetIndex - 1,
                'is_active' => true
            ]
        );

        // Sector Distribution Bar Chart
        Widget::firstOrCreate(
            [
                'dashboard_id' => $dashboard->id,
                'title' => 'Loan Exposure by Sector',
                'type' => 'BarChart'
            ],
            [
                'data_source' => json_encode(['product_id' => $product->id]),
                'configuration' => [
                    'chart_type' => 'bar',
                    'x_axis' => 'sector',
                    'y_axis' => 'outstanding_balance',
                    'title' => 'Outstanding Balance by Sector',
                    'aggregation' => 'sum'
                ],
                'position' => $widgetIndex++,
                'order_index' => $widgetIndex - 1,
                'is_active' => true
            ]
        );

        // Rating Grade Distribution Bar Chart
        Widget::firstOrCreate(
            [
                'dashboard_id' => $dashboard->id,
                'title' => 'Loan Exposure by Rating Grade',
                'type' => 'BarChart'
            ],
            [
                'data_source' => json_encode(['product_id' => $product->id]),
                'configuration' => [
                    'chart_type' => 'bar',
                    'x_axis' => 'rating_grade',
                    'y_axis' => 'outstanding_balance',
                    'title' => 'Outstanding Balance by Credit Rating',
                    'aggregation' => 'sum'
                ],
                'position' => $widgetIndex++,
                'order_index' => $widgetIndex - 1,
                'is_active' => true
            ]
        );

        // Branch Distribution Bar Chart
        Widget::firstOrCreate(
            [
                'dashboard_id' => $dashboard->id,
                'title' => 'Loan Distribution by Branch',
                'type' => 'BarChart'
            ],
            [
                'data_source' => json_encode(['product_id' => $product->id]),
                'configuration' => [
                    'chart_type' => 'bar',
                    'x_axis' => 'branch_code',
                    'y_axis' => 'outstanding_balance',
                    'title' => 'Loan Exposure by Branch',
                    'aggregation' => 'sum'
                ],
                'position' => $widgetIndex++,
                'order_index' => $widgetIndex - 1,
                'is_active' => true
            ]
        );

        // ECL Analysis Table
        Widget::firstOrCreate(
            [
                'dashboard_id' => $dashboard->id,
                'title' => 'Expected Credit Loss Analysis',
                'type' => 'Table'
            ],
            [
                'data_source' => json_encode(['product_id' => $product->id]),
                'configuration' => [
                    'columns' => ['stage', 'customer_id', 'outstanding_balance', 'expected_credit_loss', 'probability_default', 'loss_given_default'],
                    'headers' => ['Stage', 'Customer ID', 'Outstanding Balance', 'ECL', 'PD (%)', 'LGD (%)'],
                    'title' => 'ECL Analysis by Stage',
                    'limit' => 20,
                    'sort_by' => 'expected_credit_loss',
                    'sort_order' => 'desc'
                ],
                'position' => $widgetIndex++,
                'order_index' => $widgetIndex - 1,
                'is_active' => true
            ]
        );

        // Top Borrowers Table
        Widget::firstOrCreate(
            [
                'dashboard_id' => $dashboard->id,
                'title' => 'Top 10 Borrowers Concentration',
                'type' => 'Table'
            ],
            [
                'data_source' => json_encode(['product_id' => $product->id]),
                'configuration' => [
                    'columns' => ['customer_id', 'outstanding_balance', 'probability_default', 'rating_grade'],
                    'headers' => ['Customer ID', 'Outstanding Balance', 'PD (%)', 'Rating'],
                    'title' => 'Top Borrowers by Exposure',
                    'limit' => 10,
                    'sort_by' => 'outstanding_balance',
                    'sort_order' => 'desc'
                ],
                'position' => $widgetIndex++,
                'order_index' => $widgetIndex - 1,
                'is_active' => true
            ]
        );

        // Risk Heatmap
        Widget::firstOrCreate(
            [
                'dashboard_id' => $dashboard->id,
                'title' => 'Risk Concentration Heatmap',
                'type' => 'Heatmap'
            ],
            [
                'data_source' => json_encode(['product_id' => $product->id]),
                'configuration' => [
                    'chart_type' => 'heatmap',
                    'x_axis' => 'sector',
                    'y_axis' => 'rating_grade',
                    'z_axis' => 'SUM(outstanding_balance)',
                    'title' => 'Risk Concentration by Sector & Rating'
                ],
                'position' => $widgetIndex++,
                'order_index' => $widgetIndex - 1,
                'is_active' => true
            ]
        );
    }

    private function createConfigurations(): void
    {
        $this->command->info('⚙️ Creating System Configurations...');
        
        $configs = [
            ['key' => 'dashboard_cache_ttl', 'value' => '300', 'description' => 'Dashboard cache TTL in seconds'],
            ['key' => 'risk_threshold_npl', 'value' => '5.0', 'description' => 'NPL risk threshold percentage'],
            ['key' => 'portfolio_concentration_threshold', 'value' => '30.0', 'description' => 'Portfolio concentration threshold'],
            ['key' => 'base_currency', 'value' => 'ZMW', 'description' => 'Base currency for the system'],
            ['key' => 'default_date_range', 'value' => '12', 'description' => 'Default date range in months'],
            ['key' => 'max_file_upload_size', 'value' => '10485760', 'description' => 'Maximum file upload size in bytes'],
            ['key' => 'supported_file_types', 'value' => 'csv,xlsx,xls', 'description' => 'Supported file types for upload'],
        ];

        foreach ($configs as $config) {
            Configuration::firstOrCreate(
                ['key' => $config['key']],
                [
                    'value' => $config['value'],
                    'description' => $config['description']
                ]
            );
        }

        $this->command->info('✅ System configurations created');
    }
}
