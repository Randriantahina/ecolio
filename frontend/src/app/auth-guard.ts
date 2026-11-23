export interface GuardTarget {
  name?: string | symbol | null
  fullPath: string
  meta: { requiresAuth?: boolean; guestOnly?: boolean }
}

export type GuardResult = true | { name: string; query?: Record<string, string> }

/** Pure function so route protection is unit-tested without a router or a browser. */
export function authGuard(to: GuardTarget, session: { isAuthenticated: boolean }): GuardResult {
  if (to.meta.requiresAuth && !session.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && session.isAuthenticated) {
    return { name: 'home' }
  }

  return true
}
