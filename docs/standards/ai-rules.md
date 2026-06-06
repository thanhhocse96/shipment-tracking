# AI Rules for SKVN Marine

## Project Names

- Plugin: `skvn-shipment-tracking`
- Block namespace: `skvn-shipment`
- Plugin PHP prefix: `skvn_shipment-tracking`
- CSS prefix: `skvn-`

## Non-Negotiable Rules

- Do not edit `wp-content/themes/generatepress/`.
- Do not create custom Gutenberg blocks inside the theme.
- Put custom blocks in `skvn-shipment-tracking`.
- Use `block.json` for custom block metadata.
- Use `theme.json` for design tokens.
- Use `editor.css` for Gutenberg preview.
- Use frontend CSS/JS for frontend animation.
- Do not add dependencies without writing rationale.
- Do not rename namespace/prefix without explicit approval.
- Do not use shortcodes for primary layout, except CF7 shortcode usage in quote form patterns.
- Do not overwrite manually entered image ALT text.
- Do not auto-generate captions in V1.

## AI Task Format

Every task should include:

1. Context
2. Goal
3. Files allowed to change
4. Files forbidden to change
5. Acceptance checklist
6. Tension/conflict section

## File Scope Rule

By default, AI should not modify more than 3–5 files per task.

## Tension Rule

If a requested change conflicts with the rules above, AI must record the tension instead of silently breaking the architecture.
