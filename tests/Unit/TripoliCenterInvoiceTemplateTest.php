<?php

use App\Models\Currency;
use App\Models\Invoice;
use App\Space\PdfTemplateUtils;
use Illuminate\Support\Facades\File;

/**
 * @return array<string, mixed>
 */
function tripoliCenterInvoiceTemplateData(): array
{
    $currency = new Currency;
    $currency->forceFill([
        'symbol' => 'LYD ',
        'precision' => 2,
        'decimal_separator' => '.',
        'thousand_separator' => ',',
        'swap_currency_symbol' => false,
    ]);

    $item = new class
    {
        public string $name = 'Premium graduation project printing';

        public string $description = 'Full-colour printing and thermal binding';

        public int $quantity = 4;

        public string $unit_name = 'copies';

        public int $price = 25000;

        public int $total = 100000;

        public int $discount_val = 0;

        public string $discount_type = 'fixed';

        public int $discount = 0;

        public int $tax = 0;

        public function getCustomFieldValueBySlug(string $slug): ?string
        {
            return null;
        }
    };

    $tax = (object) [
        'name' => 'VAT',
        'calculation_type' => 'percentage',
        'percent' => 5,
        'fixed_amount' => 0,
        'amount' => 5000,
    ];

    $invoice = (object) [
        'invoice_number' => 'INV-2026-0042',
        'formattedInvoiceDate' => '20 Aug 2026',
        'formattedDueDate' => '27 Aug 2026',
        'customer' => (object) ['currency' => $currency],
        'items' => collect([$item]),
        'discount_per_item' => 'NO',
        'tax_per_item' => 'NO',
        'discount' => 0,
        'discount_type' => 'fixed',
        'discount_val' => 0,
        'tax_included' => false,
        'tax' => 5000,
        'taxes' => collect([$tax]),
        'sub_total' => 100000,
        'total' => 105000,
        'due_amount' => 55000,
        'paid_status' => Invoice::STATUS_PARTIALLY_PAID,
    ];

    return [
        'invoice' => $invoice,
        'customFields' => collect(),
        'company_address' => 'Tripoli, Libya<br>Printing & media services',
        'shipping_address' => 'Al Dahra<br>Tripoli, Libya',
        'billing_address' => 'Al Noor University<br>Tripoli, Libya',
        'notes' => 'Thank you for choosing Tripoli Center.',
        'logo' => null,
        'taxes' => collect(),
    ];
}

it('discovers both Tripoli Center templates and their preview images', function () {
    $templates = collect(PdfTemplateUtils::getFormattedTemplates('invoice', 'path'))
        ->where('custom', true)
        ->keyBy('name');

    expect($templates)
        ->toHaveKeys(['tripoli-center-en', 'tripoli-center-ar'])
        ->and(File::exists($templates['tripoli-center-en']['path']))->toBeTrue()
        ->and(File::exists($templates['tripoli-center-ar']['path']))->toBeTrue();
});

it('renders localized English and Arabic custom invoice markup', function (string $template, string $locale, string $direction, string $text) {
    $html = view("pdf_templates::invoice.{$template}", tripoliCenterInvoiceTemplateData())->render();

    expect($html)
        ->toContain("lang=\"{$locale}\"")
        ->toContain("dir=\"{$direction}\"")
        ->toContain($text)
        ->toContain('INV-2026-0042')
        ->toContain('Premium graduation project printing')
        ->toContain('data:image/png;base64,');

    $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4')->output();

    expect($pdf)->toStartWith('%PDF');
})->with([
    'English' => ['tripoli-center-en', 'en', 'ltr', 'Tripoli First Company'],
    'Arabic' => ['tripoli-center-ar', 'ar', 'rtl', 'شركة طرابلس الأولى'],
]);

it('paginates long invoices while keeping the custom template renderable', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $item = $data['invoice']->items->first();
    $data['invoice']->items = collect(range(1, 24))->map(fn (): object => clone $item);

    $html = view('pdf_templates::invoice.tripoli-center-en', $data)->render();
    $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4');
    $pdf->render();

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBeGreaterThan(1);
});
