// One Open Graph image per docs page, at /og/<entry id>.png. `src/routeData.ts` links them.
import { getCollection } from 'astro:content';
import { OGImageRoute } from 'astro-og-canvas';
import { ogImageFile, ogImageOptions } from '../../lib/og';

// Same filter as Starlight: draft pages are built only outside production.
const entries = await getCollection('docs', ({ data }) => import.meta.env.MODE !== 'production' || !data.draft);
const pages = Object.fromEntries(entries.map(({ id, data }) => [id, data]));

export const { getStaticPaths, GET } = await OGImageRoute({
	pages,
	getSlug: (id) => ogImageFile(id),
	getImageOptions: (_id, page: (typeof pages)[string]) => ogImageOptions(page),
});
