import { createInertiaApp } from '@inertiajs/vue3'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { createAppI18n } from '@/i18n'

const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue')

createInertiaApp({
    title: (title) => (title ? `${title} · Écolio` : 'Écolio'),
    resolve: async (name) => {
        const loader = pages[`./pages/${name}.vue`]
        if (!loader) throw new Error(`Unknown page: ${name}`)
        const page = await loader()
        // Authenticated pages share the shell; the login page opts out by setting its own layout.
        page.default.layout ??= name.startsWith('Auth/') ? undefined : AppLayout
        return page
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createAppI18n())
            .mount(el as Element)
    },
})
