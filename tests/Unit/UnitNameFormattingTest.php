<?php

use Illuminate\Support\Facades\File;

it('localizes every built-in unit for Arabic documents', function () {
    expect([
        'box' => format_unit_name('box', 'ar'),
        'cm' => format_unit_name('cm', 'ar_LY'),
        'dz' => format_unit_name('dz', 'ar'),
        'ft' => format_unit_name('ft', 'ar'),
        'g' => format_unit_name('g', 'ar'),
        'in' => format_unit_name('in', 'ar'),
        'kg' => format_unit_name('kg', 'ar'),
        'km' => format_unit_name('km', 'ar'),
        'lb' => format_unit_name('lb', 'ar'),
        'mg' => format_unit_name('mg', 'ar'),
        'pc' => format_unit_name('pc', 'ar'),
    ])->toBe([
        'box' => 'صندوق',
        'cm' => 'سم',
        'dz' => 'دزينة',
        'ft' => 'قدم',
        'g' => 'غ',
        'in' => 'بوصة',
        'kg' => 'كغ',
        'km' => 'كم',
        'lb' => 'رطل',
        'mg' => 'ملغ',
        'pc' => 'قطعة',
    ]);
});

it('preserves built-in codes in English and custom units in every locale', function () {
    expect(format_unit_name('pc', 'en'))->toBe('pc')
        ->and(format_unit_name('copies', 'ar'))->toBe('copies')
        ->and(format_unit_name(null, 'ar'))->toBe('');
});

it('formats units in every invoice and estimate PDF table', function () {
    $templatePaths = [
        resource_path('views/app/pdf/invoice/partials/table.blade.php'),
        resource_path('views/app/pdf/estimate/partials/table.blade.php'),
        storage_path('app/templates/pdf/invoice/partials/tripoli-center-layout.blade.php'),
        storage_path('app/templates/pdf/invoice/tripoli-center-modern-ar.blade.php'),
    ];

    foreach ($templatePaths as $templatePath) {
        expect(File::get($templatePath))->toContain('format_unit_name($item->unit_name');
    }
});
