# TENSIONS_OPEN.md — skvn-shipment-tracking

---

## [2026-06-07] | image-pipeline
Tension:    Thumbpress hook name chưa verified
Context:    Plugin cần biết hook nào để attach vào sau khi Thumbpress convert xong WebP. Nếu attach sai hook → callback chạy trước khi WebP ready → ALT text và category assignment sẽ bị set vào ảnh gốc JPEG, không phải WebP attachment.
Proposal:   Inspect Thumbpress source code hoặc dùng `add_action('all', ...)` debug để capture hook sequence khi upload
Constraint: Milestone 0.1.0 — phải resolve hook name trước khi build ALT text pipeline (0.2.0)
Severity:   high
Tags:       thumbpress, image-pipeline
Milestone:  0.1.0
Status:     OPEN
Decision:
