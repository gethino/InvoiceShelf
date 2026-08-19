<?php

namespace Modules\TripoliCustomizations\Providers;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\Module\ModuleFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Modules\TripoliCustomizations\Entities\CustomerOrganization;

class TripoliCustomizationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('TripoliCustomizations', 'Database/Migrations'));

        ModuleFacade::script(
            'tripoli-customizations',
            module_path('TripoliCustomizations', 'dist/tripoli-customizations.iife.js'),
        );

        Customer::resolveRelationUsing(
            'customerOrganization',
            fn (Customer $customer) => $customer->belongsTo(
                CustomerOrganization::class,
                'customer_organization_id',
            ),
        );

        Company::resolveRelationUsing(
            'customerOrganizations',
            fn (Company $company) => $company->hasMany(CustomerOrganization::class),
        );

        Company::created(function (Company $company): void {
            CompanySetting::setSettings([
                'brand_color' => '#4a3dff',
                'taxes_enabled' => 'NO',
            ], $company->id);

            if (! Setting::getSetting('login_brand_company_id')) {
                Setting::setSetting('login_brand_company_id', (string) $company->id);
            }
        });

        view()->composer('app', function (View $view): void {
            if (! Schema::hasTable('settings') || ! Schema::hasTable('companies')) {
                return;
            }

            $companyId = (int) Setting::getSetting('login_brand_company_id');
            $company = Company::query()->find($companyId);

            if (! $company) {
                return;
            }

            $view->with('tripoli_branding', [
                'brand_color' => CompanySetting::getSetting('brand_color', $company->id) ?? '#4a3dff',
                'logo_url' => $company->logo,
            ]);
        });

        $this->app->booted(fn () => $this->registerMenus());
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    private function registerMenus(): void
    {
        $settingMenu = \Menu::get('setting_menu');

        $settingMenu?->add('tripoli_customizations.settings.menu', '/admin/settings/tripoli-customizations')
            ->data('icon', 'SwatchIcon')
            ->data('name', 'Other Settings')
            ->data('owner_only', true)
            ->data('ability', '')
            ->data('model', '')
            ->data('group', '');
    }
}
