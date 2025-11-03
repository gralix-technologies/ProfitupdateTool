<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DataIngestionController;
use App\Http\Controllers\Api\PortfolioConfigurationController;



Route::middleware(['web', 'auth'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::apiResource('products', ProductController::class)
        ->names([
            'index' => 'api.products.index',
            'store' => 'api.products.store',
            'show' => 'api.products.show',
            'update' => 'api.products.update',
            'destroy' => 'api.products.destroy'
        ])
        ->middleware('permission:view-products');

    Route::get('products-active', [ProductController::class, 'active'])
        ->name('api.products.active')
        ->middleware('permission:view-products');

    Route::post('products/validate-schema', [ProductController::class, 'validateSchema'])
        ->name('api.products.validate-schema')
        ->middleware('permission:create-products');

    // Portfolio Configuration API Routes
    Route::prefix('portfolio-config')->name('api.portfolio-config.')->group(function () {
        Route::get('configuration', [PortfolioConfigurationController::class, 'getConfiguration'])
            ->name('get')
            ->middleware('permission:view-portfolio-config');
        
        Route::post('update-formula', [PortfolioConfigurationController::class, 'updateFormula'])
            ->name('update-formula')
            ->middleware('permission:manage-portfolio-config');
        
        Route::post('test-formula', [PortfolioConfigurationController::class, 'testFormula'])
            ->name('test-formula')
            ->middleware('permission:view-portfolio-config');
        
        Route::get('calculation-history', [PortfolioConfigurationController::class, 'getCalculationHistory'])
            ->name('history')
            ->middleware('permission:view-portfolio-config');
    });

    Route::post('data/upload', [DataIngestionController::class, 'uploadFile'])
        ->name('data.upload')
        ->middleware('permission:create-products');

    Route::post('data/validate-file', [DataIngestionController::class, 'validateFile'])
        ->name('data.validate-file')
        ->middleware('permission:view-products');

    Route::get('data/progress/{importId}', [DataIngestionController::class, 'getProgress'])
        ->name('data.progress')
        ->middleware('permission:view-products');

    Route::get('data/imports', [DataIngestionController::class, 'getUserImports'])
        ->name('data.imports')
        ->middleware('permission:view-products');

    Route::post('data/cancel/{importId}', [DataIngestionController::class, 'cancelImport'])
        ->name('data.cancel')
        ->middleware('permission:create-products');

    Route::get('data/sample-file', [DataIngestionController::class, 'downloadSampleFile'])
        ->name('data.sample-file')
        ->middleware('permission:view-products');

    Route::get('data/field-requirements', [DataIngestionController::class, 'getFieldRequirements'])
        ->name('data.field-requirements')
        ->middleware('permission:view-products');

    Route::get('data/field-details', [DataIngestionController::class, 'getFieldDetails'])
        ->name('data.field-details')
        ->middleware('permission:view-products');

    // Specific formula routes (must come before resource routes to avoid conflicts)
    Route::get('formulas/field-suggestions', [\App\Http\Controllers\FormulaController::class, 'fieldSuggestions'])
        ->name('api.formulas.field-suggestions')
        ->middleware('permission:view-formulas');

    Route::get('formulas/templates/list', [\App\Http\Controllers\FormulaController::class, 'templates'])
        ->name('formulas.templates')
        ->middleware('permission:view-products');

    Route::get('formulas/products/{product}/fields', [\App\Http\Controllers\FormulaController::class, 'getProductFields'])
        ->name('api.formulas.product-fields')
        ->middleware('permission:view-formulas');

    Route::post('formulas/test', [\App\Http\Controllers\FormulaController::class, 'test'])
        ->name('api.formulas.test')
        ->middleware('permission:view-products');

    Route::get('formulas/product/{product}', [\App\Http\Controllers\FormulaController::class, 'byProduct'])
        ->name('api.formulas.by-product')
        ->middleware('permission:view-products');

    Route::get('formulas/global/list', [\App\Http\Controllers\FormulaController::class, 'global'])
        ->name('api.formulas.global')
        ->middleware('permission:view-products');

    Route::post('formulas/suggestions', [\App\Http\Controllers\FormulaController::class, 'suggestions'])
        ->name('api.formulas.suggestions')
        ->middleware('permission:view-products');

    Route::post('formulas/import', [\App\Http\Controllers\FormulaController::class, 'import'])
        ->name('api.formulas.import')
        ->middleware('permission:create-products');

    // Resource routes (must come after specific routes)
    Route::apiResource('formulas', \App\Http\Controllers\FormulaController::class)
        ->names([
            'index' => 'api.formulas.index',
            'store' => 'api.formulas.store',
            'show' => 'api.formulas.show',
            'update' => 'api.formulas.update',
            'destroy' => 'api.formulas.destroy'
        ])
        ->middleware('permission:view-products');

    Route::post('formulas/{formula}/duplicate', [\App\Http\Controllers\FormulaController::class, 'duplicate'])
        ->name('api.formulas.duplicate')
        ->middleware('permission:create-products');

    Route::get('formulas/{formula}/export', [\App\Http\Controllers\FormulaController::class, 'export'])
        ->name('api.formulas.export')
        ->middleware('permission:view-products');


    Route::apiResource('dashboards', \App\Http\Controllers\DashboardController::class)
        ->names([
            'index' => 'api.dashboards.index',
            'store' => 'api.dashboards.store',
            'show' => 'api.dashboards.show',
            'update' => 'api.dashboards.update',
            'destroy' => 'api.dashboards.destroy'
        ])
        ->middleware('permission:view-products');

    Route::post('dashboards/{dashboard}/layout', [\App\Http\Controllers\DashboardController::class, 'updateLayout'])
        ->name('api.dashboards.update-layout')
        ->middleware('permission:create-products');

    Route::get('dashboards/{dashboard}/chart-data/{widgetId}', [\App\Http\Controllers\DashboardController::class, 'getChartData'])
        ->name('api.dashboards.chart-data')
        ->middleware('permission:view-products');

    Route::post('dashboards/{dashboard}/duplicate', [\App\Http\Controllers\DashboardController::class, 'duplicate'])
        ->name('api.dashboards.duplicate')
        ->middleware('permission:create-products');

    Route::post('dashboards/{dashboard}/filters', [\App\Http\Controllers\DashboardController::class, 'applyFilters'])
        ->name('api.dashboards.apply-filters')
        ->middleware('permission:view-products');

    Route::get('dashboards/{dashboard}/export/pdf', [\App\Http\Controllers\DashboardController::class, 'exportPdf'])
        ->name('api.dashboards.export-pdf')
        ->middleware('permission:view-products');

    Route::get('dashboards/{dashboard}/export/csv', [\App\Http\Controllers\DashboardController::class, 'exportCsv'])
        ->name('api.dashboards.export-csv')
        ->middleware('permission:view-products');

    Route::get('dashboards/export/formats', [\App\Http\Controllers\DashboardController::class, 'getExportFormats'])
        ->name('api.dashboards.export-formats')
        ->middleware('permission:view-products');

    Route::post('dashboards/{dashboard}/widgets', [\App\Http\Controllers\WidgetController::class, 'store'])
        ->name('api.widgets.store')
        ->middleware('permission:create-products');

    Route::put('dashboards/{dashboard}/widgets/{widget}', [\App\Http\Controllers\WidgetController::class, 'update'])
        ->name('api.widgets.update')
        ->middleware('permission:create-products');

    Route::delete('dashboards/{dashboard}/widgets/{widget}', [\App\Http\Controllers\WidgetController::class, 'destroy'])
        ->name('api.widgets.destroy')
        ->middleware('permission:create-products');

    Route::post('dashboards/{dashboard}/widgets/{widget}/position', [\App\Http\Controllers\WidgetController::class, 'updatePosition'])
        ->name('api.widgets.update-position')
        ->middleware('permission:create-products');

    Route::get('dashboards/{dashboard}/widgets/{widget}/data', [\App\Http\Controllers\Api\DashboardApiController::class, 'getWidgetDataById'])
        ->name('api.widgets.data')
        ->middleware('permission:view-products');

    Route::post('dashboards/{dashboard}/widgets/{widget}/duplicate', [\App\Http\Controllers\WidgetController::class, 'duplicate'])
        ->name('api.widgets.duplicate')
        ->middleware('permission:create-products');

    Route::apiResource('customers', \App\Http\Controllers\CustomerController::class)
        ->only(['index', 'show'])
        ->names([
            'index' => 'api.customers.index',
            'show' => 'api.customers.show'
        ])
        ->middleware('permission:view-products');

    Route::get('customers/{customer}/profitability', [\App\Http\Controllers\CustomerController::class, 'profitability'])
        ->name('api.customers.profitability')
        ->middleware('permission:view-products');

    Route::get('customers/{customer}/portfolio', [\App\Http\Controllers\CustomerController::class, 'portfolio'])
        ->name('api.customers.portfolio')
        ->middleware('permission:view-products');

    Route::post('customers/{customer}/update-metrics', [\App\Http\Controllers\CustomerController::class, 'updateMetrics'])
        ->name('api.customers.update-metrics')
        ->middleware('permission:create-products');

    Route::get('customers/search/query', [\App\Http\Controllers\CustomerController::class, 'search'])
        ->name('api.customers.search')
        ->middleware('permission:view-products');

    Route::get('customers/analytics/insights', [\App\Http\Controllers\CustomerController::class, 'insights'])
        ->name('api.customers.insights')
        ->middleware('permission:view-products');

    Route::get('customers/analytics/top-profitable', [\App\Http\Controllers\CustomerController::class, 'topProfitable'])
        ->name('api.customers.top-profitable')
        ->middleware('permission:view-products');

    Route::get('customers/analytics/branch-profitability', [\App\Http\Controllers\CustomerController::class, 'branchProfitability'])
        ->name('api.customers.branch-profitability')
        ->middleware('permission:view-products');

    Route::prefix('products/{product}/data')->name('api.products.data.')->group(function () {
        Route::get('/summary', [\App\Http\Controllers\Api\GenericProductController::class, 'summary'])
            ->name('summary')
            ->middleware('permission:view-products');
        
        Route::get('/export', [\App\Http\Controllers\Api\GenericProductController::class, 'export'])
            ->name('export')
            ->middleware('permission:view-products');
        
        Route::get('/', [\App\Http\Controllers\Api\GenericProductController::class, 'index'])
            ->name('index')
            ->middleware('permission:view-products');
        
        Route::post('/', [\App\Http\Controllers\Api\GenericProductController::class, 'store'])
            ->name('store')
            ->middleware('permission:create-products');
        
        Route::get('/{record}', [\App\Http\Controllers\Api\GenericProductController::class, 'show'])
            ->name('show')
            ->middleware('permission:view-products');
        
        Route::put('/{record}', [\App\Http\Controllers\Api\GenericProductController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit-products');
        
        Route::delete('/{record}', [\App\Http\Controllers\Api\GenericProductController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:delete-products');
    });

    Route::apiResource('product-data', \App\Http\Controllers\ProductDataController::class)
        ->names([
            'index' => 'api.product-data.index',
            'store' => 'api.product-data.store',
            'show' => 'api.product-data.show',
            'update' => 'api.product-data.update',
            'destroy' => 'api.product-data.destroy'
        ])
        ->middleware('permission:view-products');

    Route::post('product-data/bulk', [\App\Http\Controllers\ProductDataController::class, 'bulkStore'])
        ->name('api.product-data.bulk')
        ->middleware('permission:create-products');

    Route::get('product-data/summary', [\App\Http\Controllers\ProductDataController::class, 'summary'])
        ->name('api.product-data.summary')
        ->middleware('permission:view-products');

    Route::post('product-data/validate', [\App\Http\Controllers\ProductDataController::class, 'validateData'])
        ->name('api.product-data.validate')
        ->middleware('permission:view-products');

    Route::apiResource('users', \App\Http\Controllers\UserController::class)
        ->names([
            'index' => 'api.users.index',
            'store' => 'api.users.store',
            'show' => 'api.users.show',
            'update' => 'api.users.update',
            'destroy' => 'api.users.destroy'
        ])
        ->middleware('permission:manage-users');

    Route::put('users/{user}/roles', [\App\Http\Controllers\UserController::class, 'updateRoles'])
        ->name('api.users.update-roles')
        ->middleware('permission:manage-users');

    Route::get('users/{user}/activity', [\App\Http\Controllers\UserController::class, 'activity'])
        ->name('api.users.activity')
        ->middleware('permission:view-users');

    Route::get('users/search/query', [\App\Http\Controllers\UserController::class, 'search'])
        ->name('api.users.search')
        ->middleware('permission:view-users');

    Route::get('users/statistics', [\App\Http\Controllers\UserController::class, 'statistics'])
        ->name('api.users.statistics')
        ->middleware('permission:view-users');

    Route::apiResource('configurations', \App\Http\Controllers\ConfigurationController::class)
        ->names([
            'index' => 'api.configurations.index',
            'store' => 'api.configurations.store',
            'show' => 'api.configurations.show',
            'update' => 'api.configurations.update',
            'destroy' => 'api.configurations.destroy'
        ])
        ->middleware('permission:manage-configurations');

    Route::get('configurations/value/{key}', [\App\Http\Controllers\ConfigurationController::class, 'getValue'])
        ->name('api.configurations.value')
        ->middleware('permission:view-configurations');

    Route::post('configurations/set-value', [\App\Http\Controllers\ConfigurationController::class, 'setValue'])
        ->name('api.configurations.set-value')
        ->middleware('permission:manage-configurations');

    Route::post('configurations/multiple', [\App\Http\Controllers\ConfigurationController::class, 'getMultiple'])
        ->name('api.configurations.multiple')
        ->middleware('permission:view-configurations');

    Route::post('configurations/bulk-update', [\App\Http\Controllers\ConfigurationController::class, 'bulkUpdate'])
        ->name('api.configurations.bulk-update')
        ->middleware('permission:manage-configurations');

    Route::apiResource('pd-lookups', \App\Http\Controllers\PdLookupController::class)
        ->names([
            'index' => 'api.pd-lookups.index',
            'store' => 'api.pd-lookups.store',
            'show' => 'api.pd-lookups.show',
            'update' => 'api.pd-lookups.update',
            'destroy' => 'api.pd-lookups.destroy'
        ])
        ->middleware('permission:manage-lookups');

    Route::get('pd-lookups/rating/{rating}', [\App\Http\Controllers\PdLookupController::class, 'getByRating'])
        ->name('api.pd-lookups.by-rating')
        ->middleware('permission:view-lookups');

    Route::post('pd-lookups/bulk', [\App\Http\Controllers\PdLookupController::class, 'bulkStore'])
        ->name('api.pd-lookups.bulk')
        ->middleware('permission:manage-lookups');

    Route::get('pd-lookups/credit-ratings', [\App\Http\Controllers\PdLookupController::class, 'getCreditRatings'])
        ->name('api.pd-lookups.credit-ratings')
        ->middleware('permission:view-lookups');

    Route::apiResource('lgd-lookups', \App\Http\Controllers\LgdLookupController::class)
        ->names([
            'index' => 'api.lgd-lookups.index',
            'store' => 'api.lgd-lookups.store',
            'show' => 'api.lgd-lookups.show',
            'update' => 'api.lgd-lookups.update',
            'destroy' => 'api.lgd-lookups.destroy'
        ])
        ->middleware('permission:manage-lookups');

    Route::get('lgd-lookups/collateral/{type}', [\App\Http\Controllers\LgdLookupController::class, 'getByCollateralType'])
        ->name('api.lgd-lookups.by-collateral')
        ->middleware('permission:view-lookups');

    Route::post('lgd-lookups/bulk', [\App\Http\Controllers\LgdLookupController::class, 'bulkStore'])
        ->name('api.lgd-lookups.bulk')
        ->middleware('permission:manage-lookups');

    Route::get('lgd-lookups/collateral-types', [\App\Http\Controllers\LgdLookupController::class, 'getCollateralTypes'])
        ->name('api.lgd-lookups.collateral-types')
        ->middleware('permission:view-lookups');

    Route::apiResource('risk-weights', \App\Http\Controllers\RiskWeightController::class)
        ->names([
            'index' => 'api.risk-weights.index',
            'store' => 'api.risk-weights.store',
            'show' => 'api.risk-weights.show',
            'update' => 'api.risk-weights.update',
            'destroy' => 'api.risk-weights.destroy'
        ])
        ->middleware('permission:manage-lookups');

    Route::get('risk-weights/rating/{rating}/collateral/{type}', [\App\Http\Controllers\RiskWeightController::class, 'getByRatingAndCollateral'])
        ->name('api.risk-weights.by-rating-collateral')
        ->middleware('permission:view-lookups');

    Route::post('risk-weights/bulk', [\App\Http\Controllers\RiskWeightController::class, 'bulkStore'])
        ->name('api.risk-weights.bulk')
        ->middleware('permission:manage-lookups');

    Route::get('risk-weights/credit-ratings', [\App\Http\Controllers\RiskWeightController::class, 'getCreditRatings'])
        ->name('api.risk-weights.credit-ratings')
        ->middleware('permission:view-lookups');

    Route::get('risk-weights/collateral-types', [\App\Http\Controllers\RiskWeightController::class, 'getCollateralTypes'])
        ->name('api.risk-weights.collateral-types')
        ->middleware('permission:view-lookups');

    Route::get('audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->name('api.audit-logs.index')
        ->middleware('permission:view-audit-logs');

    Route::get('audit-logs/{auditLog}', [\App\Http\Controllers\AuditLogController::class, 'show'])
        ->name('api.audit-logs.show')
        ->middleware('permission:view-audit-logs');

    Route::get('audit-logs/model/{model}/{modelId}', [\App\Http\Controllers\AuditLogController::class, 'getByModel'])
        ->name('api.audit-logs.by-model')
        ->middleware('permission:view-audit-logs');

    Route::get('audit-logs/user/{user}', [\App\Http\Controllers\AuditLogController::class, 'getByUser'])
        ->name('api.audit-logs.by-user')
        ->middleware('permission:view-audit-logs');

    Route::get('audit-logs/statistics', [\App\Http\Controllers\AuditLogController::class, 'statistics'])
        ->name('api.audit-logs.statistics')
        ->middleware('permission:view-audit-logs');

    Route::get('audit-logs/actions', [\App\Http\Controllers\AuditLogController::class, 'getActions'])
        ->name('api.audit-logs.actions')
        ->middleware('permission:view-audit-logs');

    Route::get('audit-logs/models', [\App\Http\Controllers\AuditLogController::class, 'getModels'])
        ->name('api.audit-logs.models')
        ->middleware('permission:view-audit-logs');

    Route::post('audit-logs/export', [\App\Http\Controllers\AuditLogController::class, 'export'])
        ->name('api.audit-logs.export')
        ->middleware('permission:view-audit-logs');

    Route::get('import-errors', [\App\Http\Controllers\ImportErrorController::class, 'index'])
        ->name('api.import-errors.index')
        ->middleware('permission:view-import-errors');

    Route::get('import-errors/{importError}', [\App\Http\Controllers\ImportErrorController::class, 'show'])
        ->name('api.import-errors.show')
        ->middleware('permission:view-import-errors');

    Route::get('import-errors/session/{sessionId}', [\App\Http\Controllers\ImportErrorController::class, 'getBySession'])
        ->name('api.import-errors.by-session')
        ->middleware('permission:view-import-errors');

    Route::get('import-errors/product/{product}', [\App\Http\Controllers\ImportErrorController::class, 'getByProduct'])
        ->name('api.import-errors.by-product')
        ->middleware('permission:view-import-errors');

    Route::get('import-errors/statistics', [\App\Http\Controllers\ImportErrorController::class, 'statistics'])
        ->name('api.import-errors.statistics')
        ->middleware('permission:view-import-errors');

    Route::get('import-errors/error-types', [\App\Http\Controllers\ImportErrorController::class, 'getErrorTypes'])
        ->name('api.import-errors.error-types')
        ->middleware('permission:view-import-errors');

    Route::get('import-errors/sessions', [\App\Http\Controllers\ImportErrorController::class, 'getSessions'])
        ->name('api.import-errors.sessions')
        ->middleware('permission:view-import-errors');

    Route::delete('import-errors/session/{sessionId}/clear', [\App\Http\Controllers\ImportErrorController::class, 'clearSession'])
        ->name('api.import-errors.clear-session')
        ->middleware('permission:manage-import-errors');

    Route::delete('import-errors/product/{product}/clear', [\App\Http\Controllers\ImportErrorController::class, 'clearProduct'])
        ->name('api.import-errors.clear-product')
        ->middleware('permission:manage-import-errors');

    Route::post('import-errors/export', [\App\Http\Controllers\ImportErrorController::class, 'export'])
        ->name('api.import-errors.export')
        ->middleware('permission:view-import-errors');
});

Route::get('test', [\App\Http\Controllers\Api\TestApiController::class, 'test']);
Route::get('test/kpi/{type}', [\App\Http\Controllers\Api\TestApiController::class, 'getKpi']);
Route::get('test/chart/{type}', [\App\Http\Controllers\Api\TestApiController::class, 'getChart']);

Route::get('dashboards/{dashboard}/filter-options', [\App\Http\Controllers\Api\DashboardApiController::class, 'getFilterOptions'])
    ->name('api.dashboards.filter-options');

Route::get('dashboards/{dashboard}/data', [\App\Http\Controllers\Api\DashboardApiController::class, 'show'])
    ->name('api.dashboards.data');

Route::get('dashboards/{dashboard}/widgets/{widget}/data', [\App\Http\Controllers\Api\DashboardApiController::class, 'getWidgetDataById'])
    ->name('api.widgets.data-simple');

Route::get('dashboards/{dashboard}/widgets', [\App\Http\Controllers\Api\DashboardApiController::class, 'getDashboardWidgets'])
    ->name('api.dashboards.widgets');

// Currency API endpoints
Route::get('currency/base', [\App\Http\Controllers\Api\CurrencyController::class, 'getBaseCurrency'])
    ->name('api.currency.base');

Route::get('currency/available', [\App\Http\Controllers\Api\CurrencyController::class, 'getAvailableCurrencies'])
    ->name('api.currency.available');

Route::get('currency/format/{amount}', [\App\Http\Controllers\Api\CurrencyController::class, 'formatAmount'])
    ->name('api.currency.format');



