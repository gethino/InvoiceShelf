<?php

use App\Models\Currency;
use App\Models\Invoice;
use App\Services\PDFService;
use App\Space\ImageUtils;
use App\Space\PdfTemplateUtils;
use App\Support\ArabicPdfHtmlProcessor;
use Illuminate\Support\Facades\File;

/**
 * @return array<string, mixed>
 */
function tripoliCenterInvoiceTemplateData(): array
{
    $currency = new Currency;
    $currency->forceFill([
        'code' => 'LYD',
        'symbol' => 'LD',
        'precision' => 3,
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
        'customer' => (object) [
            'name' => 'Ahmed Al-Mansouri / أحمد المنصوري',
            'company_name' => 'Golden Horizon Company / شركة الأفق الذهبي',
            'currency' => $currency,
        ],
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
        'status' => Invoice::STATUS_SENT,
        'paid_status' => Invoice::STATUS_PARTIALLY_PAID,
    ];

    return [
        'invoice' => $invoice,
        'brandColor' => '#123abc',
        'faviconDataUri' => ImageUtils::toBase64Src(public_path('apple-touch-icon.png')),
        'customFields' => collect(),
        'company_address' => 'Tripoli, Libya<br>Printing & media services',
        'shipping_address' => 'Al Dahra<br>Tripoli, Libya',
        'billing_address' => 'Al Noor University<br>Tripoli, Libya',
        'notes' => 'Thank you for choosing Tripoli Center.',
        'logo' => null,
        'taxes' => collect(),
    ];
}

/**
 * @return array<string, mixed>
 */
function tripoliCenterEstimateTemplateData(): array
{
    $data = tripoliCenterInvoiceTemplateData();
    $estimate = $data['invoice'];
    $estimate->estimate_number = 'EST-2026-0042';
    $estimate->formattedEstimateDate = '20 Aug 2026';
    $estimate->formattedExpiryDate = '27 Aug 2026';

    unset($data['invoice']);
    $data['estimate'] = $estimate;

    return $data;
}

it('discovers the Tripoli Center templates and their preview images', function () {
    $templates = collect(PdfTemplateUtils::getFormattedTemplates('invoice', 'path'))
        ->where('custom', true)
        ->keyBy('name');

    expect($templates)
        ->toHaveKeys(['tripoli-center-en', 'tripoli-center-ar', 'tripoli-center-modern-ar'])
        ->and(File::exists($templates['tripoli-center-en']['path']))->toBeTrue()
        ->and(File::exists($templates['tripoli-center-ar']['path']))->toBeTrue()
        ->and(File::exists($templates['tripoli-center-modern-ar']['path']))->toBeTrue();

    $estimateTemplates = collect(PdfTemplateUtils::getFormattedTemplates('estimate', 'path'))
        ->where('custom', true)
        ->keyBy('name');

    expect($estimateTemplates)
        ->toHaveKey('tripoli-center-modern-ar')
        ->and(File::exists($estimateTemplates['tripoli-center-modern-ar']['path']))->toBeTrue();
});

it('renders the modern Arabic template with customer identity and local Almarai fonts', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', $data)->render();

    expect($html)
        ->toContain('lang="ar" dir="rtl"')
        ->toContain('data-customer-name')
        ->toContain('Ahmed Al-Mansouri / أحمد المنصوري')
        ->toContain('data-customer-company')
        ->toContain('Golden Horizon Company / شركة الأفق الذهبي')
        ->toContain('Almarai-Light.ttf')
        ->toContain('Almarai-Regular.ttf')
        ->toContain('Almarai-Bold.ttf')
        ->toContain('Almarai-ExtraBold.ttf')
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com')
        ->not->toContain('cdnjs.cloudflare.com')
        ->not->toContain('i.postimg.cc');

    view()->share($data);
    $pdf = PDFService::loadView('pdf_templates::invoice.tripoli-center-modern-ar')
        ->output();

    expect($pdf)->toStartWith('%PDF');
});

it('uses the current phone numbers and larger table typography in modern invoice and estimate templates', function () {
    $invoiceHtml = view('pdf_templates::invoice.tripoli-center-modern-ar', tripoliCenterInvoiceTemplateData())->render();
    $estimateHtml = view('pdf_templates::estimate.tripoli-center-modern-ar', tripoliCenterEstimateTemplateData())->render();

    expect($invoiceHtml)
        ->toContain('0911094545 - 0913386777')
        ->not->toContain('094-582-1748')
        ->not->toContain('091-024-4048')
        ->toMatch('/\.items-table\s*\{[^}]*font-size:\s*10px;/s')
        ->toMatch('/\.items-table th\s*\{[^}]*font-size:\s*10px;/s')
        ->toMatch('/\.label-cell\s*\{[^}]*font-size:\s*10px;/s')
        ->toMatch('/\.value-cell\s*\{[^}]*font-size:\s*11px;/s')
        ->toMatch('/\.total-value\s*\{[^}]*font-size:\s*17px;/s')
        ->and($estimateHtml)
        ->toContain('0911094545 - 0913386777')
        ->not->toContain('094-582-1748')
        ->not->toContain('091-024-4048');
});

it('scales the fixed-width modern browser preview without changing PDF rendering', function () {
    $browserHtml = view('pdf_templates::invoice.tripoli-center-modern-ar', tripoliCenterInvoiceTemplateData())->render();
    $pdfHtml = view('pdf_templates::invoice.tripoli-center-modern-ar', [
        ...tripoliCenterInvoiceTemplateData(),
        'dompdfRendering' => true,
    ])->render();

    expect($browserHtml)
        ->toContain('data-preview-shell')
        ->toContain('data-preview-canvas')
        ->toContain('width: 210mm;')
        ->toMatch('/body\.browser-preview\s*\{[^}]*display:\s*flex;[^}]*justify-content:\s*center;/s')
        ->toContain('fitPreviewToViewport')
        ->toContain('previewCanvas.style.transform = `scale(${scale})`;')
        ->and($pdfHtml)
        ->toContain('<body class="pdf-render"')
        ->not->toContain('fitPreviewToViewport');
});

it('keeps the modern template physically RTL with visible margins and a right-aligned logo', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', $data)->render();
    $source = File::get(storage_path('app/templates/pdf/invoice/tripoli-center-modern-ar.blade.php'));
    $itemsMarkup = str($html)
        ->after('<table class="items-table" data-column-order="rtl">')
        ->before('</table>')
        ->toString();

    expect($html)
        ->toContain('<body class="browser-preview" data-document-type="final_invoice" data-brand-color="#123abc">')
        ->toContain('data-column-order="rtl"')
        ->and(strpos($itemsMarkup, 'المجموع<br>Total'))->toBeLessThan(strpos($itemsMarkup, 'الرقم<br>No.'))
        ->and(strpos($html, 'التاريخ / Issue Date:'))->toBeLessThan(strpos($html, 'رقم الفاتورة / Invoice No:'))
        ->and(strpos($html, 'الاستحقاق / Due Date:'))->toBeLessThan(strpos($html, 'العميل / Customer:'));

    expect($source)
        ->toContain('body.pdf-render')
        ->toContain('padding: 10mm 12mm;')
        ->toContain('body.browser-preview')
        ->toContain('padding: 12mm;');

    expect($html)
        ->toMatch('/\.header-table\s*\{[^}]*direction:\s*ltr;/s')
        ->toMatch('/\.logo-block\s*\{[^}]*text-align:\s*right;/s');
});

it('keeps metadata labels on the right of their values', function () {
    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', tripoliCenterInvoiceTemplateData())->render();
    $customerEntry = str($html)
        ->after('data-meta-entry="customer"')
        ->before('</table>')
        ->toString();
    $invoiceNumberEntry = str($html)
        ->after('data-meta-entry="document-number"')
        ->before('</table>')
        ->toString();

    expect(strpos($customerEntry, 'Ahmed Al-Mansouri'))->toBeLessThan(strpos($customerEntry, 'العميل / Customer:'))
        ->and(strpos($invoiceNumberEntry, 'INV-2026-0042'))->toBeLessThan(strpos($invoiceNumberEntry, 'رقم الفاتورة / Invoice No:'));
});

it('keeps the notes label physically on the right of the note', function () {
    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', tripoliCenterInvoiceTemplateData())->render();
    $notesEntry = str($html)
        ->after('data-notes-order="rtl"')
        ->before('</table>')
        ->toString();

    expect($html)->toContain('data-notes-order="rtl"')
        ->and(strpos($notesEntry, 'Thank you for choosing Tripoli Center.'))
        ->toBeLessThan(strpos($notesEntry, 'ملاحظات / Notes:'));
});

it('renders real estimates as an initial invoice without payment status rows', function () {
    $data = tripoliCenterEstimateTemplateData();
    $html = view('pdf_templates::estimate.tripoli-center-modern-ar', $data)->render();
    $body = str($html)->after('</head>')->toString();

    expect($html)
        ->toContain('data-document-type="estimate"')
        ->toContain('فاتورة مبدئية')
        ->toContain('Proforma Invoice')
        ->toContain('EST-2026-0042')
        ->toContain('رقم العرض / Estimate No:')
        ->toContain('انتهاء الصلاحية / Expiry Date:')
        ->and($body)->not->toContain('<table class="payments-table">');

    view()->share($data);
    $pdf = PDFService::loadView('pdf_templates::estimate.tripoli-center-modern-ar')->output();

    expect($pdf)->toStartWith('%PDF');
});

it('uses the correct Arabic title for final, initial, and receipt documents', function (string $documentType, string $arabicTitle, string $englishTitle) {
    $data = tripoliCenterInvoiceTemplateData();
    $data['documentType'] = $documentType;

    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', $data)->render();

    expect($html)
        ->toContain("data-document-type=\"{$documentType}\"")
        ->toContain($arabicTitle)
        ->toContain($englishTitle);
})->with([
    'final invoice' => ['final_invoice', 'الفاتورة النهائية', 'Final Invoice'],
    'estimate or initial invoice' => ['initial_invoice', 'فاتورة مبدئية', 'Proforma Invoice'],
    'payment receipt' => ['payment_receipt', 'إيصال استلام', 'Payment Receipt'],
]);

it('uses the initial invoice title with the company brand color and faded favicon watermark', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $data['invoice']->status = Invoice::STATUS_DRAFT;

    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', $data)->render();

    expect($html)
        ->toContain('data-document-type="initial_invoice"')
        ->toContain('فاتورة مبدئية')
        ->toContain('data-brand-color="#123abc"')
        ->toContain('background: #123abc;')
        ->toContain('class="watermark"')
        ->toContain('data:image/png;base64,')
        ->toContain(ImageUtils::toBase64Src(public_path('apple-touch-icon.png')))
        ->not->toContain(ImageUtils::toBase64Src(public_path('favicon-96x96.png')))
        ->not->toContain('tripoli-center-watermark.jpeg');

    $source = File::get(storage_path('app/templates/pdf/invoice/tripoli-center-modern-ar.blade.php'));

    expect($source)
        ->toContain("public_path('favicon-96x96.png')")
        ->not->toContain('is_file($faviconPath)')
        ->toMatch('/\.watermark\s*\{[^}]*opacity:\s*0\.08;[^}]*z-index:\s*0;/s')
        ->toMatch('/\.items-table\s*\{[^}]*position:\s*relative;[^}]*z-index:\s*1;/s');
});

it('uses the receipt title for fully paid invoices', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $data['invoice']->paid_status = Invoice::STATUS_PAID;

    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', $data)->render();

    expect($html)
        ->toContain('data-document-type="payment_receipt"')
        ->toContain('إيصال استلام')
        ->toContain('Payment Receipt');
});

it('omits the customer company row when no company is set', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $data['invoice']->customer->company_name = '';

    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', $data)->render();

    expect($html)
        ->toContain('data-customer-name')
        ->not->toContain('data-customer-company');
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

it('uses each custom template locale for LYD formatting', function () {
    App::setLocale('ar');
    $englishHtml = view('pdf_templates::invoice.tripoli-center-en', tripoliCenterInvoiceTemplateData())->render();

    App::setLocale('en');
    $arabicHtml = view('pdf_templates::invoice.tripoli-center-ar', tripoliCenterInvoiceTemplateData())->render();

    expect($englishHtml)
        ->toContain('250&nbsp;<span style="font-family: DejaVu Sans;">LYD</span>')
        ->and($arabicHtml)
        ->toContain('<span style="font-family: DejaVu Sans;">د.ل</span>&nbsp;250');
});

it('uses compact Arabic LYD formatting in the modern invoice and estimate templates', function () {
    App::setLocale('en');

    $invoiceHtml = view('pdf_templates::invoice.tripoli-center-modern-ar', tripoliCenterInvoiceTemplateData())->render();
    $estimateHtml = view('pdf_templates::estimate.tripoli-center-modern-ar', tripoliCenterEstimateTemplateData())->render();
    $compactArabicAmount = '<span style="font-family: DejaVu Sans;">د.ل</span>&nbsp;250';

    expect($invoiceHtml)
        ->toContain($compactArabicAmount)
        ->not->toContain('250.000')
        ->and($estimateHtml)
        ->toContain($compactArabicAmount)
        ->not->toContain('250.000');
});

it('paginates long invoices while keeping the custom template renderable', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $item = $data['invoice']->items->first();
    $data['invoice']->items = collect(range(1, 24))->map(fn (): object => clone $item);

    $html = view('pdf_templates::invoice.tripoli-center-en', $data)->render();
    $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4');
    $pdf->render();

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBeGreaterThan(1);
});

it('paginates long modern Arabic invoices after shaping their text', function () {
    $data = tripoliCenterInvoiceTemplateData();
    $item = $data['invoice']->items->first();
    $data['invoice']->items = collect(range(1, 28))->map(fn (): object => clone $item);

    $html = view('pdf_templates::invoice.tripoli-center-modern-ar', $data)->render();
    $html = app(ArabicPdfHtmlProcessor::class)->process($html);
    $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4');
    $pdf->render();

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBeGreaterThan(1);
});
