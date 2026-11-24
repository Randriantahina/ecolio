<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    BookOpenCheck,
    CalendarCheck,
    GraduationCap,
    Home,
    LogOut,
    Settings,
    Wallet,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import OnlineIndicator from '@/components/app/OnlineIndicator.vue'
import type { SharedProps } from '@/types'

const { t } = useI18n()
const page = usePage<SharedProps>()

const catalogue: Record<string, { href: string; icon: Component }> = {
    accueil: { href: '/', icon: Home },
    ecolage: { href: '/ecolage', icon: Wallet },
    eleves: { href: '/eleves', icon: GraduationCap },
    absences: { href: '/absences', icon: CalendarCheck },
    reglages: { href: '/reglages', icon: Settings },
}

// The server sends the keys the role may use; labels and icons are a front concern.
const items = computed(() =>
    page.props.navigation
        .filter((key) => key in catalogue)
        .map((key) => ({ key, label: t(`nav.${key}`), ...catalogue[key]! })),
)

const utilisateur = computed(() => page.props.auth.utilisateur)
const role = computed(() =>
    utilisateur.value?.roles[0] ? t(`roles.${utilisateur.value.roles[0]}`) : '',
)
const initiales = computed(() =>
    (utilisateur.value?.nom ?? '')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
)

function actif(href: string): boolean {
    const path = page.url.split('?')[0]!
    return href === '/' ? path === '/' : path === href || path.startsWith(`${href}/`)
}

function deconnexion() {
    router.post('/deconnexion')
}
</script>

<template>
    <div class="min-h-svh md:grid md:grid-cols-[17rem_1fr]">
        <aside
            class="no-print sticky top-0 hidden h-svh flex-col bg-sidebar p-5 text-sidebar-foreground md:flex"
        >
            <Link href="/" class="mb-8 flex items-center gap-3 px-2">
                <span
                    class="grid size-10 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground"
                >
                    <BookOpenCheck class="size-5" aria-hidden="true" />
                </span>
                <span class="text-xl font-semibold tracking-tight">Écolio</span>
            </Link>

            <nav :aria-label="t('nav.menu')" class="flex flex-1 flex-col gap-1">
                <Link
                    v-for="item in items"
                    :key="item.key"
                    :href="item.href"
                    class="flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium text-sidebar-foreground/75 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    :class="
                        actif(item.href) && '!bg-sidebar-accent !text-sidebar-accent-foreground'
                    "
                    :aria-current="actif(item.href) ? 'page' : undefined"
                >
                    <component :is="item.icon" class="size-5" aria-hidden="true" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="space-y-3 border-t border-sidebar-border pt-4">
                <div class="flex items-center gap-3 px-1">
                    <span
                        class="grid size-9 place-items-center rounded-full bg-sidebar-accent text-xs font-semibold"
                        >{{ initiales }}</span
                    >
                    <div class="min-w-0 text-sm">
                        <p class="truncate font-medium">{{ utilisateur?.nom }}</p>
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
                    {{ utilisateur?.ecole?.nom }}
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

            <p
                v-if="page.props.flash.succes"
                role="status"
                class="mx-4 mt-4 rounded-xl border border-success/30 bg-success-soft p-3 text-sm md:mx-8"
            >
                {{ page.props.flash.succes }}
            </p>

            <main class="mx-auto w-full max-w-6xl flex-1 p-4 md:p-8">
                <slot />
            </main>
        </div>

        <nav
            :aria-label="t('nav.menu')"
            class="no-print fixed inset-x-0 bottom-0 z-30 grid border-t bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
            :style="{ gridTemplateColumns: `repeat(${Math.max(items.length, 1)}, minmax(0, 1fr))` }"
        >
            <Link
                v-for="item in items"
                :key="item.key"
                :href="item.href"
                class="flex h-16 flex-col items-center justify-center gap-1 text-xs font-medium text-muted-foreground"
                :class="actif(item.href) && '!text-primary'"
            >
                <component :is="item.icon" class="size-5" aria-hidden="true" />
                {{ item.label }}
            </Link>
        </nav>
    </div>
</template>
