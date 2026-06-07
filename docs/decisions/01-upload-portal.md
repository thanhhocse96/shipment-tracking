# 01 — Staff Upload Portal Plan

## Trạng thái

Proposed. Không phải approval để promote milestone.

Target milestone: `0.4.0`.

Upload Portal chỉ được nối end-to-end sau khi các dependency sau hoàn thành:

- `0.2.0`: CPT, batch meta, attachment assignment, category detection và upload
  pipeline core.
- `0.3.0`: token, access control, shipment folder và một
  `blurred-thumb.webp` cho mỗi batch.

Artifact tham khảo:

- `docs/artifacts/gemini-code-1780819991119.html`
- `docs/artifacts/testing-ui.html`

Artifact chỉ là visual/behavior reference. Không copy raw `<style>`, inline
`<script>` hoặc prototype upload simulation vào production.

## Mục tiêu

Xây `/tracking/upload/` cho staff đăng nhập:

1. Nhập metadata của batch.
2. Chọn hoặc kéo ảnh vào bốn zone.
3. Preview, xóa và chuyển ảnh giữa các zone trước khi submit.
4. Validate file và hiển thị lỗi theo từng filename.
5. Tạo batch draft, upload ảnh qua WordPress + ThumbPress pipeline.
6. Redirect sang client view của batch vừa tạo.

## Scope Desktop MVP

Form fields:

```text
Tên lô hàng
Tên khách hàng
Số container
Ngày đóng hàng
Loại sản phẩm
Thông tin thêm
```

Zones:

```text
Seal & Door Check
Temperature Monitoring
Cargo Rows
Chưa phân loại
```

Portal có:

- Sticky action/header area với tổng số ảnh và submit state.
- Sticky zone navigation để nhảy giữa bốn zone khi trang dài.
- Native file picker và desktop drag/drop.
- Tối đa bốn preview trực tiếp cho mỗi zone.
- `Xem thêm (+N)` cho phần còn lại.
- Counter, upload progress và error list.
- Manual cross-zone reassignment.
- Light UI, scoped bằng prefix `skvn-tracking-`.

Mobile upload vẫn ngoài MVP. CSS/markup phải để sẵn modifier
`.skvn-tracking-upload--mobile`, nhưng không implement mobile preview hoặc
mobile drag/drop trong `0.4.0`.

## State Model

```ts
type ZoneId = 'seal' | 'temperature' | 'cargo' | 'uncategorized';

type UploadStatus =
	| 'queued'
	| 'uploading'
	| 'success'
	| 'error';

interface PortalFile {
	id: string;
	file: File;
	zone: ZoneId;
	previewUrl: string;
	fingerprint: string;
	status: UploadStatus;
	progress: number;
	errorMessage?: string;
	attachmentId?: number;
}

interface DragState {
	imageId: string;
	sourceZone: ZoneId;
}
```

`UploadPortal` sở hữu shared state. `DropZone` không tự giữ source of truth
riêng, vì cross-zone drag cần một state chung.

Manual assignment luôn ưu tiên hơn filename auto-detection.

## TypeScript Modules

```text
src/upload-portal/
├── UploadPortal.ts
├── DropZone.ts
├── ImagePreview.ts
├── GalleryModal.ts
├── FilenameParser.ts
├── FileCounter.ts
├── FileValidator.ts
├── SubmitPipeline.ts
└── types.ts
```

### UploadPortal

- Orchestrate form, zones, global summary và submit state.
- Sở hữu collection `PortalFile`.
- Quản lý cross-zone drag state.
- Cleanup all object URLs khi reset hoặc page teardown.

### DropZone

- Click, keyboard `Enter`/`Space`, drag enter/leave/drop.
- Chỉ emit intent về orchestrator.
- Không upload ngay khi chọn file.

### FileValidator

Client allowlist:

```text
image/webp
image/jpeg
image/png
max 20 MB/file
```

Server phải validate lại toàn bộ MIME, extension, size và capability.

Duplicate fingerprint cho MVP:

```text
file.name + file.size + file.lastModified
```

Duplicate được kiểm tra trong batch hiện tại. File trùng không được thêm lần
hai và phải có message rõ ràng.

### ImagePreview

- Dùng `URL.createObjectURL()` cho local preview.
- Preview bốn ảnh đầu.
- Remove, retry và cross-zone drag handles.
- Không render filename bằng `innerHTML`; dùng `textContent`.
- Revoke object URL khi remove, reset, teardown, hoặc khi local preview chắc
  chắn không còn được sử dụng.

### FileCounter

Các state hiển thị:

```text
Chưa có ảnh nào
X ảnh đã chọn
Y / X đã upload
X ảnh đã upload
Y / X thành công
```

Error list luôn có filename gốc và lý do cụ thể.

### GalleryModal

- Hiển thị toàn bộ ảnh trong một zone.
- `aria-modal`, accessible name và focus trap.
- Đóng bằng button, `Escape` hoặc click backdrop.
- Trả focus về control đã mở modal.

### SubmitPipeline

- Không dùng progress simulation.
- Dùng `XMLHttpRequest.upload.onprogress` khi cần progress upload thật.
- Chặn double submit.
- Cho phép retry file lỗi mà không upload lại file thành công.
- Giữ batch ở `DRAFT` khi upload partial failure.

## PHP Responsibilities

```text
includes/
├── class-routing.php
├── class-upload-portal.php
├── class-upload-controller.php
└── class-image-pipeline.php
```

### Routing và render

- Route `/tracking/upload/`.
- `NOINDEX`.
- Chưa login: render inline login form, không redirect `/wp-login.php`.
- Đã login: server-render form shell, nonce và authorization-controlled
  elements.

### Upload controller

Mọi request phải:

- Check authentication và capability.
- Verify nonce.
- Sanitize metadata.
- Validate file lại ở server.
- Không tin zone, MIME, filename hoặc progress từ client.
- Trả structured error có filename và message an toàn.

### Image pipeline

Portal không tự convert WebP.

```text
WordPress upload
→ ThumbPress convert/optimize
→ plugin hook
→ guard WebP + shipments path
→ ALT
→ category
→ _skvn_shipment_id
```

Theo contract hiện tại:

- ThumbPress chưa active: log warning, skip image pipeline, không crash.
- WebP-on-upload chưa bật: log warning, skip image pipeline.
- Attachment không phải WebP: skip silently.

Không tự đổi behavior này thành abort toàn bộ portal nếu chưa có human
decision mới. UI cần nhận kết quả/warning đủ rõ để staff biết pipeline chưa
hoàn tất.

## End-to-End Submit Flow

```text
1. Validate metadata và ít nhất một file hợp lệ.
2. Lock submit UI.
3. Tạo skvn_shipment ở trạng thái DRAFT.
4. Upload từng file với zone/manual assignment.
5. WordPress + ThumbPress xử lý attachment.
6. Plugin set ALT, category và _skvn_shipment_id.
7. Auto-detect chỉ áp dụng cho zone Chưa phân loại.
8. Generate một blurred-thumb.webp cho batch.
9. Generate/lưu token theo contract 0.3.0.
10. Nếu tất cả bước bắt buộc thành công, redirect sang client view:
    /tracking/[slug]/?token=<token>
11. Nếu partial failure, giữ DRAFT và hiển thị retry/error list.
```

## UI và Motion Contract

Giữ từ prototype:

- Transition hover/focus/drag khoảng `150ms`.
- Thumbnail fade-in khoảng `200ms`.
- Progress bar transition khoảng `150ms`.
- Toast slide/fade khoảng `200–220ms`.
- Modal fade nhẹ.

Production phải có:

```css
@media (prefers-reduced-motion: reduce) {
	.skvn-tracking-upload * {
		scroll-behavior: auto;
		transition-duration: 0.01ms !important;
		animation-duration: 0.01ms !important;
	}
}
```

Không copy prototype colors trực tiếp nếu chúng conflict với site tokens.
Production dùng light UI và plugin-scoped CSS.

## Implementation Phases

### Phase A — Contract và PHP shell

- Chốt capability cho staff portal.
- Chốt request/response schema.
- Thêm routing và inline login behavior.
- Render form shell, nonce và initial config.
- Chưa nối upload thật.

### Phase B — TypeScript local state

- Implement modules và typed state.
- Form validation.
- File validation và duplicate fingerprint.
- Four-zone picker/drag/drop.
- Preview, remove, gallery và cross-zone move.
- Counter/error state.
- Submit vẫn dùng disabled adapter, chưa fake progress.

### Phase C — Upload controller

- Create draft batch endpoint.
- Upload file endpoint hoặc request strategy đã chốt.
- XHR progress.
- Server validation và structured errors.
- Attachment ID mapping về từng `PortalFile`.

### Phase D — Pipeline integration

- Nối ThumbPress result từ `0.1.0`.
- ALT/category/batch meta từ `0.2.0`.
- Token/blurred thumbnail từ `0.3.0`.
- Retry và partial failure behavior.
- Redirect client view.

### Phase E — Hardening

- Accessibility modal/drop-zone.
- Reduced motion.
- Prevent navigation khi còn queued/uploading files.
- Cleanup object URLs và abort active XHR khi rời trang.
- Onsite test với batch ảnh thật.

## Verification

Static/local:

- TypeScript strict typecheck.
- PHP syntax.
- Prefix scan.
- Build/package artifact audit.
- Unit tests cho filename parsing, validation và duplicate fingerprint nếu test
  runner đã được thêm.

Onsite:

- Login và inline login flow.
- Form validation và disabled submit.
- Picker, drag/drop và cross-zone reassignment.
- Duplicate, invalid MIME và file trên 20 MB.
- Progress thật, retry và partial failure.
- ThumbPress timing với ảnh thật.
- ALT/category/attachment meta.
- Chỉ một blurred thumbnail.
- Token redirect và client view.
- Không có console/PHP error.

## Out of Scope

- Mobile upload implementation.
- Background/offline upload.
- Chunked/resumable upload.
- Content hashing toàn file.
- Email/share integration.
- n8n automation.
- React hoặc UI framework runtime.

