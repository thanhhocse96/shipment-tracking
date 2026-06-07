# Shipment Tracking Deploy Artifact Workflow

## Purpose

Package only the `skvn-shipment-tracking` plugin for installation in WordPress.
The theme and external dependencies are installed separately.

## Local Shell

The project build environment is WSL Debian. Run Node, npm, PHP, and packaging
commands inside WSL rather than relying on Windows-installed toolchains.

From PowerShell:

```powershell
wsl -d Debian -- bash -lc ". /home/shinkuro/.nvm/nvm.sh && nvm use 20 >/dev/null && cd /mnt/d/Github/shipment-tracking && npm ci && npm run package"
```

The `nvm` initialization is required because Node is not installed globally in
the Debian distribution.

## Build

From the repository root:

```bash
. /home/shinkuro/.nvm/nvm.sh
nvm use 20
npm ci
npm run build
npm run package:artifact
```

Output:

```text
build/skvn-shipment-tracking/
build/skvn-shipment-tracking.zip
```

The zip must contain one top-level `skvn-shipment-tracking/` directory.

## Runtime Contents

Include runtime files when they exist:

```text
skvn-shipment-tracking.php
includes/
assets/
languages/
```

Compiled Vanilla TypeScript JavaScript belongs under `assets/` and is included.
TypeScript source, source maps, dependencies, and build caches are
development-only unless a release contract explicitly says otherwise.

`npm run package` performs the TypeScript build, creates the plugin-only
artifact directory, and then creates the zip. Shared hosting does not need
Node.js or TypeScript.

Exclude development-only material:

```text
.agents/
.context/
.git/
.local/
docs/
tools/
node_modules/
tests/
build/
src/
*.map
```

If a new PHP `require` or `include` path is added, update the artifact builder
in the same task and verify the referenced file exists in the zip.

## Target Requirements

- WordPress
- GeneratePress parent theme
- `skvn-marine` child theme
- Thumbpress
- PHP 8.0+

CF7, CFDB7, Rank Math, Polylang, and WooCommerce remain site-managed
dependencies. They are never bundled into this plugin artifact.

## Install

```bash
wp plugin install build/skvn-shipment-tracking.zip --force
wp plugin activate skvn-shipment-tracking
```

Use the runtime path and `--allow-root` settings from `.local/ENVIRONMENT.md`
when running locally.

## Audit

```bash
test -f build/skvn-shipment-tracking/skvn-shipment-tracking.php
unzip -l build/skvn-shipment-tracking.zip
```

Confirm:

- No original shipment media is bundled.
- No `.local/ENVIRONMENT.md` or credentials are bundled.
- No theme or external plugin source is bundled.
- Plugin bootstrap and every runtime include are present.
- `assets/js/admin-media-tabs.js` is present.
- `src/`, `node_modules/`, `package.json`, and source maps are absent.
