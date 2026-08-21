import assert from 'node:assert/strict'
import test from 'node:test'
import {
  buildWhatsAppUrl,
  normalizeWhatsAppPhone,
} from '../../resources/scripts/helpers/whatsapp.js'

test('normalizes international WhatsApp phone numbers', () => {
  assert.equal(normalizeWhatsAppPhone('+218 91-234-5678'), '218912345678')
  assert.equal(normalizeWhatsAppPhone('00218 92 345 6789'), '218923456789')
})

test('rejects missing and invalid WhatsApp phone numbers', () => {
  assert.equal(normalizeWhatsAppPhone(null), '')
  assert.equal(normalizeWhatsAppPhone('123'), '')
})

test('opens a customer chat with an encoded message', () => {
  assert.equal(
    buildWhatsAppUrl(
      '+218 91 234 5678',
      'Invoice #10: https://example.test/a?b=1',
    ),
    'https://wa.me/218912345678?text=Invoice%20%2310%3A%20https%3A%2F%2Fexample.test%2Fa%3Fb%3D1',
  )
})

test('opens the WhatsApp contact chooser when phone is unavailable', () => {
  assert.equal(
    buildWhatsAppUrl('', 'فاتورة 10 جاهزة'),
    'https://api.whatsapp.com/send?text=%D9%81%D8%A7%D8%AA%D9%88%D8%B1%D8%A9%2010%20%D8%AC%D8%A7%D9%87%D8%B2%D8%A9',
  )
})
