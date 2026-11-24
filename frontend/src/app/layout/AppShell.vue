<script setup lang="ts">
import { BookOpenCheck, CalendarCheck, GraduationCap, Home, LogOut, Wallet } from 'lucide-vue-next'
import { computed, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { authRepositoryKey } from '@/features/auth/auth.injection'
import { useSessionStore } from '@/features/auth/session.store'
import OnlineIndicator from '@/shared/network/OnlineIndicator.vue'
import InstallPrompt from '@/shared/pwa/InstallPrompt.vue'

const { t } = useI18n()
const session = useSessionStore()
const router = useRouter()
const auth = inject(authRepositoryKey)

const items = computed(() => [
  { name: 'home', label: t('nav.home'), icon: Home },
  { name: 'ecolage', label: t('nav.ecolage'), icon: Wallet },
  { name: 'eleves', label: t('nav.eleves'), icon: GraduationCap },
  { name: 'absences', label: t('nav.absences'), icon: CalendarCheck },
])

const role = computed(() => {
  const r = session.utilisateur?.roles[0]
  return r ? t(`roles.${r}`) : ''
})

const initiales = computed(() =>
  (session.utilisateur?.nom ?? '')
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase(),
)

async function deconnexion() {
  // The token is dropped even if the server cannot be reached: leaving must always work.
  try {
    await auth?.logout()
  } catch {
    /* offline logout: local session is still cleared below */
  }
  session.clear()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-svh md:grid md:grid-cols-[17rem_1fr]">
    <!-- Desktop sidebar -->
    <aside
      class="no-print sticky top-0 hidden h-svh flex-col bg-sidebar p-5 text-sidebar-foreground md:flex"
    >
      <RouterLink to="/" class="mb-8 flex items-center gap-3 px-2">
        <span
          class="grid size-10 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground"
        >
          <BookOpenCheck class="size-5" aria-hidden="true" />
        </span>
        <span class="text-xl font-semibold tracking-tight">Écolio</span>
      </RouterLink>

      <nav :aria-label="t('nav.menu')" class="flex flex-1 flex-col gap-1">
        <RouterLink
          v-for="item in items"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium text-sidebar-foreground/75 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
          active-class="!bg-sidebar-accent !text-sidebar-accent-foreground"
        >
          <component :is="item.icon" class="size-5" aria-hidden="true" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="space-y-3 border-t border-sidebar-border pt-4">
        <div class="flex items-center gap-3 px-1">
          <span
            class="grid size-9 place-items-center rounded-full bg-sidebar-accent text-xs font-semibold"
          >
            {{ initiales }}
          </span>
          <div class="min-w-0 text-sm">
            <p class="truncate font-medium">{{ session.utilisateur?.nom }}</p>
            <p class="truncate text-xs text-sidebar-foreground/60">{{ role }}</p>
          </div>
        </div>
        <button
          type="button"
          class="flex h-11 w-full items-center gap-3 rounded-lg px-3 text-sm text-sidebar-foreground/75 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
          @click="deconnexion"
        >
          <LogOut class="size-5" aria-hidden="true" />
          {{ t('nav.logout') }}
        </button>
      </div>
    </aside>

    <div class="flex min-w-0 flex-col pb-20 md:pb-0">
      <!-- Top bar -->
      <header
        class="no-print sticky top-0 z-20 flex h-14 items-center justify-between border-b bg-background/85 px-4 backdrop-blur md:px-8"
      >
        <div class="flex items-center gap-2 md:hidden">
          <span
            class="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground"
          >
            <BookOpenCheck class="size-4" aria-hidden="true" />
          </span>
          <span class="font-semibold">Écolio</span>
        </div>
        <p class="hidden truncate text-sm text-muted-foreground md:block">
          {{ session.utilisateur?.ecole?.nom }}
        </p>
        <div class="flex items-center gap-3">
          <OnlineIndicator />
          <button
            type="button"
            class="grid size-10 place-items-center rounded-lg text-muted-foreground hover:bg-accent md:hidden"
            :aria-label="t('nav.logout')"
            @click="deconnexion"
          >
            <LogOut class="size-5" aria-hidden="true" />
          </button>
        </div>
      </header>

      <InstallPrompt />

      <main class="mx-auto w-full max-w-6xl flex-1 p-4 md:p-8">
        <RouterView />
      </main>
    </div>

    <!-- Mobile tab bar: thumb reach, 44px+ targets -->
    <nav
      :aria-label="t('nav.menu')"
      class="no-print fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
    >
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="{ name: item.name }"
        class="flex h-16 flex-col items-center justify-center gap-1 text-xs font-medium text-muted-foreground"
        active-class="!text-primary"
      >
        <component :is="item.icon" class="size-5" aria-hidden="true" />
        {{ item.label }}
      </RouterLink>
    </nav>
  </div>
</template>
