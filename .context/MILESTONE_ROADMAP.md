# MILESTONE_ROADMAP.md — skvn-shipment-tracking

> Backlog. Không implement milestone tiếp theo cho đến khi human approve promotion.
> Chỉ đọc khi cần context về "sau này làm gì" — không dùng làm scope hiện tại.

Source chi tiết: `docs/architecture/01-release-roadmap-to-1.0.md`.

---

## 0.1.0 — Media Library Tab + ThumbPress Hook

Implemented; ThumbPress timing tension vẫn OPEN chờ onsite verification.

---

## 0.2.0 — CPT + Upload Pipeline Core

Implemented: CPT, private meta, attachment upload context, ALT/category,
`_skvn_shipment_id` và shipment folder boundary.

---

## 0.3.0 — Blurred Thumbnail + Token

Implemented: token lifecycle, protected original stream và một
`blurred-thumb.webp` cho mỗi batch. Onsite image verification còn pending.

---

## 0.4.0 — Routing + Upload Portal Foundation ← CURRENT

- Shared routing cho `/tracking/upload/` và `/tracking/[slug]/`
- `NOINDEX`, no-store upload route và inline login
- Authorized server-rendered form shell, nonce và config
- Light desktop UI với bốn zones
- Vanilla TypeScript shared state
- Picker, drag/drop, preview, remove và cross-zone reassignment
- Validation, duplicate detection, counters và per-file errors
- Versioned API contract; chưa implement backend upload

---

## 0.5.0 — End-to-End Upload Pipeline

- Authenticated draft creation và per-file upload endpoints
- Server-side validation, sanitization và batch ownership
- Real XHR progress, retry và partial failure handling
- WordPress media + ThumbPress + existing image pipeline integration
- Redirect sang valid private client URL
- Minimal authorized result page; chưa có Share popup
- Onsite verify ThumbPress timing và blurred WebP backend

---

## 0.6.0 — Client View + Staff Share Workflow

- Valid-token private client view; invalid token về public view
- Full-resolution protected gallery và category navigation
- Lazy-loaded cross-category lightbox, keyboard, zoom và focus management
- Server-side client view tracking
- Staff-only server-rendered Share button
- Vanilla TypeScript copy-link popup
- Token/upload routes được onsite verify không cache

---

## 0.7.0 — Lifecycle + Admin Operations + Public Projection

- Batch lifecycle: draft, published, archived
- Generate/backfill `_skvn_public_snapshot`
- Public projection allowlist và fail-closed behavior
- WP CPT admin columns, Copy Link và view statistics
- Capability/nonce protected token rotation
- Archived token links tiếp tục hoạt động nhưng không public

---

## 0.8.0 — Public Grid + Public View

- Hybrid Gutenberg page template cho `/tracking/`
- Server-rendered initial grid từ `_skvn_public_snapshot`
- REST pagination với explicit `Load more`, không infinite scroll
- Skeleton, empty và end states
- `/tracking/` INDEX và cacheable
- `/tracking/[slug]/` blurred/redacted, NOINDEX và cacheable
- Public cards/images redirect `/contact/`
- Public REST/DOM leakage audit

---

## 0.9.0 — Hardening + Onsite Release Candidate

- Activation rewrite rules và cache exclusions
- ThumbPress/GD/Imagick verification với ảnh thật
- Upload validation, retry và partial failure tests
- Token rotation, invalid-token và protected-file tests
- Accessibility, lazy loading và public projection leakage audit
- PHP 8.0 compatibility, console/PHP logs và packaged ZIP audit

---

## 1.0.0 — Stable Release

- Staff upload workflow hoạt động end-to-end
- Staff inspect và copy private client link
- Client valid token xem được authorized full-resolution gallery
- Invalid token không lấy được private metadata hoặc originals
- Public grid/view chỉ dùng stored redacted snapshot và blurred imagery
- Lifecycle, admin list, token rotation và view tracking hoạt động
- Full packaged workflow được onsite verify

Deferred sau `1.0.0`:

- Mobile-specific upload portal
- Custom Gutenberg grid block
- Email integration và n8n automation
- Chunked/resumable/background upload
- Destructive data purge UI
- Self-contained Tailwind migration khỏi WindPress
