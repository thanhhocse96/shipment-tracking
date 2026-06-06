#!/usr/bin/env node
import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const version = process.argv[2];

if (!version || !/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/.test(version)) {
	console.error('Usage: node tools/bump-project-version.mjs <semver>');
	console.error('Example: node tools/bump-project-version.mjs 0.9.0');
	process.exit(1);
}

const touched = [];

function writeIfChanged(filePath, content) {
	const current = readFileSync(filePath, 'utf8');
	if (current === content) {
		return;
	}
	writeFileSync(filePath, content, 'utf8');
	touched.push(filePath);
}

function updateTextFile(relativePath, replacers) {
	const filePath = join(root, relativePath);
	let content = readFileSync(filePath, 'utf8');
	for (const [pattern, replacement] of replacers) {
		content = content.replace(pattern, replacement);
	}
	writeIfChanged(filePath, content);
}

function updateJsonFile(relativePath, updater) {
	const filePath = join(root, relativePath);
	const data = JSON.parse(readFileSync(filePath, 'utf8'));
	updater(data);
	writeIfChanged(filePath, `${JSON.stringify(data, null, 2)}\n`);
}

function updateBlockJsonFiles(baseRelativePath) {
	const basePath = join(root, baseRelativePath);
	if (!existsSync(basePath)) {
		return;
	}
	for (const entry of readdirSync(basePath, { withFileTypes: true })) {
		if (!entry.isDirectory()) {
			continue;
		}
		const blockPath = join(basePath, entry.name, 'block.json');
		if (!existsSync(blockPath)) {
			continue;
		}
		const relativePath = blockPath.slice(root.length + 1).replaceAll('\\', '/');
		updateJsonFile(relativePath, (data) => {
			data.version = version;
		});
	}
}

updateTextFile('wp-content/themes/skvn-marine/style.css', [
	[/^Version:\s*.+$/m, `Version: ${version}`],
]);

updateTextFile('wp-content/plugins/skvn-marine-blocks/skvn-marine-blocks.php', [
	[/^ \* Version:\s*.+$/m, ` * Version: ${version}`],
]);

updateJsonFile('wp-content/plugins/skvn-marine-blocks/package.json', (data) => {
	data.version = version;
});

updateJsonFile('wp-content/plugins/skvn-marine-blocks/package-lock.json', (data) => {
	data.version = version;
	if (data.packages && data.packages['']) {
		data.packages[''].version = version;
	}
});

updateBlockJsonFiles('wp-content/plugins/skvn-marine-blocks/src');
updateBlockJsonFiles('wp-content/plugins/skvn-marine-blocks/build');

if (touched.length === 0) {
	console.log(`Project versions already set to ${version}.`);
} else {
	console.log(`Updated project versions to ${version}:`);
	for (const filePath of touched) {
		console.log(`- ${filePath.slice(root.length + 1).replaceAll('\\', '/')}`);
	}
}
