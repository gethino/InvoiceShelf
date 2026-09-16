<?php

use Illuminate\Support\Facades\File;

it('switches the base font token to Almarai for RTL documents', function () {
    $stylesheet = File::get(resource_path('css/invoiceshelf.css'));

    expect($stylesheet)
        ->toContain('--font-arabic: "Almarai", Poppins, sans-serif;')
        ->toContain('html[dir="rtl"]')
        ->toContain('--font-base: var(--font-arabic);');
});

it('ships every Almarai browser and PDF font weight locally', function (string $file) {
    expect(File::exists(resource_path("static/fonts/{$file}")))->toBeTrue();
})->with([
    'Almarai-Light.ttf',
    'Almarai-Regular.ttf',
    'Almarai-Bold.ttf',
    'Almarai-ExtraBold.ttf',
    'almarai-arabic-300-normal.woff2',
    'almarai-arabic-400-normal.woff2',
    'almarai-arabic-700-normal.woff2',
    'almarai-arabic-800-normal.woff2',
    'almarai-latin-300-normal.woff2',
    'almarai-latin-400-normal.woff2',
    'almarai-latin-700-normal.woff2',
    'almarai-latin-800-normal.woff2',
    'Almarai-OFL.txt',
]);

it('does not load Almarai from a remote stylesheet', function () {
    $stylesheet = File::get(resource_path('css/invoiceshelf.css'));

    expect($stylesheet)
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com');
});

it('uses local configurable fonts across every PDF template type', function (string $path) {
    expect(File::get(base_path($path)))
        ->toContain("@include('app.pdf.partials.document-font')");
})->with([
    'invoice' => 'resources/views/app/pdf/invoice/invoice1.blade.php',
    'estimate' => 'resources/views/app/pdf/estimate/estimate1.blade.php',
    'payment' => 'resources/views/app/pdf/payment/payment.blade.php',
    'expenses report' => 'resources/views/app/pdf/reports/expenses.blade.php',
    'customer sales report' => 'resources/views/app/pdf/reports/sales-customers.blade.php',
    'item sales report' => 'resources/views/app/pdf/reports/sales-items.blade.php',
    'profit and loss report' => 'resources/views/app/pdf/reports/profit-loss.blade.php',
    'tax summary report' => 'resources/views/app/pdf/reports/tax-summary.blade.php',
    'Tripoli custom template' => 'storage/app/templates/pdf/invoice/tripoli-center-modern-ar.blade.php',
]);
