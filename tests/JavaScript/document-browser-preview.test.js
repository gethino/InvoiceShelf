import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

const publicPage = readProjectFile(
  'resources/scripts/components/DocumentPublicPage.vue',
)
const adminRouter = readProjectFile('resources/scripts/admin/admin-router.js')

const documentViews = [
  'resources/scripts/admin/views/invoices/View.vue',
  'resources/scripts/admin/views/estimates/View.vue',
  'resources/scripts/admin/views/payments/View.vue',
  'resources/scripts/customer/views/invoices/View.vue',
  'resources/scripts/customer/views/estimates/View.vue',
  'resources/scripts/customer/views/payments/View.vue',
]

test('registers public browser pages for every document type', () => {
  for (const [plural, singular] of [
    ['invoices', 'invoice'],
    ['estimates', 'estimate'],
    ['payments', 'payment'],
  ]) {
    assert.match(adminRouter, new RegExp(`/customer/${plural}/view/:hash`))
    assert.match(adminRouter, new RegExp(`documentType: '${singular}'`))
  }
})

test('public page separates html preview from raw pdf', () => {
  assert.match(publicPage, /\?preview=1/)
  assert.match(publicPage, /\?pdf=1/)
  assert.match(publicPage, /general\.view_pdf/)
  assert.match(publicPage, /target="_blank"/)
  assert.match(publicPage, /props\.documentType === 'invoice'/)
  assert.match(publicPage, /payment_module_enabled/)
})

test('authenticated document pages show html and link to raw pdf', () => {
  for (const path of documentViews) {
    const view = readProjectFile(path)

    assert.match(view, /DocumentPreviewFrame/, path)
    assert.match(view, /\?preview=1/, path)
    assert.match(view, /general\.view_pdf/, path)
    assert.match(view, /target="_blank"/, path)
    assert.match(view, /rel="noopener noreferrer"/, path)
  }
})
