# Footer + Full-Width Overflow Debug Case

Use this reference when a WordPress/Gutenberg page says the footer is wrong, but the real symptom mixes footer rendering, GeneratePress wrappers, Gutenberg `alignfull`, full-width canvas CSS, or white space below the footer.

## Trigger Symptoms

- User says the selected footer template does not appear, but later source shows custom footer markup.
- Footer appears inside a GeneratePress wrapper such as `.site-footer`.
- Visible white strip appears under a dark custom footer.
- A full-width section like `.alignfull.skvn-translated-hero` spills outside the viewport.
- DevTools geometry shows negative `x` or `left` values for footer or hero sections.

## State Delta

Separate the problem into two states before proposing a fix:

```text
State A: Footer renderer active
Evidence: View Source contains custom footer markup, e.g. `.skvn-footer-page` and `.skvn-site-footer`.

State B: Layout still broken
Evidence: `getBoundingClientRect()` shows `.site-footer`, `.skvn-footer-page`, `.skvn-site-footer`, or `.alignfull` with negative x/left or width larger than viewport.
```

Do not keep debugging settings if State A is already true. Move from plugin/settings/PHP diagnosis to CSS layout diagnosis.

## Layer Checklist

### 1. Verify Render Layer

Ask for:

```text
Admin settings page URL:
Selected footer page ID:
Footer page status: Published/Draft/Private:
After Save + reload, selection persists: Yes/No
View Source contains footer template marker: Yes/No
Server has theme render file, e.g. `inc/footer.php`: Yes/No
Theme bootstrap requires that file: Yes/No
```

Interpretation:

- Selection persists but View Source lacks marker: theme renderer is missing, not running, or falling back.
- Server lacks theme render file while local repo has it: deploy/package issue.
- View Source contains `.skvn-footer-page`: renderer works; continue to layout layer.

### 2. Verify Layout Geometry

Ask the user to run this in DevTools Console:

```javascript
[
  '.site-footer',
  '.skvn-footer-page',
  '.skvn-site-footer',
  '.skvn-translated-hero'
].map((selector) => {
  const element = document.querySelector(selector);
  const style = element && getComputedStyle(element);

  return [
    selector,
    element && element.getBoundingClientRect(),
    style && {
      margin: style.margin,
      padding: style.padding,
      width: style.width,
      maxWidth: style.maxWidth,
      background: style.backgroundColor,
      overflowX: style.overflowX,
    },
  ];
});
```

Red flags:

```text
x < 0
left < 0
right > viewport width
width > viewport width
margin-left/right is `calc(50% - ...px)`
overflow-x is visible on footer/full-width wrappers
```

## Diagnosis Pattern

If `.alignfull` has computed margin like:

```text
margin: 0px calc(50% - 707px)
width: 1414px
x: -17.5
```

then Gutenberg/global `alignfull` rules are still affecting the section. In a project-owned full-width canvas, add a more specific reset for direct `.alignfull` children instead of adding broad `body { overflow-x: hidden; }`.

If `.site-footer` or `.skvn-site-footer` has:

```text
x: negative
width: viewport width + scrollbar/offset
overflowX: visible
```

then the footer wrapper needs clipping/background hardening, especially when a custom footer page is rendered inside GeneratePress' `.site-footer` surface.

## Fix Shape

Prefer a narrow CSS fix scoped to the project canvas and footer wrapper:

```css
.site-footer {
  overflow-x: clip;
}

.skvn-footer-page {
  background: var(--project-footer-bg);
  display: block;
  overflow-x: clip;
}

.skvn-footer-page > .wp-block-group,
.site-footer .custom-footer-root {
  margin-block: 0;
}

.project-full-width-canvas .entry-content > .alignfull {
  box-sizing: border-box;
  margin-left: 0;
  margin-right: 0;
  max-width: 100%;
  width: 100%;
}
```

Adapt selector names to the local project. Do not use `!important` until the exact overriding rule has been inspected.

## Prove The Fix

After the patch, ask the user to hard refresh and rerun the geometry command.

Expected:

```text
.skvn-translated-hero x: 0 or not negative
.site-footer x: 0 or not negative
No horizontal scroll
Footer marker remains in View Source
Default footer is not duplicated under the selected footer page
```

If the white strip remains but geometry is fixed, change diagnosis: the remaining issue is likely body/page minimum height, page background, or footer not reaching viewport bottom, not `alignfull` overflow.

## White Strip After Geometry Is Fixed

If footer and hero geometry are no longer negative, but a white strip still appears below a dark footer, inspect the element under the strip. Often the footer has ended and the page/body background is showing through.

Prefer a state-scoped class over a global body background:

```php
add_filter( 'body_class', 'project_footer_body_class' );

function project_footer_body_class( $classes ) {
	if ( project_has_custom_footer() ) {
		$classes[] = 'project-has-footer-page';
	}

	return $classes;
}
```

Then scope CSS to that state:

```css
body.project-has-footer-page {
  background: var(--project-footer-bg);
}

body.project-has-footer-page .site {
  background: var(--project-page-bg);
}
```

This keeps normal pages unaffected and prevents the viewport area after the footer from showing a mismatched body background.
