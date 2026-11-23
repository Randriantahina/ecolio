import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { LockedError, NetworkError, ValidationError } from '@/shared/api/errors'
import { createAppI18n } from '@/shared/i18n'
import type { AuthRepository } from '../domain/auth.repository'
import { authRepositoryKey } from '../auth.injection'
import { useSessionStore } from '../session.store'
import LoginView from './LoginView.vue'

const user = {
  uuid: 'u',
  nom: 'Rasoa',
  email: 'r@e.mg',
  telephone: null,
  langue: 'fr',
  roles: ['directeur'],
  ecole: { uuid: 'e', nom: 'E' },
}

async function mountView(login: AuthRepository['login']) {
  const repository: AuthRepository = { login, logout: vi.fn(), me: vi.fn() }
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div />' } },
      { path: '/connexion', name: 'login', component: LoginView },
    ],
  })
  await router.push('/connexion')
  await router.isReady()

  const wrapper = mount(LoginView, {
    global: {
      plugins: [createAppI18n(), router],
      provide: { [authRepositoryKey as symbol]: repository },
    },
  })
  return { wrapper, router }
}

async function fill(wrapper: Awaited<ReturnType<typeof mountView>>['wrapper']) {
  await wrapper.find('input[name="login"]').setValue('r@e.mg')
  await wrapper.find('input[name="password"]').setValue('secret')
}

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
})

describe('LoginView', () => {
  it('labels both fields so a screen reader and a thumb both find them', async () => {
    const { wrapper } = await mountView(vi.fn())

    expect(wrapper.find('label[for="login"]').text()).toBe('E-mail ou téléphone')
    expect(wrapper.find('label[for="password"]').text()).toBe('Mot de passe')
    expect(wrapper.find('input[name="password"]').attributes('type')).toBe('password')
  })

  it('disables the button until both fields are filled', async () => {
    const { wrapper } = await mountView(vi.fn())
    const button = wrapper.find('button[type="submit"]')

    expect(button.attributes('disabled')).toBeDefined()
    await fill(wrapper)
    expect(button.attributes('disabled')).toBeUndefined()
  })

  it('opens the session and goes home on success', async () => {
    const login = vi.fn().mockResolvedValue({ token: 't', utilisateur: user })
    const { wrapper, router } = await mountView(login)

    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(useSessionStore().isAuthenticated).toBe(true)
    expect(router.currentRoute.value.name).toBe('home')
  })

  it('returns to the page the user originally wanted', async () => {
    const login = vi.fn().mockResolvedValue({ token: 't', utilisateur: user })
    const { wrapper, router } = await mountView(login)
    await router.push({ name: 'login', query: { redirect: '/' } })

    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(router.currentRoute.value.fullPath).toBe('/')
  })

  it('shows the server message under the field on a validation error', async () => {
    const login = vi
      .fn()
      .mockRejectedValue(new ValidationError('x', { login: ['Identifiants incorrects.'] }))
    const { wrapper } = await mountView(login)

    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Identifiants incorrects.')
  })

  it('shows the lock message', async () => {
    const login = vi.fn().mockRejectedValue(new LockedError('Compte verrouillé'))
    const { wrapper } = await mountView(login)

    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toContain('Compte verrouillé')
  })

  it('says the network is unreachable instead of blaming the password', async () => {
    const login = vi.fn().mockRejectedValue(new NetworkError())
    const { wrapper } = await mountView(login)

    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toContain('Pas de connexion')
  })
})
