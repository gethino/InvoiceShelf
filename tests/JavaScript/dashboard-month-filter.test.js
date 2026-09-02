import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

const chartPaths = [
  'resources/scripts/admin/views/dashboard/DashboardChart.vue',
  'resources/scripts/admin/views/customers/partials/CustomerChart.vue',
]

test('both admin chart selectors request the localized current month range', () => {
  for (const path of chartPaths) {
    const chart = readProjectFile(path)

    assert.match(chart, /t\('dateRange\.this_month'\)/, path)
    assert.match(chart, /value: 'This month'/, path)
    assert.match(chart, /this_month(?:\s*:\s*|\s*=\s*)true/, path)
  }
})
