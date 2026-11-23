import { describe, expect, it } from 'vitest'
import { createAppI18n } from './index'

describe('i18n', () => {
  it('defaults to French, the product language', () => {
    const i18n = createAppI18n()

    expect(i18n.global.locale.value).toBe('fr')
    expect(i18n.global.t('auth.login.submit')).toBe('Se connecter')
  })

  it('interpolates values', () => {
    expect(createAppI18n().global.t('home.welcome', { nom: 'Rasoa' })).toBe('Bonjour, Rasoa')
  })

  it('shows the key rather than crashing when a translation is missing', () => {
    expect(createAppI18n().global.t('does.not.exist')).toBe('does.not.exist')
  })
})
