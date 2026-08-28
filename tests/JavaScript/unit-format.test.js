import assert from 'node:assert/strict'
import test from 'node:test'
import arabic from '../../lang/ar.json' with { type: 'json' }
import {
  formatUnitName,
  getLocalizedUnits,
} from '../../resources/scripts/helpers/unit-format.js'

const translate = (messages) => (key) =>
  key.split('.').reduce((value, segment) => value[segment], messages)

test('localizes every built-in unit in Arabic', () => {
  const expected = {
    box: 'صندوق',
    cm: 'سم',
    dz: 'دزينة',
    ft: 'قدم',
    g: 'غ',
    in: 'بوصة',
    kg: 'كغ',
    km: 'كم',
    lb: 'رطل',
    mg: 'ملغ',
    pc: 'قطعة',
  }

  for (const [unitCode, localizedName] of Object.entries(expected)) {
    assert.equal(
      formatUnitName(unitCode, 'ar_LY', translate(arabic)),
      localizedName,
    )
  }
})

test('keeps canonical and custom unit names in English', () => {
  assert.equal(formatUnitName('pc', 'en', translate(arabic)), 'pc')
  assert.equal(formatUnitName('copies', 'ar', translate(arabic)), 'copies')
})

test('adds a display name without changing persisted unit values', () => {
  const units = [{ id: 7, name: 'kg' }]

  assert.deepEqual(getLocalizedUnits(units, 'ar', translate(arabic)), [
    { id: 7, name: 'kg', display_name: 'كغ' },
  ])
  assert.deepEqual(units, [{ id: 7, name: 'kg' }])
})
