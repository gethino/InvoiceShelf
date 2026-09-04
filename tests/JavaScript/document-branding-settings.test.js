import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

test('company document settings expose html, image, watermark and stamp controls', () => {
  const settings = readProjectFile(
    'resources/scripts/admin/views/settings/DocumentTemplatesSetting.vue',
  )

  for (const asset of ['header', 'footer', 'watermark', 'paid_stamp']) {
    assert.match(settings, new RegExp(`document-branding/\\$\\{asset\\}|['\"]${asset}['\"]`))
  }

  assert.match(settings, /header_mode: 'none'/)
  assert.match(settings, /footer_mode: 'none'/)
  assert.match(settings, /settings\.header_mode === 'html'/)
  assert.match(settings, /settings\.footer_mode === 'image'/)
})

test('payment and paid invoice views expose paid stamp checkboxes', () => {
  const payment = readProjectFile(
    'resources/scripts/admin/views/payments/Create.vue',
  )
  const invoice = readProjectFile(
    'resources/scripts/admin/views/invoices/View.vue',
  )

  assert.match(payment, /currentPayment\.show_paid_stamp/)
  assert.match(invoice, /invoiceData\.paid_stamp_eligible/)
  assert.match(invoice, /\/paid-stamp/)
})
