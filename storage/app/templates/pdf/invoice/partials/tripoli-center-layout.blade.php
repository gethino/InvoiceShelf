@php
    $tripoliLogoPath = $logo && is_file($logo)
        ? $logo
        : storage_path('app/templates/pdf/invoice/assets/tripoli-center-logo.png');
    $tripoliWatermarkPath = storage_path('app/templates/pdf/invoice/assets/tripoli-center-watermark.jpeg');
    $tripoliSpellout = class_exists(\NumberFormatter::class)
        ? new \NumberFormatter($tripoliLocale, \NumberFormatter::SPELLOUT)
        : null;
    $tripoliAmountInWords = $tripoliSpellout?->format($invoice->total / 100) ?: null;
@endphp

<!DOCTYPE html>
<html lang="{{ $tripoliLocale }}" dir="{{ $tripoliDirection }}">
<head>
    <title>{{ $tripoliLabels['invoice'] }} - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <style type="text/css">
        @page {
            margin: 9mm 11mm 17mm;
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
            color: #151315;
            direction: {{ $tripoliDirection }};
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            line-height: 1.4;
        }

        table {
            border-collapse: collapse;
        }

        .document {
            position: relative;
            width: 100%;
        }

        .brand-table,
        .information-table,
        .items-table,
        .closing-table {
            width: 100%;
        }

        .brand-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .brand-logo-cell {
            width: 46%;
            text-align: {{ $tripoliDirection === 'rtl' ? 'right' : 'left' }};
        }

        .brand-logo {
            display: block;
            height: 68px;
            max-width: 265px;
            object-fit: contain;
        }

        .company-identity {
            width: 54%;
            text-align: {{ $tripoliDirection === 'rtl' ? 'left' : 'right' }};
        }

        .company-name {
            font-size: 19px;
            font-weight: 700;
            line-height: 1.2;
        }

        .company-tagline {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.35;
            margin-top: 3px;
        }

        .services {
            margin: 10px auto 0;
            max-width: 94%;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.55;
        }

        .invoice-title {
            margin: 7px auto 8px;
            text-align: center;
        }

        .invoice-title-box {
            display: inline-block;
            min-width: 185px;
            padding: 5px 26px 6px;
            border: 3px solid #bf514c;
            border-radius: 24px 0 24px 0;
            color: #b90000;
            font-family: "DejaVu Serif", "DejaVu Sans", sans-serif;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.1;
        }

        .information-table {
            margin: 0 0 9px;
            table-layout: fixed;
        }

        .information-table > tbody > tr > td {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }

        .information-table > tbody > tr > td:first-child {
            padding-{{ $tripoliDirection === 'rtl' ? 'left' : 'right' }}: 5px;
        }

        .information-table > tbody > tr > td:last-child {
            padding-{{ $tripoliDirection === 'rtl' ? 'right' : 'left' }}: 5px;
        }

        .information-panel {
            min-height: 66px;
            border-top: 2px solid #151315;
            border-bottom: 1px solid #151315;
            padding: 5px 7px;
        }

        .panel-label {
            color: #b90000;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .address-grid {
            width: 100%;
        }

        .address-grid td {
            border: 0;
            padding: 0 0 0 6px;
            vertical-align: top;
            width: 50%;
        }

        .address-grid td:first-child {
            padding: 0 6px 0 0;
        }

        .address-content {
            color: #2c292c;
            font-size: 8px;
            line-height: 1.35;
            overflow-wrap: break-word;
        }

        .metadata-table {
            width: 100%;
        }

        .metadata-table td {
            border: 0;
            padding: 1px 0;
            vertical-align: top;
        }

        .metadata-label {
            color: #555055;
            font-size: 8px;
            font-weight: 700;
            width: 42%;
        }

        .metadata-value {
            direction: ltr;
            font-size: 9px;
            font-weight: 700;
            text-align: {{ $tripoliDirection === 'rtl' ? 'left' : 'right' }};
        }

        .items-shell {
            position: relative;
        }

        .watermark {
            position: absolute;
            z-index: -1;
            top: 46px;
            left: 50%;
            width: 380px;
            margin-left: -190px;
            opacity: 0.11;
        }

        .items-table {
            border: 3px solid #111;
            direction: ltr;
            table-layout: fixed;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table th {
            border: 2px solid #111;
            padding: 5px 4px;
            color: #111;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.25;
            text-align: center;
            vertical-align: middle;
        }

        .items-table th span {
            display: block;
            color: #bd0000;
            font-size: 7px;
            font-weight: 700;
            margin-top: 1px;
        }

        .items-table td {
            border-left: 2px solid #111;
            border-right: 2px solid #111;
            border-bottom: 1px dotted #575257;
            padding: 5px 4px;
            font-size: 8px;
            line-height: 1.35;
            text-align: center;
            vertical-align: top;
        }

        .items-table .description-cell {
            direction: {{ $tripoliDirection }};
            text-align: {{ $tripoliDirection === 'rtl' ? 'right' : 'left' }};
        }

        .numeric-cell {
            direction: ltr;
        }

        .item-name {
            font-size: 9px;
            font-weight: 700;
        }

        .item-description,
        .item-meta {
            color: #504a50;
            display: block;
            font-size: 7px;
            margin-top: 2px;
        }

        .item-meta-label {
            color: #b90000;
            font-weight: 700;
        }

        .summary-table {
            margin-top: 7px;
            margin-{{ $tripoliDirection === 'rtl' ? 'right' : 'left' }}: auto;
            width: 47%;
            page-break-inside: avoid;
        }

        .summary-table td {
            border: 1px solid #111;
            padding: 3px 6px;
        }

        .summary-label {
            font-weight: 700;
        }

        .summary-value {
            direction: ltr;
            text-align: {{ $tripoliDirection === 'rtl' ? 'left' : 'right' }};
            white-space: nowrap;
        }

        .summary-total td {
            border-width: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .summary-total .summary-value {
            color: #b90000;
        }

        .amount-words {
            border: 2px solid #111;
            margin-top: 7px;
            padding: 5px 7px;
            page-break-inside: avoid;
        }

        .amount-words-label {
            color: #b90000;
            font-size: 8px;
            font-weight: 700;
        }

        .amount-words-value {
            display: inline;
            font-size: 9px;
            font-weight: 700;
            margin-{{ $tripoliDirection === 'rtl' ? 'right' : 'left' }}: 8px;
        }

        .closing-table {
            margin-top: 7px;
            page-break-inside: avoid;
            table-layout: fixed;
        }

        .closing-table td {
            border: 2px solid #111;
            height: 50px;
            padding: 5px 7px;
            vertical-align: top;
            width: 50%;
        }

        .closing-table td:first-child {
            border-{{ $tripoliDirection === 'rtl' ? 'left' : 'right' }}: 0;
        }

        .notes-label,
        .signature-label {
            font-size: 9px;
            font-weight: 700;
        }

        .notes-content {
            color: #3e393e;
            font-size: 8px;
            margin-top: 3px;
        }

        .signature-line {
            border-bottom: 1px solid #777;
            height: 25px;
            margin-top: 3px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 3px solid #bf514c;
            padding-top: 3px;
            color: #111;
            font-size: 8px;
            font-weight: 700;
            direction: ltr;
        }

        .footer-table {
            width: 100%;
        }

        .footer-table td {
            border: 0;
            padding: 0;
        }

        .footer-email {
            text-align: left;
        }

        .footer-phones {
            text-align: right;
        }
    </style>

    @includeIf('app.pdf.partials.fonts')
</head>
<body>
    <div class="document">
        <table class="brand-table">
            <tr>
                <td class="brand-logo-cell">
                    <img
                        class="brand-logo"
                        src="{{ \App\Space\ImageUtils::toBase64Src($tripoliLogoPath) }}"
                        alt="{{ $tripoliLabels['logo_alt'] }}"
                    >
                </td>
                <td class="company-identity">
                    <div class="company-name">{{ $tripoliLabels['company_name'] }}</div>
                    <div class="company-tagline">{{ $tripoliLabels['company_tagline'] }}</div>
                </td>
            </tr>
        </table>

        <div class="services">
            {{ $tripoliLabels['services'] }}<br>
            {{ $tripoliLabels['services_secondary'] }}
        </div>

        <div class="invoice-title">
            <div class="invoice-title-box">{{ $tripoliLabels['invoice'] }}</div>
        </div>

        <table class="information-table">
            <tr>
                <td>
                    <div class="information-panel">
                        <table class="address-grid">
                            <tr>
                                <td>
                                    <div class="panel-label">{{ $tripoliLabels['bill_to'] }}</div>
                                    <div class="address-content">{!! $billing_address !!}</div>
                                </td>
                                <td>
                                    @if ($shipping_address)
                                        <div class="panel-label">{{ $tripoliLabels['ship_to'] }}</div>
                                        <div class="address-content">{!! $shipping_address !!}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td>
                    <div class="information-panel">
                        <table class="metadata-table">
                            <tr>
                                <td class="metadata-label">{{ $tripoliLabels['invoice_number'] }}</td>
                                <td class="metadata-value">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="metadata-label">{{ $tripoliLabels['invoice_date'] }}</td>
                                <td class="metadata-value">{{ $invoice->formattedInvoiceDate }}</td>
                            </tr>
                            <tr>
                                <td class="metadata-label">{{ $tripoliLabels['due_date'] }}</td>
                                <td class="metadata-value">{{ $invoice->formattedDueDate }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="items-shell">
            <img
                class="watermark"
                src="{{ \App\Space\ImageUtils::toBase64Src($tripoliWatermarkPath) }}"
                alt=""
            >

            <table class="items-table">
                <thead>
                    <tr>
                        @if ($tripoliDirection === 'rtl')
                            <th style="width: 20%">{{ $tripoliLabels['total'] }}</th>
                            <th style="width: 18%">{{ $tripoliLabels['unit_price'] }}</th>
                            <th style="width: 12%">{{ $tripoliLabels['quantity'] }}</th>
                            <th style="width: 43%">{{ $tripoliLabels['description'] }}</th>
                            <th style="width: 7%">{{ $tripoliLabels['number'] }}</th>
                        @else
                            <th style="width: 7%">{{ $tripoliLabels['number'] }}</th>
                            <th style="width: 43%">{{ $tripoliLabels['description'] }}</th>
                            <th style="width: 12%">{{ $tripoliLabels['quantity'] }}</th>
                            <th style="width: 18%">{{ $tripoliLabels['unit_price'] }}</th>
                            <th style="width: 20%">{{ $tripoliLabels['total'] }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            @if ($tripoliDirection === 'rtl')
                                <td class="numeric-cell">{!! format_money_pdf($item->total, $invoice->customer->currency, $tripoliLocale) !!}</td>
                                <td class="numeric-cell">{!! format_money_pdf($item->price, $invoice->customer->currency, $tripoliLocale) !!}</td>
                                <td class="numeric-cell">
                                    {{ $item->quantity }}
                                    @if ($item->unit_name)
                                        {{ format_unit_name($item->unit_name, $tripoliLocale) }}
                                    @endif
                                </td>
                                <td class="description-cell">
                                    @include('pdf_templates::invoice.partials.tripoli-center-item-description')
                                </td>
                                <td class="numeric-cell">{{ $loop->iteration }}</td>
                            @else
                                <td class="numeric-cell">{{ $loop->iteration }}</td>
                                <td class="description-cell">
                                    @include('pdf_templates::invoice.partials.tripoli-center-item-description')
                                </td>
                                <td class="numeric-cell">
                                    {{ $item->quantity }}
                                    @if ($item->unit_name)
                                        {{ format_unit_name($item->unit_name, $tripoliLocale) }}
                                    @endif
                                </td>
                                <td class="numeric-cell">{!! format_money_pdf($item->price, $invoice->customer->currency, $tripoliLocale) !!}</td>
                                <td class="numeric-cell">{!! format_money_pdf($item->total, $invoice->customer->currency, $tripoliLocale) !!}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <table class="summary-table">
            <tr>
                <td class="summary-label">{{ $tripoliLabels['subtotal'] }}</td>
                <td class="summary-value">{!! format_money_pdf($invoice->sub_total, $invoice->customer->currency, $tripoliLocale) !!}</td>
            </tr>

            @if ((float) $invoice->discount_val !== 0.0 && $invoice->discount_per_item === 'NO')
                <tr>
                    <td class="summary-label">
                        {{ $tripoliLabels['discount'] }}
                        @if ($invoice->discount_type === 'percentage')
                            ({{ $invoice->discount }}%)
                        @endif
                    </td>
                    <td class="summary-value">{!! format_money_pdf($invoice->discount_val, $invoice->customer->currency, $tripoliLocale) !!}</td>
                </tr>
            @endif

            @if ($invoice->tax_included)
                <tr>
                    <td class="summary-label">{{ $tripoliLabels['net_total'] }}</td>
                    <td class="summary-value">
                        {!! format_money_pdf($invoice->sub_total - $invoice->discount - $invoice->tax, $invoice->customer->currency, $tripoliLocale) !!}
                    </td>
                </tr>
            @endif

            @if ($invoice->tax_per_item === 'YES')
                @foreach ($taxes as $tax)
                    <tr>
                        <td class="summary-label">
                            {{ $tax->name }}
                            @if ($tax->calculation_type === 'fixed')
                                ({!! format_money_pdf($tax->fixed_amount, $invoice->customer->currency, $tripoliLocale) !!})
                            @else
                                ({{ $tax->percent }}%)
                            @endif
                        </td>
                        <td class="summary-value">{!! format_money_pdf($tax->amount, $invoice->customer->currency, $tripoliLocale) !!}</td>
                    </tr>
                @endforeach
            @else
                @foreach ($invoice->taxes as $tax)
                    <tr>
                        <td class="summary-label">
                            {{ $tax->name }}
                            @if ($tax->calculation_type === 'fixed')
                                ({!! format_money_pdf($tax->fixed_amount, $invoice->customer->currency, $tripoliLocale) !!})
                            @else
                                ({{ $tax->percent }}%)
                            @endif
                        </td>
                        <td class="summary-value">{!! format_money_pdf($tax->amount, $invoice->customer->currency, $tripoliLocale) !!}</td>
                    </tr>
                @endforeach
            @endif

            <tr class="summary-total">
                <td class="summary-label">{{ $tripoliLabels['total'] }}</td>
                <td class="summary-value">{!! format_money_pdf($invoice->total, $invoice->customer->currency, $tripoliLocale) !!}</td>
            </tr>

            @if (in_array($invoice->paid_status, [\App\Models\Invoice::STATUS_PARTIALLY_PAID, \App\Models\Invoice::STATUS_PAID], true))
                <tr>
                    <td class="summary-label">{{ $tripoliLabels['amount_paid'] }}</td>
                    <td class="summary-value">
                        {!! format_money_pdf($invoice->total - $invoice->due_amount, $invoice->customer->currency, $tripoliLocale) !!}
                    </td>
                </tr>
                <tr>
                    <td class="summary-label">{{ $tripoliLabels['amount_due'] }}</td>
                    <td class="summary-value">{!! format_money_pdf($invoice->due_amount, $invoice->customer->currency, $tripoliLocale) !!}</td>
                </tr>
            @endif
        </table>

        <div class="amount-words">
            <span class="amount-words-label">{{ $tripoliLabels['amount_in_words'] }}</span>
            <span class="amount-words-value">
                {{ $tripoliAmountInWords ?: strip_tags(format_money_pdf($invoice->total, $invoice->customer->currency, $tripoliLocale)) }}
            </span>
        </div>

        <table class="closing-table">
            <tr>
                <td>
                    <div class="notes-label">{{ $tripoliLabels['notes'] }}</div>
                    @if ($notes)
                        <div class="notes-content">{!! $notes !!}</div>
                    @endif
                </td>
                <td>
                    <div class="signature-label">{{ $tripoliLabels['signature'] }}</div>
                    <div class="signature-line"></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-email">E-mail: Tripoli.center11@gmail.com</td>
                <td class="footer-phones">094-582-1748 &nbsp;&nbsp;&nbsp; 091-024-4048</td>
            </tr>
        </table>
    </div>
</body>
</html>
