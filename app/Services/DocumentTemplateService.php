<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Space\PdfTemplateUtils;

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
        return [
            'allowed_invoice_templates' => $this->allowedNames(self::INVOICE, $companyId),
            'default_invoice_template' => $this->defaultName(self::INVOICE, $companyId),
            'allowed_estimate_templates' => $this->allowedNames(self::ESTIMATE, $companyId),
            'default_estimate_template' => $this->defaultName(self::ESTIMATE, $companyId),
        ];
    }

    /** @param array<string, mixed> $configuration */
    public function save(int $companyId, array $configuration): void
    {
        CompanySetting::setSettings([
            'allowed_invoice_templates' => json_encode(array_values($configuration['allowed_invoice_templates']), JSON_THROW_ON_ERROR),
            'default_invoice_template' => $configuration['default_invoice_template'],
            'allowed_estimate_templates' => json_encode(array_values($configuration['allowed_estimate_templates']), JSON_THROW_ON_ERROR),
            'default_estimate_template' => $configuration['default_estimate_template'],
        ], $companyId);
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
