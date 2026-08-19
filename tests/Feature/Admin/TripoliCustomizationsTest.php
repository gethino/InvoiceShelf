<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Setting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\TripoliCustomizations\Entities\CustomerOrganization;
use Silber\Bouncer\BouncerFacade;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $currency = Currency::query()->find(1) ?? new Currency;
    $currency->id = 1;
    $currency->fill([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'precision' => 2,
        'thousand_separator' => ',',
        'decimal_separator' => '.',
    ])->save();

    $this->user = User::query()->find(1) ?? new User;
    $this->user->id = 1;
    $this->user->fill([
        'name' => 'Test Owner',
        'email' => 'owner@example.com',
        'role' => 'super admin',
        'password' => 'secret',
        'currency_id' => 1,
    ])->save();

    $this->company = Company::query()->find(1) ?? new Company;
    $this->company->id = 1;
    $this->company->fill([
        'name' => 'Test Company',
        'owner_id' => $this->user->id,
        'slug' => 'test-company',
        'unique_hash' => 'test-company-hash',
    ])->save();
    $this->company->setupDefaultData();
    CompanySetting::setSettings(['currency' => 1], $this->company->id);
    $this->user->companies()->syncWithoutDetaching($this->company->id);
    BouncerFacade::scope()->to($this->company->id);
    $this->user->assign('super admin');

    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

test('owner can save company branding tax state and login default', function () {
    putJson('/api/v1/tripoli-customizations/settings', [
        'brand_color' => '#123abc',
        'taxes_enabled' => true,
        'use_on_login' => true,
    ])->assertOk()->assertJson([
        'success' => true,
        'brand_color' => '#123abc',
        'taxes_enabled' => true,
        'use_on_login' => true,
    ]);

    expect(CompanySetting::getSetting('brand_color', $this->company->id))->toBe('#123abc')
        ->and(CompanySetting::getSetting('taxes_enabled', $this->company->id))->toBe('YES')
        ->and(Setting::getSetting('login_brand_company_id'))->toBe((string) $this->company->id);

    getJson('/api/v1/tax-types')->assertOk();
});

test('custom settings validate brand colors', function () {
    putJson('/api/v1/tripoli-customizations/settings', [
        'brand_color' => 'blue',
        'taxes_enabled' => false,
        'use_on_login' => false,
    ])->assertUnprocessable()->assertJsonValidationErrors('brand_color');
});

test('owner can create an organization and assign customer people', function () {
    $customers = Customer::factory()->count(2)->create(['company_id' => $this->company->id]);

    $organizationId = postJson('/api/v1/customer-organizations', [
        'name' => 'AMLY School',
        'notes' => 'School account',
    ])->assertSuccessful()->json('data.id');

    putJson("/api/v1/customer-organizations/{$organizationId}/members", [
        'customer_ids' => $customers->pluck('id')->all(),
    ])->assertOk()->assertJsonCount(2, 'data.customers');

    getJson('/api/v1/customer-organizations')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'AMLY School')
        ->assertJsonCount(2, 'data.0.customers');

    $this->assertDatabaseHas('customers', [
        'id' => $customers->first()->id,
        'customer_organization_id' => $organizationId,
    ]);
});

test('customer creation can assign an organization from the same company', function () {
    $organization = CustomerOrganization::query()->create([
        'company_id' => $this->company->id,
        'name' => 'AMLY School',
    ]);

    postJson('/api/v1/customers', [
        'name' => 'Emad',
        'email' => 'emad@example.com',
        'currency_id' => 1,
        'enable_portal' => false,
        'customer_organization_id' => $organization->id,
    ])->assertOk()->assertJsonPath(
        'data.customer_organization_id',
        $organization->id,
    );

    $this->assertDatabaseHas('customers', [
        'email' => 'emad@example.com',
        'customer_organization_id' => $organization->id,
    ]);
});

test('customer creation rejects an organization from another company', function () {
    $otherOrganization = CustomerOrganization::query()->create([
        'company_id' => Company::factory()->create()->id,
        'name' => 'Other company',
    ]);

    postJson('/api/v1/customers', [
        'name' => 'Emad',
        'email' => 'emad@example.com',
        'currency_id' => 1,
        'enable_portal' => false,
        'customer_organization_id' => $otherOrganization->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('customer_organization_id');
});

test('login page receives the explicit default company brand', function () {
    CompanySetting::setSettings(['brand_color' => '#123abc'], $this->company->id);
    Setting::setSetting('login_brand_company_id', (string) $this->company->id);

    $this->view('app')
        ->assertSee('window.tripoli_branding', false)
        ->assertSee('#123abc', false);
});

test('a customer can belong to only one organization', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $first = CustomerOrganization::query()->create([
        'company_id' => $this->company->id,
        'name' => 'First',
    ]);
    $second = CustomerOrganization::query()->create([
        'company_id' => $this->company->id,
        'name' => 'Second',
    ]);

    putJson("/api/v1/customer-organizations/{$first->id}/members", [
        'customer_ids' => [$customer->id],
    ])->assertOk();
    putJson("/api/v1/customer-organizations/{$second->id}/members", [
        'customer_ids' => [$customer->id],
    ])->assertOk();

    expect($customer->refresh()->customer_organization_id)->toBe($second->id);
});

test('deleting an organization keeps customers and unassigns them', function () {
    $organization = CustomerOrganization::query()->create([
        'company_id' => $this->company->id,
        'name' => 'AMLY School',
    ]);
    $customer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'customer_organization_id' => $organization->id,
    ]);

    deleteJson("/api/v1/customer-organizations/{$organization->id}")
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($customer->refresh()->customer_organization_id)->toBeNull();
});

test('organizations and members are company scoped', function () {
    $otherCompany = Company::factory()->create();
    $otherOrganization = CustomerOrganization::query()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other organization',
    ]);
    $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);

    putJson("/api/v1/customer-organizations/{$otherOrganization->id}", [
        'name' => 'Changed',
        'notes' => null,
    ])->assertForbidden();

    $ownOrganization = CustomerOrganization::query()->create([
        'company_id' => $this->company->id,
        'name' => 'Own organization',
    ]);

    putJson("/api/v1/customer-organizations/{$ownOrganization->id}/members", [
        'customer_ids' => [$otherCustomer->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('customer_ids.0');
});

test('disabled taxes allow reads and silently remove taxes from financial writes', function () {
    CompanySetting::setSettings(['taxes_enabled' => 'NO'], $this->company->id);

    getJson('/api/v1/tax-types')->assertOk();

    $customer = Customer::query()->create([
        'name' => 'Emad',
        'email' => 'emad@example.com',
        'company_id' => $this->company->id,
        'currency_id' => 1,
    ]);

    postJson('/api/v1/invoices', [
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addWeek()->toDateString(),
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-TAX-REMOVED',
        'currency_id' => 1,
        'exchange_rate' => 1,
        'discount' => 0,
        'discount_val' => 0,
        'sub_total' => 100,
        'total' => 115,
        'taxes' => [['tax_type_id' => 999, 'amount' => 15]],
        'tax' => 15,
        'tax_included' => true,
        'template_name' => 'invoice1',
        'items' => [[
            'name' => 'Tuition',
            'description' => null,
            'quantity' => 1,
            'price' => 100,
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'taxes' => [['tax_type_id' => 999, 'amount' => 15]],
            'tax' => 15,
            'total' => 115,
        ]],
    ])
        ->assertOk();

    $invoice = Invoice::query()
        ->where('invoice_number', 'INV-TAX-REMOVED')
        ->firstOrFail();

    expect($invoice->tax)->toBe(0)
        ->and($invoice->total)->toBe(100)
        ->and($invoice->taxes()->count())->toBe(0)
        ->and($invoice->items()->first()->tax)->toBe(0)
        ->and($invoice->items()->first()->taxes()->count())->toBe(0);
});

test('disabled taxes still allow tax free invoices', function () {
    CompanySetting::setSettings(['taxes_enabled' => 'NO'], $this->company->id);

    $customer = Customer::query()->create([
        'name' => 'Emad',
        'email' => 'emad@example.com',
        'company_id' => $this->company->id,
        'currency_id' => 1,
    ]);

    $invoice = [
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addWeek()->toDateString(),
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-TAX-FREE',
        'currency_id' => 1,
        'exchange_rate' => 1,
        'discount' => 0,
        'discount_val' => 0,
        'sub_total' => 100,
        'total' => 100,
        'taxes' => [],
        'tax' => 0,
        'tax_included' => false,
        'template_name' => 'invoice1',
        'items' => [[
            'name' => 'Tuition',
            'description' => null,
            'quantity' => 1,
            'price' => 100,
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'taxes' => [],
            'tax' => 0,
            'total' => 100,
        ]],
    ];

    postJson('/api/v1/invoices', $invoice)->assertOk();
});

test('disabled taxes allow item creation and silently remove its taxes', function () {
    CompanySetting::setSettings(['taxes_enabled' => 'NO'], $this->company->id);

    $itemId = postJson('/api/v1/items', [
        'name' => 'Tuition',
        'description' => 'School tuition',
        'price' => 100,
        'unit_id' => null,
        'taxes' => [[
            'tax_type_id' => 999,
            'amount' => 15,
        ]],
    ])->assertOk()->json('data.id');

    $item = Item::query()->findOrFail($itemId);

    expect($item->taxes()->count())->toBe(0);
});
