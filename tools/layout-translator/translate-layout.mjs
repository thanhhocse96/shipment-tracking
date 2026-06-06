#!/usr/bin/env node

import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const HELP = `SKVN Layout Translator

Usage:
  node tools/layout-translator/translate-layout.mjs --input artifact.html
  node tools/layout-translator/translate-layout.mjs --input artifact.html --output translated.md
  node tools/layout-translator/translate-layout.mjs --input screenshot.png --kind screenshot
  node tools/layout-translator/translate-layout.mjs --input artifact.html --format json

Options:
  --input, -i    Input HTML/CSS artifact or screenshot path.
  --output, -o   Output file. Defaults to stdout.
  --format       markdown or json. Default: markdown.
  --kind         auto, html, or screenshot. Default: auto.
  --title        Optional section title used in generated class names.
  --help, -h     Show help.
`;

const BLOCK_TAGS = new Set([
	'section',
	'article',
	'main',
	'header',
	'footer',
	'aside',
	'div',
	'figure',
	'h1',
	'h2',
	'h3',
	'h4',
	'h5',
	'h6',
	'p',
	'a',
	'button',
	'img',
	'ul',
	'ol',
	'li',
]);

const HEADING_LEVELS = new Set(['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
const MOTION_HINT = /(animate|animation|motion|reveal|fade|slide|parallax|marquee|ticker|hover|loop|particle|wave|blob|float|magnetic)/i;
const DECORATIVE_HINT = /(decor|particle|wave|blob|shape|background|bg-|overlay|gradient|noise|pattern|canvas)/i;
const GRID_HINT = /(grid|columns|cols|row|cards|features|split|two-col|three-col|four-col)/i;
const CARD_HINT = /(card|tile|feature|stat|benefit|item)/i;
const CTA_HINT = /(btn|button|cta|quote|contact|learn|view|explore|request|submit)/i;
const PLACEHOLDER_IMAGE_URL = 'https://placehold.co/900x700/eef6ff/0a2a4a?text=Replace+Image';
const MAX_INLINE_IMAGE_SRC_LENGTH = 240;

function parseArgs(argv) {
	const args = {
		format: 'markdown',
		kind: 'auto',
	};

	for (let index = 0; index < argv.length; index += 1) {
		const arg = argv[index];
		const next = argv[index + 1];

		if (arg === '--help' || arg === '-h') {
			args.help = true;
		} else if (arg === '--input' || arg === '-i') {
			args.input = next;
			index += 1;
		} else if (arg === '--output' || arg === '-o') {
			args.output = next;
			index += 1;
		} else if (arg === '--format') {
			args.format = next;
			index += 1;
		} else if (arg === '--kind') {
			args.kind = next;
			index += 1;
		} else if (arg === '--title') {
			args.title = next;
			index += 1;
		} else {
			throw new Error(`Unknown argument: ${arg}`);
		}
	}

	return args;
}

function detectKind(inputPath, explicitKind, source) {
	if (explicitKind && explicitKind !== 'auto') {
		return explicitKind;
	}

	const ext = path.extname(inputPath).toLowerCase();
	if (['.png', '.jpg', '.jpeg', '.webp', '.gif', '.avif'].includes(ext)) {
		return 'screenshot';
	}

	if (/<[a-z][\s\S]*>/i.test(source)) {
		return 'html';
	}

	return 'html';
}

function slugify(value) {
	return String(value || 'artifact')
		.toLowerCase()
		.normalize('NFKD')
		.replace(/[\u0300-\u036f]/g, '')
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '')
		.slice(0, 48) || 'artifact';
}

function stripTags(value) {
	return String(value || '')
		.replace(/<script[\s\S]*?<\/script>/gi, ' ')
		.replace(/<style[\s\S]*?<\/style>/gi, ' ')
		.replace(/<[^>]+>/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();
}

function escapeHtml(value) {
	return String(value || '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

function normalizeImageSource(src) {
	const source = String(src || '').trim();
	if (!source || source.startsWith('data:') || source.length > MAX_INLINE_IMAGE_SRC_LENGTH) {
		return {
			src: PLACEHOLDER_IMAGE_URL,
			replaced: true,
			reason: !source ? 'empty image source' : 'inline/oversized image source',
		};
	}

	return {
		src: source,
		replaced: false,
		reason: '',
	};
}

function imageBlockLines(rawSrc, rawAlt, className) {
	const normalized = normalizeImageSource(rawSrc);
	const src = escapeHtml(normalized.src);
	const alt = escapeHtml(rawAlt || 'Replace with project image');

	return [
		`\t<!-- wp:image {"sizeSlug":"large","className":"${className}"} -->`,
		`\t<figure class="wp-block-image size-large ${className}"><img src="${src}" alt="${alt}"/></figure>`,
		`\t<!-- /wp:image -->`,
	];
}

function parseAttributes(raw) {
	const attrs = {};
	const attrPattern = /([:@\w-]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'=<>`]+)))?/g;
	let match;

	while ((match = attrPattern.exec(raw))) {
		const name = match[1].toLowerCase();
		attrs[name] = match[2] ?? match[3] ?? match[4] ?? '';
	}

	return attrs;
}

function parseElements(html) {
	const elements = [];
	const pairedTagPattern = /<(h[1-6]|p|a|button|li|figure|article|section|div)\b([^>]*)>([\s\S]*?)<\/\1>/gi;
	let match;

	while ((match = pairedTagPattern.exec(html))) {
		const tag = match[1].toLowerCase();
		const rawAttributes = match[2] || '';
		const inner = match[3] || '';
		const attrs = parseAttributes(rawAttributes);
		const text = stripTags(inner);
		const classes = (attrs.class || '').split(/\s+/).filter(Boolean);

		if (['section', 'article', 'div'].includes(tag) && text.length > 220) {
			continue;
		}

		elements.push({
			tag,
			attrs,
			classes,
			text,
			inner,
			index: match.index,
		});
	}

	const imagePattern = /<img\b([^>]*)\/?>/gi;
	while ((match = imagePattern.exec(html))) {
		const attrs = parseAttributes(match[1] || '');
		const classes = (attrs.class || '').split(/\s+/).filter(Boolean);
		elements.push({
			tag: 'img',
			attrs,
			classes,
			text: '',
			inner: '',
			index: match.index,
		});
	}

	return elements.sort((left, right) => left.index - right.index);
}

function getCssBlocks(html) {
	const blocks = [];
	const stylePattern = /<style[^>]*>([\s\S]*?)<\/style>/gi;
	let styleMatch;

	while ((styleMatch = stylePattern.exec(html))) {
		blocks.push(styleMatch[1]);
	}

	return blocks;
}

function getScriptBlocks(html) {
	const blocks = [];
	const scriptPattern = /<script\b[^>]*>[\s\S]*?<\/script>/gi;
	let scriptMatch;

	while ((scriptMatch = scriptPattern.exec(html))) {
		blocks.push(scriptMatch[0]);
	}

	return blocks;
}

function getAllClasses(html) {
	const classes = new Set();
	const classPattern = /class\s*=\s*(?:"([^"]*)"|'([^']*)')/gi;
	let match;

	while ((match = classPattern.exec(html))) {
		for (const className of (match[1] || match[2] || '').split(/\s+/)) {
			if (className.trim()) {
				classes.add(className.trim());
			}
		}
	}

	return [...classes];
}

function inferSectionSlug(inputPath, args, elements) {
	if (args.title) {
		return slugify(args.title);
	}

	const firstHeading = elements.find((element) => HEADING_LEVELS.has(element.tag) && element.text);
	if (firstHeading) {
		return slugify(firstHeading.text);
	}

	return slugify(path.basename(inputPath, path.extname(inputPath)));
}

function inferLayout(elements, classes) {
	const classText = classes.join(' ');
	const hasGrid = GRID_HINT.test(classText) || elements.some((element) => GRID_HINT.test(element.classes.join(' ')));
	const cardCount = elements.filter((element) => CARD_HINT.test(element.classes.join(' '))).length;
	const imageCount = elements.filter((element) => element.tag === 'img').length;
	const ctaCount = elements.filter((element) => ['a', 'button'].includes(element.tag) || CTA_HINT.test(element.classes.join(' '))).length;

	return {
		hasGrid,
		cardCount,
		imageCount,
		ctaCount,
	};
}

function pickTopLevelElements(elements) {
	const useful = elements.filter((element) => {
		if (element.tag === 'div' && !element.text && !element.attrs.src) {
			return false;
		}
		if (element.text && element.text.length > 220 && ['section', 'article', 'div'].includes(element.tag)) {
			return false;
		}
		return true;
	});

	const chosen = [];
	const seenText = new Set();

	for (const element of useful) {
		const key = `${element.tag}:${element.text || element.attrs.src || element.attrs.href || ''}`;
		if (seenText.has(key)) {
			continue;
		}

		chosen.push(element);
		seenText.add(key);
	}

	return chosen.slice(0, 32);
}

function toBlockMarkup(elements, sectionSlug, layout) {
	const sectionClass = `skvn-translated skvn-translated--${sectionSlug}`;
	const lines = [
		`<!-- wp:group {"align":"full","className":"${sectionClass}","layout":{"type":"constrained"}} -->`,
		`<div class="wp-block-group alignfull ${sectionClass}">`,
	];

	if (layout.hasGrid) {
		lines.push(`\t<!-- wp:group {"className":"skvn-translated__grid","layout":{"type":"default"}} -->`);
		lines.push(`\t<div class="wp-block-group skvn-translated__grid">`);
	}

	for (const element of pickTopLevelElements(elements)) {
		lines.push(...elementToBlock(element));
	}

	if (layout.hasGrid) {
		lines.push('\t</div>');
		lines.push('\t<!-- /wp:group -->');
	}

	lines.push('</div>');
	lines.push('<!-- /wp:group -->');

	return lines.join('\n');
}

function elementToBlock(element) {
	const text = escapeHtml(element.text);
	const className = classForElement(element);

	if (HEADING_LEVELS.has(element.tag)) {
		const level = Number(element.tag.slice(1));
		return [
			`\t<!-- wp:heading {"level":${level},"className":"${className}"} -->`,
			`\t<h${level} class="wp-block-heading ${className}">${text || 'Editable heading'}</h${level}>`,
			`\t<!-- /wp:heading -->`,
		];
	}

	if (element.tag === 'p' || element.tag === 'li') {
		return [
			`\t<!-- wp:paragraph {"className":"${className}"} -->`,
			`\t<p class="${className}">${text || 'Editable paragraph content.'}</p>`,
			`\t<!-- /wp:paragraph -->`,
		];
	}

	if (element.tag === 'a' || element.tag === 'button') {
		const href = element.tag === 'a' ? escapeHtml(element.attrs.href || '#') : '#';
		return [
			`\t<!-- wp:buttons {"className":"skvn-translated__actions"} -->`,
			`\t<div class="wp-block-buttons skvn-translated__actions">`,
			`\t\t<!-- wp:button {"className":"${className}"} -->`,
			`\t\t<div class="wp-block-button ${className}"><a class="wp-block-button__link wp-element-button" href="${href}">${text || 'Editable call to action'}</a></div>`,
			`\t\t<!-- /wp:button -->`,
			`\t</div>`,
			`\t<!-- /wp:buttons -->`,
		];
	}

	if (element.tag === 'img') {
		return imageBlockLines(element.attrs.src, element.attrs.alt, className);
	}

	if (element.tag === 'figure') {
		const imageMatch = element.inner.match(/<img\b([^>]*)>/i);
		if (imageMatch) {
			const attrs = parseAttributes(imageMatch[1]);
			return imageBlockLines(attrs.src, attrs.alt, className);
		}
	}

	if (element.text) {
		return [
			`\t<!-- wp:group {"className":"${className}","layout":{"type":"default"}} -->`,
			`\t<div class="wp-block-group ${className}">`,
			`\t\t<!-- wp:paragraph -->`,
			`\t\t<p>${text}</p>`,
			`\t\t<!-- /wp:paragraph -->`,
			`\t</div>`,
			`\t<!-- /wp:group -->`,
		];
	}

	return [];
}

function classForElement(element) {
	if (HEADING_LEVELS.has(element.tag)) {
		return 'skvn-translated__heading';
	}

	if (element.tag === 'p' || element.tag === 'li') {
		return 'skvn-translated__text';
	}

	if (element.tag === 'a' || element.tag === 'button') {
		return CTA_HINT.test(element.classes.join(' ') + element.text)
			? 'skvn-button skvn-button--primary'
			: 'skvn-button skvn-button--secondary';
	}

	if (element.tag === 'img' || element.tag === 'figure') {
		return 'skvn-translated__media';
	}

	if (CARD_HINT.test(element.classes.join(' '))) {
		return 'skvn-translated__card';
	}

	return 'skvn-translated__item';
}

function requiredClasses(sectionSlug, layout, elements, classes) {
	const required = new Set([
		'skvn-translated',
		`skvn-translated--${sectionSlug}`,
		'skvn-translated__heading',
		'skvn-translated__text',
		'skvn-button',
		'skvn-button--primary',
	]);

	if (layout.hasGrid) {
		required.add('skvn-translated__grid');
	}

	if (layout.cardCount > 0 || elements.some((element) => CARD_HINT.test(element.classes.join(' ')))) {
		required.add('skvn-translated__card');
	}

	if (layout.imageCount > 0) {
		required.add('skvn-translated__media');
	}

	if (layout.ctaCount > 1) {
		required.add('skvn-button--secondary');
		required.add('skvn-translated__actions');
	}

	if (classes.some((className) => MOTION_HINT.test(className))) {
		required.add('skvn-motion-reveal');
	}

	return [...required];
}

function themeCssContract(sectionSlug, layout) {
	const contract = [
		`Section wrapper: .skvn-translated--${sectionSlug} should be full-width safe and use the SKVN constrained inner width.`,
		'Spacing: use theme spacing tokens; preserve mobile rhythm before matching desktop decoration.',
		'Colors: keep blue-first SKVN direction; use mint as support accent and gold only as limited premium cue.',
		'Buttons: map to existing skvn-button visual rules where possible.',
		'Editor: content must remain visible and editable without depending on frontend JavaScript.',
	];

	if (layout.hasGrid) {
		contract.push('Grid: implement responsive grid/columns in theme CSS; stack to one column on narrow screens.');
	}

	if (layout.cardCount > 0) {
		contract.push('Cards: keep border radius at 8px or less unless the design system is explicitly changed.');
	}

	if (layout.imageCount > 0) {
		contract.push('Media: images stay editable core Image blocks; theme CSS controls aspect ratio, object-fit, and safe stacking.');
	}

	return contract;
}

function animationContract(classes) {
	if (!classes.some((className) => MOTION_HINT.test(className))) {
		return [
			{
				element: '.skvn-translated',
				trigger: 'none by default',
				initial_state: 'static visible content',
				final_state: 'static visible content',
				duration: '0ms',
				easing: 'n/a',
				stagger_delay: 'none',
				reduced_motion_fallback: 'same static state',
				editor_behavior: 'static',
			},
		];
	}

	return [
		{
			element: '.skvn-motion-reveal or translated elements carrying reveal intent',
			trigger: 'scroll via shared animation runtime',
			initial_state: 'visible in editor; frontend may start translated only after runtime marks element ready',
			final_state: 'opacity 1, transform none',
			duration: '500ms',
			easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
			stagger_delay: '80ms between sibling cards when useful',
			reduced_motion_fallback: 'render final static state; no transform animation',
			editor_behavior: 'static or simplified; never hide editable content with opacity 0',
		},
	];
}

function assetsNeeded(elements, kind, inputPath) {
	if (kind === 'screenshot') {
		return [
			{
				type: 'source screenshot',
				source: inputPath,
				ownership: 'reference only; not pasted into Gutenberg as primary text/content',
			},
			{
				type: 'editable media',
				source: 'to be identified manually from screenshot',
				ownership: 'core Image blocks for content images; theme CSS for decorative background layers',
			},
		];
	}

	const assets = [];
	for (const element of elements.filter((item) => item.tag === 'img')) {
		const normalized = normalizeImageSource(element.attrs.src);
		assets.push({
			type: 'image',
			source: normalized.replaced ? normalized.src : element.attrs.src || '',
			original_source_status: normalized.replaced ? normalized.reason : 'kept',
			alt: element.attrs.alt || '',
			ownership: DECORATIVE_HINT.test(element.classes.join(' ')) ? 'theme decorative layer' : 'core Image block',
		});
	}

	if (!assets.length) {
		assets.push({
			type: 'none detected',
			source: '',
			ownership: 'n/a',
		});
	}

	return assets;
}

function notTranslated(html, classes, cssBlocks, scriptBlocks) {
	const items = [];

	if (cssBlocks.length) {
		items.push('Inline <style> blocks: move to theme CSS contract.');
	}

	if (scriptBlocks.length) {
		items.push('Inline <script> blocks: do not paste into content; route motion/interaction through shared runtime or plugin block later.');
	}

	for (const className of classes) {
		if (DECORATIVE_HINT.test(className)) {
			items.push(`Decorative class "${className}": implement as theme-controlled background/decorative layer if still needed.`);
		}
	}

	if (/<canvas\b/i.test(html)) {
		items.push('Canvas element: do not translate primary content to canvas; evaluate as theme-controlled decorative animation only.');
	}

	if (/<img\b[^>]*\bsrc=(["'])data:/i.test(html)) {
		items.push('Inline data URI images: replaced with paste-safe placeholder image; upload real media in Gutenberg.');
	}

	if (!items.length) {
		items.push('No forbidden inline CSS/JS detected. Review decorative layers manually before implementation.');
	}

	return [...new Set(items)];
}

function risks(layout, classes, cssBlocks, scriptBlocks) {
	const result = [
		'Generated markup is a first-pass translation; inspect in Gutenberg before saving as a final pattern.',
		'Original CSS specificity and layout details are intentionally not copied into content.',
		'Mobile stacking must be visually checked.',
		'Accessibility labels and heading hierarchy require human review.',
	];

	if (layout.ctaCount > 0) {
		result.push('CTA buttons must remain visible on mobile and not rely on hover.');
	}

	if (layout.imageCount > 0) {
		result.push('Image ALT text must remain editable and must not overwrite manual ALT.');
	}

	if (classes.some((className) => MOTION_HINT.test(className))) {
		result.push('Motion intent detected; implementation must use shared runtime and reduced-motion fallback.');
	}

	if (cssBlocks.length || scriptBlocks.length) {
		result.push('Inline CSS/JS was detected and excluded from Gutenberg markup.');
	}

	return result;
}

function translateHtml(inputPath, source, args) {
	const elements = parseElements(source);
	const classes = getAllClasses(source);
	const cssBlocks = getCssBlocks(source);
	const scriptBlocks = getScriptBlocks(source);
	const sectionSlug = inferSectionSlug(inputPath, args, elements);
	const layout = inferLayout(elements, classes);

	return {
		source: inputPath,
		kind: 'html',
		gutenberg_markup: toBlockMarkup(elements, sectionSlug, layout),
		required_classes: requiredClasses(sectionSlug, layout, elements, classes),
		theme_css_contract: themeCssContract(sectionSlug, layout),
		animation_contract: animationContract(classes),
		assets_needed: assetsNeeded(elements, 'html', inputPath),
		not_translated: notTranslated(source, classes, cssBlocks, scriptBlocks),
		risks: risks(layout, classes, cssBlocks, scriptBlocks),
	};
}

function translateScreenshot(inputPath, args) {
	const sectionSlug = slugify(args.title || path.basename(inputPath, path.extname(inputPath)));

	return {
		source: inputPath,
		kind: 'screenshot',
		gutenberg_markup: [
			`<!-- wp:group {"align":"full","className":"skvn-translated skvn-translated--${sectionSlug}","layout":{"type":"constrained"}} -->`,
			`<div class="wp-block-group alignfull skvn-translated skvn-translated--${sectionSlug}">`,
			'\t<!-- wp:heading {"level":2,"className":"skvn-translated__heading"} -->',
			'\t<h2 class="wp-block-heading skvn-translated__heading">Editable heading from screenshot</h2>',
			'\t<!-- /wp:heading -->',
			'',
			'\t<!-- wp:paragraph {"className":"skvn-translated__text"} -->',
			'\t<p class="skvn-translated__text">Editable copy from screenshot.</p>',
			'\t<!-- /wp:paragraph -->',
			'</div>',
			'<!-- /wp:group -->',
		].join('\n'),
		required_classes: [
			'skvn-translated',
			`skvn-translated--${sectionSlug}`,
			'skvn-translated__heading',
			'skvn-translated__text',
		],
		theme_css_contract: [
			'Screenshot cannot be parsed automatically. Use it as visual reference only.',
			'Manually identify core Gutenberg editable content vs theme-controlled decorative layers.',
			'Keep text and CTA content editable; do not turn screenshot text into images.',
		],
		animation_contract: animationContract([]),
		assets_needed: assetsNeeded([], 'screenshot', inputPath),
		not_translated: [
			'Screenshot pixels are not pasted into Gutenberg as primary content.',
			'Decorative layers must be rebuilt through theme CSS or shared animation runtime after review.',
		],
		risks: [
			'Screenshot translation needs human semantic review.',
			'Responsive behavior cannot be inferred from a single static image.',
			'Accessibility semantics must be reconstructed manually.',
		],
	};
}

function toMarkdown(result) {
	return [
		'# Layout Translator Output',
		'',
		`Source: \`${result.source}\``,
		`Kind: \`${result.kind}\``,
		'',
		'## gutenberg_markup',
		'',
		'```html',
		result.gutenberg_markup,
		'```',
		'',
		'## required_classes',
		'',
		...result.required_classes.map((item) => `- \`${item}\``),
		'',
		'## theme_css_contract',
		'',
		...result.theme_css_contract.map((item) => `- ${item}`),
		'',
		'## animation_contract',
		'',
		'```json',
		JSON.stringify(result.animation_contract, null, 2),
		'```',
		'',
		'## assets_needed',
		'',
		'```json',
		JSON.stringify(result.assets_needed, null, 2),
		'```',
		'',
		'## not_translated',
		'',
		...result.not_translated.map((item) => `- ${item}`),
		'',
		'## risks',
		'',
		...result.risks.map((item) => `- ${item}`),
		'',
	].join('\n');
}

async function main() {
	const args = parseArgs(process.argv.slice(2));

	if (args.help) {
		process.stdout.write(HELP);
		return;
	}

	if (!args.input) {
		throw new Error('Missing --input. Use --help for usage.');
	}

	if (!['markdown', 'json'].includes(args.format)) {
		throw new Error('--format must be markdown or json.');
	}

	if (!['auto', 'html', 'screenshot'].includes(args.kind)) {
		throw new Error('--kind must be auto, html, or screenshot.');
	}

	const source = await readFile(args.input, 'utf8').catch((error) => {
		if (args.kind === 'screenshot' || detectKind(args.input, args.kind, '') === 'screenshot') {
			return '';
		}
		throw error;
	});

	const kind = detectKind(args.input, args.kind, source);
	const result = kind === 'screenshot'
		? translateScreenshot(args.input, args)
		: translateHtml(args.input, source, args);

	const output = args.format === 'json'
		? `${JSON.stringify(result, null, 2)}\n`
		: toMarkdown(result);

	if (args.output) {
		await writeFile(args.output, output, 'utf8');
		return;
	}

	process.stdout.write(output);
}

main().catch((error) => {
	process.stderr.write(`ERROR: ${error.message}\n`);
	process.exitCode = 1;
});
