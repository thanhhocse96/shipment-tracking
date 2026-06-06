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
```

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
Original files không có public URL trừ khi valid token trong request.
Staff portal login: inline form, KHÔNG redirect `/wp-login.php`.

## Không Đụng Vào

- `themes/generatepress/` — tuyệt đối không
- `skvn-marine` theme — không sửa
- WooCommerce data
- Thumbpress internals — chỉ hook vào, không sửa
