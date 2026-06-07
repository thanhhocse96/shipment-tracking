# Shipment Tracking Visual Contract

Status: working standard.

## Direction

Design language: **Cold Documentation**.

References:

- frame.io for dark client-review and share-link UX
- flexport.com for trustworthy B2B logistics presentation

The `skvn-marine` child theme owns the site-wide visual system. The tracking
plugin owns only styles and interactions required by its four surfaces.

## Ownership

| Layer | Owner |
|---|---|
| Site tokens, typography, shared buttons, global layout | `skvn-marine` theme |
| Tracking portal, grid, views, lightbox UI | `skvn-shipment-tracking` |
| Editable `/contact/` content | WordPress content |
| WebP optimization | Thumbpress |

The plugin follows theme tokens where available and scopes its selectors with
`skvn-tracking-`. It must not modify the theme to force visual parity.

## Frontend Technology

- Interactive UI uses Vanilla TypeScript and browser DOM APIs.
- React and UI framework runtimes are not part of the plugin.
- PHP renders initial semantic markup and authorization-sensitive controls.
- TypeScript progressively enhances markup; essential content/navigation must
  not become inaccessible solely because JavaScript fails.
- Tailwind MVP uses the site's existing WindPress integration.
- A future standalone plugin CSS build must disable or tightly scope preflight.

## Surface Rules

### Staff Upload

- Desktop wireframe exists.
- Four clearly separated upload zones.
- Sticky navigation may jump between zones.
- Inline login form when logged out.
- Mobile layout remains undecided.

### Public Grid

- 4 columns at 1280px+, 3 at 1024px, 2 on tablet.
- Uses the single blurred thumbnail for each batch.
- Shows batch title, product type, and closing year.
- Never shows client name.
- Card click redirects to `/contact/`.

### Public View

- Uses blurred imagery and redacted metadata.
- No lightbox and no share button.
- Image click redirects to `/contact/`.

### Client View

- Dark review-oriented layout with category sidebar and gallery.
- Valid token is required for full-resolution images.
- Lightbox includes thumbnail strip, filename/category footer, keyboard
  navigation, and mobile swipe.
- Share button exists in DOM only for authorized staff.

## Accessibility

- All controls need visible focus states and keyboard operation.
- Do not rely on emoji alone for category meaning.
- Motion must respect `prefers-reduced-motion`.
- Public blur must not be implemented as a reversible CSS filter over originals.
- Editor/staff-entered ALT text takes priority over generated fallback text.

## Guardrails

- No raw theme tokens copied into plugin PHP.
- No unscoped plugin CSS.
- No CSS hiding for security or authorization.
- No public original-image URL in markup, CSS, preload, or API payload.
- No mobile behavior invented without recording the unresolved decision.
- No React/JSX dependency or client-side authorization logic.
- Public UI and REST payloads may only consume `_skvn_public_snapshot`.
