import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import {
  formatMoney,
  formatMoneyInputDisplay,
  getCurrencyPresentation,
  isCurrencySymbolOnRight,
  normalizeMoneyInput,
  parseMoneyInput,
} from '../../resources/scripts/helpers/currency-format.js'

const lyd = {
  code: 'LYD',
  symbol: 'LD',
  precision: 3,
  decimal_separator: '.',
  thousand_separator: ',',
  swap_currency_symbol: false,
}

const baseMoney = readFileSync(
  new URL(
    '../../resources/scripts/components/base/BaseMoney.vue',
    import.meta.url,
  ),
  'utf8',
)

test('uses locale-aware LYD symbols and positions', () => {
  assert.deepEqual(getCurrencyPresentation(lyd, 'en'), {
    symbol: 'LYD',
    symbolAfterAmount: true,
  })
  assert.deepEqual(getCurrencyPresentation(lyd, 'ar_LY'), {
    symbol: 'د.ل',
    symbolAfterAmount: false,
  })
})

test('places currency affixes away from the amount in LTR and RTL inputs', () => {
  assert.equal(
    isCurrencySymbolOnRight(getCurrencyPresentation(lyd, 'en'), 'en'),
    true,
  )
  assert.equal(
    isCurrencySymbolOnRight(getCurrencyPresentation(lyd, 'ar'), 'ar'),
    true,
  )
  assert.equal(
    isCurrencySymbolOnRight({ symbol: '€', symbolAfterAmount: false }, 'en'),
    false,
  )
  assert.equal(
    isCurrencySymbolOnRight({ symbol: 'ден', symbolAfterAmount: true }, 'ar'),
    false,
  )
})

test('reserves physical input space for the currency affix', () => {
  assert.match(baseMoney, /isCurrencySymbolOnRight \? 'right-3' : 'left-3'/)
  assert.match(baseMoney, /isCurrencySymbolOnRight \? 'pr-14' : 'pl-14'/)
  assert.doesNotMatch(baseMoney, /symbolAfterAmount \? 'pe-14' : 'ps-14'/)
})

test('formats LYD for English and Arabic', () => {
  assert.equal(formatMoney(10000, lyd, 'en'), '100.000 LYD')
  assert.equal(formatMoney(10000, lyd, 'ar'), 'د.ل 100.000')
})

test('preserves other currency presentation', () => {
  const euro = {
    ...lyd,
    code: 'EUR',
    symbol: '€',
    precision: 2,
    swap_currency_symbol: false,
  }
  const denar = {
    ...euro,
    code: 'MKD',
    symbol: 'ден',
    swap_currency_symbol: true,
  }

  assert.equal(formatMoney(10000, euro, 'ar'), '€ 100.00')
  assert.equal(formatMoney(10000, denar, 'en'), '100.00 ден')
})

test('keeps whole-number typing natural', () => {
  assert.equal(normalizeMoneyInput('333', lyd), '333')
  assert.equal(parseMoneyInput('333', lyd), 333)
})

test('limits editing to two meaningful fraction digits', () => {
  assert.equal(normalizeMoneyInput('333.250', lyd), '333.25')
  assert.equal(normalizeMoneyInput('333.125', lyd), '333.12')
  assert.equal(formatMoneyInputDisplay(333.25, lyd), '333.250')
})

test('accepts localized digits, decimal input, paste, and clearing', () => {
  assert.equal(normalizeMoneyInput('١٬٢٣٤٫٥٠', lyd), '1234.50')
  assert.equal(parseMoneyInput('-1,234.50', lyd), -1234.5)
  assert.equal(normalizeMoneyInput('', lyd), '')
  assert.equal(parseMoneyInput('', lyd), null)
  assert.equal(formatMoneyInputDisplay(0, lyd), '')
})
