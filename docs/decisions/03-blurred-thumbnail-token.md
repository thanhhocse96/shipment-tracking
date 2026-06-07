# 03 — Blurred Thumbnail + Token

## Trạng thái

Accepted cho milestone `0.3.0`.

## Token Contract

- Token là 16 random bytes encode thành 32 lowercase hex characters.
- Generate khi batch được tạo nếu `_skvn_token` đang rỗng.
- Không expiry trong MVP.
- Rotate tạo token mới và overwrite token cũ.
- Compare bằng `hash_equals()`.
- Token không được đưa vào public snapshot, public DOM hoặc public API.

## Original File Boundary

`original/` tiếp tục bị deny static access. Client không nhận attachment URL
trực tiếp.

Plugin cung cấp protected endpoint qua `admin-post.php`:

```text
action=skvn_tracking_file
attachment_id=<id>
token=<32-char-hex>
```

Endpoint:

1. Validate attachment và `_skvn_shipment_id`.
2. Validate token với batch.
3. Verify resolved path nằm trong `uploads/shipments/*/original/`.
4. Gửi `private, no-store`, `X-Robots-Tag` và stream file.
5. Missing/invalid token trả 404, không redirect hoặc leak metadata.

Public/client routing có thể bọc helper này ở milestone sau; security boundary
không phụ thuộc UI.

## Blurred Thumbnail Contract

- Chỉ một output: `shipments/[batch-slug]/blurred-thumb.webp`.
- Source ưu tiên attachment `seal` đầu tiên.
- Nếu chưa có `seal`, dùng attachment đầu tiên bất kỳ.
- Khi ảnh `seal` xuất hiện sau fallback, regenerate cùng output path.
- Store path trong `_skvn_thumb_blurred`.
- Store source attachment ID nội bộ trong `_skvn_thumb_source_id`.

Thumbnail phải được blur thật server-side. CSS `blur(8px)` chỉ là presentation
bổ sung, không phải privacy boundary.

Backend:

1. GD WebP + repeated Gaussian blur.
2. Imagick fallback nếu GD WebP không available.
3. Nếu cả hai không available, log warning và không ghi meta thành công.

Output được resize tối đa 640 px chiều dài nhất, WebP quality thấp vừa đủ cho
public preview.

