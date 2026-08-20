@php
    $tripoliLocale = 'ar';
    $tripoliDirection = 'rtl';
    $tripoliLabels = [
        'invoice' => 'فاتورة نهائية',
        'invoice_number' => 'رقم الفاتورة',
        'invoice_date' => 'تاريخ الإصدار',
        'due_date' => 'تاريخ الاستحقاق',
        'bill_to' => 'فاتورة إلى',
        'ship_to' => 'الشحن إلى',
        'number' => 'الرقم',
        'description' => 'الصنف / الوصف',
        'quantity' => 'الكمية',
        'unit_price' => 'سعر الوحدة',
        'discount' => 'الخصم',
        'tax' => 'الضريبة',
        'subtotal' => 'المجموع الفرعي',
        'net_total' => 'الصافي',
        'total' => 'الإجمالي',
        'amount_paid' => 'المدفوع',
        'amount_due' => 'الباقي',
        'amount_in_words' => 'المبلغ بالحروف',
        'notes' => 'ملاحظات',
        'signature' => 'التوقيع',
        'company_name' => 'شركة طرابلس الأولى',
        'company_tagline' => 'للخدمات الإعلامية والفنية والدعائية والإعلان',
        'services' => 'تصوير مستندات · طباعة كمبيوتر · سحب وتصوير خرائط · تجليد حراري (فاخر / عادي) · تجليد حلزوني · طباعة كروت الهوية',
        'services_secondary' => 'طباعة بحوث ومشاريع التخرج · طباعة على الأكواب',
        'logo_alt' => 'شعار مركز طرابلس',
    ];
@endphp

@include('pdf_templates::invoice.partials.tripoli-center-layout')
