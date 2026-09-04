<span class="item-name">{{ $item->name }}</span>

@if ($item->description)
    <span class="item-description">{!! nl2br(e($item->description)) !!}</span>
@endif

@foreach ($customFields as $field)
    @php($tripoliCustomFieldValue = $item->getCustomFieldValueBySlug($field->slug))
    @if ($tripoliCustomFieldValue !== null && $tripoliCustomFieldValue !== '')
        <span class="item-meta">
            <span class="item-meta-label">{{ $field->label }}:</span>
            {{ $tripoliCustomFieldValue }}
        </span>
    @endif
@endforeach

@if ($invoice->discount_per_item === 'YES' && (float) $item->discount_val !== 0.0)
    <span class="item-meta">
        <span class="item-meta-label">{{ $tripoliLabels['discount'] }}:</span>
        @if ($item->discount_type === 'percentage')
            {{ $item->discount }}%
        @else
            {!! format_money_pdf($item->discount_val, $invoice->customer->currency, $tripoliLocale) !!}
        @endif
    </span>
@endif

@if ($invoice->tax_per_item === 'YES' && $item->tax > 0)
    <span class="item-meta">
        <span class="item-meta-label">{{ $tripoliLabels['tax'] }}:</span>
        {!! format_money_pdf($item->tax, $invoice->customer->currency, $tripoliLocale) !!}
    </span>
@endif
