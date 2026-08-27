import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

test('BaseTable exposes opt-in mouse row navigation', () => {
  const baseTable = readProjectFile(
    'resources/scripts/components/base/base-table/BaseTable.vue',
  )

  assert.match(baseTable, /rowClickable:\s*\{[\s\S]*?default: false/)
  assert.match(baseTable, /defineEmits\(\['rowClick'\]\)/)
  assert.match(baseTable, /@click="handleRowClick\(\$event, row\)"/)
  assert.match(
    baseTable,
    /cursor-pointer transition-colors hover:bg-gray-100\/70/,
  )
  assert.match(baseTable, /emit\('rowClick', row\.data\)/)
  assert.doesNotMatch(baseTable, /@keydown[^=]*=/)
})

test('row navigation ignores interactive descendants', () => {
  const baseTable = readProjectFile(
    'resources/scripts/components/base/base-table/BaseTable.vue',
  )
  const selectorDeclaration = baseTable.match(
    /const interactiveRowSelector =\s*\n\s*'([^']+)'/,
  )

  assert.ok(selectorDeclaration)
  const interactiveRowSelector = selectorDeclaration[1]

  for (const selector of [
    'a',
    'button',
    'input',
    'select',
    'option',
    'textarea',
    'label',
    'summary',
    '[role="button"]',
    '[role="link"]',
    '[role="menu"]',
    '[role="menuitem"]',
    '[role="menuitemcheckbox"]',
    '[role="menuitemradio"]',
    '[role="checkbox"]',
    '[role="combobox"]',
    '[role="listbox"]',
    '[role="option"]',
    '[role="radio"]',
    '[role="switch"]',
    '[role="tab"]',
    '[contenteditable="true"]',
    '[data-row-click-ignore]',
  ]) {
    assert.ok(interactiveRowSelector.includes(selector), selector)
  }

  assert.match(baseTable, /event\.target\.closest\(interactiveRowSelector\)/)
})

test('record tables navigate rows to their existing primary destinations', () => {
  const destinations = new Map([
    ['invoices', '/admin/invoices/${$event.id}/view'],
    ['estimates', '/admin/estimates/${$event.id}/view'],
    ['payments', '/admin/payments/${$event.id}/view'],
    ['customers', '/admin/customers/${$event.id}/view'],
    ['items', '/admin/items/${$event.id}/edit'],
  ])

  for (const [resource, destination] of destinations) {
    const index = readProjectFile(
      `resources/scripts/admin/views/${resource}/Index.vue`,
    )

    assert.match(index, /\brow-clickable\b/, resource)
    assert.ok(index.includes(destination), resource)
  }
})
