import { mount } from '@vue/test-utils'
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
  it('greets the user and names their school', () => {
    useSessionStore().setSession('t', {
      uuid: 'u',
      nom: 'Rasoa',
      email: 'r@e.mg',
      telephone: null,
      langue: 'fr',
      roles: ['directeur'],
      ecole: { uuid: 'e', nom: 'Ecole Pilote' },
    })

    const wrapper = mount(HomeView, { global: { plugins: [createAppI18n()] } })

    expect(wrapper.text()).toContain('Bonjour, Rasoa')
    expect(wrapper.text()).toContain('École : Ecole Pilote')
  })
})
