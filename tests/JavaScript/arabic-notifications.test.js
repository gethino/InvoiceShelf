import assert from 'node:assert/strict'
import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import arabic from '../../lang/ar.json' with { type: 'json' }

function getTranslation(messages, key) {
  return key.split('.').reduce((value, segment) => value?.[segment], messages)
}

function notificationSourceBlocks() {
  const files = execFileSync(
    'rg',
    [
      '-l',
      'showNotification\\(',
      'resources/scripts',
      'Modules',
      '-g',
      '*.js',
      '-g',
      '*.vue',
      '-g',
      '!**/dist/**',
    ],
    { encoding: 'utf8' },
  )
    .trim()
    .split('\n')

  return files.flatMap((file) => {
    const source = readFileSync(file, 'utf8')

    return [...source.matchAll(/showNotification\(\{[\s\S]*?\n\s*\}\)/g)].map(
      ([block]) => ({ file, block }),
    )
  })
}

test('all statically translated toaster messages have Arabic text', () => {
  const untranslated = []
  const indirectNotificationKeys = [
    'customers.address_updated_message',
    'modules.module_not_found',
    'modules.module_not_purchased',
    'modules.version_not_supported',
    'settings.customization.estimates.estimate_settings_updated',
    'settings.customization.invoices.invoice_settings_updated',
    'settings.customization.payments.payment_settings_updated',
    'settings.notification.email_save_message',
    'settings.preferences.updated_message',
  ]

  for (const { file, block } of notificationSourceBlocks()) {
    for (const match of block.matchAll(
      /(?:global\.)?(?:t|tm)\(\s*['"]([^'"]+)['"]/g,
    )) {
      const key = match[1]

      if (key.endsWith('.')) {
        continue
      }

      const translation = getTranslation(arabic, key)

      if (
        typeof translation !== 'string' ||
        !/\p{Script=Arabic}/u.test(translation)
      ) {
        untranslated.push(`${file}: ${key} = ${JSON.stringify(translation)}`)
      }
    }
  }

  for (const key of indirectNotificationKeys) {
    const translation = getTranslation(arabic, key)

    if (
      typeof translation !== 'string' ||
      !/\p{Script=Arabic}/u.test(translation)
    ) {
      untranslated.push(`${key} = ${JSON.stringify(translation)}`)
    }
  }

  assert.deepEqual(untranslated, [])
})

test('toaster content does not contain hardcoded English text', () => {
  const hardcodedMessages = notificationSourceBlocks()
    .filter(({ block }) =>
      /(?:title|message):\s*['"`](?=[A-Za-z])[^'"`]*['"`]/.test(block),
    )
    .map(({ file, block }) => {
      const message = block.match(
        /(?:title|message):\s*['"`]([^'"`]*)['"`]/,
      )?.[1]

      return `${file}: ${message}`
    })

  assert.deepEqual(hardcodedMessages, [])
})
