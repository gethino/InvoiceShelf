<?php

namespace Modules\TripoliCustomizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TripoliCustomizations\Http\Requests\UpdateCustomizationSettingsRequest;

class CustomizationSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $company = $this->company($request);
        $this->authorize('manage company', $company);

        return response()->json($this->payload($company));
    }

    public function update(UpdateCustomizationSettingsRequest $request): JsonResponse
    {
        $company = $this->company($request);
        $data = $request->validated();

        CompanySetting::setSettings([
            'brand_color' => strtolower($data['brand_color']),
            'meta_title' => trim($data['meta_title'] ?? ''),
            'meta_description' => trim($data['meta_description'] ?? ''),
            'theme_color' => strtolower($data['theme_color']),
            'taxes_enabled' => $data['taxes_enabled'] ? 'YES' : 'NO',
            'tax_per_item' => $data['taxes_enabled']
                ? CompanySetting::getSetting('tax_per_item', $company->id) ?? 'NO'
                : 'NO',
            'sales_tax_us_enabled' => $data['taxes_enabled']
                ? CompanySetting::getSetting('sales_tax_us_enabled', $company->id) ?? 'NO'
                : 'NO',
        ], $company->id);

        if ($data['use_on_login']) {
            Setting::setSetting('login_brand_company_id', (string) $company->id);
        }

        Setting::setSetting('simplified_login', $data['simplified_login'] ? 'YES' : 'NO');

        return response()->json([
            'success' => true,
            ...$this->payload($company->refresh()),
        ]);
    }

    private function company(Request $request): Company
    {
        return Company::query()->findOrFail((int) $request->header('company'));
    }

    private function payload(Company $company): array
    {
        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'logo_url' => $company->logo,
            'dark_logo_url' => $company->dark_logo,
            'favicon_url' => $company->favicon,
            'brand_color' => CompanySetting::getSetting('brand_color', $company->id) ?? '#4a3dff',
            'meta_title' => CompanySetting::getSetting('meta_title', $company->id) ?? '',
            'meta_description' => CompanySetting::getSetting('meta_description', $company->id) ?? '',
            'theme_color' => CompanySetting::getSetting('theme_color', $company->id) ?? '#ffffff',
            'taxes_enabled' => CompanySetting::getSetting('taxes_enabled', $company->id) === 'YES',
            'use_on_login' => (int) Setting::getSetting('login_brand_company_id') === $company->id,
            'simplified_login' => Setting::getSetting('simplified_login') !== 'NO',
        ];
    }
}
