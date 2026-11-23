import { describe, expect, it } from 'vitest'
import { safeRedirect } from './safe-redirect'

describe('safeRedirect', () => {
  it('keeps an internal path with its query', () => {
    expect(safeRedirect('/eleves?page=2')).toBe('/eleves?page=2')
  })

  it.each([
    '//evil.com',
    'https://evil.com',
    '/\\evil.com',
    'javascript:alert(1)',
    '',
    undefined,
    null,
    ['/a'],
  ])('falls back to home for %j, preventing an open redirect after login', (value) => {
    expect(safeRedirect(value as string | null | undefined)).toBe('/')
  })
})
