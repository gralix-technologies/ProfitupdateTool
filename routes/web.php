<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DataIngestionController;
use App\Http\Controllers\QueueMonitorController;
use App\Http\Controllers\ProfileController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Test routes for CSRF token
Route::get('/test-csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'session_started' => session()->isStarted()
    ]);
});

Route::post('/test-csrf', function () {
    return response()->json(['message' => 'CSRF token accepted']);
});

Route::get('/api/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::middleware(['web', 'auth'])->prefix('api/queue')->group(function () {
    Route::get('/stats', [QueueMonitorController::class, 'stats']);
    Route::get('/health', [QueueMonitorController::class, 'health']);
    Route::get('/throughput', [QueueMonitorController::class, 'throughput']);
    Route::get('/failed-jobs', [QueueMonitorController::class, 'failedJobs']);
    Route::get('/performance', [QueueMonitorController::class, 'performance']);
    Route::post('/retry-job', [QueueMonitorController::class, 'retryJob']);
    Route::post('/retry-all', [QueueMonitorController::class, 'retryAllJobs']);
    Route::post('/clear-failed', [QueueMonitorController::class, 'clearFailedJobs']);
    Route::post('/cleanup', [QueueMonitorController::class, 'cleanup']);
    Route::post('/test-job', [QueueMonitorController::class, 'testJob']);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    
    // Admin routes
    Route::prefix('admin')->middleware('role:Admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'userManagement'])->name('admin.users');
        Route::get('/audit-trail', [\App\Http\Controllers\AdminController::class, 'auditTrail'])->name('admin.audit-trail');
        Route::get('/audit-trail/export', [\App\Http\Controllers\AdminController::class, 'exportAuditLogs'])->name('admin.audit-trail.export');
        Route::get('/roles', [\App\Http\Controllers\AdminController::class, 'roleManagement'])->name('admin.roles');
        Route::get('/settings', [\App\Http\Controllers\AdminController::class, 'systemSettings'])->name('admin.settings');
        Route::post('/settings', [\App\Http\Controllers\AdminController::class, 'saveSettings'])->name('admin.settings.save');
        Route::get('/logs', [\App\Http\Controllers\AdminController::class, 'systemLogs'])->name('admin.logs');
    });
    
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/dashboard/export', [DashboardController::class, 'export'])
        ->name('dashboard.export')
        ->middleware('permission:export-dashboards');
    
    Route::resource('products', ProductController::class)
        ->middleware(['permission:view-products'])
        ->only(['index', 'create', 'show', 'edit', 'update', 'destroy']);
    
    Route::post('products', [ProductController::class, 'store'])
        ->name('products.store')
        ->middleware('permission:create-products');
    
    
    Route::post('/products/{product}/copy', [ProductController::class, 'copy'])
        ->name('products.copy')
        ->middleware('permission:create-products');
    
    Route::resource('customers', \App\Http\Controllers\CustomerController::class)
        ->middleware('permission:view-products');
    
    Route::post('customers', [\App\Http\Controllers\CustomerController::class, 'store'])
        ->name('customers.store')
        ->middleware('permission:create-products');
    
    // Bulk upload routes
    Route::post('/customers/bulk-upload', [\App\Http\Controllers\CustomerController::class, 'bulkUpload'])
        ->name('customers.bulk-upload')
        ->middleware('permission:create-products');
    
    Route::get('/customers/download-template', [\App\Http\Controllers\CustomerController::class, 'downloadTemplate'])
        ->name('customers.download-template')
        ->middleware('permission:view-products');
    
    Route::post('/customers/{customer}/update-metrics', [\App\Http\Controllers\CustomerController::class, 'updateMetrics'])
        ->name('customers.update-metrics')
        ->middleware('permission:edit-products');
    
    Route::prefix('api/customers')->middleware('permission:view-products')->group(function () {
        Route::get('/{customer}/profitability', [\App\Http\Controllers\CustomerController::class, 'profitability']);
        Route::get('/branch-profitability', [\App\Http\Controllers\CustomerController::class, 'branchProfitability']);
        Route::get('/top-profitable', [\App\Http\Controllers\CustomerController::class, 'topProfitable']);
        Route::get('/search', [\App\Http\Controllers\CustomerController::class, 'search']);
        Route::get('/insights', [\App\Http\Controllers\CustomerController::class, 'insights']);
        Route::put('/{customer}/metrics', [\App\Http\Controllers\CustomerController::class, 'updateMetrics']);
        Route::get('/{customer}/portfolio', [\App\Http\Controllers\CustomerController::class, 'portfolio']);
    });
    
    // TEMPORARY: Formula routes without auth for testing
    Route::middleware(['web'])->group(function () {
        Route::get('/formulas', [\App\Http\Controllers\FormulaController::class, 'index'])->name('formulas.index');
        Route::get('/formulas/create', [\App\Http\Controllers\FormulaController::class, 'create'])->name('formulas.create');
        Route::post('/formulas', [\App\Http\Controllers\FormulaController::class, 'store'])->name('formulas.store');
        Route::get('/formulas/{formula}', [\App\Http\Controllers\FormulaController::class, 'show'])->name('formulas.show');
        Route::get('/formulas/{formula}/edit', [\App\Http\Controllers\FormulaController::class, 'edit'])->name('formulas.edit');
        Route::put('/formulas/{formula}', [\App\Http\Controllers\FormulaController::class, 'update'])->name('formulas.update');
        Route::delete('/formulas/{formula}', [\App\Http\Controllers\FormulaController::class, 'destroy'])->name('formulas.destroy');
    });

    Route::prefix('api/formulas')->middleware(['web', 'auth', 'permission:view-formulas'])->group(function () {
        Route::post('/test', [\App\Http\Controllers\FormulaController::class, 'test']);
        Route::post('/{formula}/duplicate', [\App\Http\Controllers\FormulaController::class, 'duplicate']);
        Route::get('/product/{product}', [\App\Http\Controllers\FormulaController::class, 'byProduct']);
        Route::get('/global', [\App\Http\Controllers\FormulaController::class, 'global']);
        Route::get('/{formula}/export', [\App\Http\Controllers\FormulaController::class, 'export']);
        Route::post('/import', [\App\Http\Controllers\FormulaController::class, 'import']);
        Route::post('/suggestions', [\App\Http\Controllers\FormulaController::class, 'suggestions']);
        Route::get('/templates', [\App\Http\Controllers\FormulaController::class, 'templates']);
        Route::get('/product-examples', [\App\Http\Controllers\FormulaController::class, 'productExamples']);
        Route::get('/products/{product}/fields', [\App\Http\Controllers\FormulaController::class, 'getProductFields']);
    });
    
    Route::get('/data-ingestion', [DataIngestionController::class, 'index'])
        ->name('data-ingestion.index')
        ->middleware('permission:view-products');
    
    Route::get('/dashboards', [\App\Http\Controllers\DashboardController::class, 'list'])
        ->name('dashboards.index')
        ->middleware('permission:view-products');
    
    Route::get('/dashboards/create', [\App\Http\Controllers\DashboardController::class, 'create'])
        ->name('dashboards.create')
        ->middleware('permission:create-products');
    
    Route::get('/dashboards/{dashboard}', [\App\Http\Controllers\DashboardController::class, 'show'])
        ->name('dashboards.show')
        ->middleware('permission:view-products');
    
    Route::get('/dashboards/{dashboard}/simple', [\App\Http\Controllers\DashboardController::class, 'showSimple'])
        ->name('dashboards.simple')
        ->middleware('permission:view-products');
    
    Route::get('/dashboards/consolidated', [\App\Http\Controllers\DashboardController::class, 'consolidated'])
        ->name('dashboards.consolidated')
        ->middleware('permission:view-products');
    
    Route::get('/dashboards/{dashboard}/edit', [\App\Http\Controllers\DashboardController::class, 'edit'])
        ->name('dashboards.edit')
        ->middleware('permission:create-products');

    Route::post('/dashboards', [\App\Http\Controllers\DashboardController::class, 'store'])
        ->name('dashboards.store')
        ->middleware('permission:create-dashboards');

    Route::put('/dashboards/{dashboard}', [\App\Http\Controllers\DashboardController::class, 'update'])
        ->name('dashboards.update')
        ->middleware('permission:create-products');
    
    Route::get('/products/{product}/dashboard', [\App\Http\Controllers\DashboardController::class, 'productDashboard'])
        ->name('products.dashboard')
        ->middleware('permission:view-products');
    
    Route::get('/queue-monitor', [QueueMonitorController::class, 'index'])
        ->name('queue-monitor.index')
        ->middleware('permission:view-products'); // Adjust permission as needed

    Route::get('/data-import', [\App\Http\Controllers\DataImportController::class, 'index'])
        ->name('data-import.index')
        ->middleware('permission:create-products');
        
    Route::post('/data-import', [\App\Http\Controllers\DataImportController::class, 'store'])
        ->name('data-import.store')
        ->middleware('permission:create-products');
});

Route::get('/test/consolidated', [\App\Http\Controllers\DashboardController::class, 'consolidated'])
    ->name('test.consolidated');

// Temporary test routes without authentication
Route::get('/test/dashboard/{dashboard}', [\App\Http\Controllers\DashboardController::class, 'show'])
    ->name('test.dashboard.show');

Route::get('/test/api/dashboards/{dashboard}', [\App\Http\Controllers\Api\DashboardApiController::class, 'show'])
    ->name('test.api.dashboard.show');

Route::get('/test/api/dashboards/{dashboard}/widgets/{widget}/data', function($dashboardId, $widgetId) {
    $widget = \App\Models\Widget::findOrFail($widgetId);
    $controller = new \App\Http\Controllers\Api\DashboardApiController(
        app(\App\Services\ChartDataService::class),
        app(\App\Services\DashboardFilterService::class),
        app(\App\Services\CurrencyService::class)
    );
    
    $result = $controller->getWidgetData($widget, []);
    return response()->json([
        'success' => !isset($result['error']),
        'data' => $result
    ]);
})->name('test.api.widget.data');

// Temporary test route to debug dashboard widgets
Route::get('/test/dashboard/{id}', function($id) {
    $dashboard = App\Models\Dashboard::with('widgets')->find($id);
    if (!$dashboard) {
        return response()->json(['error' => 'Dashboard not found'], 404);
    }
    
    $widgetData = [];
    foreach ($dashboard->widgets as $widget) {
        $dataSource = $widget->data_source;
        $configuration = $widget->configuration;
        
        // Parse data_source if it's a JSON string
        if (is_string($dataSource)) {
            $dataSource = json_decode($dataSource, true);
        }
        
        $widgetData[] = [
            'widget_id' => $widget->id,
            'title' => $widget->title,
            'type' => $widget->type,
            'configuration' => $configuration,
            'data_source' => $dataSource,
            'api_response' => null
        ];
    }
    
    return response()->json([
        'dashboard' => [
            'id' => $dashboard->id,
            'name' => $dashboard->name,
            'product_id' => $dashboard->product_id
        ],
        'widgets' => $widgetData
    ], 200, [], JSON_PRETTY_PRINT);
})->name('test.dashboard.debug');


