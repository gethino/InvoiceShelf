<?php

use App\Domains\Reporting\Queries\CustomerStatementQuery;
use App\Domains\Reporting\Rendering\CustomerStatementPdfRenderer;
use App\Domains\Reporting\ReportingServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

test('the reporting domain owns report queries rendering and authorization', function () {
    expect(app()->getProviders(ReportingServiceProvider::class))->toHaveCount(1)
        ->and(app(CustomerStatementQuery::class))->toBeInstanceOf(CustomerStatementQuery::class)
        ->and(app(CustomerStatementPdfRenderer::class))->toBeInstanceOf(CustomerStatementPdfRenderer::class)
        ->and(Gate::has('view report'))->toBeTrue();

    expect(class_exists('App\\Policies\\ReportPolicy'))->toBeFalse()
        ->and(class_exists('App\\Services\\CustomerStatementService'))->toBeFalse()
        ->and(class_exists('App\\Services\\CustomerStatementPdfService'))->toBeFalse()
        ->and(class_exists('App\\Http\\Requests\\CustomerStatementRequest'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Company\\Report\\CustomerSalesReportController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Company\\Report\\CustomerStatementReportController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Company\\Report\\ExpensesReportController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Company\\Report\\ItemSalesReportController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Company\\Report\\ProfitLossReportController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Company\\Report\\TaxSummaryReportController'))->toBeFalse();
});

test('the reporting domain preserves authenticated report routes', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => preg_match(
            '#^reports/(?:customers/[^/]+/statement|sales/(?:customers|items)/[^/]+|expenses/[^/]+|tax-summary/[^/]+|profit-loss/[^/]+)$#',
            $route->uri(),
        ) === 1)
        ->reject(fn ($route): bool => str_contains($route->uri(), 'download-receipt') || str_contains($route->uri(), 'receipt'))
        ->keyBy(fn ($route): string => implode('|', $route->methods()).' '.$route->uri());

    expect($routes->keys()->sort()->values()->all())->toBe(collect([
        'GET|HEAD reports/customers/{customer}/statement',
        'GET|HEAD reports/expenses/{hash}',
        'GET|HEAD reports/profit-loss/{hash}',
        'GET|HEAD reports/sales/customers/{hash}',
        'GET|HEAD reports/sales/items/{hash}',
        'GET|HEAD reports/tax-summary/{hash}',
    ])->sort()->values()->all());

    foreach ($routes as $route) {
        expect($route->getActionName())
            ->toStartWith('App\\Domains\\Reporting\\Http\\Controllers\\')
            ->and($route->gatherMiddleware())->toContain('web', 'auth:sanctum');
    }
});
