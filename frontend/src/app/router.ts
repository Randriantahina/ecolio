import { createRouter, createWebHistory, type RouterHistory } from 'vue-router'
import { useSessionStore } from '@/features/auth/session.store'
import { authGuard } from './auth-guard'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guestOnly?: boolean
  }
}

export function createAppRouter(history: RouterHistory = createWebHistory()) {
  const router = createRouter({
    history,
    routes: [
      {
        path: '/',
        name: 'home',
        component: () => import('@/features/home/ui/HomeView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/connexion',
        name: 'login',
        component: () => import('@/features/auth/ui/LoginView.vue'),
        meta: { guestOnly: true },
      },
    ],
  })

  // The store is read inside the guard, not at creation: Pinia must be installed on the app first.
  router.beforeEach((to) => authGuard(to, useSessionStore()))

  return router
}
