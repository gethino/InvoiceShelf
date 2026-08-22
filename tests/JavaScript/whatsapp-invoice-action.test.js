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
  assert.match(
    englishMessages.invoices.whatsapp_message,
    /hope you are doing well/,
  )
  assert.match(englishMessages.invoices.whatsapp_message_copied, /copied/)
  assert.match(
    englishMessages.invoices.whatsapp_message_copy_failed,
    /could not be copied/,
  )
  assert.equal(arabicMessages.invoices.send_via_whatsapp, 'إرسال عبر واتساب')
  assert.match(arabicMessages.invoices.whatsapp_message, /\{url\}/)
  assert.match(arabicMessages.invoices.whatsapp_message, /نأمل أن تكون بخير/)
  assert.match(arabicMessages.invoices.whatsapp_message_copied, /تم نسخ/)
  assert.match(
    arabicMessages.invoices.whatsapp_message_copy_failed,
    /تعذّر نسخ/,
  )
})

test('copies the full message without delaying WhatsApp', () => {
  const copyPosition = invoiceDropdown.indexOf(
    'utils.copyTextToClipboard(message).then(notifyWhatsAppCopyResult)',
  )
  const openPosition = invoiceDropdown.indexOf('window.open(', copyPosition)

  assert.notEqual(copyPosition, -1)
  assert.notEqual(openPosition, -1)
  assert.ok(copyPosition < openPosition)
  assert.doesNotMatch(invoiceDropdown, /await utils\.copyTextToClipboard/)
})
