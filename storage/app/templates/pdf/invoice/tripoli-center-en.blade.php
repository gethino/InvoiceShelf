@php
    $tripoliLocale = 'en';
    $tripoliDirection = 'ltr';
    $tripoliLabels = [
        'invoice' => 'Invoice',
        'invoice_number' => 'Invoice No.',
        'invoice_date' => 'Issue Date',
        'due_date' => 'Due Date',
        'bill_to' => 'Bill To',
        'ship_to' => 'Ship To',
        'number' => 'No.',
        'description' => 'Description',
        'quantity' => 'Qty',
        'unit_price' => 'Unit Price',
        'discount' => 'Discount',
        'tax' => 'Tax',
        'subtotal' => 'Subtotal',
        'net_total' => 'Net Total',
        'total' => 'Total Amount',
        'amount_paid' => 'Amount Paid',
        'amount_due' => 'Balance Due',
        'amount_in_words' => 'Amount in Words',
        'notes' => 'Notes',
        'signature' => 'Signature',
        'company_name' => 'Tripoli First Company',
        'company_tagline' => 'Media, Technical, Publicity & Advertising Services',
        'services' => 'Document copying · Computer printing · Map copying & printing · Thermal binding (premium / standard) · Spiral binding · ID card printing',
        'services_secondary' => 'Research papers & graduation projects · Mug printing',
        'logo_alt' => 'Tripoli Center logo',
    ];
@endphp

@include('pdf_templates::invoice.partials.tripoli-center-layout')
