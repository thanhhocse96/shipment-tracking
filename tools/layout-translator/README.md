# SKVN Layout Translator CLI

This is an inherited development utility, not shipment tracking runtime code.
For tracking artifacts, generated generic `skvn-*` classes are proposals only;
production plugin classes must follow the `skvn-tracking-` contract.

Dev-only CLI for translating AI-made HTML/CSS artifacts or screenshots into SKVN Marine Gutenberg pattern components.

This tool does not modify WordPress runtime content. It reads an artifact and outputs a translation report for review.

## Usage

```bash
node tools/layout-translator/translate-layout.mjs --input docs/artifacts/example.html
```

Write Markdown output:

```bash
node tools/layout-translator/translate-layout.mjs --input docs/artifacts/example.html --output docs/artifacts/example.translation.md
```

Write JSON output:

```bash
node tools/layout-translator/translate-layout.mjs --input docs/artifacts/example.html --format json --output docs/artifacts/example.translation.json
```

Screenshot placeholder mode:

```bash
node tools/layout-translator/translate-layout.mjs --input reference.png --kind screenshot --title "Hero Reference"
```

## Output

The CLI emits:

- `gutenberg_markup`
- `required_classes`
- `theme_css_contract`
- `animation_contract`
- `assets_needed`
- `not_translated`
- `risks`

## Rules

- No raw `<style>` is copied into Gutenberg markup.
- No raw `<script>` is copied into Gutenberg markup.
- Core blocks are preferred first.
- Generated classes use `skvn-*`.
- Image blocks are paste-safe: no empty or self-closing `core/image` output.
- Inline `data:` images are replaced with a placeholder URL and recorded in `assets_needed`.
- Motion is only a contract; implementation must use the shared theme animation runtime.
- Screenshot mode is a scaffold, not computer vision.
