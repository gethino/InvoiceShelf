<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->select(['company_id', 'company_name'])
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->distinct()
            ->orderBy('company_id')
            ->each(function (object $customer): void {
                $name = trim($customer->company_name);

                if ($name === '') {
                    return;
                }

                $organizationId = DB::table('customer_organizations')
                    ->where('company_id', $customer->company_id)
                    ->where('name', $name)
                    ->value('id');

                if (! $organizationId) {
                    $organizationId = DB::table('customer_organizations')->insertGetId([
                        'company_id' => $customer->company_id,
                        'name' => $name,
                        'notes' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('customers')
                    ->where('company_id', $customer->company_id)
                    ->where('company_name', $customer->company_name)
                    ->update(['customer_organization_id' => $organizationId]);
            });

        DB::table('companies')->orderBy('id')->each(function (object $company): void {
            foreach (['taxes_enabled' => 'NO', 'brand_color' => '#4a3dff'] as $option => $value) {
                DB::table('company_settings')->updateOrInsert(
                    ['company_id' => $company->id, 'option' => $option],
                    ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
                );
            }
        });

        $defaultCompanyId = DB::table('companies')->orderBy('id')->value('id');

        if ($defaultCompanyId) {
            DB::table('settings')->updateOrInsert(
                ['option' => 'login_brand_company_id'],
                ['value' => (string) $defaultCompanyId, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    public function down(): void
    {
        DB::table('company_settings')->whereIn('option', ['taxes_enabled', 'brand_color'])->delete();
        DB::table('settings')->where('option', 'login_brand_company_id')->delete();
    }
};
