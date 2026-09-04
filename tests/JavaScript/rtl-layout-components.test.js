import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import test from 'node:test'

function readProjectFile(path) {
  return readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')
}

const documentViewPaths = [
  'resources/scripts/admin/views/invoices/View.vue',
  'resources/scripts/admin/views/estimates/View.vue',
  'resources/scripts/admin/views/payments/View.vue',
  'resources/scripts/admin/views/recurring-invoices/partials/RecurringInvoiceViewSidebar.vue',
  'resources/scripts/customer/views/invoices/View.vue',
  'resources/scripts/customer/views/estimates/View.vue',
  'resources/scripts/customer/views/payments/View.vue',
]

const customerViewSidebarPath =
  'resources/scripts/admin/views/customers/partials/CustomerViewSidebar.vue'

const selectableIndexPaths = [
  'resources/scripts/admin/views/customers/Index.vue',
  'resources/scripts/admin/views/invoices/Index.vue',
  'resources/scripts/admin/views/estimates/Index.vue',
  'resources/scripts/admin/views/items/Index.vue',
  'resources/scripts/admin/views/payments/Index.vue',
  'resources/scripts/admin/views/recurring-invoices/Index.vue',
  'resources/scripts/admin/views/expenses/Index.vue',
]

test('uses logical positioning in document sidebars', () => {
  for (const path of documentViewPaths) {
    const view = readProjectFile(path)

    assert.match(view, /\binset-s-0\b/, path)
    assert.match(view, /\bborder-s(?:-4)?\b/, path)
    assert.doesNotMatch(view, /\bleft-0\b/, path)
    assert.doesNotMatch(view, /\bborder-l(?:-4)?\b/, path)
    assert.doesNotMatch(view, /\btext-right\b/, path)
  }
})

test('uses logical positioning in the customer view sidebar', () => {
  const customerView = readProjectFile(
    'resources/scripts/admin/views/customers/View.vue',
  )
  const customerViewSidebar = readProjectFile(customerViewSidebarPath)

  assert.match(customerView, /\bxl:ps-96\b/)
  assert.doesNotMatch(customerView, /\bxl:pl-96\b/)
  assert.match(customerViewSidebar, /\binset-s-0\b/)
  assert.match(customerViewSidebar, /\bms-56\b/)
  assert.match(customerViewSidebar, /\bxl:ms-64\b/)
  assert.match(customerViewSidebar, /\bborder-s\b/)
  assert.match(customerViewSidebar, /rtl:block/)
  assert.match(customerViewSidebar, /rtl:hidden/)
  assert.doesNotMatch(
    customerViewSidebar,
    /\b(?:left-0|ml-56|xl:ml-64|border-l|border-l-4|pr-2|text-right|ml-1)\b/,
  )
})

test('isolates date values from the surrounding table direction', () => {
  const baseTable = readProjectFile(
    'resources/scripts/components/base/base-table/BaseTable.vue',
  )

  assert.match(baseTable, /<bdi v-if="isDateColumn\(column\)" dir="ltr">/)
  assert.match(baseTable, /return \/\(\?:date\|at\)\$\/i\.test\(column\.key\)/)
})

test('uses the translated generic login action for quick login', () => {
  const login = readProjectFile('resources/scripts/admin/views/auth/Login.vue')
  const english = JSON.parse(readProjectFile('lang/en.json'))
  const arabic = JSON.parse(readProjectFile('lang/ar.json'))

  assert.match(login, /\$t\('login\.login'\)/)
  assert.doesNotMatch(login, /quick_login\.sign_in_as/)
  assert.equal(english.login.login, 'Login')
  assert.equal(arabic.login.login, 'دخول')
})

test('uses logical spacing for customer view actions and transaction items', () => {
  const customerView = readProjectFile(
    'resources/scripts/admin/views/customers/View.vue',
  )

  assert.equal(customerView.match(/class="me-3"/g)?.length, 2)
  assert.equal(customerView.match(/class="me-3 text-gray-600"/g)?.length, 4)
  assert.match(customerView, /'ms-3': isLoading/)
  assert.doesNotMatch(customerView, /\b(?:mr|ml)-3\b/)
})

test('mirrors document search icons without changing BaseInput globally', () => {
  for (const path of documentViewPaths) {
    const view = readProjectFile(path)

    assert.match(view, /rtl:block/, path)
    assert.match(view, /rtl:hidden/, path)
  }
})

test('uses logical spacing in shared header and navigation components', () => {
  const siteHeader = readProjectFile(
    'resources/scripts/admin/layouts/partials/TheSiteHeader.vue',
  )
  const companySwitcher = readProjectFile(
    'resources/scripts/components/CompanySwitcher.vue',
  )
  const globalSearch = readProjectFile(
    'resources/scripts/components/GlobalSearchBar.vue',
  )
  const listItem = readProjectFile(
    'resources/scripts/components/list/BaseListItem.vue',
  )
  const breadcrumbItem = readProjectFile(
    'resources/scripts/components/base/BaseBreadcrumbItem.vue',
  )

  assert.equal(siteHeader.match(/class="ms-2"/g)?.length, 2)
  assert.match(siteHeader, /float-left ms-2/)
  assert.match(companySwitcher, /\binset-e-0\b/)
  assert.match(globalSearch, /\binset-e-0\b/)
  assert.match(listItem, /\bme-3\b/)
  assert.match(breadcrumbItem, /\bme-2\b/)
})

test('positions selectable table controls at inline start', () => {
  for (const path of selectableIndexPaths) {
    const index = readProjectFile(path)

    assert.match(index, /\binset-s-6\b/, path)
    assert.doesNotMatch(index, /\bleft-6\b/, path)
  }

  const checkbox = readProjectFile(
    'resources/scripts/components/base/BaseCheckbox.vue',
  )

  assert.match(checkbox, /\bms-3\b/)
  assert.doesNotMatch(checkbox, /\bml-3\b/)
})

test('keeps multiselect controls and text clear in RTL', () => {
  const multiselect = readProjectFile(
    'resources/scripts/components/base-select/BaseMultiselect.vue',
  )
  const createItems = readProjectFile(
    'resources/scripts/admin/components/estimate-invoice-common/CreateItems.vue',
  )
  const styles = readProjectFile('resources/css/invoiceshelf.css')

  assert.match(multiselect, /base-multiselect-control-text/)
  assert.match(multiselect, /base-multiselect-caret/)
  assert.doesNotMatch(multiselect, /rtl:justify-start/)
  assert.match(styles, /\[dir="rtl"\] \.base-multiselect-caret/)
  assert.match(styles, /left: 0\.5rem/)
  assert.match(styles, /padding-left: 2\.5rem/)
  assert.match(multiselect, /'ps-3\.5 relative z-10/)
  assert.match(multiselect, /z-10 ms-3\.5 animate-spin/)
  assert.match(multiselect, /\btext-start\b/)
  assert.doesNotMatch(multiselect, /rounded-md pl-3\.5/)
  assert.match(createItems, /class="me-2"/)
  assert.doesNotMatch(createItems, /class="mr-2"/)
})

test('uses logical spacing throughout the customer selection popup', () => {
  const customerSelect = readProjectFile(
    'resources/scripts/components/base/BaseCustomerSelectPopup.vue',
  )

  assert.match(customerSelect, /\bms-3\b/)
  assert.match(customerSelect, /\bme-4\b/)
  assert.match(customerSelect, /\btext-start\b/)
  assert.doesNotMatch(customerSelect, /\b(?:ml|mr)-(?:1|3|4|5|6)\b/)
  assert.doesNotMatch(customerSelect, /\btext-left\b/)
})

test('keeps date picker text clear of its logical-start icon', () => {
  const datePicker = readProjectFile(
    'resources/scripts/components/base/BaseDatePicker.vue',
  )
  const styles = readProjectFile('resources/css/invoiceshelf.css')

  assert.match(datePicker, /base-date-picker-icon/)
  assert.match(datePicker, /base-date-picker-input font-base py-2/)
  assert.match(datePicker, /dir="ltr"/)
  assert.match(datePicker, /altInput\?\.setAttribute\('dir', 'ltr'\)/)
  assert.match(styles, /\[dir="rtl"\] \.base-date-picker-icon/)
  assert.match(styles, /unicode-bidi: isolate/)
  assert.match(styles, /text-align: right/)
})

test('isolates telephone values and mirrors the Flatpickr popup', () => {
  const input = readProjectFile(
    'resources/scripts/components/base/BaseInput.vue',
  )
  const styles = readProjectFile('resources/css/invoiceshelf.css')

  assert.match(input, /:dir="type === 'tel' \? 'ltr' : undefined"/)
  assert.match(input, /'rtl:text-right': type === 'tel'/)
  assert.match(styles, /\[dir="rtl"\] \.flatpickr-calendar/)
  assert.match(styles, /flex-direction: row-reverse/)
  assert.match(styles, /\.flatpickr-calendar\.rtl \.dayContainer/)
})

test('keeps estimate table checkboxes inside logical columns', () => {
  const estimates = readProjectFile(
    'resources/scripts/admin/views/estimates/Index.vue',
  )

  assert.match(estimates, /thClass: 'extra w-10 pe-0'/)
  assert.match(estimates, /tdClass: 'font-medium text-gray-900 pe-0'/)
  assert.doesNotMatch(estimates, /w-10 pr-0/)
})

test('aligns shared text fields with the active writing direction', () => {
  const input = readProjectFile(
    'resources/scripts/components/base/BaseInput.vue',
  )
  const textarea = readProjectFile(
    'resources/scripts/components/base/BaseTextarea.vue',
  )
  const datePicker = readProjectFile(
    'resources/scripts/components/base/BaseDatePicker.vue',
  )
  const timePicker = readProjectFile(
    'resources/scripts/components/base/BaseTimePicker.vue',
  )

  for (const control of [input, textarea, timePicker]) {
    assert.match(control, /\btext-start\b/)
  }

  assert.match(datePicker, /base-date-picker-input/)
  assert.doesNotMatch(textarea, /\btext-left\b/)
  assert.match(timePicker, /\binset-s-0\b/)
  assert.match(timePicker, /font-base ps-8 py-2/)
  assert.doesNotMatch(timePicker, /font-base pl-8 py-2/)
})

test('aligns shared table headers with the active writing direction', () => {
  const baseTable = readProjectFile(
    'resources/scripts/components/base/base-table/BaseTable.vue',
  )

  assert.match(baseTable, /px-6 py-3 text-start text-xs/)
  assert.doesNotMatch(baseTable, /px-6 py-3 text-left text-xs/)
})

test('uses logical alignment in the customer table', () => {
  const customerIndex = readProjectFile(
    'resources/scripts/admin/views/customers/Index.vue',
  )

  assert.match(customerIndex, /thClass: 'extra w-10 pe-0'/)
  assert.match(customerIndex, /text-end text-sm font-medium ps-0/)
  assert.match(customerIndex, /name="TrashIcon" class="me-3/)
  assert.doesNotMatch(customerIndex, /\b(?:left-6|pr-0|pl-0|text-right)\b/)
})

test('keeps notifications top-right with RTL-aware internal layout', () => {
  const notificationRoot = readProjectFile(
    'resources/scripts/components/notifications/NotificationRoot.vue',
  )
  const notificationItem = readProjectFile(
    'resources/scripts/components/notifications/NotificationItem.vue',
  )

  assert.match(notificationRoot, /items-end/)
  assert.match(notificationRoot, /rtl:items-start/)
  assert.match(notificationRoot, /sm:translate-x-2/)
  assert.match(notificationItem, /flex-1 w-0 ms-3 text-start/)
  assert.match(notificationItem, /flex shrink-0 ms-4/)
  assert.doesNotMatch(notificationItem, /\bml-3\b/)
  assert.doesNotMatch(notificationItem, /\btext-left\b/)
})

test('uses logical spacing between dropdown action icons and text', () => {
  const dropdownDirectory = new URL(
    '../../resources/scripts/admin/components/dropdowns/',
    import.meta.url,
  )

  for (const fileName of readdirSync(dropdownDirectory)) {
    if (!fileName.endsWith('.vue')) {
      continue
    }

    const dropdown = readProjectFile(
      `resources/scripts/admin/components/dropdowns/${fileName}`,
    )

    assert.doesNotMatch(dropdown, /\bmr-3\b/, fileName)
  }
})
