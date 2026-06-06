# Site Branding Guideline

Status: working standard.
Owner: `skvn-marine` theme for visual system; `skvn-marine-blocks` plugin for plugin-specific admin/editor assets only.

## Purpose

SKVN Marine can be customized, but customization must be managed through known files. Do not scatter brand values across Gutenberg content, inline CSS, custom HTML, or plugin logic.

This document defines where brand variables, visual assets, and editor-facing branding decisions live.

Before coding any branding change, update or verify this document first. If a new token, class family, screenshot/icon asset, editor style rule, or customization control is introduced, this document must state which file owns it.

## Brand Layers

| Layer | Owner | Editable by | Rule |
|---|---|---|---|
| Design tokens | Theme | Developer | Source of truth for colors, spacing, radius, shadow, layout widths |
| Gutenberg palette/layout presets | Theme | Developer | Must stay aligned with CSS tokens |
| Component and pattern styling | Theme | Developer | Use `skvn-*` classes |
| Page copy and page media | WordPress content | Editor | Editable Gutenberg content; no raw `<style>` or `<script>` |
| Plugin icon/banner | Plugin | Developer | Plugin identity asset only; not the site visual system |

## Identity Boundary

- `SKVN Marine` is the project/theme/plugin brand.
- `Minh Hai Fishery` is the current site/company content identity.
- Do not hardcode client/company names into theme logic unless it is a deliberate pattern or translatable string.
- Site title/logo should come from WordPress/GeneratePress site identity settings when possible.

## File Registry

| Concern | Primary file | Secondary file | Notes |
|---|---|---|---|
| Brand profile contract | `docs/decisions/brand-profile-theme-tokens.md` | `.context/planning/001_VERSION_0_7_0_BRAND_PROFILE_PLANNING.md` | Source of truth for 0.7 semantic profile names and sync rules |
| CSS color tokens | `wp-content/themes/skvn-marine/style.css` `:root` | `wp-content/themes/skvn-marine/theme.json` palette | Keep names and values aligned |
| Layout widths | `wp-content/themes/skvn-marine/style.css` `--skvn-content-width`, `--skvn-wide-width` | `wp-content/themes/skvn-marine/theme.json` layout | Gutenberg editor and frontend must match |
| Typography base | `wp-content/themes/skvn-marine/style.css` body/headings | `wp-content/themes/skvn-marine/theme.json` typography | System font stack in V1 |
| Radius/shadow | `wp-content/themes/skvn-marine/style.css` `--skvn-radius-card`, `--skvn-shadow-card` | None | Use tokens before component overrides |
| Buttons | `wp-content/themes/skvn-marine/style.css` `.skvn-button*` and block button selectors | Patterns using `core/buttons` | Primary CTA remains blue |
| Sections/cards | `wp-content/themes/skvn-marine/style.css` `.skvn-section*`, `.skvn-card*`, translated classes | `wp-content/themes/skvn-marine/patterns/*.php` | Use `skvn-*` classes |
| HTML-2-Gutenberg translated layouts | `wp-content/themes/skvn-marine/style.css` `.skvn-translated-*`, `.skvn-kpi-strip*`, `.skvn-placeholder-media` | `wp-content/themes/skvn-marine/patterns/artifact-exporter-test.php` | Reuse these implemented families before inventing new translated layout classes |
| Block styles | `wp-content/themes/skvn-marine/inc/block-styles.php` | `style.css` | Register editor-visible styles here |
| Editor parity | `wp-content/themes/skvn-marine/inc/setup.php` | `style.css`, future editor CSS if split | `add_editor_style()` must load required visual CSS |
| Theme screenshot | `wp-content/themes/skvn-marine/screenshot.png` | None | Theme marketplace/admin preview image |
| Shared motion | `wp-content/themes/skvn-marine/assets/js/animations.js` | `style.css` motion classes | Must respect `prefers-reduced-motion` |
| Page display controls UI | `wp-content/themes/skvn-marine/assets/js/page-display-controls.js` | `assets/css/page-display-controls-editor.css` | Editor/admin control surface |
| Page display meta | Theme PHP files that register/save `_skvn_*` meta | None | Keep page chrome controls in theme |
| Media ALT automation | `wp-content/themes/skvn-marine/inc/media.php` | None | Fill ALT only when empty |
| Woo visual override | `wp-content/themes/skvn-marine/inc/woocommerce.php` | `style.css` | Visual only; no product data ownership |
| WindPress integration | `wp-content/themes/skvn-marine/inc/windpress.php` | None | Tailwind is not the production brand source of truth |
| Plugin icon | `wp-content/plugins/skvn-marine-blocks/assets/icon-256x256.png` | None | Plugin identity only |
| Plugin banner | `wp-content/plugins/skvn-marine-blocks/assets/banner-772x250.png` | None | Plugin identity only |

## Customization Order

0. Check `docs/decisions/brand-profile-theme-tokens.md` before changing profile-level tokens.
1. Update `theme.json` presets and `style.css` `:root` tokens together.
2. Update component CSS in `style.css`.
3. Update or add patterns in `wp-content/themes/skvn-marine/patterns/`.
4. Update editor parity if frontend and editor drift.
5. Update screenshots/icons only after the visual direction is approved.

## Brand Profile Contract

V1 / 0.7.0 introduces semantic profile aliases for artifact mapping and editor/frontend parity.

Canonical profile roles:

```text
primary
accent
dark/navy
surface
soft surface
text
muted text
border
CTA
card radius
card shadow
section spacing
content width
wide width
```

The detailed token names, current value mapping, `theme.json` sync pairs, and external references live in `docs/decisions/brand-profile-theme-tokens.md`.

## Visual Rules

- Blue-first system for V1.
- Primary CTA remains blue.
- Mint supports freshness, hygiene, cold-chain, factory/process, trust, and form support surfaces.
- Gold is limited to certification, premium species, buyer-value highlights, and top-grade labels.
- Do not use gold as a dominant background.
- Text content must stay editable Gutenberg content, not baked into images or canvas.
- Real seafood/factory/export imagery is preferred over decorative-only media when the user needs to inspect the subject.

## HTML-2-Gutenberg Impact

HTML-2-Gutenberg may accept Tailwind-ready artifacts, but Tailwind classes are input hints only. The final WordPress output must use Gutenberg block markup and `skvn-*` classes controlled by the theme.

HTML-2-Gutenberg brand report output must include:

```text
brand_source_scan
brand_mapping
brand_mismatch
token_changes_needed
```

- `brand_source_scan` records artifact colors, Tailwind color utilities, fonts, radius, shadows, spacing, and CTA emphasis.
- `brand_mapping` maps those cues to existing SKVN theme tokens, `theme.json` presets, block styles, or `skvn-*` classes.
- `brand_mismatch` records visual choices that conflict with SKVN direction.
- `token_changes_needed` records token/class/preset gaps needed for parity without raw CSS in content.

Artifact colors must map back to SKVN theme tokens. Prototype hex/rgb/hsl values and Tailwind color utilities must not be preserved as the production contract. If a token is missing, the translator records `token_changes_needed`; it does not add raw color declarations, inline styles, or utility-heavy prototype color classes to Gutenberg content.

V1 / 0.5.1 documents this contract only. Theme-owned brand profile and token implementation remain deferred to V1 / 0.7.0.

When the importer outputs a new `skvn-*` class, record the class contract in the workflow output and implement its styling in the theme files listed above.

For SKVN Marine translated artifacts, prefer implemented CSS families first:

- `skvn-translated-page`
- `skvn-translated-hero*`
- `skvn-kpi-strip*`
- `skvn-translated-split*`
- `skvn-placeholder-media`
- `skvn-section__eyebrow`
- `skvn-section__heading`
- `skvn-section__lead`
- `skvn-card`
- `skvn-button*`
- `is-style-skvn-primary`

Do not use new layout-critical families such as `skvn-card-grid`, `skvn-logo-grid`, `skvn-testimonial-grid`, or `skvn-hero__*` unless the same task implements theme CSS or the translator lists them under `missing_theme_classes`.

## Guardrails

- Update or verify this guideline before coding branding changes.
- Do not place raw `<style>` or `<script>` in Gutenberg content.
- Do not preserve prototype/Tailwind colors as production content classes or inline styles.
- Do not edit GeneratePress parent files.
- Do not move brand tokens into the plugin.
- Do not create dynamic Tailwind class contracts that the theme cannot audit.
- Do not rename `skvn-*`, `skvn_marine_`, `skvn_marine_blocks_`, or `skvn-marine`.
- Keep frontend and editor visual behavior close enough for safe editing.

## Review Checklist

- [ ] `style.css` tokens and `theme.json` presets match.
- [ ] New visual classes use `skvn-*`.
- [ ] Content remains editable in Gutenberg.
- [ ] Editor view does not hide content with `opacity: 0`.
- [ ] Mobile spacing and text wrapping checked.
- [ ] Contrast checked for CTA, text, and badges.
- [ ] Theme/plugin assets are updated only in their owned folders.
