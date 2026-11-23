import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { effectScope } from 'vue'
import { useInstallPrompt } from './use-install-prompt'

function fireInstallEvent(outcome: 'accepted' | 'dismissed' = 'accepted') {
  const event = new Event('beforeinstallprompt', { cancelable: true }) as Event & {
    prompt: () => Promise<void>
    userChoice: Promise<{ outcome: string }>
  }
  event.prompt = vi.fn().mockResolvedValue(undefined)
  event.userChoice = Promise.resolve({ outcome })
  window.dispatchEvent(event)
  return event
}

function mountComposable() {
  const scope = effectScope()
  return { prompt: scope.run(() => useInstallPrompt())!, scope }
}

beforeEach(() => {
  localStorage.clear()
  sessionStorage.clear()
})
afterEach(() => vi.restoreAllMocks())

describe('useInstallPrompt', () => {
  it('stays quiet on the very first visit, even when the browser offers installation', () => {
    const { prompt, scope } = mountComposable()
    fireInstallEvent()

    expect(prompt.canInstall.value).toBe(false)
    scope.stop()
  })

  it('offers installation from the second visit (spec 8.1)', () => {
    localStorage.setItem('ecolio.visits', '1')
    const { prompt, scope } = mountComposable()

    fireInstallEvent()

    expect(prompt.canInstall.value).toBe(true)
    scope.stop()
  })

  it('counts one visit per browser session, not one per reload', () => {
    mountComposable().scope.stop()
    mountComposable().scope.stop()

    expect(localStorage.getItem('ecolio.visits')).toBe('1')
  })

  it('takes over the browser prompt so the app can show it at the right moment', () => {
    localStorage.setItem('ecolio.visits', '1')
    const { scope } = mountComposable()

    const event = fireInstallEvent()

    expect(event.defaultPrevented).toBe(true)
    scope.stop()
  })

  it('installs through the stored event and then hides', async () => {
    localStorage.setItem('ecolio.visits', '1')
    const { prompt, scope } = mountComposable()
    const event = fireInstallEvent('accepted')

    const outcome = await prompt.install()

    expect(event.prompt).toHaveBeenCalledOnce()
    expect(outcome).toBe('accepted')
    expect(prompt.canInstall.value).toBe(false)
    scope.stop()
  })

  it('remembers a refusal so the banner does not nag on every visit', () => {
    localStorage.setItem('ecolio.visits', '1')
    const first = mountComposable()
    fireInstallEvent()
    first.prompt.dismiss()
    first.scope.stop()

    sessionStorage.clear()
    const second = mountComposable()
    fireInstallEvent()

    expect(second.prompt.canInstall.value).toBe(false)
    second.scope.stop()
  })

  it('hides once the app is installed', () => {
    localStorage.setItem('ecolio.visits', '1')
    const { prompt, scope } = mountComposable()
    fireInstallEvent()

    window.dispatchEvent(new Event('appinstalled'))

    expect(prompt.canInstall.value).toBe(false)
    scope.stop()
  })

  it('does not crash when storage is unavailable', () => {
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new Error('blocked')
    })
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('blocked')
    })

    expect(() => mountComposable().scope.stop()).not.toThrow()
  })

  it('answers "unavailable" when there is nothing to install', async () => {
    const { prompt, scope } = mountComposable()

    expect(await prompt.install()).toBe('unavailable')
    scope.stop()
  })
})
