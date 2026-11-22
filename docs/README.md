# Laravel Modules documentation site

The source of the documentation site. It is an [Astro](https://astro.build) app with the [Starlight](https://starlight.astro.build) theme. It is not part of the Composer package.

## Run it

Node 22.23.4 or later is required.

```bash
cd docs
npm ci
npm run dev
```

Open http://localhost:4321/laravel-modules/. The site is served under the `/laravel-modules` base path.

Build the site, with the link check, and preview the result:

```bash
npm run build
npm run preview
```

`npm run build` fails on a broken internal link or anchor.

## Where things are

- Pages: `src/content/docs/`. One Markdown or MDX file per page. The path is the URL.
- Sidebar: `astro.config.mjs`, the `sidebar` array. Add a page to it, or the page does not show in the navigation.
- Logos and images: `src/assets/`. Theme: `src/styles/custom.css`.

## Rules

- Links: Astro does not add the base path to links in Markdown or in frontmatter. Write internal links as `/laravel-modules/<path>/`, or relative to the page. Link only to pages that exist.
- Components: Starlight components (`Steps`, `FileTree`, `Card`, `Tabs`, `Aside`) need an `.mdx` file and an import from `@astrojs/starlight/components`. Reference pages stay plain Markdown.
- Types: each page has one [Diátaxis](https://diataxis.fr) type, and the sidebar groups follow it: Start here (tutorials and how-to guides), Concepts (explanation), Guides (how-to), Reference, Upgrading, Ecosystem. Do not mix steps, explanation and reference on one page. Link to the page of the other type instead.
- Guides: a how-to guide title names the goal, for example "Create a module". Guides live under `guides/<topic>/`.
- Truth: the code is the source. Check a command, option, config key or class name against `src/` and `config/config.php` before you write it.

## Deploy

The workflow `.github/workflows/docs.yml` builds the site and deploys it to GitHub Pages when a change to `docs/` reaches `master`. The site is at https://nwidart.github.io/laravel-modules/.
