# MILESTONES.md — skvn-shipment-tracking

Current milestone: 0.4.0

---

## 0.4.0 — Routing + Upload Portal Foundation

**Goal:** Dựng routing chung cho `/tracking/*` và hoàn thiện desktop Upload
Portal ở mức local UI/state, sẵn sàng nối backend upload thật trong `0.5.0`.

### Acceptance Criteria

- [ ] `/tracking/upload/` và `/tracking/[slug]/` được route bởi plugin
- [ ] Hai route đều có `NOINDEX`; upload route gửi private/no-store headers
- [ ] Chưa login thấy inline WordPress login form, không redirect `/wp-login.php`
- [ ] Chỉ staff có `manage_skvn_tracking` nhận form shell, nonce và portal config
- [ ] Light desktop UI có đủ metadata form và bốn drop zones
- [ ] Vanilla TypeScript dùng shared typed state cho toàn portal
- [ ] Picker, drag/drop, preview, remove và cross-zone reassignment hoạt động
- [ ] Manual zone assignment luôn ưu tiên hơn filename auto-detection
- [ ] Client validation hỗ trợ WebP/JPEG/PNG, tối đa 20 MB mỗi file
- [ ] Duplicate fingerprint, counters và per-file error display hoạt động
- [ ] Object URL được revoke khi remove, reset hoặc teardown
- [ ] Submit disabled khi chưa có ít nhất một file hợp lệ
- [ ] Draft/upload request-response schemas được document và version hóa
- [ ] Không fake progress và chưa implement backend upload endpoint
- [ ] TypeScript strict typecheck và deploy artifact audit pass

### Out of Scope cho 0.4.0

- Tạo batch và upload file thật (`0.5.0`)
- XHR upload progress và retry network (`0.5.0`)
- Full client gallery và Share popup (`0.6.0`)
- Batch lifecycle, admin list và public snapshot (`0.7.0`)
- Public grid/view (`0.8.0`)
- Mobile-specific upload portal (sau `1.0.0`)

### Runtime Note

ThumbPress timing tension vẫn OPEN nhưng không chặn local UI của `0.4.0`.
Tension này phải được onsite verify trước khi hoàn thành `0.5.0`.

### Files dự kiến

```text
includes/class-routing.php
includes/class-upload-portal.php
src/upload-portal/*
assets/css/upload-portal.css
```

### Definition of Done

Routing, authorization shell và local portal interaction hoạt động mà không cần
fake backend. Static checks, TypeScript typecheck, context consistency và
package audit đều pass.
