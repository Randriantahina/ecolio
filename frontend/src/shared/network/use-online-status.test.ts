import { afterEach, describe, expect, it, vi } from 'vitest'
import { effectScope } from 'vue'
import { useOnlineStatus } from './use-online-status'

afterEach(() => vi.restoreAllMocks())

describe('useOnlineStatus', () => {
  it('starts from the browser state', () => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(false)
    const scope = effectScope()

    const online = scope.run(() => useOnlineStatus())!
    expect(online.value).toBe(false)
    scope.stop()
  })

  it('follows online and offline events', () => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(true)
    const scope = effectScope()
    const online = scope.run(() => useOnlineStatus())!

    window.dispatchEvent(new Event('offline'))
    expect(online.value).toBe(false)
    window.dispatchEvent(new Event('online'))
    expect(online.value).toBe(true)
    scope.stop()
  })

  it('stops listening when its scope ends, so unmounted screens do not leak listeners', () => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(true)
    const scope = effectScope()
    const online = scope.run(() => useOnlineStatus())!
    scope.stop()

    window.dispatchEvent(new Event('offline'))
    expect(online.value).toBe(true)
  })
})
