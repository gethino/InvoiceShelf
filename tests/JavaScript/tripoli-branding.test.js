import assert from 'node:assert/strict'
import test from 'node:test'

import {
  applyBrandColor,
  createBrandPalette,
  isSimplifiedLogin,
  normalizeBrandColor,
  requestErrorMessage,
  resolveHeaderLogo,
  resolveMobileMenuLogo,
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

test('simplified login requires an explicit enabled module setting', () => {
  assert.equal(isSimplifiedLogin({ simplified_login: true }), true)
  assert.equal(isSimplifiedLogin({ simplified_login: false }), false)
  assert.equal(isSimplifiedLogin(null), false)
})

test('header logo prefers dark company branding with safe fallbacks', () => {
  assert.equal(
    resolveHeaderLogo(
      { dark_logo: '/dark.png', logo: '/logo.png' },
      { admin_portal_logo: 'admin.png' },
    ),
    '/dark.png',
  )
  assert.equal(resolveHeaderLogo({ logo: '/logo.png' }), '/logo.png')
  assert.equal(
    resolveHeaderLogo({}, { admin_portal_logo: 'admin.png' }),
    '/storage/admin.png',
  )
  assert.equal(resolveHeaderLogo({}), false)
})

test('mobile menu logo uses normal company branding and ignores dark logo', () => {
  assert.equal(
    resolveMobileMenuLogo(
      { dark_logo: '/dark.png', logo: '/logo.png' },
      { admin_portal_logo: 'admin.png' },
    ),
    '/logo.png',
  )
  assert.equal(
    resolveMobileMenuLogo(
      { dark_logo: '/dark.png' },
      { admin_portal_logo: 'admin.png' },
    ),
    '/storage/admin.png',
  )
  assert.equal(resolveMobileMenuLogo({ dark_logo: '/dark.png' }), false)
})

test('request error message prefers validation details', () => {
  assert.equal(
    requestErrorMessage(
      {
        response: {
          data: {
            message: 'Validation failed.',
            errors: { company_favicon: ['Favicon must be square.'] },
          },
        },
      },
      'Something went wrong.',
    ),
    'Favicon must be square.',
  )
  assert.equal(
    requestErrorMessage(null, 'Something went wrong.'),
    'Something went wrong.',
  )
})
