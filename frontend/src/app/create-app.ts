import { VueQueryPlugin } from '@tanstack/vue-query'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import type { RouterHistory } from 'vue-router'
import { authRepositoryKey } from '@/features/auth/auth.injection'
import { HttpAbsencesRepository } from '@/features/absences/data/http-absences-repository'
import { HttpEcolageRepository } from '@/features/ecolage/data/http-ecolage-repository'
import { HttpElevesRepository } from '@/features/eleves/data/http-eleves-repository'
import { HttpStructureRepository } from '@/features/structure/data/http-structure-repository'
import { HttpAuthRepository } from '@/features/auth/data/http-auth-repository'
import { useSessionStore } from '@/features/auth/session.store'
import { createHttpClient } from '@/shared/api/http-client'
import { createAppI18n } from '@/shared/i18n'
import App from '../App.vue'
import { repositoriesKey } from './repositories'
import { createAppRouter } from './router'

export interface AppOptions {
  history?: RouterHistory
  apiBaseUrl: string
  fetchImpl?: typeof fetch
}

/** Composition root: the only place where concrete adapters are chosen and wired together. */
export function createEcolioApp(options: AppOptions) {
  const app = createApp(App)
  const pinia = createPinia()
  app.use(pinia)

  const session = useSessionStore(pinia)
  const router = createAppRouter(options.history)

  const http = createHttpClient({
    baseUrl: options.apiBaseUrl,
    getToken: () => session.token,
    fetchImpl: options.fetchImpl,
    onUnauthorized: () => {
      session.clear()
      void router.push({ name: 'login' })
    },
  })

  app.use(router)
  app.use(createAppI18n())
  app.use(VueQueryPlugin)
  app.provide(authRepositoryKey, new HttpAuthRepository(http))
  app.provide(repositoriesKey, {
    structure: new HttpStructureRepository(http),
    eleves: new HttpElevesRepository(http),
    ecolage: new HttpEcolageRepository(http),
    absences: new HttpAbsencesRepository(http),
  })

  return { app, router, http, session }
}
