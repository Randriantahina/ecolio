<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useOnlineStatus } from './use-online-status'

const { t } = useI18n()
const online = useOnlineStatus()
</script>

<template>
  <span role="status" class="inline-flex items-center gap-2 text-sm">
    <span
      class="size-2 rounded-full"
      :class="online ? 'bg-emerald-500' : 'bg-amber-500'"
      aria-hidden="true"
    />
    {{ online ? t('network.online') : t('network.offline') }}
  </span>
</template>
