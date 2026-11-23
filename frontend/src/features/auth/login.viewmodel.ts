import { computed, ref } from 'vue'
import { LockedError, NetworkError, ValidationError } from '@/shared/api/errors'
import type { AuthRepository } from './domain/auth.repository'
import type { Utilisateur } from './domain/auth.schemas'

/** Narrow port instead of the Pinia store: the ViewModel only needs to hand the session over. */
export interface SessionPort {
  setSession(token: string, utilisateur: Utilisateur): void
}

export type LoginErrorKind = 'locked' | 'offline' | 'unexpected'

export function useLoginViewModel(repository: AuthRepository, session: SessionPort) {
  const login = ref('')
  const password = ref('')
  const loading = ref(false)
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorKind = ref<LoginErrorKind | null>(null)
  const serverMessage = ref<string | null>(null)

  const canSubmit = computed(
    () => login.value.trim() !== '' && password.value !== '' && !loading.value,
  )

  function resetErrors() {
    fieldErrors.value = {}
    errorKind.value = null
    serverMessage.value = null
  }

  async function submit(): Promise<boolean> {
    // The same guard serves the disabled button and a double Enter press on a slow connection.
    if (!canSubmit.value) return false

    resetErrors()
    loading.value = true

    try {
      const result = await repository.login({
        login: login.value.trim(),
        password: password.value,
        deviceName: 'web',
      })
      session.setSession(result.token, result.utilisateur)
      return true
    } catch (error) {
      if (error instanceof ValidationError) {
        fieldErrors.value = error.errors
      } else if (error instanceof LockedError) {
        errorKind.value = 'locked'
        serverMessage.value = error.message
      } else if (error instanceof NetworkError) {
        errorKind.value = 'offline'
      } else {
        // Technical messages stay out of the UI; they are not actionable for a secretary.
        errorKind.value = 'unexpected'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  return { login, password, loading, fieldErrors, errorKind, serverMessage, canSubmit, submit }
}
