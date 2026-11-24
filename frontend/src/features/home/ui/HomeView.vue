<script setup lang="ts">
import { ArrowRight, CalendarCheck, GraduationCap, Wallet } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { useSessionStore } from '@/features/auth/session.store'
import PageHeader from '@/shared/ui/PageHeader.vue'

const { t } = useI18n()
const session = useSessionStore()

const raccourcis = [
  { name: 'ecolage', cle: 'ecolage', icon: Wallet, accent: 'bg-success-soft text-success' },
  {
    name: 'absences',
    cle: 'absences',
    icon: CalendarCheck,
    accent: 'bg-warning-soft text-warning',
  },
  { name: 'eleves', cle: 'eleves', icon: GraduationCap, accent: 'bg-accent text-primary' },
] as const
</script>

<template>
  <PageHeader
    :title="t('home.welcome', { nom: session.utilisateur?.nom ?? '' })"
    :subtitle="t('home.subtitle')"
  />

  <div class="grid gap-4 md:grid-cols-3">
    <RouterLink
      v-for="r in raccourcis"
      :key="r.name"
      :to="{ name: r.name }"
      class="group flex flex-col rounded-2xl border bg-card p-6 transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
    >
      <span class="mb-5 grid size-12 place-items-center rounded-xl" :class="r.accent">
        <component :is="r.icon" class="size-6" aria-hidden="true" />
      </span>
      <h2 class="text-lg font-semibold">{{ t(`home.shortcuts.${r.cle}.title`) }}</h2>
      <p class="mt-1 flex-1 text-sm text-muted-foreground">
        {{ t(`home.shortcuts.${r.cle}.body`) }}
      </p>
      <span class="mt-5 flex items-center gap-1 text-sm font-medium text-primary">
        {{ t('nav.' + r.name) }}
        <ArrowRight
          class="size-4 transition-transform group-hover:translate-x-1"
          aria-hidden="true"
        />
      </span>
    </RouterLink>
  </div>
</template>
