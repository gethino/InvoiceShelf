@php
    $isEstimateDocument = isset($estimate);
    $invoice = $estimate ?? $invoice;
    $tripoliLocale = 'ar';
    $tripoliLogoPath = $logo && is_file($logo)
        ? $logo
        : storage_path('app/templates/pdf/invoice/assets/tripoli-center-logo.png');
    $tripoliWatermarkSrc = is_string($faviconDataUri ?? null) && str_starts_with($faviconDataUri, 'data:image/png;base64,')
        ? $faviconDataUri
        : \App\Space\ImageUtils::toBase64Src(public_path('favicon-96x96.png'));
    $brandColor = is_string($brandColor ?? null) && preg_match('/^#[0-9a-f]{6}$/i', $brandColor)
        ? $brandColor
        : '#e54128';
    $customerName = trim((string) ($invoice->customer->name ?? ''));
    $customerCompany = trim((string) ($invoice->customer->company_name ?? ''));
    $paidAmount = max(0, $invoice->total - ($invoice->due_amount ?? $invoice->total));
    $discountAmount = $invoice->discount_val ?? 0;
    $amountFormatter = class_exists(\NumberFormatter::class)
        ? new \NumberFormatter('ar', \NumberFormatter::SPELLOUT)
        : null;
    $amountInWords = $amountFormatter?->format($invoice->total / 100);
    $displayTaxes = $invoice->tax_per_item === 'YES' ? $taxes : $invoice->taxes;
    $tripoliLabels = [
        'discount' => 'الخصم / Discount',
        'tax' => 'الضريبة / Tax',
    ];
    $paymentStatus = match ($invoice->paid_status ?? null) {
        \App\Models\Invoice::STATUS_PAID => 'مدفوع / Paid',
        \App\Models\Invoice::STATUS_PARTIALLY_PAID => 'مدفوع جزئياً / Partially paid',
        default => 'متبقي / Due',
    };
    $documentType = $documentType ?? match (true) {
        $isEstimateDocument => 'estimate',
        ($invoice->paid_status ?? null) === \App\Models\Invoice::STATUS_PAID => 'payment_receipt',
        ($invoice->status ?? null) === \App\Models\Invoice::STATUS_DRAFT => 'initial_invoice',
        default => 'final_invoice',
    };
    [$documentTitleArabic, $documentTitleEnglish] = match ($documentType) {
        'estimate', 'initial_invoice' => ['فاتورة مبدئية', 'Proforma Invoice'],
        'payment_receipt' => ['إيصال استلام', 'Payment Receipt'],
        default => ['الفاتورة النهائية', 'Final Invoice'],
    };
    $documentNumber = $isEstimateDocument ? $invoice->estimate_number : $invoice->invoice_number;
    $documentIssueDate = $isEstimateDocument ? $invoice->formattedEstimateDate : $invoice->formattedInvoiceDate;
    $documentDueDate = $isEstimateDocument ? $invoice->formattedExpiryDate : $invoice->formattedDueDate;
    $documentNumberLabel = $isEstimateDocument ? 'رقم العرض / Estimate No:' : 'رقم الفاتورة / Invoice No:';
    $documentDueDateLabel = $isEstimateDocument ? 'انتهاء الصلاحية / Expiry Date:' : 'الاستحقاق / Due Date:';
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $documentTitleArabic }} / {{ $documentTitleEnglish }} - {{ $documentNumber }}</title>

    <style type="text/css">
        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 300;
            src: url("{{ resource_path('static/fonts/Almarai-Light.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 400;
            src: url("{{ resource_path('static/fonts/Almarai-Regular.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 700;
            src: url("{{ resource_path('static/fonts/Almarai-Bold.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 800;
            src: url("{{ resource_path('static/fonts/Almarai-ExtraBold.ttf') }}") format("truetype");
        }

        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            color: #17202a;
            direction: rtl;
            font-family: "Almarai", "DejaVu Sans", sans-serif;
            font-size: 9px;
            font-weight: 400;
            line-height: 1.55;
            text-align: right;
        }

        body.browser-preview {
            background: #eef1f5;
            overflow-x: hidden;
            padding: 12mm;
        }

        body.pdf-render {
            background: #fff;
            padding: 10mm 12mm;
        }

        table {
            border-collapse: collapse;
        }

        .preview-shell {
            width: 100%;
        }

        .invoice {
            width: 100%;
        }

        body.browser-preview .invoice {
            background: #fff;
            max-width: none;
            padding: 9mm 10mm;
            width: 210mm;
        }

        .header-table {
            border-bottom: 3px solid {{ $brandColor }};
            direction: ltr;
            margin-bottom: 8px;
            table-layout: fixed;
            width: 100%;
        }

        .header-table td {
            border: 0;
            padding: 0 0 9px;
            vertical-align: middle;
        }

        .brand-block {
            direction: rtl;
            padding-right: 8mm !important;
            text-align: right;
            width: 55%;
        }

        .company-main {
            color: {{ $brandColor }};
            font-size: 20px;
            font-weight: 800;
            line-height: 1.25;
        }

        .company-sub {
            color: #2c3e50;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.55;
        }

        .logo-block {
            direction: rtl;
            padding-left: 8mm !important;
            text-align: right;
            width: 45%;
        }

        .logo {
            display: inline-block;
            max-height: 72px;
            max-width: 100%;
        }

        .services-table {
            width: 100%;
            border-top: 1px solid #d0d7e0;
            margin-top: 7px;
            table-layout: fixed;
        }

        .services-table td {
            border: 0;
            color: #3d4a5c;
            font-size: 9px;
            padding: 3px 2px 0;
            text-align: right;
            vertical-align: top;
            width: 33.333%;
        }

        .service-dot {
            color: {{ $brandColor }};
            font-weight: 800;
        }

        .invoice-title-table {
            margin: 9px auto 8px;
            width: 56%;
        }

        .invoice-title-table td {
            border: 0;
            vertical-align: middle;
        }

        .title-line {
            border-top: 2px solid #d0d7e0;
            width: 22%;
        }

        .invoice-title {
            color: {{ $brandColor }};
            font-size: 18px;
            font-weight: 800;
            padding: 0 12px;
            text-align: center;
            white-space: nowrap;
        }

        .meta-table {
            border: 1px solid #d0d7e0;
            direction: ltr;
            margin-bottom: 10px;
            table-layout: fixed;
            width: 100%;
        }

        .meta-table td {
            background: #fafbfc;
            border: 1px solid #e5e9ef;
            direction: rtl;
            padding: 6px 9px;
            text-align: right;
            vertical-align: middle;
            width: 50%;
        }

        .meta-entry {
            direction: ltr;
            table-layout: fixed;
            width: 100%;
        }

        .meta-entry td {
            background: transparent;
            border: 0;
            padding: 0;
            width: auto;
        }

        .meta-label {
            color: {{ $brandColor }};
            direction: rtl;
            font-size: 10px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
            width: 42% !important;
        }

        .meta-value {
            direction: rtl;
            font-size: 11px;
            font-weight: 700;
            padding-right: 5px !important;
            text-align: right;
            width: 58% !important;
        }

        .numeric {
            direction: ltr;
            unicode-bidi: embed;
        }

        .items-table {
            border: 1px solid #d0d7e0;
            direction: ltr;
            font-size: 10px;
            position: relative;
            table-layout: fixed;
            width: 100%;
            z-index: 1;
        }

        .items-shell {
            position: relative;
        }

        .watermark {
            left: 50%;
            margin-left: -47mm;
            opacity: 0.08;
            position: absolute;
            top: 34mm;
            width: 94mm;
            z-index: 0;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table th {
            background: {{ $brandColor }};
            border: 1px solid {{ $brandColor }};
            color: #fff;
            direction: rtl;
            font-size: 10px;
            font-weight: 800;
            line-height: 1.35;
            padding: 5px 3px;
            text-align: center;
        }

        .items-table td {
            border: 1px solid #e5e9ef;
            padding: 5px 4px;
            text-align: center;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #f8f9fc;
        }

        .items-table .description-cell {
            direction: rtl;
            text-align: right;
        }

        .item-name {
            font-size: 10px;
            font-weight: 700;
        }

        .item-description,
        .item-meta {
            color: #5e6f88;
            display: block;
            font-size: 9px;
            margin-top: 2px;
        }

        .item-meta-label {
            color: {{ $brandColor }};
            font-weight: 700;
        }

        .summary-table,
        .payments-table {
            border: 1px solid #d0d7e0;
            direction: ltr;
            margin-top: 8px;
            page-break-inside: avoid;
            table-layout: fixed;
            width: 100%;
        }

        .summary-table td,
        .payments-table td {
            border: 1px solid #e5e9ef;
            direction: rtl;
            padding: 5px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .label-cell {
            font-size: 10px;
            font-weight: 700;
        }

        .value-cell {
            background: #fafbfc;
            direction: ltr;
            font-size: 11px;
            font-weight: 700;
            unicode-bidi: embed;
        }

        .words-cell {
            background: #fafbfc;
            direction: rtl;
            font-size: 11px;
            text-align: right !important;
        }

        .discount-value {
            color: #1e7e34;
        }

        .total-value {
            background: {{ $brandColor }};
            color: #fff;
            font-size: 17px;
            font-weight: 800;
        }

        .paid-label {
            color: #d35400;
        }

        .signature-line {
            border-bottom: 1px solid #8b96a5;
            display: inline-block;
            height: 14px;
            width: 75%;
        }

        .notes-box {
            background: #fafbfc;
            border: 1px solid #d0d7e0;
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .notes-table {
            direction: ltr;
            font-size: 11px;
            table-layout: fixed;
            width: 100%;
        }

        .notes-table td {
            border: 0;
            padding: 6px 9px;
            vertical-align: top;
        }

        .notes-label {
            color: {{ $brandColor }};
            direction: rtl;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
            width: 20%;
        }

        .notes-content {
            direction: rtl;
            text-align: right;
            width: 80%;
        }

        .contact-table {
            background: #fafbfc;
            border: 1px solid #d0d7e0;
            margin-top: 9px;
            page-break-inside: avoid;
            table-layout: fixed;
            width: 100%;
        }

        .contact-table td {
            border: 0;
            color: #2c3e50;
            direction: ltr;
            font-size: 9px;
            font-weight: 700;
            padding: 6px 3px;
            text-align: center;
            width: 33.333%;
        }

        .contact-label {
            color: {{ $brandColor }};
        }

        .footer {
            border-top: 1px solid #d0d7e0;
            color: {{ $brandColor }};
            font-size: 9px;
            font-weight: 800;
            line-height: 1.55;
            margin-top: 8px;
            padding-top: 6px;
            text-align: center;
        }

        .footer-muted {
            color: #5e6f88;
            font-size: 7px;
            font-weight: 300;
        }

        @media screen {
            body.browser-preview .preview-shell {
                margin: 0;
                max-width: none;
                position: relative;
            }

            body.browser-preview .invoice {
                left: 50%;
                margin-left: -105mm;
                position: absolute;
                top: 0;
                transform-origin: top center;
            }
        }

        @media screen and (max-width: 900px) {
            body.browser-preview {
                padding: 12px;
            }

        }

        @media print {
            body.browser-preview {
                background: #fff;
                padding: 10mm 12mm;
            }

            body.browser-preview .preview-shell {
                height: auto !important;
                margin: 0;
                max-width: none;
                width: 100% !important;
            }

            body.browser-preview .invoice {
                margin-left: 0;
                padding: 0;
                position: static;
                transform: none !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="{{ ($dompdfRendering ?? false) ? 'pdf-render' : 'browser-preview' }}" data-document-type="{{ $documentType }}" data-brand-color="{{ $brandColor }}">
    @include('app.pdf.partials.company-branding')
    <div class="preview-shell" data-preview-shell>
    <div class="invoice" data-preview-canvas>
        <table class="header-table">
            <tr>
                <td class="brand-block">
                    <div class="company-main">شركة طرابلس الأولى</div>
                    <div class="company-sub">للخدمات الإعلامية والفنية والدعاية والإعلان</div>

                    <table class="services-table">
                        <tr>
                            <td><span class="service-dot">•</span> تصوير مستندات</td>
                            <td><span class="service-dot">•</span> طباعة كمبيوتر</td>
                            <td><span class="service-dot">•</span> سحب وتصوير خرائط</td>
                        </tr>
                        <tr>
                            <td><span class="service-dot">•</span> تجليد حراري وحلزوني</td>
                            <td><span class="service-dot">•</span> طباعة كروت الهوية</td>
                            <td><span class="service-dot">•</span> بحوث ومشاريع تخرج</td>
                        </tr>
                    </table>
                </td>
                <td class="logo-block">
                    <img
                        class="logo"
                        src="{{ \App\Space\ImageUtils::toBase64Src($tripoliLogoPath) }}"
                        alt="شعار شركة طرابلس الأولى"
                    >
                </td>
            </tr>
        </table>

        <table class="invoice-title-table">
            <tr>
                <td class="title-line"></td>
                <td class="invoice-title">
                    <span dir="rtl">{{ $documentTitleArabic }}</span>
                    <span aria-hidden="true"> / </span>
                    <span dir="ltr">{{ $documentTitleEnglish }}</span>
                </td>
                <td class="title-line"></td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td>
                    <table class="meta-entry" data-meta-entry="issue-date">
                        <tr>
                            <td class="meta-value numeric">{{ $documentIssueDate }}</td>
                            <td class="meta-label">التاريخ / Issue Date:</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="meta-entry" data-meta-entry="document-number">
                        <tr>
                            <td class="meta-value numeric">{{ $documentNumber }}</td>
                            <td class="meta-label">{{ $documentNumberLabel }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="meta-entry" data-meta-entry="due-date">
                        <tr>
                            <td class="meta-value numeric">{{ $documentDueDate }}</td>
                            <td class="meta-label">{{ $documentDueDateLabel }}</td>
                        </tr>
                    </table>
                </td>
                <td data-customer-name>
                    <table class="meta-entry" data-meta-entry="customer">
                        <tr>
                            <td class="meta-value">{{ $customerName !== '' ? $customerName : '—' }}</td>
                            <td class="meta-label">العميل / Customer:</td>
                        </tr>
                    </table>
                </td>
            </tr>
            @if ($customerCompany !== '')
                <tr data-customer-company>
                    <td colspan="2">
                        <table class="meta-entry" data-meta-entry="company">
                            <tr>
                                <td class="meta-value">{{ $customerCompany }}</td>
                                <td class="meta-label">الجهة / Company:</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @endif
        </table>

        <div class="items-shell">
            <img
                class="watermark"
                src="{{ $tripoliWatermarkSrc }}"
                alt=""
            >

            <table class="items-table" data-column-order="rtl">
            <thead>
                <tr>
                    <th style="width: 20%">المجموع<br>Total</th>
                    <th style="width: 16%">سعر الوحدة<br>Unit Price</th>
                    <th style="width: 12%">الكمية<br>Qty</th>
                    <th style="width: 44%">الصنف / Description</th>
                    <th style="width: 8%">الرقم<br>No.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td class="numeric">{!! format_money_pdf($item->total, $invoice->customer->currency, $tripoliLocale) !!}</td>
                        <td class="numeric">{!! format_money_pdf($item->price, $invoice->customer->currency, $tripoliLocale) !!}</td>
                        <td class="numeric">
                            {{ $item->quantity }}
                            @if ($item->unit_name)
                                {{ format_unit_name($item->unit_name, $tripoliLocale) }}
                            @endif
                        </td>
                        <td class="description-cell">
                            @include('pdf_templates::invoice.partials.tripoli-center-item-description')
                        </td>
                        <td class="numeric">{{ $loop->iteration }}</td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>

        <table class="summary-table">
            <tr>
                @if ((float) $discountAmount !== 0.0)
                <td class="value-cell discount-value" style="width: 32%">{!! format_money_pdf($discountAmount, $invoice->customer->currency, $tripoliLocale) !!}</td>
                <td class="label-cell" style="width: 18%">الخصم<br>Discount</td>
                @endif
                <td class="value-cell" style="width: 32%">{!! format_money_pdf($invoice->sub_total, $invoice->customer->currency, $tripoliLocale) !!}</td>
                <td class="label-cell" style="width: 18%">المجموع الفرعي<br>Subtotal</td>
            </tr>

            @foreach ($displayTaxes as $tax)
                <tr>
                    <td class="value-cell" colspan="2">{!! format_money_pdf($tax->amount, $invoice->customer->currency, $tripoliLocale) !!}</td>
                    <td class="label-cell" colspan="2">
                        {{ $tax->name }}
                        @if ($tax->calculation_type === 'fixed')
                            ({!! format_money_pdf($tax->fixed_amount, $invoice->customer->currency, $tripoliLocale) !!})
                        @else
                            ({{ $tax->percent }}%)
                        @endif
                    </td>
                </tr>
            @endforeach

            <tr>
                <td class="words-cell" colspan="3">
                    {{ $amountInWords ?: strip_tags(format_money_pdf($invoice->total, $invoice->customer->currency, $tripoliLocale)) }}
                </td>
                <td class="label-cell">المبلغ بالحروف<br>Amount in words</td>
            </tr>
            <tr>
                <td class="value-cell total-value">{!! format_money_pdf($invoice->total, $invoice->customer->currency, $tripoliLocale) !!}</td>
                <td class="label-cell">الإجمالي / Total</td>
                <td class="value-cell">{!! format_money_pdf($invoice->total, $invoice->customer->currency, $tripoliLocale) !!}</td>
                <td class="label-cell">الصافي / Net</td>
            </tr>
        </table>

        @if (! $isEstimateDocument)
            <table class="payments-table">
                <tr>
                    <td class="value-cell" style="width: 32%">{!! format_money_pdf($paidAmount, $invoice->customer->currency, $tripoliLocale) !!}</td>
                    <td class="label-cell paid-label" style="width: 18%">المدفوع<br>Paid</td>
                    <td style="width: 32%"><span class="signature-line"></span></td>
                    <td class="label-cell" style="width: 18%">التوقيع<br>Signature</td>
                </tr>
                <tr>
                    <td class="value-cell" style="direction: rtl">{{ $paymentStatus }}</td>
                    <td class="label-cell">حالة السداد<br>Payment Status</td>
                    <td class="value-cell">{!! format_money_pdf($invoice->due_amount, $invoice->customer->currency, $tripoliLocale) !!}</td>
                    <td class="label-cell">الباقي<br>Balance</td>
                </tr>
            </table>
        @endif

        @if ($notes)
            <div class="notes-box">
                <table class="notes-table" data-notes-order="rtl">
                    <tr>
                        <td class="notes-content">{!! $notes !!}</td>
                        <td class="notes-label">ملاحظات / Notes:</td>
                    </tr>
                </table>
            </div>
        @endif

        <table class="contact-table">
            <tr>
                <td><span class="contact-label">Phone:</span> <span dir="ltr">0911094545 - 0913386777</span></td>
                <td><span class="contact-label">Email:</span> Tripoli.center11@gmail.com</td>
                <td><span class="contact-label">Web:</span> tripolicenter.com</td>
            </tr>
        </table>

        <div class="footer">
            شركة طرابلس الأولى<br>
            <span class="footer-muted">للخدمات الإعلامية والفنية والدعاية والإعلان</span>
        </div>
    </div>
    </div>

    @unless ($dompdfRendering ?? false)
        <script>
            (() => {
                const previewShell = document.querySelector('[data-preview-shell]');
                const previewCanvas = document.querySelector('[data-preview-canvas]');

                if (!previewShell || !previewCanvas) {
                    return;
                }

                const fitPreviewToViewport = () => {
                    const bodyStyles = window.getComputedStyle(document.body);
                    const horizontalPadding = Number.parseFloat(bodyStyles.paddingLeft)
                        + Number.parseFloat(bodyStyles.paddingRight);
                    const availableWidth = Math.max(document.documentElement.clientWidth - horizontalPadding, 0);
                    const canvasWidth = previewCanvas.offsetWidth;
                    const scale = canvasWidth > 0 ? Math.min(1, availableWidth / canvasWidth) : 1;

                    previewCanvas.style.transform = `scale(${scale})`;
                    previewShell.style.height = `${previewCanvas.offsetHeight * scale}px`;
                };

                window.addEventListener('resize', fitPreviewToViewport, { passive: true });
                document.querySelectorAll('img').forEach((image) => {
                    if (!image.complete) {
                        image.addEventListener('load', fitPreviewToViewport, { once: true });
                    }
                });

                if (document.fonts?.ready) {
                    document.fonts.ready.then(fitPreviewToViewport);
                }

                fitPreviewToViewport();
            })();
        </script>
    @endunless
</body>
</html>
