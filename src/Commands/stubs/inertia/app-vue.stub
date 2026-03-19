import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const el = document.getElementById('app')

if (el && el.dataset.page) {
    createInertiaApp({
        resolve: (name) => {
            const appPages = import.meta.glob('./Pages/**/*.vue')
            const modulePages = import.meta.glob('/Modules/*/resources/js/Pages/**/*.vue')

            const parts = name.split('/')
            const modulePage = `/Modules/${parts[0]}/resources/js/Pages/${parts.slice(1).join('/')}.vue`

            if (modulePages[modulePage]) {
                return modulePages[modulePage]()
            }

            return resolvePageComponent(`./Pages/${name}.vue`, appPages)
        },
        setup({ el, App, props, plugin }) {
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .mount(el)
        },
        progress: {
            color: '#4B5563',
        },
    })
}
