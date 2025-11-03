<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\ProfitabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private ProfitabilityService $profitabilityService
    ) {}

    
    public function index(Request $request): Response
    {
        $filters = $request->only(['branch_code', 'risk_level', 'is_active', 'search']);
        $customers = $this->customerRepository->paginate(15, $filters);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $filters,
            'branches' => $this->getBranches(),
            'riskLevels' => ['Low', 'Medium', 'High']
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('Customers/Create', [
            'riskLevels' => ['Low', 'Medium', 'High']
        ]);
    }

    
    public function store(Request $request): RedirectResponse
    {
        try {
            \Log::info('Customer creation started', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'data' => $request->all()
            ]);

            $validated = $request->validate([
                'customer_id' => 'required|string|unique:customers',
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'branch_code' => 'required|string|max:10',
                'risk_level' => 'required|in:Low,Medium,High',
                'is_active' => 'boolean',
                'total_loans_outstanding' => 'numeric|min:0',
                'total_deposits' => 'numeric|min:0',
                'npl_exposure' => 'numeric|min:0',
                'profitability' => 'numeric',
                'demographics' => 'array'
            ]);

            $customer = Customer::create($validated);

            \Log::info('Customer created successfully', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name
            ]);

            return redirect()->route('customers.index')
                ->with('message', 'Customer created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Customer creation validation failed', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Customer creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create customer: ' . $e->getMessage()]);
        }
    }

    
    public function show(Customer $customer): Response
    {
        $customer->load(['productData.product']);
        
        $profitabilityData = $this->profitabilityService->calculateCustomerProfitability($customer);
        $nplData = $this->profitabilityService->getNPLExposure($customer);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'profitability' => $profitabilityData,
            'nplExposure' => $nplData,
            'productsByCategory' => $this->getProductsByCategory($customer)
        ]);
    }

    
    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            'riskLevels' => ['Low', 'Medium', 'High']
        ]);
    }

    
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'branch_code' => 'required|string|max:10',
            'risk_level' => 'required|in:Low,Medium,High',
            'is_active' => 'boolean',
            'total_loans_outstanding' => 'numeric|min:0',
            'total_deposits' => 'numeric|min:0',
            'npl_exposure' => 'numeric|min:0',
            'profitability' => 'numeric',
            'demographics' => 'array'
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('message', 'Customer updated successfully!');
    }

    
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('message', 'Customer deleted successfully!');
    }

    
    public function profitability(Customer $customer): JsonResponse
    {
        $profitabilityData = $this->profitabilityService->calculateCustomerProfitability($customer);
        
        return response()->json([
            'success' => true,
            'data' => $profitabilityData
        ]);
    }

    
    public function branchProfitability(Request $request): JsonResponse
    {
        $branchCode = $request->get('branch_code');
        
        if (!$branchCode) {
            return response()->json([
                'success' => false,
                'message' => 'Branch code is required'
            ], 400);
        }

        $branchData = $this->profitabilityService->getBranchProfitability($branchCode);
        
        return response()->json([
            'success' => true,
            'data' => $branchData
        ]);
    }

    
    public function topProfitable(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $branchCode = $request->get('branch_code');
        
        $topCustomers = $this->profitabilityService->getTopProfitableCustomers($limit, $branchCode);
        
        return response()->json([
            'success' => true,
            'data' => $topCustomers
        ]);
    }

    
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);
        
        $customers = Customer::where('name', 'like', "%{$query}%")
            ->orWhere('customer_id', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'customer_id', 'name', 'email', 'branch_code', 'profitability', 'risk_level']);

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    
    public function insights(Request $request): JsonResponse
    {
        $branchCode = $request->get('branch_code');
        $riskLevel = $request->get('risk_level');
        
        $query = Customer::query();
        
        if ($branchCode) {
            $query->where('branch_code', $branchCode);
        }
        
        if ($riskLevel) {
            $query->where('risk_level', $riskLevel);
        }
        
        $customers = $query->get();
        
        $insights = [
            'total_customers' => $customers->count(),
            'active_customers' => $customers->where('is_active', true)->count(),
            'total_profitability' => $customers->sum('profitability'),
            'average_profitability' => $customers->avg('profitability'),
            'total_loans' => $customers->sum('total_loans_outstanding'),
            'total_deposits' => $customers->sum('total_deposits'),
            'total_npl_exposure' => $customers->sum('npl_exposure'),
            'risk_distribution' => [
                'Low' => $customers->where('risk_level', 'Low')->count(),
                'Medium' => $customers->where('risk_level', 'Medium')->count(),
                'High' => $customers->where('risk_level', 'High')->count(),
            ],
            'profitability_by_risk' => [
                'Low' => $customers->where('risk_level', 'Low')->avg('profitability'),
                'Medium' => $customers->where('risk_level', 'Medium')->avg('profitability'),
                'High' => $customers->where('risk_level', 'High')->avg('profitability'),
            ],
            'top_profitable' => $customers->sortByDesc('profitability')->take(5)->values(),
            'high_risk_customers' => $customers->where('risk_level', 'High')->count(),
            'npl_ratio' => $customers->sum('total_loans_outstanding') > 0 
                ? ($customers->sum('npl_exposure') / $customers->sum('total_loans_outstanding')) * 100 
                : 0
        ];

        return response()->json([
            'success' => true,
            'data' => $insights
        ]);
    }

    
    public function updateMetrics(Customer $customer): JsonResponse
    {
        try {
            $this->profitabilityService->updateCustomerMetrics($customer);
            
            return response()->json([
                'success' => true,
                'message' => 'Customer metrics updated successfully',
                'data' => $customer->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function portfolio(Customer $customer): JsonResponse
    {
        $customer->load(['productData.product']);
        
        $portfolio = [
            'loans' => $customer->getProductsByCategory('Loan'),
            'deposits' => $customer->getProductsByCategory('Deposit'),
            'accounts' => $customer->getProductsByCategory('Account'),
            'transactions' => $customer->getProductsByCategory('Transaction'),
            'other' => $customer->getProductsByCategory('Other'),
        ];

        $summary = [
            'total_products' => $customer->productData->count(),
            'total_value' => $customer->total_loans_outstanding + $customer->total_deposits,
            'loan_to_deposit_ratio' => $customer->total_deposits > 0 
                ? ($customer->total_loans_outstanding / $customer->total_deposits) * 100 
                : 0,
            'categories' => array_map(function($products) {
                return [
                    'count' => $products->count(),
                    'total_value' => $products->sum(function($product) {
                        return $product->data['amount'] ?? $product->data['balance'] ?? 0;
                    })
                ];
            }, $portfolio)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'portfolio' => $portfolio,
                'summary' => $summary
            ]
        ]);
    }

    
    private function getBranches(): array
    {
        return Customer::distinct('branch_code')
            ->whereNotNull('branch_code')
            ->pluck('branch_code')
            ->sort()
            ->values()
            ->toArray();
    }

    
    private function getProductsByCategory(Customer $customer): array
    {
        $categories = ['Loan', 'Deposit', 'Account', 'Transaction', 'Other'];
        $result = [];

        foreach ($categories as $category) {
            $products = $customer->getProductsByCategory($category);
            $result[$category] = [
                'products' => $products,
                'count' => $products->count(),
                'total_value' => $products->sum(function($product) {
                    return $product->data['amount'] ?? $product->data['balance'] ?? 0;
                })
            ];
        }

        return $result;
    }

    
    public function bulkUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_shift($csvData); // Remove header row
            
            $created = 0;
            $errors = [];
            
            foreach ($csvData as $index => $row) {
                try {
                    // Map CSV columns to customer data
                    $customerData = $this->mapCsvRowToCustomerData($row, $header);
                    
                    // Validate the mapped data
                    $validated = Validator::make($customerData, [
                        'customer_id' => 'required|string|unique:customers',
                        'name' => 'required|string|max:255',
                        'email' => 'nullable|email|max:255',
                        'phone' => 'nullable|string|max:20',
                        'branch_code' => 'required|string|max:10',
                        'risk_level' => 'required|in:Low,Medium,High',
                        'is_active' => 'boolean',
                        'total_loans_outstanding' => 'numeric|min:0',
                        'total_deposits' => 'numeric|min:0',
                        'npl_exposure' => 'numeric|min:0',
                        'profitability' => 'numeric',
                        'demographics' => 'array'
                    ])->validate();
                    
                    Customer::create($validated);
                    $created++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            $message = "Bulk upload completed! {$created} customers created successfully.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " rows had errors.";
            }
            
            return redirect()->route('customers.index')
                ->with('message', $message)
                ->with('errors', $errors);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['csv_file' => 'Error processing CSV file: ' . $e->getMessage()]);
        }
    }

    
    public function downloadTemplate(): Response
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer_bulk_upload_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'customer_id',
                'name',
                'email',
                'phone',
                'branch_code',
                'risk_level',
                'is_active',
                'total_loans_outstanding',
                'total_deposits',
                'npl_exposure',
                'profitability',
                'age',
                'gender',
                'occupation',
                'address',
                'city',
                'country'
            ]);
            
            // Sample data rows
            fputcsv($file, [
                'CUST001',
                'John Mwape',
                'john.mwape@email.com',
                '+260977123456',
                'LUS001',
                'Low',
                '1',
                '50000.00',
                '25000.00',
                '0.00',
                '1500.00',
                '35',
                'Male',
                'Engineer',
                '123 Independence Avenue',
                'Lusaka',
                'Zambia'
            ]);
            
            fputcsv($file, [
                'CUST002',
                'Mary Banda',
                'mary.banda@email.com',
                '+260977654321',
                'NDL001',
                'Medium',
                '1',
                '75000.00',
                '40000.00',
                '2500.00',
                '2200.00',
                '28',
                'Female',
                'Teacher',
                '456 Cairo Road',
                'Ndola',
                'Zambia'
            ]);
            
            fputcsv($file, [
                'CUST003',
                'Peter Sinkamba',
                'peter.sinkamba@email.com',
                '+260977789012',
                'KIT001',
                'High',
                '1',
                '100000.00',
                '15000.00',
                '8000.00',
                '500.00',
                '42',
                'Male',
                'Business Owner',
                '789 Mwamba Road',
                'Kitwe',
                'Zambia'
            ]);
            
            fputcsv($file, [
                'CUST006',
                'Alice Mumba',
                'alice.mumba@email.com',
                '+260977567890',
                'CHI001',
                'Low',
                '1',
                '25000.00',
                '45000.00',
                '0.00',
                '1800.00',
                '29',
                'Female',
                'Nurse',
                '987 Katondo Street',
                'Chingola',
                'Zambia'
            ]);
            
            fputcsv($file, [
                'CUST007',
                'James Phiri',
                'james.phiri@email.com',
                '+260977678901',
                'KAB001',
                'Medium',
                '1',
                '60000.00',
                '30000.00',
                '3000.00',
                '1600.00',
                '38',
                'Male',
                'Manager',
                '147 Independence Street',
                'Kabwe',
                'Zambia'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    
    private function mapCsvRowToCustomerData(array $row, array $header): array
    {
        $data = array_combine($header, $row);
        
        return [
            'customer_id' => $data['customer_id'] ?? '',
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'branch_code' => $data['branch_code'] ?? '',
            'risk_level' => $data['risk_level'] ?? 'Medium',
            'is_active' => filter_var($data['is_active'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'total_loans_outstanding' => floatval($data['total_loans_outstanding'] ?? 0),
            'total_deposits' => floatval($data['total_deposits'] ?? 0),
            'npl_exposure' => floatval($data['npl_exposure'] ?? 0),
            'profitability' => floatval($data['profitability'] ?? 0),
            'demographics' => [
                'age' => $data['age'] ?? null,
                'gender' => $data['gender'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Zambia'
            ]
        ];
    }
}


