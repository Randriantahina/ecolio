import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useSessionStore } from './session.store'

const user = {
  uuid: 'u-1',
  nom: 'Rasoa',
  email: null,
  telephone: '034 11 222 33',
  langue: 'fr',
  roles: ['secretaire'],
  ecole: { uuid: 'e-1', nom: 'Ecole Pilote' },
}

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
})

describe('session store', () => {
  it('starts anonymous', () => {
    const session = useSessionStore()

    expect(session.isAuthenticated).toBe(false)
    expect(session.token).toBeNull()
  })

  it('holds the token and user after login', () => {
    const session = useSessionStore()
    session.setSession('1|abc', user)

    expect(session.isAuthenticated).toBe(true)
    expect(session.token).toBe('1|abc')
    expect(session.utilisateur?.nom).toBe('Rasoa')
  })

  it('survives a page reload, which is the point of a 30-day token on a flaky connection', () => {
    useSessionStore().setSession('1|abc', user)

    setActivePinia(createPinia())
    const reloaded = useSessionStore()

    expect(reloaded.token).toBe('1|abc')
    expect(reloaded.utilisateur?.uuid).toBe('u-1')
  })

  it('forgets everything on clear', () => {
    const session = useSessionStore()
    session.setSession('1|abc', user)
    session.clear()

    expect(session.isAuthenticated).toBe(false)
    expect(localStorage.length).toBe(0)
  })

  it('knows the roles of the user', () => {
    const session = useSessionStore()
    session.setSession('1|abc', user)

    expect(session.hasRole('secretaire')).toBe(true)
    expect(session.hasRole('directeur')).toBe(false)
  })

  it('ignores corrupted stored data instead of crashing the app at startup', () => {
    localStorage.setItem('ecolio.session', '{not json')

    expect(useSessionStore().isAuthenticated).toBe(false)
  })
})
