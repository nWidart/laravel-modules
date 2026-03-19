import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const el = document.getElementById('app')

if (el && el.dataset.page) {
    createInertiaApp({
        resolve: (name) => {
            const appPages = import.meta.glob('./Pages/**/*.jsx')
            const modulePages = import.meta.glob('/Modules/*/resources/js/Pages/**/*.jsx')

            const parts = name.split('/')
            const modulePage = `/Modules/${parts[0]}/resources/js/Pages/${parts.slice(1).join('/')}.jsx`

            if (modulePages[modulePage]) {
                return modulePages[modulePage]()
            }

            return resolvePageComponent(`./Pages/${name}.jsx`, appPages)
        },
        setup({ el, App, props }) {
            createRoot(el).render(<App {...props} />)
        },
        progress: {
            color: '#4B5563',
        },
    })
}
