import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory } from 'vue-router'
import { createEcolioApp } from './create-app'

beforeEach(() => localStorage.clear())

describe('createEcolioApp', () => {
  it('boots to the login page for an anonymous visitor, with the connectivity indicator', async () => {
    const { app, router } = createEcolioApp({
      history: createMemoryHistory(),
      apiBaseUrl: 'http://api.test/api/v1',
    })
    const root = document.createElement('div')
    document.body.append(root)

    app.mount(root)
    await router.isReady()
    await flushPromises()

    expect(root.textContent).toContain('Connexion')
    expect(root.querySelector('[role="status"]')).not.toBeNull()
    app.unmount()
  })

  it('drops the session and returns to login when the API says the token is no longer valid', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(new Response('{}', { status: 401 }))
    const { app, router, http, session } = createEcolioApp({
      history: createMemoryHistory(),
      apiBaseUrl: 'http://api.test/api/v1',
      fetchImpl,
    })
    app.mount(document.createElement('div'))
    session.setSession('expired', {
      uuid: 'u',
      nom: 'R',
      email: 'r@e.mg',
      telephone: null,
      langue: 'fr',
      roles: [],
      ecole: null,
    })
    await router.push('/')

    await http.get('/me').catch(() => undefined)
    await flushPromises()

    expect(session.isAuthenticated).toBe(false)
    expect(router.currentRoute.value.name).toBe('login')
    app.unmount()
  })
})
