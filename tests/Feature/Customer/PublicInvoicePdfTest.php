<?php

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\get;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
});

test('invoice resource exposes a working permanent signed PDF URL', function () {
    $invoice = Invoice::factory()->create(['recurring_invoice_id' => null]);
    $resource = (new InvoiceResource($invoice))->resolve();
    $originalStatus = $invoice->status;

    expect($resource['whatsapp_pdf_url'])
        ->toBeString()
        ->and(Request::create($resource['whatsapp_pdf_url'])->hasValidSignature())
        ->toBeTrue();

    get(route('customer.invoice.pdf', ['invoice' => $invoice->unique_hash]))
        ->assertForbidden();

    get($resource['whatsapp_pdf_url'].'&tampered=1')
        ->assertForbidden();

    get(URL::signedRoute('customer.invoice.pdf', ['invoice' => 'missing-invoice']))
        ->assertNotFound();

    get($resource['whatsapp_pdf_url'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($invoice->fresh()->status)->toBe($originalStatus);
});
