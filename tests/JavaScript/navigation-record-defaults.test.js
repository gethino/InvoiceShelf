import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { findCashPaymentMethod } from '../../resources/scripts/helpers/payment-methods.js'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

test('finds Cash case-insensitively without choosing a fallback', () => {
  assert.equal(findCashPaymentMethod([{ id: 4, name: ' cash ' }])?.id, 4)
  assert.equal(findCashPaymentMethod([{ id: 8, name: 'Card' }]), undefined)
})

test('new payments and expenses default to Cash after methods load', () => {
  const payment = readProjectFile('resources/scripts/admin/stores/payment.js')
  const expense = readProjectFile(
    'resources/scripts/admin/views/expenses/Create.vue',
  )

  assert.match(payment, /findCashPaymentMethod\(this\.paymentModes\)\?\.id/)
  assert.match(expense, /findCashPaymentMethod\(expenseStore\.paymentModes\)\?\.id/)
})

test('dashboard greeting and mobile company identity are localized', () => {
  const dashboard = readProjectFile(
    'resources/scripts/admin/views/dashboard/Dashboard.vue',
  )
  const sidebar = readProjectFile(
    'resources/scripts/admin/layouts/partials/TheSiteSidebar.vue',
  )
  const english = JSON.parse(readProjectFile('lang/en.json'))
  const arabic = JSON.parse(readProjectFile('lang/ar.json'))

  assert.match(dashboard, /\$t\('dashboard\.hello'\)/)
  assert.match(dashboard, /<bdi dir="auto">/)
  assert.equal(english.dashboard.hello, 'Hello,')
  assert.equal(arabic.dashboard.hello, 'مرحباً،')
  assert.match(sidebar, /companyStore\.selectedCompany\?\.logo/)
  assert.doesNotMatch(sidebar, /MainLogo|InvoiceShelf Logo/)
})
