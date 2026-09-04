import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

const customerForms = [
  'resources/scripts/admin/views/customers/Create.vue',
  'resources/scripts/admin/components/modal-components/CustomerModal.vue',
]

test('customer forms keep secondary data collapsed by default', () => {
  for (const path of customerForms) {
    const form = readProjectFile(path)

    assert.match(form, /const showOptionalInfo = ref\(false\)/, path)
    assert.match(form, /customers\.show_optional_info/, path)
    assert.match(form, /customers\.hide_optional_info/, path)
    assert.match(form, /v-if="showOptionalInfo"/, path)
  }
})

test('customer forms support optional organizations and disabled taxes', () => {
  for (const path of customerForms) {
    const form = readProjectFile(path)

    assert.match(form, /customerOrganizationField/, path)
    assert.match(form, /showOptionalInfo && taxesEnabled/, path)
    assert.match(form, /selectedCompanySettings\?\.taxes_enabled !== 'NO'/, path)
  }
})

test('customer phone controls use telephone bidi behavior', () => {
  for (const path of customerForms) {
    const form = readProjectFile(path)

    assert.match(form, /currentCustomer\.phone[\s\S]*?type="tel"/, path)
    assert.match(form, /billing\.phone[\s\S]*?type="tel"/, path)
  }
})

test('customer workflow strings are bilingual', () => {
  const english = JSON.parse(readProjectFile('lang/en.json'))
  const arabic = JSON.parse(readProjectFile('lang/ar.json'))
  const tripoliArabic = JSON.parse(
    readProjectFile('Modules/TripoliCustomizations/Resources/locales/ar.json'),
  )

  assert.equal(english.customers.show_optional_info, 'Show optional info')
  assert.equal(arabic.customers.show_optional_info, 'إظهار المعلومات الاختيارية')
  assert.equal(english.customers.display_name, 'Customer name')
  assert.equal(arabic.customers.display_name, 'اسم العميل (شخص)')
  assert.equal(
    arabic.customers.primary_contact_name,
    'اسم شخص الاتصال (اختياري)',
  )
  assert.equal(
    tripoliArabic.tripoli_customizations.customer_organization.label,
    'الشركة (اختياري)',
  )
  assert.doesNotMatch(arabic.customers.display_name, /العرض/)
})
