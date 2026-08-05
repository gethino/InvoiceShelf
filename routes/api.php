<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CountriesController;
use App\Http\Controllers\Company\Customer\CustomersController;
use App\Http\Controllers\Company\Customer\CustomerStatsController;
use App\Http\Controllers\Company\Dashboard\DashboardController;
use App\Http\Controllers\Company\Estimate\EstimatesController;
use App\Http\Controllers\Company\Estimate\EstimateTemplatesController;
use App\Http\Controllers\Company\General\BootstrapController;
use App\Http\Controllers\Company\General\ConfigController;
use App\Http\Controllers\Company\General\FormatsController;
use App\Http\Controllers\Company\General\SearchController;
use App\Http\Controllers\Company\General\SerialNumberController;
use App\Http\Controllers\Company\Invoice\InvoicesController;
use App\Http\Controllers\Company\Invoice\InvoiceTemplatesController;
use App\Http\Controllers\Company\RecurringInvoice\RecurringInvoiceController;
use App\Http\Controllers\Company\RecurringInvoice\RecurringInvoiceFrequencyController;
use App\Http\Controllers\CustomerPortal\Auth\ForgotPasswordController as AuthForgotPasswordController;
use App\Http\Controllers\CustomerPortal\Auth\ResetPasswordController as AuthResetPasswordController;
use App\Http\Controllers\CustomerPortal\Estimate\AcceptEstimateController as CustomerAcceptEstimateController;
use App\Http\Controllers\CustomerPortal\Estimate\EstimatesController as CustomerEstimatesController;
use App\Http\Controllers\CustomerPortal\General\BootstrapController as CustomerBootstrapController;
use App\Http\Controllers\CustomerPortal\General\DashboardController as CustomerDashboardController;
use App\Http\Controllers\CustomerPortal\General\ProfileController as CustomerProfileController;
use App\Http\Controllers\CustomerPortal\Invoice\InvoicesController as CustomerInvoicesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ping
// ----------------------------------

Route::get('ping', function () {
    return response()->json([
        'success' => 'invoiceshelf-self-hosted',
    ]);
})->name('ping');

// Version 1 endpoints
// --------------------------------------
Route::prefix('/v1')->group(function () {

    // App version
    // ----------------------------------

    require app_path('Platform/Operations/routes/version.php');

    // Authentication & Password Reset
    // ----------------------------------

    require app_path('Domains/Accounts/routes/public.php');

    // Countries
    // ----------------------------------

    Route::get('/countries', CountriesController::class);

    // Onboarding
    // ----------------------------------

    Route::middleware(['redirect-if-installed'])->prefix('installation')->group(function () {
        require app_path('Platform/Operations/Installation/routes/api.php');
        require app_path('Platform/Ai/routes/installer.php');
    });

    // Super Admin
    // ----------------------------------

    Route::middleware(['auth:sanctum', 'super-admin'])->prefix('super-admin')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);
        require app_path('Domains/Accounts/routes/admin.php');
    });

    // Stop impersonation - uses auth:sanctum only (the impersonated user's token, not super-admin)
    Route::middleware(['auth:sanctum'])->prefix('super-admin')->group(function () {
        require app_path('Domains/Accounts/routes/impersonation.php');
    });

    Route::middleware(['auth:sanctum', 'company'])->group(function () {
        Route::middleware(['bouncer'])->group(function () {
            require app_path('Domains/Accounts/routes/company.php');

            // Bootstrap
            // ----------------------------------

            Route::get('/bootstrap', BootstrapController::class);

            // Currencies
            // ----------------------------------

            require app_path('Domains/Money/routes/company.php');

            // Dashboard
            // ----------------------------------

            Route::get('/dashboard', DashboardController::class);

            // Search users
            // ----------------------------------

            Route::get('/search', SearchController::class);

            Route::get('/search/user', [SearchController::class, 'users']);

            // MISC
            // ----------------------------------

            Route::get('/config', ConfigController::class);

            Route::get('/timezones', [FormatsController::class, 'timezones']);

            Route::get('/date/formats', [FormatsController::class, 'dateFormats']);

            Route::get('/time/formats', [FormatsController::class, 'timeFormats']);

            Route::get('/next-number', [SerialNumberController::class, 'nextNumber']);

            Route::get('/number-placeholders', [SerialNumberController::class, 'placeholders']);

            Route::get('/current-company', [BootstrapController::class, 'currentCompany']);

            // Customers
            // ----------------------------------

            Route::post('/customers/delete', [CustomersController::class, 'delete']);

            Route::get('customers/{customer}/stats', CustomerStatsController::class);

            require app_path('Domains/Reporting/routes/company.php');
            Route::resource('customers', CustomersController::class);

            // Items
            // ----------------------------------

            require app_path('Domains/Catalog/routes/company.php');

            // Invoices
            // -------------------------------------------------

            Route::get('/invoices/{invoice}/send/preview', [InvoicesController::class, 'sendPreview']);

            Route::post('/invoices/{invoice}/send', [InvoicesController::class, 'send']);

            Route::post('/invoices/{invoice}/clone', [InvoicesController::class, 'clone']);

            Route::post('/invoices/{invoice}/convert-to-estimate', [InvoicesController::class, 'convertToEstimate']);

            Route::post('/invoices/{invoice}/credit-note', [InvoicesController::class, 'createCreditNote']);

            Route::post('/invoices/{invoice}/status', [InvoicesController::class, 'changeStatus']);

            Route::post('/invoices/delete', [InvoicesController::class, 'delete']);

            Route::get('/invoices/templates', InvoiceTemplatesController::class);

            Route::apiResource('invoices', InvoicesController::class);

            // Recurring Invoice
            // -------------------------------------------------

            Route::get('/recurring-invoice-frequency', RecurringInvoiceFrequencyController::class);

            Route::post('/recurring-invoices/delete', [RecurringInvoiceController::class, 'delete']);

            Route::apiResource('recurring-invoices', RecurringInvoiceController::class);

            // Estimates
            // -------------------------------------------------

            Route::get('/estimates/{estimate}/send/preview', [EstimatesController::class, 'sendPreview']);

            Route::post('/estimates/{estimate}/send', [EstimatesController::class, 'send']);

            Route::post('/estimates/{estimate}/clone', [EstimatesController::class, 'clone']);

            Route::post('/estimates/{estimate}/status', [EstimatesController::class, 'changeStatus']);

            Route::post('/estimates/{estimate}/convert-to-invoice', [EstimatesController::class, 'convertToInvoice']);

            Route::get('/estimates/templates', EstimateTemplatesController::class);

            Route::post('/estimates/delete', [EstimatesController::class, 'delete']);

            Route::apiResource('estimates', EstimatesController::class);

            // Expenses
            // ----------------------------------

            require app_path('Domains/Purchases/routes/company.php');

            // Payments
            // ----------------------------------

            require app_path('Domains/Receivables/routes/company.php');

            // Custom fields
            // ----------------------------------

            require app_path('Domains/Metadata/routes/company.php');

            // Backup & Disk
            // ----------------------------------

            require app_path('Platform/Storage/routes/company.php');

            // PDF rendering and fonts
            // ----------------------------------

            require app_path('Platform/Pdf/routes/admin.php');

            require app_path('Platform/Operations/routes/settings.php');

            // Mails
            // ----------------------------------

            require app_path('Platform/Mail/routes/company.php');

            require app_path('Platform/Ai/routes/company.php');

            // Tax Types
            // ----------------------------------

            require app_path('Domains/Taxation/routes/company.php');

        });

        // Self Update
        // ----------------------------------
        // Disabled inside the official Docker image — containers upgrade via
        // `docker compose pull`, not the in-app updater (see EnsureNotContainerized).

        require app_path('Platform/Operations/routes/updater.php');

        require app_path('Domains/Accounts/routes/management.php');

    });

    Route::prefix('/{company:slug}/customer')->group(function () {

        // Authentication & Password Reset
        // ----------------------------------

        Route::prefix('auth')->group(function () {

            // Send reset password mail
            Route::post('password/email', [AuthForgotPasswordController::class, 'sendResetLinkEmail']);

            // handle reset password form process
            Route::post('reset/password', [AuthResetPasswordController::class, 'reset'])->name('customer.password.reset');
        });

        // Invoices, Estimates, Payments and Expenses endpoints
        // -------------------------------------------------------

        Route::middleware(['auth:customer', 'customer-portal'])->group(function () {
            Route::get('/bootstrap', CustomerBootstrapController::class);

            Route::get('/dashboard', CustomerDashboardController::class);

            Route::get('invoices', [CustomerInvoicesController::class, 'index']);

            Route::get('invoices/{id}', [CustomerInvoicesController::class, 'show']);

            Route::post('/estimate/{estimate}/status', CustomerAcceptEstimateController::class);

            Route::get('estimates', [CustomerEstimatesController::class, 'index']);

            Route::get('estimates/{id}', [CustomerEstimatesController::class, 'show']);

            require app_path('Domains/Receivables/routes/customer.php');

            require app_path('Domains/Purchases/routes/customer.php');

            Route::post('/profile', [CustomerProfileController::class, 'updateProfile']);

            Route::get('/me', [CustomerProfileController::class, 'getUser']);

            Route::get('/countries', CountriesController::class);
        });
    });
});

require app_path('Platform/Operations/routes/webhooks.php');
