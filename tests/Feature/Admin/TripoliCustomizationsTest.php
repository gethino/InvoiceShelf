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

function base64Png(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);

    ob_start();
    imagepng($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    return 'data:image/png;base64,'.base64_encode($contents);
}

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
        'meta_title' => 'Tripoli Center',
        'meta_description' => 'Tripoli Center invoicing portal.',
        'theme_color' => '#102030',
        'taxes_enabled' => true,
        'use_on_login' => true,
        'simplified_login' => false,
    ])->assertOk()->assertJson([
        'success' => true,
        'brand_color' => '#123abc',
        'meta_title' => 'Tripoli Center',
        'meta_description' => 'Tripoli Center invoicing portal.',
        'theme_color' => '#102030',
        'taxes_enabled' => true,
        'use_on_login' => true,
        'simplified_login' => false,
    ]);

    expect(CompanySetting::getSetting('brand_color', $this->company->id))->toBe('#123abc')
        ->and(CompanySetting::getSetting('meta_title', $this->company->id))->toBe('Tripoli Center')
        ->and(CompanySetting::getSetting('meta_description', $this->company->id))->toBe('Tripoli Center invoicing portal.')
        ->and(CompanySetting::getSetting('theme_color', $this->company->id))->toBe('#102030')
        ->and(CompanySetting::getSetting('taxes_enabled', $this->company->id))->toBe('YES')
        ->and(Setting::getSetting('login_brand_company_id'))->toBe((string) $this->company->id)
        ->and(Setting::getSetting('simplified_login'))->toBe('NO');

    getJson('/api/v1/tax-types')->assertOk();
});

test('custom settings validate brand colors', function () {
    putJson('/api/v1/tripoli-customizations/settings', [
        'brand_color' => 'blue',
        'meta_title' => '',
        'meta_description' => '',
        'theme_color' => '#ffffff',
        'taxes_enabled' => false,
        'use_on_login' => false,
        'simplified_login' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('brand_color');
});

test('simplified login defaults to enabled', function () {
    getJson('/api/v1/tripoli-customizations/settings')
        ->assertOk()
        ->assertJsonPath('simplified_login', true);
});

test('owner can upload and remove dark logo and square favicon', function () {
    $darkLogo = json_encode([
        'name' => 'dark-logo.png',
        'data' => base64Png(2, 1),
    ], JSON_THROW_ON_ERROR);
    $favicon = json_encode([
        'name' => 'favicon.png',
        'data' => base64Png(2, 2),
    ], JSON_THROW_ON_ERROR);

    postJson('/api/v1/company/upload-logo', [
        'dark_company_logo' => $darkLogo,
        'company_favicon' => $favicon,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['dark_logo_url', 'favicon_url']);

    expect($this->company->fresh()->getMedia('dark_logo'))->toHaveCount(1)
        ->and($this->company->fresh()->getMedia('favicon'))->toHaveCount(1);

    postJson('/api/v1/company/upload-logo', [
        'is_dark_company_logo_removed' => true,
        'is_company_favicon_removed' => true,
    ])->assertOk();

    expect($this->company->fresh()->getMedia('dark_logo'))->toBeEmpty()
        ->and($this->company->fresh()->getMedia('favicon'))->toBeEmpty();
});

test('favicon must be a square png', function () {
    $favicon = json_encode([
        'name' => 'favicon.png',
        'data' => base64Png(2, 1),
    ], JSON_THROW_ON_ERROR);

    postJson('/api/v1/company/upload-logo', [
        'company_favicon' => $favicon,
    ])->assertUnprocessable()->assertJsonValidationErrors('company_favicon');
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
    CompanySetting::setSettings([
        'brand_color' => '#123abc',
        'meta_title' => 'Tripoli Center',
        'meta_description' => 'Invoices & payments',
        'theme_color' => '#123abc',
    ], $this->company->id);
    $this->company->addMediaFromBase64(base64Png(2, 2))
        ->usingFileName('favicon.png')
        ->toMediaCollection('favicon');
    Setting::setSetting('login_brand_company_id', (string) $this->company->id);
    Setting::setSetting('simplified_login', 'YES');

    $faviconUrl = $this->company->fresh()->favicon;

    $this->view('app')
        ->assertSee('window.tripoli_branding', false)
        ->assertSee('#123abc', false)
        ->assertSee('<title>Tripoli Center</title>', false)
        ->assertSee('<meta name="description" content="Invoices &amp; payments">', false)
        ->assertSee($faviconUrl, false)
        ->assertSee('/apple-touch-icon.png', false)
        ->assertSee('/site.webmanifest', false)
        ->assertSee('"simplified_login":true', false);
});

test('login page preserves default metadata without custom branding', function () {
    Setting::setSetting('login_brand_company_id', (string) $this->company->id);

    expect([
        public_path('favicon-96x96.png'),
        public_path('favicon.svg'),
        public_path('favicon.ico'),
        public_path('apple-touch-icon.png'),
        public_path('site.webmanifest'),
        public_path('web-app-manifest-192x192.png'),
        public_path('web-app-manifest-512x512.png'),
    ])->each->toBeFile();

    $this->view('app')
        ->assertSee('<title>InvoiceShelf - Self Hosted Invoicing Platform</title>', false)
        ->assertSee('/favicon-96x96.png', false)
        ->assertSee('/favicon.svg', false)
        ->assertSee('/favicon.ico', false)
        ->assertSee('/apple-touch-icon.png', false)
        ->assertSee('<meta name="apple-mobile-web-app-title" content="Tripoli Center">', false)
        ->assertSee('/site.webmanifest', false)
        ->assertDontSee('<meta name="description"', false);
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
