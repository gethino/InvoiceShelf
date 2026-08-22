<?php

use App\Models\EmailLog;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
});

$documents = [
    'invoice' => [[
        'model' => Invoice::class,
        'route_prefix' => 'invoices',
        'number_field' => 'invoice_number',
        'collection' => 'invoice',
        'attributes' => [
            'status' => Invoice::STATUS_VIEWED,
            'recurring_invoice_id' => null,
        ],
    ]],
    'estimate' => [[
        'model' => Estimate::class,
        'route_prefix' => 'estimates',
        'number_field' => 'estimate_number',
        'collection' => 'estimate',
        'attributes' => ['status' => Estimate::STATUS_VIEWED],
    ]],
    'payment' => [[
        'model' => Payment::class,
        'route_prefix' => 'payments',
        'number_field' => 'payment_number',
        'collection' => 'payment',
        'attributes' => [],
    ]],
];

test('document endpoints provide frontend, html preview, and raw pdf modes', function () use ($documents) {
    actingAs(User::findOrFail(1));

    foreach ($documents as [$documentCase]) {
        $document = $documentCase['model']::factory()->create($documentCase['attributes']);

        get("/{$documentCase['route_prefix']}/pdf/{$document->unique_hash}?preview=1")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee($document->{$documentCase['number_field']});

        $emailLog = EmailLog::factory()->create([
            'mailable_type' => $documentCase['model'],
            'mailable_id' => $document->id,
            'token' => "browser-preview-{$documentCase['collection']}",
        ]);
        $publicUrl = "/customer/{$documentCase['route_prefix']}/view/{$emailLog->token}";

        get($publicUrl)
            ->assertOk()
            ->assertViewIs('app');

        get($publicUrl.'?preview=1')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee($document->{$documentCase['number_field']});

        get($publicUrl.'?pdf=1')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', "inline; filename=\"{$document->{$documentCase['number_field']}}.pdf\"");
    }
});
