# MILESTONES.md — skvn-shipment-tracking

Current milestone: 0.2.0

---

## 0.2.0 — CPT + Upload Pipeline Core

**Goal:** Tạo data model và image pipeline core để Upload Portal có thể dùng ở
milestone sau.

### Acceptance Criteria

- [ ] Register CPT `skvn_shipment` với `public: false`, `show_ui: true`,
      `rewrite: false`
- [ ] Register private batch meta với sanitize/auth callbacks
- [ ] Activation tạo capability `manage_skvn_tracking` và thư mục
      `uploads/shipments/`
- [ ] Upload context route file vào `shipments/[batch-slug]/original/`
- [ ] WebP attachment trong shipment context được gán `_skvn_shipment_id`
- [ ] Manual category override ưu tiên hơn filename auto-detect
- [ ] ALT fallback đúng format `[batch-slug] - [category] - [filename]`
- [ ] Plugin ALT chạy trước theme ALT hook priority 10
- [ ] ThumbPress timing được onsite verify; tension giữ OPEN cho đến khi human
      xác nhận

### Out of scope cho 0.2.0

- Blurred thumbnail generation (0.3.0)
- Token generation/access control (0.3.0)
- Upload portal (0.4.0+)
- Public/client view (0.5.0+)

### Open Question

`add_attachment` priority 5 đã được chọn từ source review, nhưng local runtime
không có ThumbPress để acceptance-test timing. Không đóng
`TENSIONS_OPEN.md` cho đến khi onsite verification hoàn tất.

### Files chính

```
includes/class-post-type.php
includes/class-plugin-lifecycle.php
includes/class-image-pipeline.php
```

### Definition of Done

Tất cả acceptance criteria có thể kiểm tra tĩnh pass. Runtime acceptance test
với ThumbPress thật được human xác nhận trước khi ship.
