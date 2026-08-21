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
