import CustomerOrganizationField from './components/CustomerOrganizationField.vue'
import TripoliCustomizationSettings from './views/TripoliCustomizationSettings.vue'
import moduleLocales from '../locales/locales.js'
import {
  applyBrandColor,
  isSimplifiedLogin,
  requestErrorMessage,
  resolveHeaderLogo,
} from './branding.js'

function transformBootstrapData(data) {
  const settings = data.current_company_settings || {}

  if (settings.taxes_enabled === 'NO') {
    settings.tax_per_item = 'NO'
    settings.tax_included = 'NO'
    settings.tax_included_by_default = 'NO'
    settings.sales_tax_us_enabled = 'NO'
    data.setting_menu = (data.setting_menu || []).filter(
      (item) => item.link !== '/admin/settings/tax-types',
    )
  }

  applyBrandColor(settings.brand_color)

  return data
}

window.TripoliCustomizations = {
  applyBrandColor,
  isSimplifiedLogin,
  requestErrorMessage,
  resolveHeaderLogo,
  customerOrganizationField: CustomerOrganizationField,
  transformBootstrapData,
}

window.InvoiceShelf.booting((app, router) => {
  window.InvoiceShelf.addMessages(moduleLocales)

  if (window.tripoli_branding) {
    applyBrandColor(window.tripoli_branding.brand_color)

    if (window.tripoli_branding.logo_url) {
      window.login_page_logo = window.tripoli_branding.logo_url
    }
  }

  router.addRoute('settings', {
    path: 'tripoli-customizations',
    name: 'tripoli.customizations',
    meta: { isOwner: true },
    component: TripoliCustomizationSettings,
  })
})
