export function normalizeWhatsAppPhone(phone) {
  const normalizedPhone = String(phone ?? '')
    .replace(/\D/g, '')
    .replace(/^00/, '')

  return /^\d{8,15}$/.test(normalizedPhone) ? normalizedPhone : ''
}

export function buildWhatsAppUrl(phone, message) {
  const normalizedPhone = normalizeWhatsAppPhone(phone)
  const encodedMessage = encodeURIComponent(message)

  if (!normalizedPhone) {
    return `https://api.whatsapp.com/send?text=${encodedMessage}`
  }

  return `https://wa.me/${normalizedPhone}?text=${encodedMessage}`
}
