# MILESTONES.md — skvn-shipment-tracking

Current milestone: 0.3.0

---

## 0.3.0 — Blurred Thumbnail + Token

**Goal:** Hoàn thiện private/public image boundary và token foundation trước khi
xây Upload Portal.

### Acceptance Criteria

- [ ] Batch mới tự có token 32-char hex
- [ ] Token compare dùng `hash_equals`
- [ ] Staff có helper rotate token; token cũ mất hiệu lực ngay
- [ ] Original attachment không có usable static public URL
- [ ] Protected file endpoint chỉ stream khi token đúng batch
- [ ] Invalid/missing token fail closed, không trả partial file data
- [ ] Mỗi batch chỉ có một `blurred-thumb.webp`
- [ ] Source ưu tiên ảnh đầu tiên của Seal & Door, fallback ảnh đầu tiên
- [ ] Thumbnail được blur thật server-side và lưu ngoài `original/`

### Out of scope cho 0.3.0

- Upload portal (0.4.0+)
- Public/client view (0.5.0+)
- Share popup và admin token UI (0.6.0)
- Public snapshot generation (0.5.0)

### Runtime Note

ThumbPress timing tension vẫn OPEN. Blurred thumbnail generation chạy sau
shipment attachment processor nên onsite test phải xác nhận cả WebP source và
output timing.

### Files chính

```
includes/class-access-control.php
includes/class-blurred-thumbnail.php
```

### Definition of Done

Static checks pass. Token/file access và blur output được onsite verify với ảnh
thật trước khi ship.
