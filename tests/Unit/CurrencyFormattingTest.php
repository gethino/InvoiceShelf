<?php

use App\Models\Currency;
use Illuminate\Support\Facades\App;

function currencyFormattingTestCurrency(array $attributes = []): Currency
{
    $currency = new Currency;
    $currency->forceFill(array_merge([
        'code' => 'LYD',
        'symbol' => 'LD',
        'precision' => 3,
        'decimal_separator' => '.',
        'thousand_separator' => ',',
        'swap_currency_symbol' => false,
    ], $attributes));

    return $currency;
}

function visibleMoneyText(string $html): string
{
    return str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

it('formats LYD after the amount in English', function () {
    App::setLocale('en');

    $formatted = format_money_pdf(10000, currencyFormattingTestCurrency());

    expect(visibleMoneyText($formatted))->toBe('100 LYD');
});

it('formats the Arabic LYD symbol before the amount', function () {
    $formatted = format_money_pdf(10000, currencyFormattingTestCurrency(), 'ar_LY');

    expect(visibleMoneyText($formatted))->toBe('د.ل 100');
});

it('preserves configured presentation for other currencies', function () {
    $currency = currencyFormattingTestCurrency([
        'code' => 'EUR',
        'symbol' => '€',
        'precision' => 2,
        'decimal_separator' => ',',
        'thousand_separator' => '.',
        'swap_currency_symbol' => false,
    ]);

    $formatted = format_money_pdf(123450, $currency, 'ar');

    expect(visibleMoneyText($formatted))->toBe('€ 1.234,5');
});

it('removes only insignificant decimal zeros', function (int $amount, string $expected) {
    $formatted = format_money_pdf($amount, currencyFormattingTestCurrency());

    expect(visibleMoneyText($formatted))->toBe($expected);
})->with([
    'whole amount' => [10000, '100 LYD'],
    'single meaningful decimal' => [10050, '100.5 LYD'],
    'two meaningful decimals' => [10055, '100.55 LYD'],
    'negative fractional amount' => [-10050, '-100.5 LYD'],
]);
