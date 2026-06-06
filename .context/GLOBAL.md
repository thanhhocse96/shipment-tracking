# GLOBAL.md — skvn-shipment-tracking

<!-- MANUAL_START -->
[manual] Project Identity
Plugin: skvn-shipment-tracking
PHP prefix: skvn_tracking_
CSS prefix: skvn-tracking-
Text domain: skvn-shipment-tracking
CPT: skvn_shipment
Upload folder: wp-content/uploads/shipments/
PHP version: 8.0 (shared hosting constraint)

[manual] Purpose
Plugin quản lý ảnh tracking lô hàng xuất nhập khẩu cho B2B seafood export company.
Dual purpose: Operations (staff upload → share link → client) + SEO (public gallery → lead).

[manual] Stack & Dependencies
WordPress           core platform
GeneratePress       parent theme — KHÔNG đụng vào
skvn-marine         child theme — không sửa, chỉ follow visual contract
Thumbpress          WebP conversion và image optimization
                    Plugin hook vào Thumbpress, không tự xử lý WebP

[manual] Module Index
includes/class-media-tabs.php     Media Library tab injection + ajax filter
includes/class-image-pipeline.php Upload hook, ALT text, category assignment (0.2.0+)
includes/class-access-control.php Token validation, share button render (0.3.0+)
includes/class-routing.php        Custom routing cho /tracking/* URLs (0.4.0+)
assets/js/admin-media-tabs.js     Tab switch UI — chỉ enqueue trên upload.php

[manual] Surfaces
/tracking/upload/              Staff portal — NOINDEX
/tracking/                     Public grid — INDEX (SEO chính)
/tracking/[slug]/              Public view — NOINDEX
/tracking/[slug]/?token=xxx    Client view — NOINDEX

[manual] Current Phase
Milestone 0.1.0 — Media Library Tab + Thumbpress Hook
Xem .context/MILESTONES.md cho acceptance criteria chi tiết.

[manual] Key Invariants
- Thumbpress xử lý WebP — plugin không tự convert
- blurred-thumb.webp: CHỈ 1 file per batch
- Share button: server-side PHP render, không CSS hide / JS toggle
- Original files: không public URL trừ khi valid token
- ALT text: plugin set trước theme hook hoặc higher priority
- Script admin-media-tabs.js: chỉ enqueue trên upload.php
<!-- MANUAL_END -->

<!-- AUTO_START -->
[auto] — chưa có context-gen scan. Chạy `python ../context-mapping/cli.py build .` để generate.
<!-- AUTO_END -->
