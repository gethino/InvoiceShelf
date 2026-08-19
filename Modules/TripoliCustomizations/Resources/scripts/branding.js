const DEFAULT_BRAND_COLOR = '#4a3dff'

const SHADE_MIXES = {
  50: ['#ffffff', 0.93],
  100: ['#ffffff', 0.84],
  200: ['#ffffff', 0.68],
  300: ['#ffffff', 0.48],
  400: ['#ffffff', 0.22],
  500: [null, 0],
  600: ['#000000', 0.12],
  700: ['#000000', 0.25],
  800: ['#000000', 0.38],
  900: ['#000000', 0.5],
  950: ['#000000', 0.64],
}

export function normalizeBrandColor(color) {
  return /^#[0-9a-f]{6}$/i.test(color || '')
    ? color.toLowerCase()
    : DEFAULT_BRAND_COLOR
}

export function createBrandPalette(color) {
  const normalized = normalizeBrandColor(color)

  return Object.fromEntries(
    Object.entries(SHADE_MIXES).map(([shade, [target, amount]]) => [
      shade,
      target ? mixHex(normalized, target, amount) : hexToRgb(normalized),
    ]),
  )
}

export function applyBrandColor(color, root = document.documentElement) {
  const palette = createBrandPalette(color)

  Object.entries(palette).forEach(([shade, value]) => {
    root.style.setProperty(
      `--color-primary-${shade}`,
      `rgb(${value.join(' ')})`,
    )
  })
}

function mixHex(source, target, amount) {
  const sourceRgb = hexToRgb(source)
  const targetRgb = hexToRgb(target)

  return sourceRgb.map((channel, index) =>
    Math.round(channel + (targetRgb[index] - channel) * amount),
  )
}

function hexToRgb(color) {
  return [1, 3, 5].map((offset) =>
    Number.parseInt(color.slice(offset, offset + 2), 16),
  )
}
