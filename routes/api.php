<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CompaniesController;
use App\Http\Controllers\Admin\CountriesController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Company\Auth\AuthController;
use App\Http\Controllers\Company\Auth\ForgotPasswordController;
use App\Http\Controllers\Company\Auth\InvitationRegistrationController;
use App\Http\Controllers\Company\Auth\ResetPasswordController;
use App\Http\Controllers\Company\Customer\CustomersController;
use App\Http\Controllers\Company\Customer\CustomerStatementController;
use App\Http\Controllers\Company\Customer\CustomerStatsController;
use App\Http\Controllers\Company\Customer\SendCustomerStatementController;
use App\Http\Controllers\Company\CustomField\CustomFieldsController;
use App\Http\Controllers\Company\Dashboard\DashboardController;
use App\Http\Controllers\Company\Estimate\EstimatesController;
use App\Http\Controllers\Company\Estimate\EstimateTemplatesController;
use App\Http\Controllers\Company\Expense\ExpenseCategoriesController;
use App\Http\Controllers\Company\Expense\ExpensesController;
use App\Http\Controllers\Company\General\BootstrapController;
use App\Http\Controllers\Company\General\ConfigController;
use App\Http\Controllers\Company\General\FormatsController;
use App\Http\Controllers\Company\General\InvitationResponseController;
use App\Http\Controllers\Company\General\NotesController;
use App\Http\Controllers\Company\General\SearchController;
use App\Http\Controllers\Company\General\SerialNumberController;
use App\Http\Controllers\Company\Invoice\InvoicesController;
use App\Http\Controllers\Company\Invoice\InvoiceTemplatesController;
use App\Http\Controllers\Company\Members\MembersController;
use App\Http\Controllers\Company\Payment\CreditAllocationsController;
use App\Http\Controllers\Company\Payment\PaymentMethodsController;
use App\Http\Controllers\Company\Payment\PaymentsController;
use App\Http\Controllers\Company\RecurringInvoice\RecurringInvoiceController;
use App\Http\Controllers\Company\RecurringInvoice\RecurringInvoiceFrequencyController;
use App\Http\Controllers\Company\Role\AbilitiesController;
use App\Http\Controllers\Company\Role\RolesController;
use App\Http\Controllers\Company\Settings\CompanyController;
use App\Http\Controllers\Company\Settings\CompanySettingsController;
use App\Http\Controllers\Company\Settings\InvitationController;
use App\Http\Controllers\Company\Settings\UserProfileController;
use App\Http\Controllers\CustomerPortal\Auth\ForgotPasswordController as AuthForgotPasswordController;
use App\Http\Controllers\CustomerPortal\Auth\ResetPasswordController as AuthResetPasswordController;
use App\Http\Controllers\CustomerPortal\Estimate\AcceptEstimateController as CustomerAcceptEstimateController;
use App\Http\Controllers\CustomerPortal\Estimate\EstimatesController as CustomerEstimatesController;
use App\Http\Controllers\CustomerPortal\Expense\ExpensesController as CustomerExpensesController;
use App\Http\Controllers\CustomerPortal\General\BootstrapController as CustomerBootstrapController;
use App\Http\Controllers\CustomerPortal\General\DashboardController as CustomerDashboardController;
use App\Http\Controllers\CustomerPortal\General\ProfileController as CustomerProfileController;
use App\Http\Controllers\CustomerPortal\Invoice\InvoicesController as CustomerInvoicesController;
use App\Http\Controllers\CustomerPortal\Payment\PaymentMethodController;
use App\Http\Controllers\CustomerPortal\Payment\PaymentsController as CustomerPaymentsController;
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

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

        // Send reset password mail
        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:10,2');

        // handle reset password form process
        Route::post('reset/password', [ResetPasswordController::class, 'reset']);
    });

    // Invitation Registration (public)
    // ----------------------------------

    Route::get('/invitations/{token}/details', [InvitationRegistrationController::class, 'details']);
    Route::post('/auth/register-with-invitation', [InvitationRegistrationController::class, 'register']);

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
        Route::get('companies', [CompaniesController::class, 'index']);
        Route::get('companies/{company}', [CompaniesController::class, 'show']);
        Route::put('companies/{company}', [CompaniesController::class, 'update']);

        Route::get('users', [UsersController::class, 'index']);
        Route::get('users/{user}', [UsersController::class, 'show']);
        Route::put('users/{user}', [UsersController::class, 'update']);
        Route::post('users/{user}/impersonate', [UsersController::class, 'impersonate']);
    });

    // Stop impersonation - uses auth:sanctum only (the impersonated user's token, not super-admin)
    Route::middleware(['auth:sanctum'])->prefix('super-admin')->group(function () {
        Route::post('stop-impersonating', [UsersController::class, 'stopImpersonating']);
    });

    Route::middleware(['auth:sanctum', 'company'])->group(function () {
        Route::middleware(['bouncer'])->group(function () {

            // Bootstrap
            // ----------------------------------

            Route::get('/bootstrap', BootstrapController::class);

            // Invitations (user-scoped — respond to invitations)
            // ----------------------------------

            Route::get('/invitations/pending', [InvitationResponseController::class, 'pending']);
            Route::post('/invitations/{invitation:token}/accept', [InvitationResponseController::class, 'accept']);
            Route::post('/invitations/{invitation:token}/decline', [InvitationResponseController::class, 'decline']);

            // Currencies
            // ----------------------------------

            require app_path('Domains/Money/routes/company.php');

            // Dashboard
            // ----------------------------------

            Route::get('/dashboard', DashboardController::class);

            // Auth check
            // ----------------------------------

            Route::get('/auth/check', [AuthController::class, 'check']);

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

            // Company Invitations (company-scoped — send invitations)
            // ----------------------------------

            Route::apiResource('company-invitations', InvitationController::class)->only(['index', 'store', 'destroy']);

            // Customers
            // ----------------------------------

            Route::post('/customers/delete', [CustomersController::class, 'delete']);

            Route::get('customers/{customer}/stats', CustomerStatsController::class);

            Route::get('customers/{customer}/statement', CustomerStatementController::class);
            Route::post('customers/{customer}/statement/send', SendCustomerStatementController::class);
            Route::post('customers/{customer}/credit-allocations', [CreditAllocationsController::class, 'store']);

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

            Route::get('/expenses/{expense}/show/receipt', [ExpensesController::class, 'showReceipt']);

            Route::post('/expenses/{expense}/upload/receipts', [ExpensesController::class, 'uploadReceipt']);

            Route::post('/expenses/delete', [ExpensesController::class, 'delete']);

            Route::apiResource('expenses', ExpensesController::class);

            Route::apiResource('categories', ExpenseCategoriesController::class);

            // Payments
            // ----------------------------------

            Route::get('/payments/{payment}/send/preview', [PaymentsController::class, 'sendPreview']);

            Route::post('/payments/{payment}/send', [PaymentsController::class, 'send']);

            Route::put('/payments/{payment}/allocations', [PaymentsController::class, 'replaceAllocations']);

            Route::post('/payments/delete', [PaymentsController::class, 'delete']);

            Route::apiResource('payments', PaymentsController::class);

            Route::apiResource('payment-methods', PaymentMethodsController::class);

            // Custom fields
            // ----------------------------------

            Route::resource('custom-fields', CustomFieldsController::class);

            // Backup & Disk
            // ----------------------------------

            require app_path('Platform/Storage/routes/company.php');

            // PDF rendering and fonts
            // ----------------------------------

            require app_path('Platform/Pdf/routes/admin.php');

            // Settings
            // ----------------------------------

            Route::get('/me', [UserProfileController::class, 'show']);

            Route::put('/me', [UserProfileController::class, 'update']);

            Route::get('/me/settings', [UserProfileController::class, 'showSettings']);

            Route::put('/me/settings', [UserProfileController::class, 'updateSettings']);

            Route::post('/me/upload-avatar', [UserProfileController::class, 'uploadAvatar']);

            Route::put('/company', [CompanyController::class, 'updateCompany']);

            Route::post('/company/upload-logo', [CompanyController::class, 'uploadCompanyLogo']);

            Route::get('/company/settings', [CompanySettingsController::class, 'show']);

            Route::post('/company/settings', [CompanySettingsController::class, 'update']);

            require app_path('Platform/Operations/routes/settings.php');

            Route::get('/company/has-transactions', [CompanySettingsController::class, 'checkTransactions']);

            // Mails
            // ----------------------------------

            require app_path('Platform/Mail/routes/company.php');

            require app_path('Platform/Ai/routes/company.php');

            Route::apiResource('notes', NotesController::class);

            // Tax Types
            // ----------------------------------

            require app_path('Domains/Taxation/routes/company.php');

            // Roles
            // ----------------------------------

            Route::get('abilities', AbilitiesController::class);

            Route::apiResource('roles', RolesController::class);
        });

        // Self Update
        // ----------------------------------
        // Disabled inside the official Docker image — containers upgrade via
        // `docker compose pull`, not the in-app updater (see EnsureNotContainerized).

        require app_path('Platform/Operations/routes/updater.php');

        // Companies
        // -------------------------------------------------

        Route::post('companies', [CompaniesController::class, 'store']);

        Route::post('/transfer/ownership/{user}', [CompanySettingsController::class, 'transferOwnership']);

        Route::post('companies/delete', [CompaniesController::class, 'destroy']);

        Route::get('companies', [CompaniesController::class, 'userCompanies']);

        // Users
        // ----------------------------------

        Route::post('/members/delete', [MembersController::class, 'delete']);

        Route::apiResource('/members', MembersController::class);

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

            Route::get('payments', [CustomerPaymentsController::class, 'index']);

            Route::get('payments/{id}', [CustomerPaymentsController::class, 'show']);

            Route::get('/payment-method', PaymentMethodController::class);

            Route::get('expenses', [CustomerExpensesController::class, 'index']);

            Route::get('expenses/{id}', [CustomerExpensesController::class, 'show']);

            Route::post('/profile', [CustomerProfileController::class, 'updateProfile']);

            Route::get('/me', [CustomerProfileController::class, 'getUser']);

            Route::get('/countries', CountriesController::class);
        });
    });
});

require app_path('Platform/Operations/routes/webhooks.php');
