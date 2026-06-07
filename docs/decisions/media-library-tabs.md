# Media Library Tabs Decision

## Status

Accepted for milestone 0.1.0.

## Decision

`/wp-admin/upload.php` exposes two plugin-owned filters:

```text
[ Post - Pages ] [ Shipment Tracking ]
```

`Post - Pages` is the default scope.

| Scope | Attachment query |
|---|---|
| `posts` | `_skvn_shipment_id` does not exist |
| `shipment` | `_skvn_shipment_id` exists |

The browser sends only the enum `posts` or `shipment`. PHP owns the meta key
and comparison. An arbitrary meta key from the browser is never accepted.

## Grid View

Grid view is the primary milestone surface.

1. PHP enqueues the compiled admin script only on `upload.php`.
2. The script adds `skvn_tracking_scope` to the Media Library collection props.
3. WordPress Backbone sends the prop in the `query-attachments` request.
4. PHP sanitizes and allowlists the raw request value.
5. `ajax_query_attachments_args` merges the shipment clause with any existing
   `meta_query`.
6. Backbone updates the grid without a page reload.

The script listens for WordPress's `wp-media-grid-ready` event and also handles
an already-created `wp.media.frames.browse` frame. It must preserve the current
search, date, MIME type, ordering, and pagination behavior owned by WordPress.
Attachment AJAX requests without the plugin scope are left unchanged so media
modals on other admin screens are not filtered accidentally.

## List View

WordPress list view is rendered by `WP_Media_List_Table`, not the Media Grid
Backbone collection. Replacing the table through custom AJAX is outside
milestone 0.1.0.

List view keeps the same two controls, but switching scope navigates to
`upload.php?mode=list&skvn_tracking_scope=<scope>`. A scoped `pre_get_posts`
filter applies the same attachment meta condition.

Therefore, "switch without reload" applies to Grid view. List view is a
server-rendered fallback.

## UI Contract

- Controls use native buttons with `aria-pressed`.
- The active scope is reflected in the URL with `history.replaceState` in Grid
  view.
- DOM selectors are scoped to `#wp-media-grid` or the Media Library `.wrap`.
- Selector assumptions must be smoke-tested after a WordPress core update.
- No React or UI framework runtime is introduced.

## Security

- Both scopes require the existing WordPress Media Library capability checks.
- Request input is passed through `wp_unslash()` and `sanitize_key()`.
- Only `posts` and `shipment` are accepted; invalid values become `posts`.
- Existing `meta_query` clauses are nested under an `AND` relation rather than
  overwritten.

## Onsite Verification

Offline browser/runtime testing is intentionally deferred. Onsite testing must
cover:

- Initial Grid load defaults to `Post - Pages`.
- Both scopes return the expected attachment sets.
- Grid switching does not reload the page.
- Search, date, and MIME filters remain functional after switching.
- List fallback reloads and preserves the selected scope.
- No browser console error occurs.
