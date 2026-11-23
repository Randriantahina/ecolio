import { describe, expect, it } from 'vitest'
import { loginResponseSchema, utilisateurSchema } from './auth.schemas'

const validUser = {
  uuid: 'u-1',
  nom: 'Rasoa',
  email: 'rasoa@ecole.mg',
  telephone: null,
  langue: 'fr',
  roles: ['directeur'],
  ecole: { uuid: 'e-1', nom: 'Ecole Pilote' },
}

describe('auth schemas', () => {
  it('accepts a school user', () => {
    expect(utilisateurSchema.parse(validUser).roles).toEqual(['directeur'])
  })

  it('accepts a platform super admin, who has no school', () => {
    expect(utilisateurSchema.parse({ ...validUser, ecole: null }).ecole).toBeNull()
  })

  it('accepts a phone-only user', () => {
    expect(
      utilisateurSchema.parse({ ...validUser, email: null, telephone: '034 11 222 33' }).telephone,
    ).toBe('034 11 222 33')
  })

  it('rejects a payload missing roles so a broken API never reaches the UI silently', () => {
    expect(() => utilisateurSchema.parse({ ...validUser, roles: undefined })).toThrow()
  })

  it('parses a login response with its token', () => {
    const parsed = loginResponseSchema.parse({ token: '1|abc', utilisateur: validUser })
    expect(parsed.token).toBe('1|abc')
  })

  it('rejects a login response without a token', () => {
    expect(() => loginResponseSchema.parse({ utilisateur: validUser })).toThrow()
  })
})
