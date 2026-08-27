<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ArabicLocalizationTest extends TestCase
{
    public function test_it_uses_formal_libyan_terminology_for_invoicing(): void
    {
        $translations = $this->translations('lang/ar.json');
        $expectedTranslations = [
            'navigation.items' => 'الأصناف',
            'navigation.recurring-invoices' => 'الفواتير الدورية',
            'navigation.expenses' => 'المصروفات',
            'navigation.estimates' => 'عروض الأسعار',
            'navigation.payments' => 'المدفوعات',
            'general.tax' => 'ضريبة',
            'customers.state' => 'المنطقة/البلدية',
            'customers.amount_due' => 'المبلغ المستحق',
            'payments.payment_mode' => 'طريقة الدفع',
            'expenses.receipt' => 'الإيصال',
            'pdf_bill_to' => 'الفاتورة إلى:',
            'pdf_ship_to' => 'عنوان الشحن:',
        ];

        foreach ($expectedTranslations as $key => $translation) {
            self::assertSame($translation, data_get($translations, $key));
        }
    }

    public function test_it_does_not_contain_replaced_regional_accounting_terms(): void
    {
        $translations = $this->translations('lang/ar.json');
        $translationValues = [];

        array_walk_recursive($translations, function (mixed $value) use (&$translationValues): void {
            if (is_string($value)) {
                $translationValues[] = $value;
            }
        });

        $translationsText = implode("\n", $translationValues);

        self::assertStringNotContainsString('الدفوعات', $translationsText);
        self::assertStringNotContainsString('الاداءات', $translationsText);
        self::assertStringNotContainsString('التقديرات', $translationsText);
        self::assertStringNotContainsString('الولاية/المنطقة', $translationsText);
        self::assertStringNotContainsString('الفواتير المتكررة', $translationsText);
    }

    public function test_it_uses_consistent_organization_terminology_in_tripoli_customizations(): void
    {
        $translations = $this->translations('Modules/TripoliCustomizations/Resources/locales/ar.json');

        self::assertSame(
            'جهات العملاء',
            data_get($translations, 'tripoli_customizations.organizations.title'),
        );
        self::assertSame(
            'جهة العميل (اختياري)',
            data_get($translations, 'tripoli_customizations.customer_organization.label'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function translations(string $path): array
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$path);

        self::assertNotFalse($contents);

        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }
}
