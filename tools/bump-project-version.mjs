#!/usr/bin/env node

import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const bootstrap = resolve(root, 'skvn-shipment-tracking.php');
const version = process.argv[2];

if (!version || !/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/.test(version)) {
	console.error('Usage: node tools/bump-project-version.mjs <semver>');
	console.error('Example: node tools/bump-project-version.mjs 0.1.0');
	process.exit(1);
}

let content;

try {
	content = readFileSync(bootstrap, 'utf8');
} catch {
	console.error('Missing plugin bootstrap: skvn-shipment-tracking.php');
	process.exit(1);
}

const updated = content.replace(
	/^ \* Version:\s*.+$/m,
	` * Version: ${version}`,
);

if (updated === content) {
	console.log(`Plugin version already set to ${version}, or no Version header was found.`);
	process.exit(0);
}

writeFileSync(bootstrap, updated, 'utf8');
console.log(`Updated skvn-shipment-tracking.php to version ${version}.`);
