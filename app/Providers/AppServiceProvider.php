<?php

namespace App\Providers;

use App\Domains\Receivables\Contracts\PaymentPdfDataProvider;
use App\Domains\Sales\Contracts\EstimatePdfDataProvider;
use App\Domains\Sales\Contracts\InvoicePdfDataProvider;
use App\Platform\Operations\Installation\Application\InstallationState;
use App\Platform\Persistence\ModelIdentityMap;
use App\Policies\CompanyPolicy;
use App\Policies\CreditNotePolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\EstimatePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OwnerPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\RecurringInvoicePolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingsPolicy;
use App\Policies\UserPolicy;
use App\Services\Document\EstimateService;
use App\Services\Document\InvoiceService;
use App\Services\Document\PaymentService;
use App\Support\Bouncer\BouncerDefaultScope;
use Gate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Silber\Bouncer\Database\Models as BouncerModels;
use Silber\Bouncer\Database\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     * The path to the "customer home" route for your application.
     *
     * This is used by Laravel authentication to redirect customers after login.
     *
     * @var string
     */
    public const CUSTOMER_HOME = '/customer/dashboard';

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ModelIdentityMap::enforce();
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        if (InstallationState::isDbCreated()) {
            $this->addMenus();
        }

        Gate::policy(Role::class, RolePolicy::class);
        $this->bootAuth();
        $this->bootBroadcast();

        // In demo mode, prevent all outgoing emails and notifications
        if (config('app.env') === 'demo') {
            Mail::fake();
            Notification::fake();
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EstimatePdfDataProvider::class, EstimateService::class);
        $this->app->bind(InvoicePdfDataProvider::class, InvoiceService::class);
        $this->app->bind(PaymentPdfDataProvider::class, PaymentService::class);

        BouncerModels::scope(new BouncerDefaultScope);
    }

    public function addMenus()
    {
        // main menu
        \Menu::make('main_menu', function ($menu) {
            foreach (config('invoiceshelf.main_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });

        // admin menu (super admin mode)
        \Menu::make('admin_menu', function ($menu) {
            foreach (config('invoiceshelf.admin_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });

        // setting menu
        \Menu::make('setting_menu', function ($menu) {
            foreach (config('invoiceshelf.setting_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });

        \Menu::make('customer_portal_menu', function ($menu) {
            foreach (config('invoiceshelf.customer_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });
    }

    public function generateMenu($menu, $data)
    {
        $menu->add($data['title'], $data['link'])
            ->data('icon', $data['icon'])
            ->data('name', $data['name'])
            ->data('owner_only', $data['owner_only'])
            ->data('super_admin_only', $data['super_admin_only'] ?? false)
            ->data('ability', $data['ability'])
            ->data('model', $data['model'])
            ->data('group', $data['group'])
            ->data('group_label', $data['group_label'] ?? '')
            ->data('priority', $data['priority'] ?? 100);
    }

    public function bootAuth()
    {

        Gate::define('create company', [CompanyPolicy::class, 'create']);
        Gate::define('transfer company ownership', [CompanyPolicy::class, 'transferOwnership']);
        Gate::define('delete company', [CompanyPolicy::class, 'delete']);

        Gate::define('manage company', [SettingsPolicy::class, 'manageCompany']);
        Gate::define('send invoice', [InvoicePolicy::class, 'send']);
        Gate::define('create credit note', [CreditNotePolicy::class, 'create']);
        Gate::define('send estimate', [EstimatePolicy::class, 'send']);
        Gate::define('send payment', [PaymentPolicy::class, 'send']);

        Gate::define('delete multiple customers', [CustomerPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple users', [UserPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple invoices', [InvoicePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple estimates', [EstimatePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple expenses', [ExpensePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple payments', [PaymentPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple recurring invoices', [RecurringInvoicePolicy::class, 'deleteMultiple']);

        Gate::define('view dashboard', [DashboardPolicy::class, 'view']);

        Gate::define('owner only', [OwnerPolicy::class, 'managedByOwner']);
    }

    public function bootBroadcast()
    {
        Broadcast::routes(['middleware' => 'api.auth']);
    }
}
