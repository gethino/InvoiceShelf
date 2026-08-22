import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import {
  formatMoney,
  formatMoneyInputDisplay,
  getCurrencyPresentation,
  getMoneyInputFractionDigits,
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
  assert.equal(formatMoney(10000, lyd, 'en'), '100 LYD')
  assert.equal(formatMoney(10000, lyd, 'ar'), 'د.ل 100')
  assert.equal(formatMoney(10050, lyd, 'en'), '100.5 LYD')
  assert.equal(formatMoney(10055, lyd, 'ar'), 'د.ل 100.55')
})

test('preserves other currency presentation', () => {
  const euro = {
    ...lyd,
    code: 'EUR',
    symbol: '€',
    precision: 2,
    decimal_separator: ',',
    thousand_separator: '.',
    swap_currency_symbol: false,
  }
  const denar = {
    ...euro,
    code: 'MKD',
    symbol: 'ден',
    swap_currency_symbol: true,
  }

  assert.equal(formatMoney(123450, euro, 'ar'), '€ 1.234,5')
  assert.equal(formatMoney(123450, denar, 'en'), '1.234,5 ден')
})

test('keeps whole-number typing natural', () => {
  assert.equal(normalizeMoneyInput('333', lyd), '333')
  assert.equal(parseMoneyInput('333', lyd), 333)
})

test('limits editing to two meaningful fraction digits', () => {
  assert.equal(normalizeMoneyInput('333.250', lyd), '333.25')
  assert.equal(normalizeMoneyInput('333.125', lyd), '333.12')
  assert.equal(formatMoneyInputDisplay(333.25, lyd), '333.25')
})

test('preserves only explicitly typed fraction digits during editing', () => {
  for (const [typedValue, expected] of [
    ['10000', '10,000'],
    ['10000.5', '10,000.5'],
    ['10000.50', '10,000.50'],
    ['10000.00', '10,000.00'],
  ]) {
    const normalized = normalizeMoneyInput(typedValue, lyd)
    const fractionDigits = getMoneyInputFractionDigits(normalized, lyd)
    const parsed = parseMoneyInput(normalized, lyd)

    assert.equal(formatMoneyInputDisplay(parsed, lyd, fractionDigits), expected)
  }

  assert.match(baseMoney, /inputValue\.value \|\| props\.modelValue/)
  assert.match(baseMoney, /minimumFractionDigits\.value/)
})

test('accepts localized digits, decimal input, paste, and clearing', () => {
  assert.equal(normalizeMoneyInput('١٬٢٣٤٫٥٠', lyd), '1234.50')
  assert.equal(parseMoneyInput('-1,234.50', lyd), -1234.5)
  assert.equal(normalizeMoneyInput('', lyd), '')
  assert.equal(parseMoneyInput('', lyd), null)
  assert.equal(formatMoneyInputDisplay(0, lyd), '')
})
