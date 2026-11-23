<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { useInstallPrompt } from './use-install-prompt'

const { t } = useI18n()
const { canInstall, install, dismiss } = useInstallPrompt()
</script>

<template>
  <section
    v-if="canInstall"
    role="region"
    :aria-label="t('pwa.install.title')"
    class="flex items-center justify-between gap-3 border-b bg-muted px-4 py-3"
  >
    <div class="text-sm">
      <p class="font-medium">{{ t('pwa.install.title') }}</p>
      <p class="text-muted-foreground">{{ t('pwa.install.body') }}</p>
    </div>
    <div class="flex shrink-0 gap-2">
      <Button variant="ghost" class="h-11" data-action="dismiss" @click="dismiss">
        {{ t('pwa.install.later') }}
      </Button>
      <Button class="h-11" data-action="install" @click="install">
        {{ t('pwa.install.accept') }}
      </Button>
    </div>
  </section>
</template>
