# AI Rules for SKVN Shipment Tracking

## Project Identity

- Plugin: `skvn-shipment-tracking`
- CPT: `skvn_shipment`
- PHP prefix: `skvn_tracking_`
- CSS prefix: `skvn-tracking-`
- Text domain: `skvn-shipment-tracking`
- Upload root: `wp-content/uploads/shipments/`
- PHP compatibility: 8.0
- Frontend: Vanilla TypeScript, no React

## Source Priority

1. Human-provided shipment tracking fact/design document
2. `AGENTS.md` and relevant `AGENTS_*.md`
3. `.context/` current milestone and tension state
4. `docs/` and inherited utilities

Copied SKVN Marine files are reference material only until adapted to this
project. They cannot change plugin identity, prefix, ownership, or scope.

## Non-Negotiable Rules

- Do not edit GeneratePress or the `skvn-marine` theme.
- Follow the theme visual contract without moving theme tokens into the plugin.
- Do not touch WooCommerce data or external plugin internals.
- Thumbpress owns WebP conversion; this plugin only hooks into its pipeline.
- Generate exactly one `blurred-thumb.webp` per batch.
- Never expose original shipment images without a valid token.
- Render the staff share button server-side; customers must not receive it in DOM.
- Keep `/tracking/` indexable and every other `/tracking/*` surface noindex.
- Do not expose client names on public surfaces.
- Do not implement Quote Flow, `/contact/` content, n8n, or archive lifecycle here.
- Sanitize input, escape output, and verify nonces for every form.
- Do not overwrite manually entered image captions/ALT text.
- Do not implement a later milestone without human promotion.
- Do not archive or resolve tension entries without human approval.
- Do not add React, JSX, React DOM, or a UI framework runtime.
- Keep authorization and private-data decisions in PHP, never TypeScript.
- Tailwind MVP uses existing WindPress; do not add another Tailwind dependency.
- Public templates and REST endpoints may only read `_skvn_public_snapshot`.
- Never fetch private meta in a public request with the intent to redact later.
- Token links do not expire in MVP but must support staff-triggered rotation.
- Uninstall keeps operational data by default.

## Task Scope

Every implementation task should identify:

1. Current milestone and acceptance criteria
2. Files allowed to change
3. Relevant `AGENTS_*.md` references
4. Security and access-control implications
5. Verification commands
6. New tension, if the requirement remains undecided

Prefer 3-5 changed files per focused task. Cross-cutting documentation/tooling
sync may exceed that when the human explicitly requests repository-wide cleanup.

## Verification

Follow the verification gate in `AGENTS.md`. Any failing consistency, PHP
syntax, or prefix check must be fixed before commit.
