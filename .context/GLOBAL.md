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
includes/class-post-type.php         CPT, private meta, sanitizers, capability map
includes/class-plugin-lifecycle.php  Activation, upgrade setup, capability, upload base
includes/class-media-tabs.php        Media Library Grid/List filters
includes/class-image-pipeline.php    Upload context, shipment path, ALT/category/meta
includes/class-access-control.php    Token lifecycle, validation, protected file stream
includes/class-blurred-thumbnail.php One server-blurred public thumbnail per batch
includes/class-routing.php           Planned custom routing for /tracking/* (0.4.0+)
assets/js/admin-media-tabs.js        Tab switch UI — chỉ enqueue trên upload.php

Chi tiết ownership/hook/call flow:
docs/architecture/00-includes-module-map.md

[manual] Surfaces
/tracking/upload/              Staff portal — NOINDEX
/tracking/                     Public grid — INDEX (SEO chính)
/tracking/[slug]/              Public view — NOINDEX
/tracking/[slug]/?token=xxx    Client view — NOINDEX

[manual] Current Phase
Milestone 0.4.0 — Routing + Upload Portal Foundation
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
