<script setup lang="ts">
import { BookOpenCheck, Eye, EyeOff } from 'lucide-vue-next'
import { computed, inject, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { authRepositoryKey } from '../auth.injection'
import { useLoginViewModel } from '../login.viewmodel'
import { safeRedirect } from '../safe-redirect'
import { useSessionStore } from '../session.store'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const repository = inject(authRepositoryKey)
if (!repository) throw new Error('AuthRepository is not provided')

const vm = useLoginViewModel(repository, useSessionStore())

const alertMessage = computed(() => {
  switch (vm.errorKind.value) {
    case 'locked':
      return vm.serverMessage.value ?? t('auth.errors.locked')
    case 'offline':
      return t('auth.errors.offline')
    case 'unexpected':
      return t('auth.errors.unexpected')
    default:
      return null
  }
})

const showPassword = ref(false)

async function onSubmit() {
  if (await vm.submit()) {
    await router.replace(safeRedirect(route.query.redirect as string | undefined))
  }
}
</script>

<template>
  <main class="flex min-h-svh flex-col items-center justify-center bg-background px-5 py-10">
    <div class="w-full max-w-md">
      <div class="mb-8 flex flex-col items-center text-center">
        <span
          class="mb-5 grid size-14 place-items-center rounded-2xl bg-primary text-primary-foreground shadow-sm"
        >
          <BookOpenCheck class="size-7" aria-hidden="true" />
        </span>
        <h1 class="text-3xl font-semibold tracking-tight">{{ t('auth.login.welcome') }}</h1>
        <p class="mt-2 text-muted-foreground">{{ t('auth.login.subtitle') }}</p>
      </div>

      <form
        class="grid gap-5 rounded-2xl border bg-card p-6 shadow-sm sm:p-8"
        novalidate
        @submit.prevent="onSubmit"
      >
        <div class="grid gap-2">
          <Label for="login">{{ t('auth.login.login') }}</Label>
          <Input
            id="login"
            v-model="vm.login.value"
            name="login"
            autocomplete="username"
            class="h-12 text-base"
          />
          <p
            v-for="message in vm.fieldErrors.value.login"
            :key="message"
            class="text-sm text-destructive"
          >
            {{ message }}
          </p>
        </div>

        <div class="grid gap-2">
          <Label for="password">{{ t('auth.login.password') }}</Label>
          <div class="relative">
            <Input
              id="password"
              v-model="vm.password.value"
              name="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              class="h-12 pr-12 text-base"
            />
            <button
              type="button"
              class="absolute right-1.5 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-lg text-muted-foreground transition hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring"
              :aria-label="
                showPassword ? t('auth.login.hidePassword') : t('auth.login.showPassword')
              "
              :aria-pressed="showPassword"
              @click="showPassword = !showPassword"
            >
              <EyeOff v-if="showPassword" class="size-5" aria-hidden="true" />
              <Eye v-else class="size-5" aria-hidden="true" />
            </button>
          </div>
          <p
            v-for="message in vm.fieldErrors.value.password"
            :key="message"
            class="text-sm text-destructive"
          >
            {{ message }}
          </p>
        </div>

        <p
          v-if="alertMessage"
          role="alert"
          class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive"
        >
          {{ alertMessage }}
        </p>

        <Button type="submit" class="mt-1 h-12 text-base" :disabled="!vm.canSubmit.value">
          {{ vm.loading.value ? t('auth.login.submitting') : t('auth.login.submit') }}
        </Button>
      </form>

      <p class="mt-8 text-center text-sm text-muted-foreground">Écolio · Gestion d’école</p>
    </div>
  </main>
</template>
