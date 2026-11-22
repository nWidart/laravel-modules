// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';
import starlightLinksValidator from 'starlight-links-validator';
import starlightLlmsTxt from 'starlight-llms-txt';

// https://astro.build/config
export default defineConfig({
    // Served from GitHub Pages as a project site: https://nwidart.github.io/laravel-modules/
    site: 'https://nwidart.github.io',
    base: '/laravel-modules',

    integrations: [
        starlight({
            title: 'Laravel Modules',
            description: 'Split a large Laravel application into self-contained modules, each with its own routes, views, migrations and providers.',
            logo: {
                light: './src/assets/logo-light.svg',
                dark: './src/assets/logo-dark.svg',
            },
            social: [
                { icon: 'github', label: 'GitHub', href: 'https://github.com/nWidart/laravel-modules' },
            ],
            editLink: {
                baseUrl: 'https://github.com/nWidart/laravel-modules/edit/master/docs/',
            },
            lastUpdated: true,
            // Open Graph images: src/pages/og/ renders them, src/routeData.ts adds their meta tags.
            routeMiddleware: './src/routeData.ts',
            head: [
                // Links in `head` do not get the base path.
                { tag: 'link', attrs: { rel: 'apple-touch-icon', href: '/laravel-modules/apple-touch-icon.png' } },
                { tag: 'meta', attrs: { name: 'theme-color', content: '#0B1220' } },
            ],
            customCss: [
                '@fontsource-variable/space-grotesk',
                '@fontsource-variable/jetbrains-mono',
                './src/styles/custom.css',
            ],
            plugins: [
                // Fails `astro build` on broken internal links and anchors.
                starlightLinksValidator(),
                // Writes llms.txt, llms-full.txt and llms-small.txt to the build output.
                starlightLlmsTxt(),
            ],
            // Explicit order. A slug string uses the page title as its label.
            // All groups are present: Start here, Concepts, Guides, Reference, Upgrading, Ecosystem.
            sidebar: [
                {
                    label: 'Start here',
                    items: [
                        'start-here/introduction',
                        'start-here/installation',
                        'start-here/first-module',
                    ],
                },
                {
                    label: 'Concepts',
                    items: [
                        'concepts/module-anatomy',
                        'concepts/how-modules-boot',
                        'concepts/status-and-activators',
                        'concepts/autoloading',
                        'concepts/generators-and-stubs',
                    ],
                },
                {
                    label: 'Guides',
                    items: [
                        {
                            label: 'Modules',
                            collapsed: true,
                            items: [
                                'guides/modules/create-a-module',
                                'guides/modules/enable-and-disable-modules',
                                'guides/modules/delete-a-module',
                                'guides/modules/set-a-default-module',
                                'guides/modules/change-the-folder-layout',
                                'guides/modules/change-the-location-and-namespace',
                                'guides/modules/control-the-load-order',
                                'guides/modules/scan-extra-locations',
                            ],
                        },
                        {
                            label: 'HTTP and UI',
                            collapsed: true,
                            items: [
                                'guides/http/routes',
                                'guides/http/controllers',
                                'guides/http/middleware',
                                'guides/http/views-and-components',
                                'guides/http/translations',
                                'guides/http/config',
                            ],
                        },
                        {
                            label: 'Database',
                            collapsed: true,
                            items: [
                                'guides/database/migrations',
                                'guides/database/models',
                                'guides/database/factories-and-seeders',
                                'guides/database/prune-models',
                            ],
                        },
                        {
                            label: 'Front end',
                            collapsed: true,
                            items: [
                                'guides/frontend/vite',
                                'guides/frontend/inertia',
                            ],
                        },
                        {
                            label: 'Console and events',
                            collapsed: true,
                            items: [
                                'guides/console-and-events/commands',
                                'guides/console-and-events/schedules',
                                'guides/console-and-events/events-and-listeners',
                                'guides/console-and-events/lifecycle-events',
                            ],
                        },
                        {
                            label: 'Testing',
                            collapsed: true,
                            items: [
                                'guides/testing/run-module-tests',
                                'guides/testing/coverage',
                            ],
                        },
                        {
                            label: 'Customise',
                            collapsed: true,
                            items: [
                                'guides/customise/stubs',
                                'guides/customise/replacement-tokens',
                                'guides/customise/custom-activator',
                                'guides/customise/custom-commands',
                                'guides/customise/macros',
                            ],
                        },
                        {
                            label: 'Distribute',
                            collapsed: true,
                            items: [
                                'guides/distribute/publish-a-module-as-a-package',
                                'guides/distribute/install-modules',
                            ],
                        },
                        {
                            label: 'Production',
                            collapsed: true,
                            items: [
                                'guides/production/deploy',
                                'guides/production/prohibit-destructive-commands',
                                'guides/production/health-checks',
                            ],
                        },
                    ],
                },
                {
                    label: 'Reference',
                    items: [
                        {
                            label: 'Artisan commands',
                            items: [
                                { label: 'Overview', slug: 'reference/commands' },
                                'reference/commands/module-management',
                                'reference/commands/generators',
                                'reference/commands/database',
                                'reference/commands/publish',
                                'reference/commands/utility',
                            ],
                        },
                        'reference/configuration',
                        'reference/module-json',
                        {
                            label: 'PHP API',
                            items: [
                                'reference/api/repository',
                                'reference/api/module',
                                'reference/api/module-service-provider',
                                'reference/api/helpers',
                                'reference/api/events',
                                'reference/api/contracts',
                                'reference/api/exceptions',
                            ],
                        },
                        'reference/stubs',
                    ],
                },
                {
                    label: 'Upgrading',
                    items: [
                        'upgrading/compatibility',
                        'upgrading/upgrade-to-13',
                        'upgrading/13-x-release-notes',
                        'upgrading/deprecations',
                    ],
                },
                {
                    label: 'Ecosystem',
                    items: [
                        'ecosystem/ai-assistants',
                        'ecosystem/community-packages',
                        'ecosystem/contributing-and-support',
                    ],
                },
            ],
        }),
    ],
});
