# MILESTONE_ROADMAP.md — skvn-shipment-tracking

> Backlog. Không implement milestone tiếp theo cho đến khi human approve promotion.
> Chỉ đọc khi cần context về "sau này làm gì" — không dùng làm scope hiện tại.

---

## 0.1.0 — Media Library Tab + Thumbpress Hook

Implemented; ThumbPress timing tension vẫn OPEN chờ onsite verification.

---

## 0.2.0 — CPT + Upload Pipeline Core ← CURRENT

- Register `skvn_shipment` CPT
- Meta fields: batch title, client name, container number, closing date, product type, token, thumb path
- Image upload qua WP media pipeline với Thumbpress hook (dùng kết quả từ 0.1.0)
- ALT text fallback system
- `_skvn_shipment_id` meta assignment per attachment
- Auto-detect category từ filename

---

## 0.3.0 — Blurred Thumbnail + Token

- Generate `blurred-thumb.webp` (1 per batch)
- 32-char hex token generation
- Folder structure: `shipments/[batch-slug]/original/` + `blurred-thumb.webp`
- Token-based access control logic

---

## 0.4.0 — Staff Upload Portal

- `/tracking/upload/` URL + routing
- 4 drop zones UI
- Form metadata fields
- Login check + inline login form
- Upload flow end-to-end
- Redirect sang client view sau submit

---

## 0.5.0 — Public Grid + Public View

- `/tracking/` public grid với infinite scroll
- Card component: blurred thumbnail + redacted metadata
- Click card → redirect `/contact/`
- `/tracking/[slug]/` public view
- Redact logic implementation
- SEO: INDEX `/tracking/`, NOINDEX `/tracking/[slug]/`

---

## 0.6.0 — Client View + Share Button

- `/tracking/[slug]/?token=xxx` client view
- Token validation
- Full resolution gallery với sidebar category nav
- Lightbox: thumbnail strip, keyboard nav, mobile swipe
- Share button: server-side render cho staff
- NOINDEX header cho client view

---

## 1.0.0 — Stable Release

- Tất cả surfaces hoạt động
- Mobile layouts (chưa wireframe)
- Login screen staff (chưa wireframe)
- E2E test với real batch upload
- Performance: infinite scroll, image load
- n8n automation hook points (chưa implement)
