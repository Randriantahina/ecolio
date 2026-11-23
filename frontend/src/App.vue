<script setup lang="ts">
import { RouterView } from 'vue-router'
import OnlineIndicator from '@/shared/network/OnlineIndicator.vue'
</script>

<template>
  <div class="min-h-svh bg-background text-foreground">
    <header class="flex justify-end border-b px-4 py-2">
      <OnlineIndicator />
    </header>
    <RouterView />
  </div>
</template>
