---
title: "Docs Open Graph images: astro-og-canvas quirks with Starlight and variable fonts"
author: Nwidart
date: 2026-11-22
branch: docs/social-preview
cost: { minutes: 45, tokens: 150000 }
id: l-e2bfde35
scope: repo
status: active
src: agent
touches:
  - docs/src/lib/og.ts
  - docs/src/pages/og/[...slug].ts
  - docs/src/routeData.ts
  - docs/astro.config.mjs
  - docs/public/apple-touch-icon.png
---

## Decided
- One 1200x630 card per docs page, built by astro-og-canvas at `/og/<entry id>.png`. A Starlight route middleware (`src/routeData.ts`) adds `og:image`, its type, size and alt, and `twitter:image` with its alt. Starlight already writes the other og: tags and `twitter:card`.
- Fonts come from the installed `@fontsource-variable` woff2 files (latin subset). CanvasKit reads woff2 and applies the requested weight to the variable font, so no static font package is needed.
- The logo PNG and the background (wordmark, footer) are drawn at build time into `node_modules/.astro-og-canvas/brand/`, not committed. `src/lib/og.ts` measures the text with the same layout astro-og-canvas uses and shrinks the title (64px, then 52px) and truncates with "…" so the text never overflows.
- The 404 page is not in the docs collection and uses the home card.

## Tried and ruled out
- `@fontsource/space-grotesk` static files: not needed, the variable font renders real bold.

## Watch out
- CanvasKit names the variable Space Grotesk font "Space Grotesk Light" (its default instance). `fontFamilies` must use that exact name. With an unknown name CanvasKit silently falls back to another loaded font (JetBrains Mono).
- astro-og-canvas has no option to reserve space at the bottom: a long paragraph ends at the bottom padding. The footer is kept clear with a navy `border` on the `block-end` side, which the background image then paints over.
- Starlight's route data gives the home page the id `''`, while its collection id is `index`. `ogImageFile()` maps `''` to `index.png`.
- Starlight `head` config links do not get the base path. Write `/laravel-modules/...` in the href.
- Loading two woff2 subsets with the same family name (latin and latin-ext) does not merge them; only one is used.
