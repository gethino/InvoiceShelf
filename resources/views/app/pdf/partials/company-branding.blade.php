@php
    $brandingCompany = $company
        ?? ($payment->company ?? null)
        ?? ($estimate->company ?? null)
        ?? ($invoice->company ?? null);
    $documentBranding = $brandingCompany
        ? app(\App\Services\DocumentBrandingService::class)->for($brandingCompany)
        : null;
    $showDocumentPaidStamp = $documentBranding && $documentBranding['paid_stamp_image'] && match ($documentBrandingType ?? null) {
        'payment' => (bool) $payment->show_paid_stamp,
        'invoice' => (bool) $invoice->show_paid_stamp
            && $invoice->paid_status === \App\Models\Invoice::STATUS_PAID,
        default => false,
    };
@endphp

@if ($documentBranding)
    <style>
        body {
            direction: {{ $documentBranding['direction'] }};
            font-family: "DejaVu Sans", sans-serif;
            padding-top: {{ $documentBranding['header_mode'] === 'none' ? '0' : '54px' }};
            padding-bottom: {{ $documentBranding['footer_mode'] === 'none' ? '0' : '46px' }};
        }

        .company-document-header,
        .company-document-footer {
            position: fixed;
            right: 0;
            left: 0;
            z-index: 20;
            color: {{ $documentBranding['brand_color'] }};
            text-align: {{ $documentBranding['direction'] === 'rtl' ? 'right' : 'left' }};
        }

        .company-document-header {
            top: -42px;
            height: 70px;
        }

        .company-document-footer {
            bottom: -42px;
            height: 58px;
        }

        .company-document-header img,
        .company-document-footer img {
            display: block;
            width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .company-document-watermark {
            position: fixed;
            top: 29%;
            right: 15%;
            left: 15%;
            z-index: -1;
            text-align: center;
            opacity: 0.07;
        }

        .company-document-watermark img {
            width: 70%;
            max-height: 360px;
            object-fit: contain;
        }

        .company-document-paid-stamp {
            position: fixed;
            right: {{ $documentBranding['direction'] === 'rtl' ? 'auto' : '28px' }};
            bottom: 28px;
            left: {{ $documentBranding['direction'] === 'rtl' ? '28px' : 'auto' }};
            z-index: 30;
            width: 105px;
            max-height: 105px;
        }
    </style>

    @if ($documentBranding['header_mode'] === 'image' && $documentBranding['header_image'])
        <div class="company-document-header" aria-hidden="true">
            <img src="{{ $documentBranding['header_image'] }}" alt="">
        </div>
    @elseif ($documentBranding['header_mode'] === 'html' && $documentBranding['header_html'])
        <div class="company-document-header">{!! $documentBranding['header_html'] !!}</div>
    @endif

    @if ($documentBranding['footer_mode'] === 'image' && $documentBranding['footer_image'])
        <div class="company-document-footer" aria-hidden="true">
            <img src="{{ $documentBranding['footer_image'] }}" alt="">
        </div>
    @elseif ($documentBranding['footer_mode'] === 'html' && $documentBranding['footer_html'])
        <div class="company-document-footer">{!! $documentBranding['footer_html'] !!}</div>
    @endif

    @if ($documentBranding['watermark_image'])
        <div class="company-document-watermark" aria-hidden="true">
            <img src="{{ $documentBranding['watermark_image'] }}" alt="">
        </div>
    @endif

    @if ($showDocumentPaidStamp)
        <img class="company-document-paid-stamp" src="{{ $documentBranding['paid_stamp_image'] }}" alt="@lang('paid_stamp')">
    @endif
@endif
