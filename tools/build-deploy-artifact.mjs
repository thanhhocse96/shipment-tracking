import {
	cpSync,
	existsSync,
	mkdirSync,
	rmSync,
} from 'node:fs';
import { relative, resolve, sep } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const artifactRoot = resolve(root, 'build');
const pluginTarget = resolve(artifactRoot, 'skvn-shipment-tracking');
const runtimeEntries = [
	'skvn-shipment-tracking.php',
	'includes',
	'assets',
	'languages',
];

function assertInsideRoot(targetPath) {
	const rel = relative(root, targetPath);
	if (rel === '' || rel.startsWith('..') || rel.includes(`..${sep}`)) {
		throw new Error(`Refusing to operate outside repo root: ${targetPath}`);
	}
}

assertInsideRoot(artifactRoot);
assertInsideRoot(pluginTarget);

const bootstrap = resolve(root, 'skvn-shipment-tracking.php');
if (!existsSync(bootstrap)) {
	throw new Error('Missing plugin bootstrap: skvn-shipment-tracking.php');
}

if (existsSync(artifactRoot)) {
	rmSync(artifactRoot, { recursive: true, force: true });
}

mkdirSync(pluginTarget, { recursive: true });

for (const entry of runtimeEntries) {
	const source = resolve(root, entry);
	if (!existsSync(source)) {
		continue;
	}

	cpSync(source, resolve(pluginTarget, entry), { recursive: true });
}

console.log(`Deploy artifact ready: ${pluginTarget}`);
