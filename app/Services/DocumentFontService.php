<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;

final class DocumentFontService
{
    public const SETTING = 'document_template_font';

    public const DEFAULT = 'poppins';

    /**
     * @var array<string, array{label: string, family: string}>
     */
    private const FONTS = [
        'poppins' => [
            'label' => 'Poppins',
            'family' => 'Poppins',
        ],
        'almarai' => [
            'label' => 'Almarai',
            'family' => 'Almarai',
        ],
    ];

    /** @return array<int, array{value: string, label: string}> */
    public function options(): array
    {
        return array_map(
            fn (string $value, array $font): array => [
                'value' => $value,
                'label' => $font['label'],
            ],
            array_keys(self::FONTS),
            self::FONTS,
        );
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_keys(self::FONTS);
    }

    public function valueFor(Company|int $company): string
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $font = CompanySetting::getSetting(self::SETTING, $companyId);

        return is_string($font) && array_key_exists($font, self::FONTS)
            ? $font
            : self::DEFAULT;
    }

    /** @return array{value: string, family: string, stack: string} */
    public function for(Company|int $company): array
    {
        $value = $this->valueFor($company);
        $family = self::FONTS[$value]['family'];

        return [
            'value' => $value,
            'family' => $family,
            'stack' => sprintf('"%s", "Almarai", "DejaVu Sans", sans-serif', $family),
        ];
    }
}
