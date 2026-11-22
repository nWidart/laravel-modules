// Open Graph cards: one 1200x630 PNG per docs page, rendered at build time by astro-og-canvas.
//
// astro-og-canvas draws the logo, the page title and the description. The brand parts it
// cannot draw (wordmark, footer) go on a background image that this module renders with
// CanvasKit, the same engine astro-og-canvas uses. Colours follow `art/social-preview.html`.
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';
import type { CanvasKit, FontMgr } from 'canvaskit-wasm';

export const OG_WIDTH = 1200;
export const OG_HEIGHT = 630;

type RGB = [number, number, number];

const colors = {
	navy: [11, 18, 32], // #0B1220
	mint: [94, 242, 194], // #5EF2C2
	text: [238, 242, 248], // #EEF2F8
	muted: [180, 190, 208], // #B4BED0
	faint: [125, 138, 165], // #7D8AA5
	rule: [34, 48, 77], // #22304D
} satisfies Record<string, RGB>;

// Paths are relative to the docs root, where `astro build` runs. Latin subsets only: the
// docs are in English. Other scripts would render as missing-glyph boxes.
const fonts = {
	sans: './node_modules/@fontsource-variable/space-grotesk/files/space-grotesk-latin-wght-normal.woff2',
	mono: './node_modules/@fontsource-variable/jetbrains-mono/files/jetbrains-mono-latin-wght-normal.woff2',
};
// CanvasKit names a variable font after its default instance. The weight still applies.
const SANS = 'Space Grotesk Light';
const MONO = 'JetBrains Mono';

const LOGO_SVG = './src/assets/logo-dark.svg';
const OUT_DIR = './node_modules/.astro-og-canvas/brand';

const PADDING = 64;
const LOGO_SIZE = 56;
// Space kept free above the bottom padding for the footer and a gap above its rule.
// astro-og-canvas reserves it through `border` on the block-end side; the background
// paints over that border.
const FOOTER = 76;
// Distance from the footer rule to the bottom padding.
const RULE_OFFSET = 40;
// Height left for the title and description between the logo and the footer.
const TEXT_HEIGHT = OG_HEIGHT - PADDING - LOGO_SIZE - PADDING - (PADDING + FOOTER);
// astro-og-canvas lays the text out one padding narrower than the space between the margins.
const TEXT_WIDTH = OG_WIDTH - 3 * PADDING;

type FontStyle = {
	color: RGB;
	size: number;
	weight: 'Normal' | 'Medium' | 'SemiBold' | 'Bold';
	lineHeight: number;
	families: string[];
};

const description: FontStyle = { color: colors.muted, size: 28, weight: 'Normal', lineHeight: 1.35, families: [SANS] };
// Largest first. A title takes the first size that fits on two lines, else the last one.
const titleSizes = [64, 52];
const title = (size: number): FontStyle => ({ color: colors.text, size, weight: 'Bold', lineHeight: 1.05, families: [SANS] });

let canvasKit: Promise<{ ck: CanvasKit; fontMgr: FontMgr }> | undefined;

function loadCanvasKit() {
	canvasKit ??= (async () => {
		const require = createRequire(import.meta.url);
		const { default: init } = await import('canvaskit-wasm/full');
		const ck = await init({ locateFile: (file: string) => require.resolve(`canvaskit-wasm/bin/full/${file}`) });
		const font = async (file: string) => new Uint8Array(await readFile(file)).buffer;
		const fontMgr = ck.FontMgr.FromData(await font(fonts.sans), await font(fonts.mono));
		if (!fontMgr) throw new Error('Open Graph images: CanvasKit could not load the fonts.');
		return { ck, fontMgr };
	})();
	return canvasKit;
}

/** Renders the logo and the background once per build and returns their paths. */
async function renderBrand() {
	await mkdir(OUT_DIR, { recursive: true });
	const logo = path.join(OUT_DIR, 'logo.png');
	const background = path.join(OUT_DIR, 'background.png');

	const { default: sharp } = await import('sharp');
	await sharp(LOGO_SVG, { density: 300 }).resize(LOGO_SIZE, LOGO_SIZE).png().toFile(logo);

	const { ck, fontMgr } = await loadCanvasKit();
	const surface = ck.MakeSurface(OG_WIDTH, OG_HEIGHT);
	if (!surface) throw new Error('Open Graph images: CanvasKit could not create a surface.');
	const canvas = surface.getCanvas();
	canvas.clear(ck.Color(...colors.navy));

	const text = (content: string, family: string, size: number, weight: FontStyle['weight'], color: RGB) => {
		const builder = ck.ParagraphBuilder.Make(
			new ck.ParagraphStyle({
				textStyle: { color: ck.Color(...color), fontFamilies: [family], fontSize: size, fontStyle: { weight: ck.FontWeight[weight] } },
			}),
			fontMgr,
		);
		builder.addText(content);
		const paragraph = builder.build();
		paragraph.layout(OG_WIDTH);
		return paragraph;
	};

	// Wordmark, centred on the logo that astro-og-canvas draws at (PADDING, PADDING).
	const wordmark = text('Laravel Modules', SANS, 30, 'Bold', colors.text);
	canvas.drawParagraph(wordmark, PADDING + LOGO_SIZE + 20, PADDING + (LOGO_SIZE - wordmark.getHeight()) / 2);

	// Footer: a rule, the package name on the left, a mint "Documentation" on the right.
	const footerBottom = OG_HEIGHT - PADDING;
	const rule = new ck.Paint();
	rule.setColor(ck.Color(...colors.rule));
	canvas.drawRect(ck.XYWHRect(PADDING, footerBottom - RULE_OFFSET, OG_WIDTH - 2 * PADDING, 2), rule);
	const accent = new ck.Paint();
	accent.setColor(ck.Color(...colors.mint));
	canvas.drawRect(ck.XYWHRect(PADDING, footerBottom - RULE_OFFSET, 96, 2), accent);

	const pkg = text('nwidart/laravel-modules', MONO, 22, 'Normal', colors.faint);
	canvas.drawParagraph(pkg, PADDING, footerBottom - pkg.getHeight());
	const docs = text('Documentation', SANS, 22, 'Medium', colors.mint);
	canvas.drawParagraph(docs, OG_WIDTH - PADDING - docs.getMaxIntrinsicWidth(), footerBottom - docs.getHeight());

	const png = surface.makeImageSnapshot().encodeToBytes();
	surface.dispose();
	if (!png) throw new Error('Open Graph images: CanvasKit could not encode the background.');
	await writeFile(background, png);

	return { logo, background };
}

let brand: ReturnType<typeof renderBrand> | undefined;

/** Measures the paragraph astro-og-canvas builds for a title and a description. */
async function textHeight(titleText: string, titleStyle: FontStyle, descriptionText: string) {
	const { ck, fontMgr } = await loadCanvasKit();
	const style = (font: FontStyle) => ({
		color: ck.Color(...font.color),
		fontFamilies: font.families,
		fontSize: font.size,
		fontStyle: { weight: ck.FontWeight[font.weight] },
		heightMultiplier: font.lineHeight,
	});
	const builder = ck.ParagraphBuilder.Make(new ck.ParagraphStyle({ textStyle: style(titleStyle) }), fontMgr);
	builder.addText(titleText);
	if (descriptionText) {
		// Same spacer as astro-og-canvas between the title and the description.
		builder.pushStyle(new ck.TextStyle({ fontSize: PADDING / 3, heightMultiplier: 1 }));
		builder.addText('\n\n');
		builder.pushStyle(new ck.TextStyle(style(description)));
		builder.addText(descriptionText);
	}
	const paragraph = builder.build();
	paragraph.layout(TEXT_WIDTH);
	const result = { height: paragraph.getHeight(), lines: paragraph.getLineMetrics().length };
	paragraph.delete();
	return result;
}

/** Drops words from the end, with an ellipsis, until `fits` accepts the text. */
async function truncate(text: string, fits: (candidate: string) => Promise<boolean>) {
	if (await fits(text)) return text;
	const words = text.split(/\s+/);
	while (words.length > 1) {
		words.pop();
		const candidate = `${words.join(' ').replace(/[\s,.;:–—-]+$/, '')}…`;
		if (await fits(candidate)) return candidate;
	}
	return '';
}

/** Picks the title size and shortens the text so the card never overflows. */
async function fitText(titleText: string, descriptionText: string) {
	let titleStyle = title(titleSizes[titleSizes.length - 1]);
	for (const size of titleSizes) {
		if ((await textHeight(titleText, title(size), '')).lines <= 2) {
			titleStyle = title(size);
			break;
		}
	}
	const fittedTitle = await truncate(titleText, async (t) => {
		const { height, lines } = await textHeight(t, titleStyle, '');
		return lines <= 3 && height <= TEXT_HEIGHT;
	});
	const fittedDescription = await truncate(
		descriptionText,
		async (d) => (await textHeight(fittedTitle, titleStyle, d)).height <= TEXT_HEIGHT,
	);
	return { title: fittedTitle, description: fittedDescription, titleStyle };
}

/** Options for astro-og-canvas `getImageOptions`. */
export async function ogImageOptions(page: { title: string; description?: string }) {
	brand ??= renderBrand();
	const { logo, background } = await brand;
	const text = await fitText(page.title, page.description ?? '');

	return {
		title: text.title,
		description: text.description,
		padding: PADDING,
		bgGradient: [colors.navy],
		bgImage: { path: background, fit: 'none' as const, position: 'start' as const },
		logo: { path: logo, size: [LOGO_SIZE, LOGO_SIZE] as [number, number] },
		border: { side: 'block-end' as const, width: FOOTER, color: colors.navy },
		fonts: [fonts.sans],
		font: { title: text.titleStyle, description },
	};
}

/**
 * File name of a page's card under `/og/`: `<entry id>.png`. The home page is `index.png`:
 * its collection id is `index`, but Starlight's route data gives it the id ``.
 */
export function ogImageFile(entryId: string) {
	return `${entryId || 'index'}.png`;
}
