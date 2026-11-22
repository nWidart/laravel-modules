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
            // Add new groups (Concepts, Guides, Upgrading, Ecosystem) as new entries in this array.
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
            ],
        }),
    ],
});
