<?php

use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

    $this->owner = User::query()->where('role', 'super admin')->firstOrFail();
    $this->company = $this->owner->companies()->firstOrFail();
    $this->company->update(['unique_hash' => 'document-rendering-company']);
    actingAs($this->owner);

    CompanySetting::setSettings([
        'language' => 'ar',
        'currency' => Currency::query()->firstOrFail()->id,
        'document_header_mode' => 'html',
        'document_header_html' => '<strong>رأس الشركة</strong>',
        'document_footer_mode' => 'html',
        'document_footer_html' => '<span>تذييل الشركة</span>',
    ], $this->company->id);

    $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    foreach (['watermark', 'paid_stamp'] as $asset) {
        $this->company->addMediaFromBase64($png)
            ->usingFileName("{$asset}.png")
            ->toMediaCollection($this->company::DOCUMENT_BRANDING_COLLECTIONS[$asset]);
    }
});

test('documents share bilingual branding watermark and eligible paid stamp', function () {
    $invoice = Invoice::factory()->create([
        'status' => Invoice::STATUS_SENT,
        'paid_status' => Invoice::STATUS_PAID,
        'recurring_invoice_id' => null,
        'show_paid_stamp' => true,
    ]);
    $estimate = Estimate::factory()->create();
    $payment = Payment::factory()->create(['show_paid_stamp' => true]);

    foreach ([
        "/invoices/pdf/{$invoice->unique_hash}?preview=1",
        "/estimates/pdf/{$estimate->unique_hash}?preview=1",
        "/payments/pdf/{$payment->unique_hash}?preview=1",
    ] as $url) {
        get($url)
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('رأس الشركة', false)
            ->assertSee('تذييل الشركة', false)
            ->assertSee('<div class="company-document-watermark"', false);
    }

    get("/invoices/pdf/{$invoice->unique_hash}?preview=1")
        ->assertSee('<img class="company-document-paid-stamp"', false);
    get("/payments/pdf/{$payment->unique_hash}?preview=1")
        ->assertSee('<img class="company-document-paid-stamp"', false);
    get("/estimates/pdf/{$estimate->unique_hash}?preview=1")
        ->assertDontSee('<img class="company-document-paid-stamp"', false);

    foreach ([
        "/invoices/pdf/{$invoice->unique_hash}",
        "/estimates/pdf/{$estimate->unique_hash}",
        "/payments/pdf/{$payment->unique_hash}",
    ] as $url) {
        get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
});

test('both sales report previews use the shared Arabic company shell', function () {
    foreach (['customers', 'items'] as $report) {
        get("/reports/sales/{$report}/{$this->company->unique_hash}?from_date=2026-01-01&to_date=2026-12-31&preview=1")
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('رأس الشركة', false)
            ->assertSee('تذييل الشركة', false)
            ->assertSee('<div class="company-document-watermark"', false);

        get("/reports/sales/{$report}/{$this->company->unique_hash}?from_date=2026-01-01&to_date=2026-12-31&download=1")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
});
