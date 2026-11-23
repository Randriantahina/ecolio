import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { createMemoryHistory } from 'vue-router'
import { useSessionStore } from '@/features/auth/session.store'
import { createAppRouter } from './router'

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

describe('app router', () => {
  it('sends an anonymous visitor from the home page to login', async () => {
    const router = createAppRouter(createMemoryHistory())

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/')
  })

  it('shows home to an authenticated user', async () => {
    useSessionStore().setSession('t', user)
    const router = createAppRouter(createMemoryHistory())

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('home')
  })

  it('does not show the login page to someone already signed in', async () => {
    useSessionStore().setSession('t', user)
    const router = createAppRouter(createMemoryHistory())

    await router.push('/connexion')

    expect(router.currentRoute.value.name).toBe('home')
  })
})
