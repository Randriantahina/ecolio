import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createAppI18n } from '@/shared/i18n'
import OnlineIndicator from './OnlineIndicator.vue'

afterEach(() => vi.restoreAllMocks())

function render() {
  return mount(OnlineIndicator, { global: { plugins: [createAppI18n()] } })
}

describe('OnlineIndicator', () => {
  it('says "En ligne" when connected', () => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(true)

    expect(render().text()).toContain('En ligne')
  })

  it('says "Hors ligne" when disconnected, and exposes it to screen readers', async () => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(true)
    const wrapper = render()

    window.dispatchEvent(new Event('offline'))
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Hors ligne')
    expect(wrapper.attributes('role')).toBe('status')
  })
})
