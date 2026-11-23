import { computed, onScopeDispose, ref } from 'vue'

interface BeforeInstallPromptEvent extends Event {
  prompt(): Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

const VISITS_KEY = 'ecolio.visits'
const DISMISSED_KEY = 'ecolio.install-dismissed'
const COUNTED_KEY = 'ecolio.visit-counted'

/** Storage may be blocked (private mode); install hints are a nicety and must never break the app. */
function safe<T>(action: () => T, fallback: T): T {
  try {
    return action()
  } catch {
    return fallback
  }
}

/** One visit = one browser session, so reloading during a power cut does not look like a returning user. */
function countVisit(): number {
  return safe(() => {
    let visits = Number(localStorage.getItem(VISITS_KEY) ?? '0')
    if (!sessionStorage.getItem(COUNTED_KEY)) {
      visits += 1
      localStorage.setItem(VISITS_KEY, String(visits))
      sessionStorage.setItem(COUNTED_KEY, '1')
    }
    return visits
  }, 0)
}

/** Spec 8.1: the install invitation appears from the second visit, never on the first. */
export function useInstallPrompt() {
  const deferred = ref<BeforeInstallPromptEvent | null>(null)
  const dismissed = ref(safe(() => localStorage.getItem(DISMISSED_KEY) === '1', false))
  const visits = countVisit()

  const canInstall = computed(() => deferred.value !== null && visits >= 2 && !dismissed.value)

  function onBeforeInstallPrompt(event: Event) {
    // Holding the event back lets the app pick the moment instead of the browser's mini-infobar.
    event.preventDefault()
    deferred.value = event as BeforeInstallPromptEvent
  }
  const onInstalled = () => (deferred.value = null)

  window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
  window.addEventListener('appinstalled', onInstalled)
  onScopeDispose(() => {
    window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.removeEventListener('appinstalled', onInstalled)
  })

  async function install(): Promise<'accepted' | 'dismissed' | 'unavailable'> {
    const event = deferred.value
    if (!event) return 'unavailable'

    await event.prompt()
    const { outcome } = await event.userChoice
    // The browser allows a single prompt per event.
    deferred.value = null
    return outcome
  }

  function dismiss() {
    dismissed.value = true
    safe(() => localStorage.setItem(DISMISSED_KEY, '1'), undefined)
  }

  return { canInstall, install, dismiss }
}
