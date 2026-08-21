const arabicIndicDigits = '٠١٢٣٤٥٦٧٨٩'
const easternArabicDigits = '۰۱۲۳۴۵۶۷۸۹'

export function getCurrentLocale() {
  const locale = globalThis.window?.i18n?.global?.locale

  if (typeof locale === 'string') {
    return locale
  }

  return locale?.value || 'en'
}

export function isArabicLocale(locale) {
  return (
    String(locale || 'en')
      .toLowerCase()
      .split(/[-_]/, 1)[0] === 'ar'
  )
}

export function getCurrencyPresentation(currency, locale = getCurrentLocale()) {
  if (String(currency?.code || '').toUpperCase() === 'LYD') {
    const isArabic = isArabicLocale(locale)

    return {
      symbol: isArabic ? 'د.ل' : 'LYD',
      symbolAfterAmount: !isArabic,
    }
  }

  return {
    symbol: String(currency?.symbol || ''),
    symbolAfterAmount: Boolean(currency?.swap_currency_symbol),
  }
}

export function formatMoney(amount, currency, locale = getCurrentLocale()) {
  const numericAmount = (Number(amount) || 0) / 100
  const formattedAmount = formatNumericValue(numericAmount, currency)
  const { symbol, symbolAfterAmount } = getCurrencyPresentation(
    currency,
    locale,
  )

  if (!symbol) {
    return formattedAmount
  }

  return symbolAfterAmount
    ? `${formattedAmount} ${symbol}`
    : `${symbol} ${formattedAmount}`
}

export function normalizeMoneyInput(value, currency) {
  if (value === null || value === undefined || value === '') {
    return ''
  }

  const decimalSeparator = currency?.decimal_separator || '.'
  const thousandSeparator = currency?.thousand_separator || ','
  const maximumFractionDigits = Math.min(
    2,
    Math.max(0, Number(currency?.precision) || 0),
  )
  let normalized = normalizeDigits(String(value).trim())
    .replaceAll('٬', '')
    .replaceAll('٫', decimalSeparator)

  if (thousandSeparator && thousandSeparator !== decimalSeparator) {
    normalized = normalized.replaceAll(thousandSeparator, '')
  }

  const isNegative = normalized.includes('-')
  normalized = normalized.replaceAll('-', '')

  const decimalIndex = normalized.indexOf(decimalSeparator)
  const integerSource =
    decimalIndex === -1 ? normalized : normalized.slice(0, decimalIndex)
  const fractionSource =
    decimalIndex === -1 ? '' : normalized.slice(decimalIndex + 1)
  const hasDecimal = decimalIndex !== -1 && maximumFractionDigits > 0
  const integer =
    integerSource.replace(/\D/g, '').replace(/^0+(?=\d)/, '') ||
    (hasDecimal ? '0' : '')
  const fraction = fractionSource
    .replace(/\D/g, '')
    .slice(0, maximumFractionDigits)

  if (!integer && !fraction) {
    return isNegative ? '-' : ''
  }

  return `${isNegative ? '-' : ''}${integer}${
    hasDecimal ? decimalSeparator : ''
  }${fraction}`
}

export function parseMoneyInput(value, currency) {
  const normalized = normalizeMoneyInput(value, currency)

  if (normalized === '' || normalized === '-' || normalized === '-0.') {
    return null
  }

  const decimalSeparator = currency?.decimal_separator || '.'
  const numericValue = Number(normalized.replace(decimalSeparator, '.'))

  return Number.isFinite(numericValue) ? numericValue : null
}

export function formatMoneyInputDisplay(value, currency) {
  if (value === null || value === undefined || value === '') {
    return ''
  }

  const numericValue = Number(value)

  if (!Number.isFinite(numericValue) || numericValue === 0) {
    return ''
  }

  return formatNumericValue(numericValue, currency)
}

function formatNumericValue(value, currency) {
  const precision = Math.max(0, Math.abs(Number(currency?.precision) || 0))
  const decimalSeparator = currency?.decimal_separator || '.'
  const thousandSeparator = currency?.thousand_separator || ','
  const negativeSign = value < 0 ? '-' : ''
  const [integer, fraction] = Math.abs(value).toFixed(precision).split('.')
  const groupedInteger = integer.replace(
    /\B(?=(\d{3})+(?!\d))/g,
    thousandSeparator,
  )

  return `${negativeSign}${groupedInteger}${
    precision ? decimalSeparator + fraction : ''
  }`
}

function normalizeDigits(value) {
  return [...value]
    .map((character) => {
      const arabicIndicIndex = arabicIndicDigits.indexOf(character)
      if (arabicIndicIndex !== -1) {
        return String(arabicIndicIndex)
      }

      const easternArabicIndex = easternArabicDigits.indexOf(character)
      if (easternArabicIndex !== -1) {
        return String(easternArabicIndex)
      }

      return character
    })
    .join('')
}
