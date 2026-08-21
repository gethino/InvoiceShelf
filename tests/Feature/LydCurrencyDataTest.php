<?php

use App\Models\Currency;
use Database\Seeders\CurrenciesTableSeeder;

it('seeds LYD with its canonical symbol after the amount', function () {
    Currency::query()->delete();

    $this->seed(CurrenciesTableSeeder::class);

    $currency = Currency::query()->where('code', 'LYD')->sole();

    expect($currency->symbol)->toBe('LYD')
        ->and((bool) $currency->swap_currency_symbol)->toBeTrue();
});

it('updates existing LYD records', function () {
    $currency = Currency::query()->create([
        'name' => 'Libyan Dinar',
        'code' => 'LYD',
        'symbol' => 'LD',
        'precision' => 3,
        'thousand_separator' => ',',
        'decimal_separator' => '.',
        'swap_currency_symbol' => false,
    ]);

    $migration = require database_path('migrations/2026_08_21_230145_update_lyd_currency_symbol_and_position.php');
    $migration->up();

    expect($currency->refresh()->symbol)->toBe('LYD')
        ->and((bool) $currency->swap_currency_symbol)->toBeTrue();
});
