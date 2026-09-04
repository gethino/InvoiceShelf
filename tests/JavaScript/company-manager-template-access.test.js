import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

test('company switcher is hidden unless another company is accessible', () => {
  const switcher = readProjectFile(
    'resources/scripts/components/CompanySwitcher.vue',
  )

  assert.match(switcher, /currentUser\.can_switch_companies/)
  assert.match(switcher, /currentUser\.can_create_company/)
  assert.match(switcher, /companyStore\.companies\.length > 1/)
  assert.doesNotMatch(switcher, /\n\s*<div\n\s*v-else/)
})

test('manager user routes and forms use explicit management capabilities', () => {
  const router = readProjectFile('resources/scripts/admin/admin-router.js')
  const routerGuard = readProjectFile('resources/scripts/router/index.js')
  const userForm = readProjectFile(
    'resources/scripts/admin/views/users/Create.vue',
  )
  const userIndex = readProjectFile(
    'resources/scripts/admin/views/users/Index.vue',
  )

  assert.match(router, /canManageUsers: true/)
  assert.match(routerGuard, /currentUser\.can_manage_users/)
  assert.match(userForm, /currentUser\.can_manage_user_companies/)
  assert.match(userForm, /role\.name !== 'super admin'/)
  assert.match(userIndex, /row\.data\.can_edit/)
  assert.match(userIndex, /row\.data\.can_delete/)
})

test('document creation uses company template defaults without personal default controls', () => {
  const invoiceStore = readProjectFile(
    'resources/scripts/admin/stores/invoice.js',
  )
  const estimateStore = readProjectFile(
    'resources/scripts/admin/stores/estimate.js',
  )
  const templateModal = readProjectFile(
    'resources/scripts/admin/components/modal-components/SelectTemplateModal.vue',
  )
  const settingsView = readProjectFile(
    'resources/scripts/admin/views/settings/DocumentTemplatesSetting.vue',
  )

  assert.match(invoiceStore, /response\.data\.defaultTemplate/)
  assert.match(estimateStore, /response\.data\.defaultTemplate/)
  assert.doesNotMatch(templateModal, /isMarkAsDefault/)
  assert.match(settingsView, /allowed_invoice_templates/)
  assert.match(settingsView, /allowed_estimate_templates/)
})
