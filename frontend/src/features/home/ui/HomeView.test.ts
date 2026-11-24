import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useSessionStore } from '@/features/auth/session.store'
import { createAppI18n } from '@/shared/i18n'
import HomeView from './HomeView.vue'

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
})

describe('HomeView', () => {
  it('greets the user and offers the three daily tasks', () => {
    useSessionStore().setSession('t', {
      uuid: 'u',
      nom: 'Rasoa',
      email: 'r@e.mg',
      telephone: null,
      langue: 'fr',
      roles: ['directeur'],
      ecole: { uuid: 'e', nom: 'Ecole Pilote' },
    })

    const wrapper = mount(HomeView, {
      global: { plugins: [createAppI18n()], stubs: { RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Bonjour, Rasoa')
    const destinations = wrapper.findAllComponents(RouterLinkStub).map((l) => l.props('to'))
    expect(destinations).toEqual([{ name: 'ecolage' }, { name: 'absences' }, { name: 'eleves' }])
  })
})
