<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Services\DocumentTemplateService;
use App\Space\PdfTemplateUtils;
use Laravel\Sanctum\Sanctum;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Role;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

    $this->owner = User::query()->where('role', 'super admin')->firstOrFail();
    $this->company = $this->owner->companies()->firstOrFail();
    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->owner, ['*']);
});

test('unconfigured company exposes all templates with legacy defaults', function () {
    $response = getJson('/api/v1/company/document-templates')->assertOk();

    $invoiceNames = array_column(PdfTemplateUtils::getFormattedTemplates('invoice', ''), 'name');
    $estimateNames = array_column(PdfTemplateUtils::getFormattedTemplates('estimate', ''), 'name');

    expect($response->json('settings.allowed_invoice_templates'))->toEqual($invoiceNames)
        ->and($response->json('settings.allowed_estimate_templates'))->toEqual($estimateNames)
        ->and($response->json('settings.default_invoice_template'))->toBe(
            in_array('invoice1', $invoiceNames, true) ? 'invoice1' : $invoiceNames[0]
        )
        ->and($response->json('settings.default_estimate_template'))->toBe(
            in_array('estimate1', $estimateNames, true) ? 'estimate1' : $estimateNames[0]
        );
});

test('owner configures company template allowlists and defaults', function () {
    $invoiceNames = array_column(PdfTemplateUtils::getFormattedTemplates('invoice', ''), 'name');
    $estimateNames = array_column(PdfTemplateUtils::getFormattedTemplates('estimate', ''), 'name');
    $invoiceSelection = array_slice($invoiceNames, 0, 2);
    $estimateSelection = array_slice($estimateNames, 0, 2);

    putJson('/api/v1/company/document-templates', [
        'allowed_invoice_templates' => $invoiceSelection,
        'default_invoice_template' => $invoiceSelection[1] ?? $invoiceSelection[0],
        'allowed_estimate_templates' => $estimateSelection,
        'default_estimate_template' => $estimateSelection[1] ?? $estimateSelection[0],
    ])->assertOk();

    getJson('/api/v1/invoices/templates')
        ->assertOk()
        ->assertJsonPath('invoiceTemplates.0.name', $invoiceSelection[0])
        ->assertJsonCount(count($invoiceSelection), 'invoiceTemplates')
        ->assertJsonPath('defaultTemplate', $invoiceSelection[1] ?? $invoiceSelection[0]);

    getJson('/api/v1/estimates/templates')
        ->assertOk()
        ->assertJsonPath('estimateTemplates.0.name', $estimateSelection[0])
        ->assertJsonCount(count($estimateSelection), 'estimateTemplates')
        ->assertJsonPath('defaultTemplate', $estimateSelection[1] ?? $estimateSelection[0]);
});

test('template settings validate catalog values and matching defaults', function () {
    putJson('/api/v1/company/document-templates', [
        'allowed_invoice_templates' => ['missing-invoice-template'],
        'default_invoice_template' => 'missing-invoice-template',
        'allowed_estimate_templates' => ['missing-estimate-template'],
        'default_estimate_template' => 'different-template',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors([
            'allowed_invoice_templates.0',
            'allowed_estimate_templates.0',
            'default_estimate_template',
        ]);
});

test('manager cannot configure document templates', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    $manager->companies()->attach($this->company->id);
    BouncerFacade::scope()->to($this->company->id);
    Role::query()->firstOrCreate(['name' => 'manager']);
    $manager->assign('manager');
    Sanctum::actingAs($manager, ['*']);

    getJson('/api/v1/company/document-templates')->assertForbidden();

    putJson('/api/v1/company/document-templates', [])->assertForbidden();
});

test('template and branding settings remain isolated by company', function () {
    $otherCompany = Company::factory()->create(['owner_id' => $this->owner->id]);
    $this->owner->companies()->attach($otherCompany->id);
    $invoiceNames = array_column(PdfTemplateUtils::getFormattedTemplates('invoice', ''), 'name');
    $estimateNames = array_column(PdfTemplateUtils::getFormattedTemplates('estimate', ''), 'name');

    CompanySetting::setSettings(['brand_color' => '#111111'], $this->company->id);
    CompanySetting::setSettings(['brand_color' => '#222222'], $otherCompany->id);

    app(DocumentTemplateService::class)->save($this->company->id, [
        'allowed_invoice_templates' => [$invoiceNames[0]],
        'default_invoice_template' => $invoiceNames[0],
        'allowed_estimate_templates' => [$estimateNames[0]],
        'default_estimate_template' => $estimateNames[0],
    ]);

    expect(app(DocumentTemplateService::class)->allowedNames('invoice', $this->company->id))
        ->toBe([$invoiceNames[0]])
        ->and(app(DocumentTemplateService::class)->allowedNames('invoice', $otherCompany->id))
        ->toEqual($invoiceNames)
        ->and(CompanySetting::getSetting('brand_color', $this->company->id))->toBe('#111111')
        ->and(CompanySetting::getSetting('brand_color', $otherCompany->id))->toBe('#222222');
});

test('disabled template remains resolvable for existing documents', function () {
    $invoiceNames = array_column(PdfTemplateUtils::getFormattedTemplates('invoice', ''), 'name');
    $disabledTemplate = $invoiceNames[1] ?? $invoiceNames[0];

    app(DocumentTemplateService::class)->save($this->company->id, [
        'allowed_invoice_templates' => [$invoiceNames[0]],
        'default_invoice_template' => $invoiceNames[0],
        'allowed_estimate_templates' => ['estimate1'],
        'default_estimate_template' => 'estimate1',
    ]);

    expect(app(DocumentTemplateService::class)->allowedNames('invoice', $this->company->id))
        ->not->toContain($disabledTemplate)
        ->and(PdfTemplateUtils::findFormattedTemplate('invoice', $disabledTemplate, ''))
        ->not->toBeNull();
});
