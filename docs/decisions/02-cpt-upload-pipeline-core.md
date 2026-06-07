# 02 — CPT + Upload Pipeline Core

## Trạng thái

Accepted cho milestone `0.2.0`.

Human approved promotion từ `0.1.0` sang `0.2.0`. ThumbPress timing tension vẫn
OPEN cho đến khi onsite test xác nhận; decision này không archive tension đó.

## Scope

- Register private CPT `skvn_shipment`.
- Register private batch meta.
- Add `manage_skvn_tracking` capability cho administrator khi activation.
- Tạo base upload directory `wp-content/uploads/shipments/`.
- Cung cấp upload context API cho Upload Portal milestone sau.
- Route upload có context vào `shipments/[batch-slug]/original/`.
- Process WebP attachments: ALT, category và `_skvn_shipment_id`.

Không generate token, public snapshot hoặc blurred thumbnail trong `0.2.0`.

## Upload Context

Upload Portal chưa được implement, vì vậy pipeline cung cấp API request-scoped:

```php
skvn_tracking_set_upload_context( $batch_id, $category, $caption );
skvn_tracking_clear_upload_context();
```

Context phải được set ngay trước WordPress media upload và clear trong
`finally`. Pipeline không đọc batch/category tùy ý trực tiếp từ public request.

Allowed categories:

```text
seal
temperature
cargo
uncategorized
```

Manual category khác `uncategorized` được giữ nguyên. `uncategorized` cho phép
filename auto-detection.

## Attachment Pipeline

```text
WordPress upload with context
→ shipments/[batch-slug]/original/
→ ThumbPress conversion
→ add_attachment priority 5
→ guard image/webp
→ guard /shipments/
→ set category
→ set _skvn_shipment_id
→ set ALT
```

Fallback hook `thumbpress_file_meta_refreshed` gọi cùng processor khi hook đó
tồn tại trên runtime.

## ALT Contract

Caption staff nhập có ưu tiên cao nhất. Nếu caption rỗng:

```text
[batch-slug] - [category label] - [cleaned filename]
```

Filename normalization:

- Bỏ extension.
- `_` và `-` thành space.
- Giữ sequence trong ngoặc dưới dạng số.
- Lowercase và collapse whitespace.
- Numeric cargo filename được prefix `row`.

## Security

- CPT không public và không rewrite.
- Meta không expose qua REST.
- Meta auth callback yêu cầu `manage_skvn_tracking`.
- Upload context chỉ nhận batch CPT hợp lệ.
- Batch/category assignment không lấy từ browser request trong attachment hook.
- Original file public-serving protection thuộc access-control milestone; URL
  không được sử dụng trên public surfaces.

