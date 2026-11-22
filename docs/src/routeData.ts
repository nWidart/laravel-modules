// Adds the Open Graph image of each page to its <head>. Starlight already writes
// og:title, og:description, og:url and twitter:card.
import { defineRouteMiddleware } from '@astrojs/starlight/route-data';
import { OG_HEIGHT, OG_WIDTH, ogImageFile } from './lib/og';

export const onRequest = defineRouteMiddleware((context) => {
	const route = context.locals.starlightRoute;
	// The 404 page is not in the docs collection, so it has no card. It uses the home page's.
	const isNotFound = route.entry.id === '404';
	const base = import.meta.env.BASE_URL.replace(/\/?$/, '/');
	const image = new URL(`${base}og/${ogImageFile(isNotFound ? 'index' : route.entry.id)}`, context.site ?? context.url).href;
	const alt = isNotFound ? 'Laravel Modules' : route.entry.data.title;

	route.head.push(
		{ tag: 'meta', attrs: { property: 'og:image', content: image } },
		{ tag: 'meta', attrs: { property: 'og:image:type', content: 'image/png' } },
		{ tag: 'meta', attrs: { property: 'og:image:width', content: String(OG_WIDTH) } },
		{ tag: 'meta', attrs: { property: 'og:image:height', content: String(OG_HEIGHT) } },
		{ tag: 'meta', attrs: { property: 'og:image:alt', content: alt } },
		{ tag: 'meta', attrs: { name: 'twitter:image', content: image } },
		{ tag: 'meta', attrs: { name: 'twitter:image:alt', content: alt } },
	);
});
