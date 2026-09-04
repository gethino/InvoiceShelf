import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

test('line discounts are revealed per row', () => {
  const row = readProjectFile(
    'resources/scripts/admin/components/estimate-invoice-common/CreateItemRow.vue',
  )

  assert.match(row, /const showDiscount = ref\(/)
  assert.match(row, /v-if="!showDiscount"/)
  assert.match(row, /ReceiptPercentIcon/)
  assert.match(row, /@click="showDiscount = true"/)
})

test('invoice sidebar requests only the current customer invoices', () => {
  const view = readProjectFile(
    'resources/scripts/admin/views/invoices/View.vue',
  )

  assert.match(view, /params\.customer_id = invoiceData\.value\.customer_id/)
  assert.match(view, /await loadInvoice\(\)[\s\S]*await loadInvoices\(\)/)
})

test('Arabic estimate statuses and recurring invoice UI are translated', () => {
  const arabic = JSON.parse(readProjectFile('lang/ar.json'))

  assert.equal(arabic.estimates.viewed, 'شوهد')
  assert.equal(arabic.estimates.expired, 'منتهي الصلاحية')
  assert.equal(arabic.recurring_invoices.title, 'الفواتير الدورية')
  assert.equal(arabic.recurring_invoices.frequency.every_week, 'كل أسبوع')

  const serialized = JSON.stringify(arabic.recurring_invoices)
  assert.doesNotMatch(serialized, /Recurring Invoice|Select Frequency|Every Week/)
})
