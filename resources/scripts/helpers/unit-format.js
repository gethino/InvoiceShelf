const BUILT_IN_UNIT_CODES = new Set([
  'box',
  'cm',
  'dz',
  'ft',
  'g',
  'in',
  'kg',
  'km',
  'lb',
  'mg',
  'pc',
])

export function formatUnitName(unitName, locale, translate) {
  if (typeof unitName !== 'string') {
    return unitName
  }

  const unitCode = unitName.trim().toLowerCase()
  const language = locale.toLowerCase().replace('_', '-').split('-')[0]

  return language === 'ar' && BUILT_IN_UNIT_CODES.has(unitCode)
    ? translate(`unit_names.${unitCode}`)
    : unitName
}

export function getLocalizedUnits(units, locale, translate) {
  return units.map((unit) => ({
    ...unit,
    display_name: formatUnitName(unit.name, locale, translate),
  }))
}
