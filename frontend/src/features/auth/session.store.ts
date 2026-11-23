import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { utilisateurSchema, type Utilisateur } from './domain/auth.schemas'
import { z } from 'zod'

const STORAGE_KEY = 'ecolio.session'

const storedSchema = z.object({ token: z.string(), utilisateur: utilisateurSchema })

/** Storage can throw (private mode, blocked site data) or hold garbage; the app must still start anonymous. */
function readStored(): { token: string; utilisateur: Utilisateur } | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? storedSchema.parse(JSON.parse(raw)) : null
  } catch {
    return null
  }
}

function writeStored(value: { token: string; utilisateur: Utilisateur } | null) {
  try {
    if (value) localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    else localStorage.removeItem(STORAGE_KEY)
  } catch {
    // Without storage the session lasts until reload; acceptable degradation.
  }
}

export const useSessionStore = defineStore('session', () => {
  const stored = readStored()
  const token = ref<string | null>(stored?.token ?? null)
  const utilisateur = ref<Utilisateur | null>(stored?.utilisateur ?? null)

  const isAuthenticated = computed(() => token.value !== null)

  function setSession(newToken: string, newUser: Utilisateur) {
    token.value = newToken
    utilisateur.value = newUser
    writeStored({ token: newToken, utilisateur: newUser })
  }

  function clear() {
    token.value = null
    utilisateur.value = null
    writeStored(null)
  }

  function hasRole(role: string): boolean {
    return utilisateur.value?.roles.includes(role) ?? false
  }

  return { token, utilisateur, isAuthenticated, setSession, clear, hasRole }
})
