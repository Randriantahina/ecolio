<script setup lang="ts">
import { computed, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

async function onSubmit() {
  if (await vm.submit()) {
    await router.replace(safeRedirect(route.query.redirect as string | undefined))
  }
}
</script>

<template>
  <main class="flex min-h-svh items-center justify-center p-4">
    <Card class="w-full max-w-sm">
      <CardHeader>
        <CardTitle>{{ t('auth.login.title') }}</CardTitle>
        <CardDescription>{{ t('auth.login.subtitle') }}</CardDescription>
      </CardHeader>
      <CardContent>
        <form class="grid gap-4" novalidate @submit.prevent="onSubmit">
          <div class="grid gap-2">
            <Label for="login">{{ t('auth.login.login') }}</Label>
            <Input
              id="login"
              v-model="vm.login.value"
              name="login"
              autocomplete="username"
              class="h-11"
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
            <Input
              id="password"
              v-model="vm.password.value"
              name="password"
              type="password"
              autocomplete="current-password"
              class="h-11"
            />
            <p
              v-for="message in vm.fieldErrors.value.password"
              :key="message"
              class="text-sm text-destructive"
            >
              {{ message }}
            </p>
          </div>

          <p v-if="alertMessage" role="alert" class="text-sm text-destructive">
            {{ alertMessage }}
          </p>

          <Button type="submit" class="h-11" :disabled="!vm.canSubmit.value">
            {{ vm.loading.value ? t('auth.login.submitting') : t('auth.login.submit') }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </main>
</template>
