# HTML-2-Gutenberg Translation — Test 01

## gutenberg_markup

Use `.local/test-artifacts/HTML-2-Gu-Test-01.gutenberg.html`.

Paste into Gutenberg Code Editor on a page using the SKVN Full Width layout.

## required_classes

- `skvn-page`, `skvn-page--html2gu-test`
- `skvn-section`, `skvn-section--services`, `skvn-section--dark`, `skvn-section--trusted`, `skvn-section--why`, `skvn-section--testimonials`
- `skvn-hero`, `skvn-hero__columns`, `skvn-hero__content`, `skvn-hero__title`, `skvn-hero__lead`, `skvn-hero__media`, `skvn-hero__thumbs`
- `skvn-eyebrow`, `skvn-button-row`, `skvn-button`, `skvn-button--primary`, `skvn-button--secondary`
- `skvn-image`, `skvn-image--thumb`, `skvn-image--hero`, `skvn-image--split`
- `skvn-card-grid`, `skvn-card-grid--3`, `skvn-card`, `skvn-service-card`
- `skvn-card__image`, `skvn-card__title`, `skvn-card__meta`, `skvn-card__link`
- `skvn-cta-band`, `skvn-cta-band__columns`, `skvn-cta-band__action`
- `skvn-logo-grid`, `skvn-logo-card`, `skvn-logo-card__logo`, `skvn-logo-card__title`
- `skvn-split`, `skvn-split__media`, `skvn-split__content`, `skvn-check-list`
- `skvn-testimonial-grid`, `skvn-testimonial-card`, `skvn-testimonial-card__name`, `skvn-testimonial-card__company`, `skvn-testimonial-card__quote`
- `skvn-motion-reveal`

## theme_css_contract

- Full-width sections use `alignfull`; inner content remains constrained by theme layout width.
- Hero uses two columns on desktop, stacked on mobile.
- Service/testimonial grids use 3 columns desktop, 2 tablet, 1 mobile.
- Logo grid uses 5 columns desktop, then 3, 2, 1 by viewport.
- Cards use theme radius, border/ring, shadow, and hover elevation.
- Dark CTA uses navy/dark surface and primary blue button.
- Placeholder images should keep stable aspect ratios to avoid layout shift.
- Editor view must remain visible and usable; do not hide reveal items with `opacity: 0`.

## animation_contract

- Element: `.skvn-motion-reveal`
- Trigger: scroll enter.
- Initial state: visible in editor; frontend may start slightly translated only after runtime attaches.
- Final state: visible, translateY(0).
- Duration: 450ms.
- Easing: ease-out.
- Stagger: 60ms inside card grids.
- Reduced motion: no transform, no stagger, content visible immediately.
- Editor behavior: static.

## assets_needed

- Hero image.
- Two hero detail/thumb images.
- Three service images.
- Five client logos.
- One process/why-work image.
- Placeholder URLs are included only for paste testing; replace through Gutenberg media library.

## not_translated

- Prototype top bar/header/nav.
- Prototype footer.
- Raw Tailwind utility classes.
- Inline SVG arrows and check icons; theme CSS should draw icons or use list styling.
- Original callback form with `onsubmit`; replaced by editable CTA button because CF7/quote form is deferred.
- Raw `<style>` and `<script>` content from the artifact.

## risks

- Current theme may not yet style all new `skvn-*` classes, so pasted markup may look plain until CSS contract is implemented.
- Placeholder image URLs are external and should be replaced with media-library assets for production.
- The CTA form is not functional by design in 0.5.1.
- Header/footer should be controlled by GeneratePress/theme page controls, not pasted into content.
- Test mobile stacking, card text wrapping, and button visibility after theme CSS is added.
