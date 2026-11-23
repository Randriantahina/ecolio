import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useSessionStore } from '@/features/auth/session.store'
import { authGuard } from './auth-guard'

const user = {
  uuid: 'u',
  nom: 'Rasoa',
  email: 'r@e.mg',
  telephone: null,
  langue: 'fr',
  roles: ['directeur'],
  ecole: { uuid: 'e', nom: 'E' },
}

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
})

describe('authGuard', () => {
  it('lets anonymous visitors reach public pages', () => {
    expect(authGuard({ name: 'login', fullPath: '/connexion', meta: {} }, useSessionStore())).toBe(
      true,
    )
  })

  it('sends anonymous visitors of a protected page to login, remembering where they wanted to go', () => {
    const result = authGuard(
      { name: 'home', fullPath: '/eleves?page=2', meta: { requiresAuth: true } },
      useSessionStore(),
    )

    expect(result).toEqual({ name: 'login', query: { redirect: '/eleves?page=2' } })
  })

  it('lets an authenticated user into protected pages', () => {
    const session = useSessionStore()
    session.setSession('t', user)

    expect(authGuard({ name: 'home', fullPath: '/', meta: { requiresAuth: true } }, session)).toBe(
      true,
    )
  })

  it('keeps an authenticated user out of the login page', () => {
    const session = useSessionStore()
    session.setSession('t', user)

    expect(
      authGuard({ name: 'login', fullPath: '/connexion', meta: { guestOnly: true } }, session),
    ).toEqual({
      name: 'home',
    })
  })
})
