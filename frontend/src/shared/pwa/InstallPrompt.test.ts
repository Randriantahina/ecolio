import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createAppI18n } from '@/shared/i18n'
import InstallPrompt from './InstallPrompt.vue'

function fireInstallEvent() {
  const event = new Event('beforeinstallprompt', { cancelable: true }) as Event & {
    prompt: () => Promise<void>
    userChoice: Promise<{ outcome: string }>
  }
  event.prompt = vi.fn().mockResolvedValue(undefined)
  event.userChoice = Promise.resolve({ outcome: 'accepted' })
  window.dispatchEvent(event)
  return event
}

beforeEach(() => {
  localStorage.clear()
  sessionStorage.clear()
  localStorage.setItem('ecolio.visits', '1')
})

describe('InstallPrompt', () => {
  it('renders nothing until the browser offers installation', () => {
    const wrapper = mount(InstallPrompt, { global: { plugins: [createAppI18n()] } })

    expect(wrapper.find('[role="region"]').exists()).toBe(false)
    wrapper.unmount()
  })

  it('shows the banner and installs on click', async () => {
    const wrapper = mount(InstallPrompt, { global: { plugins: [createAppI18n()] } })
    const event = fireInstallEvent()
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Installer Écolio')
    await wrapper.find('button[data-action="install"]').trigger('click')

    expect(event.prompt).toHaveBeenCalledOnce()
    wrapper.unmount()
  })

  it('hides for good when the user says later', async () => {
    const wrapper = mount(InstallPrompt, { global: { plugins: [createAppI18n()] } })
    fireInstallEvent()
    await wrapper.vm.$nextTick()

    await wrapper.find('button[data-action="dismiss"]').trigger('click')

    expect(wrapper.find('[role="region"]').exists()).toBe(false)
    wrapper.unmount()
  })
})
