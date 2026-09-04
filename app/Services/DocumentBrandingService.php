<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\App;

class DocumentBrandingService
{
    /** @return array<string, string|null> */
    public function for(Company $company): array
    {
        $brandColor = CompanySetting::getSetting('brand_color', $company->id);

        if (! is_string($brandColor) || ! preg_match('/^#[0-9a-f]{6}$/i', $brandColor)) {
            $brandColor = '#5851d8';
        }

        return [
            'direction' => App::isLocale('ar') ? 'rtl' : 'ltr',
            'locale' => App::getLocale(),
            'brand_color' => $brandColor,
            'header_mode' => $this->mode('document_header_mode', $company->id),
            'header_html' => CompanySetting::getSetting('document_header_html', $company->id) ?? '',
            'header_image' => $company->documentBrandingAssetDataUri('header'),
            'footer_mode' => $this->mode('document_footer_mode', $company->id),
            'footer_html' => CompanySetting::getSetting('document_footer_html', $company->id) ?? '',
            'footer_image' => $company->documentBrandingAssetDataUri('footer'),
            'watermark_image' => $company->documentBrandingAssetDataUri('watermark'),
            'paid_stamp_image' => $company->documentBrandingAssetDataUri('paid_stamp'),
        ];
    }

    private function mode(string $key, int $companyId): string
    {
        $mode = CompanySetting::getSetting($key, $companyId);

        return in_array($mode, ['image', 'html'], true) ? $mode : 'none';
    }
}
