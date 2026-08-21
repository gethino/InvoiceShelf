import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const invoiceDropdown = readFileSync(
  new URL(
    '../../resources/scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue',
    import.meta.url,
  ),
  'utf8',
)

const englishMessages = JSON.parse(
  readFileSync(new URL('../../lang/en.json', import.meta.url), 'utf8'),
)
const arabicMessages = JSON.parse(
  readFileSync(new URL('../../lang/ar.json', import.meta.url), 'utf8'),
)

test('shows the WhatsApp invoice action only with send permission', () => {
  assert.match(invoiceDropdown, /<WhatsAppIcon/)
  assert.match(invoiceDropdown, /abilities\.SEND_INVOICE/)
  assert.match(invoiceDropdown, /@click="sendInvoiceViaWhatsApp\(row\)"/)
})

test('provides localized WhatsApp labels and message templates', () => {
  assert.equal(englishMessages.invoices.send_via_whatsapp, 'Send via WhatsApp')
  assert.match(englishMessages.invoices.whatsapp_message, /\{url\}/)
  assert.equal(arabicMessages.invoices.send_via_whatsapp, 'إرسال عبر واتساب')
  assert.match(arabicMessages.invoices.whatsapp_message, /\{url\}/)
})
