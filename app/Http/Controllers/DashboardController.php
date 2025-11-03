<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDashboardRequest;
use App\Http\Requests\UpdateDashboardRequest;
use App\Models\Dashboard;
use App\Models\Customer;
use App\Models\ProductData;
use App\Models\Product;
use App\Repositories\DashboardRepository;
use App\Services\ChartDataService;
use App\Services\DashboardExportService;
use App\Services\DashboardStatsService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    public function __construct(
        private DashboardRepository $dashboardRepository,
        private ChartDataService $chartDataService,
        private DashboardExportService $exportService,
        private DashboardStatsService $statsService
    ) {}

    
    public function index(Request $request): Response
    {
        $dashboardData = $this->statsService->getDashboardStats();
        $recentCustomers = $this->statsService->getRecentCustomers();
        $riskAlerts = $this->statsService->getRiskAlerts();
        
        $stats = [
            $dashboardData['total_customers'],
            $dashboardData['portfolio_value'],
            $dashboardData['growth_rate']
        ];
        
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentCustomers' => $recentCustomers,
            'riskAlerts' => $riskAlerts,
            'portfolioPerformance' => $dashboardData['portfolio_performance'],
            'productBreakdown' => $dashboardData['product_breakdown']
        ]);
    }

    
    public function list(Request $request): Response
    {
        // Show all active dashboards to all users
        $dashboards = Dashboard::with(['widgets', 'user', 'product'])
            ->withCount('widgets')
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return Inertia::render('Dashboards/Index', [
            'dashboards' => $dashboards
        ]);
    }

    
    public function create(): Response
    {
        $formulas = \App\Models\Formula::with(['product', 'creator'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $products = \App\Models\Product::select('id', 'name', 'category')
            ->orderBy('name')
            ->get();
            
        $customers = \App\Models\Customer::select('customer_id', 'name', 'branch_code')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboards/Create', [
            'formulas' => $formulas,
            'products' => $products,
            'customers' => $customers,
            'widgetTypes' => [
                'KPI' => 'Key Performance Indicator',
                'BarChart' => 'Bar Chart',
                'LineChart' => 'Line Chart',
                'PieChart' => 'Pie Chart',
                'Table' => 'Data Table',
                'Heatmap' => 'Heatmap'
            ]
        ]);
    }

    
    public function store(StoreDashboardRequest $request)
    {
        try {
            \Log::info('Dashboard creation started', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'data' => $request->validated(),
                'all_data' => $request->all(),
                'widgets_data' => $request->get('widgets', 'NOT_FOUND')
            ]);

            $dashboard = $this->dashboardRepository->create([
                'name' => $request->name,
                'user_id' => $request->user()->id,
                'layout' => $request->layout ?? [],
                'filters' => $request->filters ?? [],
                'product_id' => $request->product_id,
                'description' => $request->description,
                'is_public' => $request->is_public ?? false,
                'is_active' => $request->is_active ?? true
            ]);

            // Process widgets if provided
            if ($request->has('widgets') && is_array($request->widgets)) {
                $this->processWidgets($dashboard, $request->widgets);
            }

            \Log::info('Dashboard created successfully', [
                'dashboard_id' => $dashboard->id,
                'dashboard_name' => $dashboard->name
            ]);

            // Check if this is an API request
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard created successfully',
                    'data' => $dashboard->load('widgets')
                ], 201);
            }

            // For web requests, return Inertia response
            return redirect()->route('dashboards.show', $dashboard)
                ->with('success', 'Dashboard created successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Dashboard creation validation failed', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Dashboard creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create dashboard.',
                    'error' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create dashboard: ' . $e->getMessage()]);
        }
    }

    
    public function show(Dashboard $dashboard): Response
    {
        $this->authorize('view', $dashboard);
        
        $dashboard->load('widgets');
        
        return Inertia::render('Dashboards/Show', [
            'dashboard' => $dashboard
        ]);
    }

    
    public function showSimple(Dashboard $dashboard): Response
    {
        $this->authorize('view', $dashboard);
        
        $dashboard->load('widgets');
        
        return Inertia::render('Dashboards/SimpleShow', [
            'dashboard' => $dashboard
        ]);
    }

    
    public function consolidated(): Response
    {
        $dashboards = $this->dashboardRepository->getUserDashboards(
            auth()->id(),
            100 // Get all dashboards
        );

        return Inertia::render('Dashboards/ConsolidatedShow', [
            'dashboards' => $dashboards
        ]);
    }

    
    public function productDashboard(Product $product): Response
    {
        $dashboard = Dashboard::where('product_id', $product->id)->with('widgets')->first();
        
        if (!$dashboard) {
            $dashboard = Dashboard::create([
                'name' => "{$product->name} Analytics Dashboard",
                'description' => "Dynamic analytics dashboard for {$product->name}",
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'layout' => [],
                'filters' => []
            ]);
        }

        return Inertia::render('Dashboards/Show', [
            'dashboard' => $dashboard,
            'product' => $product
        ]);
    }

    
    public function edit(Dashboard $dashboard): Response
    {
        $this->authorize('update', $dashboard);
        
        $dashboard->load('widgets');
        
        $formulas = \App\Models\Formula::with(['product', 'creator'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $products = \App\Models\Product::select('id', 'name', 'category')
            ->orderBy('name')
            ->get();
            
        $customers = \App\Models\Customer::select('customer_id', 'name', 'branch_code')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Dashboards/Edit', [
            'dashboard' => $dashboard,
            'formulas' => $formulas,
            'products' => $products,
            'customers' => $customers
        ]);
    }

    
    public function update(UpdateDashboardRequest $request, Dashboard $dashboard)
    {
        $this->authorize('update', $dashboard);
        
        try {
            $updated = $this->dashboardRepository->update($dashboard, [
                'name' => $request->name,
                'layout' => $request->layout,
                'filters' => $request->filters,
                'product_id' => $request->product_id,
                'description' => $request->description,
                'is_public' => $request->is_public,
                'is_active' => $request->is_active
            ]);

            // Process widgets if provided
            if ($request->has('widgets') && is_array($request->widgets)) {
                $this->processWidgets($dashboard, $request->widgets);
            }

            // Handle different response types
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard updated successfully',
                    'data' => $dashboard->fresh(['widgets', 'product', 'user'])
                ]);
            }

            return redirect()->route('dashboards.show', $dashboard)
                ->with('success', 'Dashboard updated successfully');
                
        } catch (\Exception $e) {
            \Log::error('Dashboard update error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update dashboard',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update dashboard: ' . $e->getMessage()]);
        }
    }

    /**
     * Process widgets array and create/update widget records
     */
    private function processWidgets(Dashboard $dashboard, array $widgets): void
    {
        // First, delete all existing widgets for this dashboard
        $dashboard->widgets()->delete();
        
        // Create new widgets
        foreach ($widgets as $index => $widgetData) {
            if (isset($widgetData['type']) && isset($widgetData['configuration'])) {
                $dashboard->widgets()->create([
                    'title' => $widgetData['title'] ?? 'Untitled Widget',
                    'type' => $widgetData['type'],
                    'configuration' => $widgetData['configuration'] ?? [],
                    'position' => $widgetData['position'] ?? ['x' => 0, 'y' => 0, 'width' => 4, 'height' => 3],
                    'data_source' => $widgetData['data_source'] ?? null,
                    'is_active' => $widgetData['is_active'] ?? true,
                    'order_index' => $index
                ]);
            }
        }
    }

    
    public function destroy(Dashboard $dashboard): JsonResponse
    {
        $this->authorize('delete', $dashboard);
        
        $this->dashboardRepository->delete($dashboard);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard deleted successfully'
        ]);
    }

    
    public function updateLayout(Request $request, Dashboard $dashboard): JsonResponse
    {
        $this->authorize('update', $dashboard);
        
        $request->validate([
            'layout' => 'required|array'
        ]);

        $updated = $this->dashboardRepository->update($dashboard, [
            'layout' => $request->layout
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard layout updated successfully',
            'data' => $updated
        ]);
    }

    
    public function getChartData(Request $request, Dashboard $dashboard, int $widgetId): JsonResponse
    {
        $this->authorize('view', $dashboard);
        
        $widget = $dashboard->widgets()->findOrFail($widgetId);
        
        $data = $this->chartDataService->getChartData(
            $widget,
            $request->get('filters', [])
        );

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    
    public function duplicate(Dashboard $dashboard): JsonResponse
    {
        $this->authorize('view', $dashboard);
        
        $duplicated = $this->dashboardRepository->cloneDashboard($dashboard, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Dashboard duplicated successfully',
            'data' => $duplicated->load('widgets')
        ]);
    }

    
    public function applyFilters(Request $request, Dashboard $dashboard): JsonResponse
    {
        $this->authorize('view', $dashboard);
        
        $request->validate([
            'filters' => 'required|array',
            'filters.date_range' => 'nullable|array',
            'filters.date_range.start' => 'nullable|date',
            'filters.date_range.end' => 'nullable|date|after_or_equal:filters.date_range.start',
            'filters.branch' => 'nullable|string|max:255',
            'filters.currency' => 'nullable|string|max:10',
            'filters.demographic' => 'nullable|string|max:255',
            'filters.product_type' => 'nullable|string|max:255'
        ]);

        $widgets = $dashboard->widgets()->where('is_active', true)->get();
        $filteredData = [];
        
        foreach ($widgets as $widget) {
            try {
                $data = $this->chartDataService->getChartData($widget, $request->filters);
                $filteredData[$widget->id] = $data;
            } catch (\Exception $e) {
                $filteredData[$widget->id] = ['error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $filteredData,
            'applied_filters' => $request->filters
        ]);
    }

    
    public function exportPdf(Request $request, Dashboard $dashboard)
    {
        $this->authorize('view', $dashboard);
        
        $request->validate([
            'filters' => 'nullable|array'
        ]);

        try {
            $filePath = $this->exportService->exportToPdf($dashboard, $request->get('filters', []));
            
            return response()->download(
                Storage::path($filePath),
                basename($filePath),
                ['Content-Type' => 'application/pdf']
            )->deleteFileAfterSend();
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function exportCsv(Request $request, Dashboard $dashboard)
    {
        $this->authorize('view', $dashboard);
        
        $request->validate([
            'filters' => 'nullable|array'
        ]);

        try {
            $filePath = $this->exportService->exportToCsv($dashboard, $request->get('filters', []));
            
            return response()->download(
                Storage::path($filePath),
                basename($filePath),
                ['Content-Type' => 'text/csv']
            )->deleteFileAfterSend();
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function getExportFormats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->exportService->getAvailableFormats()
        ]);
    }

    
    public function getFilterOptions(Dashboard $dashboard): JsonResponse
    {
        $this->authorize('view', $dashboard);
        
        $filterOptions = [
            'branches' => $this->getUniqueBranches(),
            'currencies' => $this->getUniqueCurrencies(),
            'demographics' => $this->getUniqueDemographics(),
            'product_types' => $this->getUniqueProductTypes(),
            'date_range' => $this->getDateRange()
        ];

        return response()->json([
            'success' => true,
            'data' => $filterOptions
        ]);
    }

    
    private function getUniqueBranches(): array
    {
        return \DB::table('customers')
            ->whereNotNull('branch_code')
            ->distinct()
            ->pluck('branch_code')
            ->filter()
            ->values()
            ->toArray();
    }

    
    private function getUniqueCurrencies(): array
    {
        return \DB::table('product_data')
            ->whereNotNull('data->currency')
            ->distinct()
            ->pluck('data->currency')
            ->filter()
            ->values()
            ->toArray();
    }

    
    private function getUniqueDemographics(): array
    {
        return \DB::table('customers')
            ->whereNotNull('demographics->segment')
            ->distinct()
            ->pluck('demographics->segment')
            ->filter()
            ->values()
            ->toArray();
    }

    
    private function getUniqueProductTypes(): array
    {
        return \DB::table('products')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();
    }

    
    private function getDateRange(): array
    {
        $minDate = \DB::table('product_data')
            ->whereNotNull('created_at')
            ->min('created_at');
            
        $maxDate = \DB::table('product_data')
            ->whereNotNull('created_at')
            ->max('created_at');

        return [
            'min' => $minDate ? date('Y-m-d', strtotime($minDate)) : date('Y-m-d', strtotime('-1 year')),
            'max' => $maxDate ? date('Y-m-d', strtotime($maxDate)) : date('Y-m-d')
        ];
    }

    
    private function getDashboardStats(): array
    {
        $totalCustomers = Customer::count();
        
        $portfolioValue = ProductData::sum('data->principal_amount');
        
        $currentMonth = ProductData::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('data->principal_amount');
        
        $previousMonth = ProductData::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('data->principal_amount');
        
        $growthRate = $previousMonth > 0 ? (($currentMonth - $previousMonth) / $previousMonth) * 100 : 0;
        
        $riskAlerts = ProductData::where('data->status', 'NPL')->count();
        
        $previousCustomers = Customer::where('created_at', '<', now()->subMonth())->count();
        $customerChange = $previousCustomers > 0 ? (($totalCustomers - $previousCustomers) / $previousCustomers) * 100 : 0;
        
        $previousPortfolio = ProductData::where('created_at', '<', now()->subMonth())->sum('data->principal_amount');
        $portfolioChange = $previousPortfolio > 0 ? (($portfolioValue - $previousPortfolio) / $previousPortfolio) * 100 : 0;
        
        return [
            [
                'title' => 'Total Customers',
                'value' => number_format($totalCustomers),
                'change' => $customerChange > 0 ? '+' . number_format($customerChange, 1) . '%' : number_format($customerChange, 1) . '%',
                'changeType' => $customerChange >= 0 ? 'positive' : 'negative',
                'icon' => 'users',
                'color' => 'blue',
                'description' => 'Active customers'
            ],
            [
                'title' => 'Portfolio Value',
                'value' => app(CurrencyService::class)->formatAmount($portfolioValue),
                'change' => $portfolioChange > 0 ? '+' . number_format($portfolioChange, 1) . '%' : number_format($portfolioChange, 1) . '%',
                'changeType' => $portfolioChange >= 0 ? 'positive' : 'negative',
                'icon' => 'currency-dollar',
                'color' => 'green',
                'description' => 'Total assets under management'
            ],
            [
                'title' => 'Growth Rate',
                'value' => number_format($growthRate, 1) . '%',
                'change' => $growthRate > 0 ? '+' . number_format($growthRate, 1) . '%' : number_format($growthRate, 1) . '%',
                'changeType' => $growthRate >= 0 ? 'positive' : 'negative',
                'icon' => 'trending-up',
                'color' => 'yellow',
                'description' => 'Monthly growth rate'
            ],
            [
                'title' => 'Risk Alerts',
                'value' => number_format($riskAlerts),
                'change' => $riskAlerts > 0 ? '-' . number_format($riskAlerts) : '0',
                'changeType' => 'positive', // Lower risk alerts is positive
                'icon' => 'alert-triangle',
                'color' => 'red',
                'description' => 'Active risk alerts'
            ]
        ];
    }

    
    private function getRecentCustomers(): array
    {
        return Customer::latest()
            ->take(5)
            ->get()
            ->map(function ($customer) {
                $portfolioValue = ProductData::where('customer_id', $customer->customer_id)
                    ->sum('data->principal_amount');
                
                return [
                    'id' => $customer->customer_id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'portfolio_value' => $portfolioValue,
                    'segment' => $customer->demographics['segment'] ?? 'Standard',
                    'initials' => $this->getInitials($customer->name)
                ];
            })
            ->toArray();
    }

    
    private function getRiskAlerts(): array
    {
        $alerts = [];
        
        $nplCustomers = ProductData::where('data->status', 'NPL')
            ->with('customer')
            ->take(3)
            ->get();
        
        foreach ($nplCustomers as $data) {
            $alerts[] = [
                'type' => 'High Risk Customer',
                'description' => "Customer ID: {$data->customer_id} - Credit score dropped below threshold",
                'customer_id' => $data->customer_id,
                'customer_name' => $data->customer->name ?? 'Unknown',
                'severity' => 'high'
            ];
        }
        
        $sectors = ProductData::selectRaw('JSON_EXTRACT(data, "$.sector") as sector, COUNT(*) as count, SUM(JSON_EXTRACT(data, "$.principal_amount")) as total')
            ->groupBy('sector')
            ->havingRaw('total > (SELECT SUM(JSON_EXTRACT(data, "$.principal_amount")) * 0.3 FROM product_data)')
            ->take(2)
            ->get();
        
        foreach ($sectors as $sector) {
            $alerts[] = [
                'type' => 'Portfolio Concentration',
                'description' => "High concentration in {$sector->sector} sector",
                'sector' => $sector->sector,
                'percentage' => number_format(($sector->total / ProductData::sum('data->principal_amount')) * 100, 1),
                'severity' => 'medium'
            ];
        }
        
        return $alerts;
    }

    
    private function formatLargeNumber($number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    
    private function getInitials($name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
}


