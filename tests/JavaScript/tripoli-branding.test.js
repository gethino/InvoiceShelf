import assert from 'node:assert/strict'
import test from 'node:test'

import {
  applyBrandColor,
  createBrandPalette,
  normalizeBrandColor,
} from '../../Modules/TripoliCustomizations/Resources/scripts/branding.js'

test('normalizes valid colors and falls back for invalid colors', () => {
  assert.equal(normalizeBrandColor('#12ABef'), '#12abef')
  assert.equal(normalizeBrandColor('blue'), '#4a3dff')
})

test('creates a complete palette around the company color', () => {
  const palette = createBrandPalette('#123456')

  assert.deepEqual(palette[500], [18, 52, 86])
  assert.equal(Object.keys(palette).length, 11)
  assert.ok(palette[50][0] > palette[500][0])
  assert.ok(palette[950][2] < palette[500][2])
})

test('writes palette values to CSS custom properties', () => {
  const values = new Map()
  const root = {
    style: {
      setProperty(name, value) {
        values.set(name, value)
      },
    },
  }

  applyBrandColor('#123456', root)

  assert.equal(values.get('--color-primary-500'), 'rgb(18 52 86)')
  assert.equal(values.size, 11)
})
