<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { SharedProps } from '@/types'

const { t } = useI18n()
const page = usePage<SharedProps>()
const nom = computed(() => page.props.auth.utilisateur?.nom ?? '')
</script>

<template>
    <Head :title="t('nav.home')" />
    <header class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
            {{ t('home.welcome', { nom }) }}
        </h1>
        <p class="mt-1 text-sm text-muted-foreground md:text-base">{{ t('home.subtitle') }}</p>
    </header>
</template>
