# MILESTONES.md — skvn-shipment-tracking

Current milestone: 0.1.0

---

## 0.1.0 — Media Library Tab + Thumbpress Hook

**Goal:** Verify rằng hai cơ chế nền tảng hoạt động đúng trước khi build các surface phức tạp hơn.

### Acceptance Criteria

- [ ] `/wp-admin/upload.php` hiển thị đúng 2 tab: **Post - Pages** (default) và **Shipment Tracking**
- [ ] Tab **Post - Pages** filter: chỉ hiển thị attachments KHÔNG có meta `_skvn_shipment_id`
- [ ] Tab **Shipment Tracking** filter: chỉ hiển thị attachments CÓ meta `_skvn_shipment_id`
- [ ] Tab switch không reload page (JS handle)
- [ ] Script chỉ enqueue trên `upload.php`, không enqueue trên trang admin khác
- [ ] Thumbpress hook đã xác định được — biết hook name cụ thể để attach sau khi WebP xong
- [ ] Thumbpress hook test: upload 1 ảnh → hook callback được gọi đúng timing
- [ ] Không có JS error trong browser console

### Out of scope cho 0.1.0

- ALT text pipeline (0.2.0)
- Blurred thumbnail generation (0.3.0)
- CPT `skvn_shipment` và meta fields (0.2.0)
- Upload portal (0.4.0+)
- Public/client view (0.5.0+)

### Open Question cần resolve trong 0.1.0

**Thumbpress hook name** — chưa verify. Candidates từ Thumbpress docs/source:
- `thumbpress_after_convert` (nếu có)
- `add_attachment` (WP native, sau khi Thumbpress xong?)
- Cần inspect Thumbpress source để xác định.

Tạo entry trong `TENSIONS_OPEN.md` nếu chưa verify được.

### Files sẽ tạo trong milestone này

```
skvn-shipment-tracking/
├── skvn-shipment-tracking.php    ← main plugin file, CPT register placeholder
├── includes/
│   └── class-media-tabs.php      ← tab injection + ajax filter
└── assets/js/
    └── admin-media-tabs.js       ← tab switch UI
```

### Definition of Done

Tất cả acceptance criteria checked. Verification gate pass. Thumbpress hook name đã confirm hoặc đã có TENSIONS entry nếu còn uncertain.
