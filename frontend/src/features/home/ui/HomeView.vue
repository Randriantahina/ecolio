<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '@/features/auth/session.store'

const { t } = useI18n()
const session = useSessionStore()
</script>

<template>
  <main class="p-4">
    <h1 class="text-xl font-semibold">
      {{ t('home.welcome', { nom: session.utilisateur?.nom ?? '' }) }}
    </h1>
    <p v-if="session.utilisateur?.ecole" class="text-muted-foreground">
      {{ t('home.school', { nom: session.utilisateur.ecole.nom }) }}
    </p>
  </main>
</template>
