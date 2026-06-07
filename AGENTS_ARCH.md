# AGENTS_ARCH.md — Architecture & PHP Rules

> Load khi: tạo PHP file mới, hook/filter mới, CPT, hoặc rename/prefix task.

---

## Plugin Identity

```
Plugin slug:    skvn-shipment-tracking
PHP prefix:     skvn_tracking_
CSS prefix:     skvn-tracking-
Text domain:    skvn-shipment-tracking
Upload folder:  wp-content/uploads/shipments/
CPT:            skvn_shipment
PHP version:    8.0 (shared hosting constraint)
Frontend:       Vanilla TypeScript
```

## Frontend Architecture

- Không dùng React, JSX hoặc UI framework runtime.
- TypeScript compile/bundle thành JavaScript để WordPress enqueue.
- PHP render initial markup và mọi element liên quan authorization.
- TypeScript chỉ quản lý interaction: tab switch, upload UX, load more,
  clipboard, gallery/lightbox navigation và mobile gestures.
- Không đưa token/private image URL vào public DOM hoặc JS payload.
- Tailwind MVP dùng WindPress đã có trong site; không thêm dependency.
- Khi tách CSS build riêng, disable/scope preflight để không ảnh hưởng WP admin/theme.

## CPT Contract

```php
post_type: skvn_shipment
public:    false
rewrite:   false
```

Plugin tự handle routing cho `/tracking/*`; không dùng default WordPress
single/archive template của CPT.

### Batch Meta

| Meta key | Purpose | Public exposure |
|---|---|---|
| `_skvn_batch_title` | Tên batch/thư mục | Full |
| `_skvn_batch_notes` | Ghi chú nội bộ | Không |
| `_skvn_client_name` | Tên khách hàng | Redacted |
| `_skvn_container_number` | Số container | Redacted |
| `_skvn_closing_date` | Ngày đóng hàng | Chỉ năm |
| `_skvn_product_type` | Loại sản phẩm | Full |
| `_skvn_token` | Token access 32-char hex | Không |
| `_skvn_thumb_blurred` | Internal blurred thumb path | Không |
| `_skvn_batch_status` | draft / published / archived | Không |
| `_skvn_last_viewed` | Last valid-token view | Không |
| `_skvn_view_count` | Valid-token view count | Không |
| `_skvn_public_snapshot` | Stored redacted public projection | Public source duy nhất |

Attachment thuộc batch phải có `_skvn_shipment_id`.

## Public Data Boundary

Public grid, public view và public REST API chỉ được đọc
`_skvn_public_snapshot`. Không load `_skvn_client_name`,
`_skvn_container_number`, `_skvn_token` hoặc private attachment URLs trong
public request rồi redact sau.

Snapshot regenerate khi batch title, product type, closing date, blurred thumb
hoặc public labels thay đổi. Nếu snapshot thiếu/invalid, fail closed: skip hoặc
safe empty state; không fallback sang private meta.

## PHP Security — Bắt buộc mỗi khi xử lý input/output

```php
// Input sanitization
$batch_title = isset($_POST['batch_title'])
    ? sanitize_text_field(wp_unslash($_POST['batch_title']))
    : '';

$token = isset($_GET['token'])
    ? sanitize_text_field(wp_unslash($_GET['token']))
    : '';

// Output escaping
echo esc_html($batch_title);
echo esc_attr($token);
echo esc_url($share_url);
echo wp_kses_post($content);

// Nonce cho mọi form
wp_nonce_field('skvn_tracking_upload', 'skvn_tracking_nonce');
```

Không bao giờ echo biến POST/GET trực tiếp. Không bao giờ bỏ qua nonce check.

## Media Library Tab — Architecture

Filter logic dùng `ajax_query_attachments_args`. Script chỉ enqueue trên `upload.php`.

```php
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'upload.php') return;
    // enqueue script
});
```

Tab default: **Post - Pages** (không phải Shipment Tracking).

## ALT Text Hook Priority

`skvn_marine_auto_set_image_alt_from_title()` trong theme fill ALT khi empty.
Plugin PHẢI set ALT trước khi theme hook chạy, hoặc dùng higher priority.
Không để hai hook conflict — xem AGENTS_PIPELINE.md.

## Share Button — Server-Side Only

```php
// Đúng
if (is_user_logged_in() && current_user_can('manage_skvn_tracking')) {
    echo '<button class="skvn-tracking-share">📤</button>';
}

// Sai — không dùng CSS hide hay JS toggle
```

## Access Control

Token: 32-char random hex. Generate khi tạo batch.
Token không expiry trong MVP; staff có thể rotate để revoke link cũ.
Original files không có public URL trừ khi valid token trong request.
Staff portal login: inline form, KHÔNG redirect `/wp-login.php`.

Valid token → client view full resolution, client name visible, NOINDEX.
Invalid token → redirect sang public view, không trả partial private data.

Uninstall mặc định giữ data và shipment files. Purge chỉ qua explicit admin
action có capability, nonce và confirmation.

## Không Đụng Vào

- `themes/generatepress/` — tuyệt đối không
- `skvn-marine` theme — không sửa
- WooCommerce data
- Thumbpress internals — chỉ hook vào, không sửa
- Trang `/contact/` và Quote Flow — WordPress/site scope khác
