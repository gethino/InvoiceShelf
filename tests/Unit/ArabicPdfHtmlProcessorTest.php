<?php

use App\Support\ArabicPdfHtmlProcessor;
use ArPHP\I18N\Arabic;

it('shapes Arabic while preserving HTML and non-Arabic content', function () {
    $processor = new ArabicPdfHtmlProcessor(new Arabic);
    $html = '<html lang="ar"><body><strong>فاتورة العميل</strong> INV-42</body></html>';

    $processed = $processor->process($html);

    expect($processed)
        ->toContain('<html lang="ar"><body><strong>')
        ->toContain('</strong> INV-42</body></html>')
        ->not->toContain('فاتورة العميل');

    expect(preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $processed))->toBe(1);
});

it('returns non-Arabic HTML unchanged', function () {
    $processor = new ArabicPdfHtmlProcessor(new Arabic);
    $html = '<p>Invoice INV-42</p>';

    expect($processor->process($html))->toBe($html);
});
