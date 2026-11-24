<script setup lang="ts">
defineProps<{ title: string; body?: string }>()
</script>

<template>
  <div
    class="grid place-items-center rounded-2xl border border-dashed bg-card/50 px-6 py-14 text-center"
  >
    <div class="mb-3 text-muted-foreground"><slot name="icon" /></div>
    <p class="font-medium">{{ title }}</p>
    <p v-if="body" class="mt-1 max-w-sm text-sm text-muted-foreground">{{ body }}</p>
  </div>
</template>
