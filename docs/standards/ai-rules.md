# AI Rules for SKVN Shipment Tracking

## Project Identity

- Plugin: `skvn-shipment-tracking`
- CPT: `skvn_shipment`
- PHP prefix: `skvn_tracking_`
- CSS prefix: `skvn-tracking-`
- Text domain: `skvn-shipment-tracking`
- Upload root: `wp-content/uploads/shipments/`
- PHP compatibility: 8.0

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
