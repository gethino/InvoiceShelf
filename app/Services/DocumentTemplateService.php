<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Space\PdfTemplateUtils;
use App\Support\PdfHtmlSanitizer;

class DocumentTemplateService
{
    public const INVOICE = 'invoice';

    public const ESTIMATE = 'estimate';

    /**
     * @return array<int, array{name: string, path: string, custom: bool}>
     */
    public function catalog(string $type, string $imageFormat = 'base64'): array
    {
        return array_values(PdfTemplateUtils::getFormattedTemplates($type, $imageFormat));
    }

    /**
     * @return array<int, array{name: string, path: string, custom: bool}>
     */
    public function allowedTemplates(string $type, int $companyId, string $imageFormat = 'base64'): array
    {
        $templates = $this->catalog($type, $imageFormat);
        $configuredNames = $this->configuredNames($type, $companyId);

        if ($configuredNames === null) {
            return $templates;
        }

        $allowedTemplates = array_values(array_filter(
            $templates,
            fn (array $template): bool => in_array($template['name'], $configuredNames, true)
        ));

        return $allowedTemplates ?: $templates;
    }

    /** @return array<int, string> */
    public function allowedNames(string $type, int $companyId): array
    {
        return array_column($this->allowedTemplates($type, $companyId, ''), 'name');
    }

    public function defaultName(string $type, int $companyId): string
    {
        $allowedNames = $this->allowedNames($type, $companyId);
        $configuredDefault = CompanySetting::getSetting($this->defaultSettingKey($type), $companyId);

        if ($configuredDefault && in_array($configuredDefault, $allowedNames, true)) {
            return $configuredDefault;
        }

        $legacyDefault = $type === self::INVOICE ? 'invoice1' : 'estimate1';

        return in_array($legacyDefault, $allowedNames, true)
            ? $legacyDefault
            : $allowedNames[0];
    }

    /**
     * @return array{allowed_invoice_templates: array<int, string>, default_invoice_template: string, allowed_estimate_templates: array<int, string>, default_estimate_template: string}
     */
    public function configuration(int $companyId): array
    {
        $company = Company::query()->findOrFail($companyId);

        return [
            'allowed_invoice_templates' => $this->allowedNames(self::INVOICE, $companyId),
            'default_invoice_template' => $this->defaultName(self::INVOICE, $companyId),
            'allowed_estimate_templates' => $this->allowedNames(self::ESTIMATE, $companyId),
            'default_estimate_template' => $this->defaultName(self::ESTIMATE, $companyId),
            'header_mode' => CompanySetting::getSetting('document_header_mode', $companyId) ?? 'none',
            'header_html' => CompanySetting::getSetting('document_header_html', $companyId) ?? '',
            'footer_mode' => CompanySetting::getSetting('document_footer_mode', $companyId) ?? 'none',
            'footer_html' => CompanySetting::getSetting('document_footer_html', $companyId) ?? '',
            'header_url' => $company->documentBrandingAssetUrl('header'),
            'footer_url' => $company->documentBrandingAssetUrl('footer'),
            'watermark_url' => $company->documentBrandingAssetUrl('watermark'),
            'paid_stamp_url' => $company->documentBrandingAssetUrl('paid_stamp'),
        ];
    }

    /** @param array<string, mixed> $configuration */
    public function save(int $companyId, array $configuration): void
    {
        $settings = [
            'allowed_invoice_templates' => json_encode(array_values($configuration['allowed_invoice_templates']), JSON_THROW_ON_ERROR),
            'default_invoice_template' => $configuration['default_invoice_template'],
            'allowed_estimate_templates' => json_encode(array_values($configuration['allowed_estimate_templates']), JSON_THROW_ON_ERROR),
            'default_estimate_template' => $configuration['default_estimate_template'],
        ];

        if (array_key_exists('header_mode', $configuration)) {
            $settings['document_header_mode'] = $configuration['header_mode'];
            $settings['document_header_html'] = PdfHtmlSanitizer::sanitize($configuration['header_html'] ?? '');
        }

        if (array_key_exists('footer_mode', $configuration)) {
            $settings['document_footer_mode'] = $configuration['footer_mode'];
            $settings['document_footer_html'] = PdfHtmlSanitizer::sanitize($configuration['footer_html'] ?? '');
        }

        CompanySetting::setSettings($settings, $companyId);
    }

    /** @return array<int, string>|null */
    private function configuredNames(string $type, int $companyId): ?array
    {
        $value = CompanySetting::getSetting($this->allowedSettingKey($type), $companyId);

        if ($value === null) {
            return null;
        }

        $names = json_decode($value, true);

        return is_array($names) ? $names : null;
    }

    private function allowedSettingKey(string $type): string
    {
        return "allowed_{$type}_templates";
    }

    private function defaultSettingKey(string $type): string
    {
        return "default_{$type}_template";
    }
}
