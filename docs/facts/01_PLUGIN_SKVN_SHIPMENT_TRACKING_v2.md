# PLUGIN — skvn-shipment-tracking
# Spec v2 — Tổng hợp từ conversation review

> Tài liệu này kế thừa v1 và bổ sung toàn bộ quyết định từ design review session.
> Đọc file này trước khi bắt đầu bất kỳ task nào liên quan đến plugin này.

---

## 1. Mục đích

Plugin quản lý ảnh tracking lô hàng xuất nhập khẩu cho B2B seafood export company.

Dual purpose:
- **Operations**: Staff upload ảnh → generate share link → gửi khách xem lô hàng
- **SEO**: Public gallery page indexable → thu traffic → convert sang lead

---

## 2. Stack & Dependencies

```
WordPress           → core platform
GeneratePress       → parent theme (KHÔNG được đụng vào)
skvn-marine         → child theme (visual system)
ThumbPress          → WebP conversion và image optimization
                      Plugin tracking hook VÀO ThumbPress, không tự xử lý WebP
```

Plugin slug:        `skvn-shipment-tracking`
PHP prefix:         `skvn_tracking_`
CSS prefix:         `skvn-tracking-`
Text domain:        `skvn-shipment-tracking`
Upload folder:      `wp-content/uploads/shipments/`

### Frontend stack — quyết định từ review

```
Upload portal (desktop):  Vanilla TypeScript
                          Lý do: cross-zone drag state cần shared class property,
                          không phù hợp với Alpine component boundary

Public surfaces:          PHP server-render + TypeScript
                          Nút "Load more" gọi REST API, không auto infinite scroll
                          Vanilla TS cho lightbox và share popup

Tailwind MVP:             WindPress (đã có trong stack, không thêm dependency)
Tailwind full version:    Self-contained CSS build riêng trong plugin
                          Migrate khi plugin cần chạy độc lập với WindPress
```

### Cấu trúc JS

```
assets/js/
├── upload-portal/
│   ├── UploadPortal.ts      ← orchestrator
│   ├── DropZone.ts          ← single zone logic
│   ├── ImagePreview.ts      ← preview render (desktop only)
│   ├── FilenameParser.ts    ← auto-detect category
│   ├── FileCounter.ts       ← counter + error display (desktop + mobile)
│   └── SubmitPipeline.ts    ← form + upload
├── lightbox/
│   └── LightboxController.ts
└── share/
    └── SharePopup.ts
```

---

## 3. Surfaces — 4 URLs, 1 Plugin

```
/tracking/upload/              Staff portal   — tạo batch, upload ảnh, 4 zones
/tracking/                     Public grid    — load more, blur thumbnail, INDEX
/tracking/[slug]/              Public view    — blur, redacted metadata, NOINDEX
/tracking/[slug]/?token=xxx    Client view    — full resolution, share button
```

### SEO / Index rules

```
/tracking/                → INDEX (mục đích SEO chính)
/tracking/[slug]/         → NOINDEX (public view, không có token)
/tracking/[slug]/?token=  → NOINDEX (client view, private)
/tracking/upload/         → NOINDEX (staff portal)
```

### Interaction rules — Public surfaces

```
Public grid  → bấm vào card      → redirect /contact/
Public view  → bấm vào ảnh blur  → redirect /contact/
```

### Cache rules — quyết định từ review

```
/tracking/                → CACHE — SEO surface chính
/tracking/[slug]/         → CACHE — public, noindex, blurred only, không có data nhạy cảm
/tracking/[slug]/?token=  → KHÔNG CACHE — private, mỗi user thấy khác nhau
/tracking/upload/         → KHÔNG CACHE — session-dependent
```

`.htaccess` / LiteSpeed cache config:

```apache
SetEnvIf Request_URI "token=" NO_CACHE
SetEnvIf Request_URI "tracking/upload" NO_CACHE
```

### Sitemap

```
/tracking/                → Rank Math auto-detect (WordPress page thật)
/tracking/[slug]/         → NOINDEX, Rank Math tự exclude
Không cần custom sitemap integration
```

---

## 4. Custom Post Type — skvn_shipment

```
post_type:    skvn_shipment
public:       false (không dùng default WP template)
show_ui:      true  (hiện trong WP admin)
show_in_menu: true
rewrite:      false (plugin tự handle routing)
```

### Meta fields per batch

| Meta key | Mô tả | Public hiển thị |
|---|---|---|
| `_skvn_batch_title` | Tên thư mục gốc | Có (full) |
| `_skvn_batch_notes` | Thông tin thêm | Không |
| `_skvn_client_name` | Tên khách hàng | Redacted |
| `_skvn_container_number` | Số container | Redacted |
| `_skvn_closing_date` | Ngày đóng hàng | Chỉ hiện năm |
| `_skvn_product_type` | Loại sản phẩm | Có (để group grid) |
| `_skvn_token` | 32-char hex random | Không (dùng trong URL) |
| `_skvn_thumb_blurred` | Path blurred thumbnail | Không (dùng internal) |
| `_skvn_batch_status` | draft / published / archived | Không |
| `_skvn_last_viewed` | Timestamp lần cuối client xem | Không (admin only) |
| `_skvn_view_count` | Số lượt xem có token | Không (admin only) |
| `_skvn_public_snapshot` | Projection đã redact, dùng cho public render/API | Có — source public duy nhất |

### Redact logic

```
Client name:      "Yamamoto Trading Co."  →  "Redacted"
Container number: "CSNU1234567"           →  "Redacted"
Closing date:     "2024-01-15"            →  "2024"   (chỉ hiện năm)
```

### Public projection — quyết định cuối

Redacted data được generate và lưu sẵn khi batch được tạo/cập nhật/publish.
Public request không load private meta rồi mới redact.

```php
_skvn_public_snapshot = [
    'title'            => 'LOT-2401-SALMON-JP',
    'product_type'     => 'Frozen Grouper Fish Fillet',
    'client_name'      => 'Redacted',
    'container_number' => 'Redacted',
    'closing_year'     => '2024',
    'thumb_url'        => 'https://.../blurred-thumb.webp',
    'contact_url'      => '/contact/',
];
```

Rules:

- Public grid, public view và public REST API chỉ đọc `_skvn_public_snapshot`.
- Không trả private meta trong public query, REST response, DOM, preload hay JS state.
- Snapshot phải regenerate khi public-safe source field hoặc blurred thumb thay đổi.
- Client view có valid token đọc private meta trực tiếp sau authorization.
- Nếu snapshot thiếu/invalid, không fallback sang private meta; skip batch hoặc render
  safe empty state và log warning.

---

## 5. Batch Lifecycle — quyết định từ review

```
DRAFT → PUBLISHED → ARCHIVED → DELETED
```

| Status | Grid /tracking/ | Token link | Ghi chú |
|---|---|---|---|
| DRAFT | Không xuất hiện | Hoạt động | Staff review trước khi public |
| PUBLISHED | Xuất hiện | Hoạt động | Indexable |
| ARCHIVED | Không xuất hiện | Vẫn hoạt động | Link cũ vẫn dùng được |
| DELETED | Không | Không | Files xóa theo |

Field lưu: `_skvn_batch_status` với values `draft | published | archived`.
Không dùng WP native post status để tránh nhầm lẫn với `archived` không có sẵn.

Public grid sort chronological, newest first. Product type là metadata/filter
future, không group trong MVP.

### Validation bắt buộc

Batch phải có ít nhất 1 ảnh mới được tạo. Nút submit disabled nếu tất cả zones trống.
`/tracking/[slug]/` không tồn tại nếu batch không có ảnh.

---

## 6. Admin List View — quyết định từ review

Dùng WP default CPT admin list, không build custom admin page.
Sales không cần access — đây là internal ops workflow.

### Custom columns

```
[ ] | Tên lô hàng | Loại sản phẩm | Năm | Status | Link | Lần cuối xem | Ngày tạo
```

- **Link column**: nút "Copy link" — copy token URL vào clipboard, không hiện raw token
- **Lần cuối xem**: "2 ngày trước (3 lượt)" từ `_skvn_last_viewed` + `_skvn_view_count`
- **Status column**: badge DRAFT / PUBLISHED / ARCHIVED

### Capability

```php
'capability_type' => 'post',
'capabilities'    => [
    'edit_post'   => 'manage_skvn_tracking',
    'delete_post' => 'manage_skvn_tracking',
],
```

---

## 7. Photo Categories — Auto-detect + Manual Override

### 4 zones trong upload portal

| Zone | Emoji | Auto-detect rule từ filename |
|---|---|---|
| Seal & Door Check | 🔒 | filename chứa "seal" hoặc "door" |
| Temperature Monitoring | 🌡️ | filename chứa "data logger" hoặc "temp" |
| Cargo Rows | 📦 | filename là số thuần (VD: "28", "29") |
| Uncategory | — | Không match rule nào |

### Priority

```
Staff kéo ảnh vào zone cụ thể     → dùng zone đó (manual override)
Staff dùng zone Uncategory         → auto-detect từ filename
```

### Filename examples thực tế

```
28 (3).jpg                    →  Cargo Rows (row 28, ảnh 3)
data logger in carton (1).jpg →  Temperature Monitoring
right door.jpg                →  Seal & Door Check
seal cont (1).jpg             →  Seal & Door Check
```

---

## 8. ALT Text — Fallback System

```
Priority 1: Staff nhập caption per ảnh   →  dùng caption đó
Priority 2: Không có caption             →  [batch-slug] - [category] - [cleaned-filename]
```

### Cleaned filename

```
"seal cont (1).jpg"           →  "seal cont 1"
"data logger in carton (2)"   →  "data logger in carton 2"
"28 (3)"                      →  "row 28 3"
```

### ALT example output

```
lot-2401-salmon-jp - seal & door check - seal cont 1
lot-2401-salmon-jp - temperature monitoring - data logger in carton 2
lot-2401-salmon-jp - cargo rows - row 28 3
```

### Conflict với theme media.php

Plugin set ALT tại `add_attachment` priority 5.
Theme `skvn_marine_auto_set_image_alt_from_title()` chạy ở priority 10.
Plugin chạy trước → theme chỉ fill khi empty → không conflict.

---

## 9. Image Pipeline

```
Upload → ThumbPress (WebP + optimize) → add_attachment priority 5
       → Guard MIME/path → Set ALT → Gán category → Gán batch meta
       → Generate blurred thumbnail (WP Cron defer nếu cần)
```

### ThumbPress hook — quyết định từ review

```php
add_action(
    'add_attachment',
    'skvn_tracking_process_uploaded_attachment',
    5   // trước theme ALT hook ở priority 10
);

function skvn_tracking_process_uploaded_attachment( int $attachment_id ): void {
    // Guard 1: chỉ xử lý WebP
    if ( 'image/webp' !== get_post_mime_type( $attachment_id ) ) {
        return;
    }

    // Guard 2: chỉ xử lý ảnh trong thư mục shipments
    $file = get_attached_file( $attachment_id );
    if ( ! str_contains( $file, '/shipments/' ) ) {
        return;
    }

    // Pipeline: set ALT, gán category, gán batch meta
}
```

### Fail-fast conditions

```
ThumbPress chưa active              → log warning, skip pipeline, không crash
thumbpress_webp_on_upload chưa bật  → log warning, skip pipeline
Attachment sau upload không phải WebP → skip silently (guard tự handle)
```

### Fallback hook

`thumbpress_file_meta_refreshed` — dùng khi staff chạy manual/bulk conversion sau upload.
Nhận attachment ID, cùng pipeline với `add_attachment`.

### Acceptance test

Upload ảnh thật trên runtime có ThumbPress active để verify `add_attachment` nhận đúng WebP MIME.
Bắt buộc trước khi ship 0.1.0, không phải blocker để viết code.

### Blurred thumbnail — 1 file per batch

```
Source:  Ảnh đầu tiên của Seal & Door Check
         (hoặc ảnh đầu tiên bất kỳ nếu không có Seal & Door)
Output:  wp-content/uploads/shipments/[batch-slug]/blurred-thumb.webp
CSS:     filter: blur(8px); transform: scale(1.05);
         scale(1.05) để che blur edge ở viền ảnh
```

### Folder structure

```
wp-content/uploads/shipments/
└── lot-2401-salmon-jp/
    ├── original/              ← ảnh WebP gốc (không serve public URL)
    └── blurred-thumb.webp     ← 1 file public
```

---

## 10. Access Control

### Token-based access

```
Token: 32-char random hex, generate khi tạo batch
URL:   /tracking/[slug]/?token=abc123...

Valid token   → full resolution, metadata hiển thị, NOINDEX header
Invalid token → redirect sang public view (blur, redacted)
No token      → public view
```

### Client view tracking — server-side

```php
// Khi valid token request đến
update_post_meta( $batch_id, '_skvn_last_viewed', current_time('mysql') );
$count = (int) get_post_meta( $batch_id, '_skvn_view_count', true );
update_post_meta( $batch_id, '_skvn_view_count', $count + 1 );
```

GA4 / Jetpack dùng cho aggregate traffic `/tracking/` — không dùng để track client cụ thể.

### Share button

```
Render: Server-side PHP

is_user_logged_in() && current_user_can('manage_skvn_tracking')
    → render share button
    → click → mở share popup

Khách hàng (không login)
    → button không có trong DOM
```

### Staff portal access

```
/tracking/upload/ → check WP session
Đã login          → hiện portal
Chưa login        → form login inline (KHÔNG redirect /wp-login.php)
```

---

## 11. Gutenberg Integration — /tracking/ public grid

### MVP — Option A: Page template hybrid

Tạo WordPress page slug `/tracking/`, assign page template riêng của plugin.

```php
add_filter( 'theme_page_templates', function( $templates ) {
    $templates['skvn-tracking-grid.php'] = 'Shipment Tracking Grid';
    return $templates;
});
```

Template render order:

```php
the_content();               // Gutenberg blocks: hero, SEO copy, intro
skvn_tracking_render_grid(); // PHP render CPT grid bên dưới
```

Editor viết SEO content bình thường trong Gutenberg.
Grid tự động append phía dưới do template xử lý.
Editor không thấy grid trong editor — chỉ thấy trên frontend.

### Future — Option B: Custom block skvn-tracking/grid

Bổ sung sau MVP khi cần layout flexibility.

**Block attributes:**

```json
{
    "columns": { "type": "number", "default": 4 },
    "showBatchTitle": { "type": "boolean", "default": true },
    "showProductType": { "type": "boolean", "default": true },
    "showYear": { "type": "boolean", "default": true },
    "showPhotoCount": { "type": "boolean", "default": false }
}
```

**Sidebar controls:**

```
Panel: "Grid Settings"
  Số cột: ○ 3  ● 4  ○ 5   ← RadioControl (không RangeControl)

Panel: "Hiển thị thông tin"
  ☑ Tên lô hàng       (warning SEO nếu tắt)
  ☑ Loại sản phẩm
  ☑ Năm đóng hàng
  ☐ Số lượng ảnh      (default off)
```

**CSS columns:**

```css
.skvn-tracking-grid--col-3 { grid-template-columns: repeat(3, 1fr); }
.skvn-tracking-grid--col-4 { grid-template-columns: repeat(4, 1fr); }
.skvn-tracking-grid--col-5 { grid-template-columns: repeat(5, 1fr); }

@media (max-width: 1024px) {
    .skvn-tracking-grid--col-4,
    .skvn-tracking-grid--col-5 { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .skvn-tracking-grid { grid-template-columns: repeat(2, 1fr); }
}
```

Mobile luôn collapse về 2 col bất kể editor chọn gì.

---

## 12. Public Grid — /tracking/

### Card design — một trạng thái duy nhất

Grid luôn hiển thị blurred thumbnail + Redacted. Không có token logic trên grid.
FOMO driver: visitor thấy có data nhưng không xem được → contact.

```
┌─────────────────────────┐
│                         │
│     Blurred thumbnail   │
│   filter: blur(8px)     │
│   transform: scale(1.05)│
│                         │
├─────────────────────────┤
│ Khách hàng  [Redacted]  │
│ Container   [Redacted]  │
│ Ngày đóng   [Redacted]  │
└─────────────────────────┘
       Click → /contact/
```

Không có mixed states. Grid cacheable 100%.
Card và REST response lấy hoàn toàn từ `_skvn_public_snapshot`.

### Loading states

```
Initial load:   Skeleton cards
                1 hàng đầy đủ (toàn bộ chiều rộng)
                1 hàng tiếp theo hiện 2 cards
                Shimmer animation

Pagination:     "Load more" button — không auto-trigger khi scroll
                Position: căn giữa, bên dưới grid

End of list:    Text nhỏ căn giữa: "All shipments displayed"
                Không có icon, không có border
```

### Empty state

Khi không có batch published:

```
Grid area: block Gutenberg render fallback text
           "Dữ liệu đang được cập nhật"
Editorial content SEO phía trên vẫn render bình thường
```

### REST API endpoint

```
GET /wp-json/skvn-tracking/v1/batches?page=2&per_page=12&status=published
```

Response shape:

```json
{
    "batches": [
        {
            "title": "LOT-2401-SALMON-JP",
            "product_type": "Frozen Grouper Fish Fillet",
            "year": "2024",
            "thumb_url": "https://.../blurred-thumb.webp",
            "contact_url": "/contact/"
        }
    ],
    "has_more": true,
    "next_page": 3
}
```

```php
register_rest_route( 'skvn-tracking/v1', '/batches', [
    'methods'             => 'GET',
    'callback'            => 'skvn_tracking_rest_get_batches',
    'permission_callback' => '__return_true',
    'args'                => [
        'page'     => [ 'type' => 'integer', 'default' => 1, 'minimum' => 1 ],
        'per_page' => [ 'type' => 'integer', 'default' => 12, 'maximum' => 24 ],
    ],
]);
```

---

## 13. Public View — /tracking/[slug]/

```
Tất cả ảnh: blurred thumbnail
Metadata:   Redacted theo quy tắc Section 4
Bấm ảnh:   Redirect /contact/ (không lightbox)
Share:      Không có
NOINDEX:    Header bắt buộc
```

---

## 14. Client View — /tracking/[slug]/?token=xxx

```
Layout desktop:
┌─ Sidebar ──────┬─ Gallery ───────────────────────────┐
│ 🔒 Seal & Door │ [img][img][img][img]  File name      │
│ 🌡️ Temperature │ [img][img][img][img]  File name      │
│ 📦 Cargo Rows  │                                      │
│    Uncategory  │                                      │
└────────────────┴─────────────────────────────────────┘
                  📤  ← chỉ staff thấy (server-render)
```

### Lightbox — quyết định từ review

```
Trigger:        Click ảnh
Navigation:     Prev / Next button
                Arrow Left / Right keyboard (bắt buộc)
Zoom:           Nút + và - đơn giản
Save:           Native browser right-click (không có nút download)
Close:          ESC keyboard (bắt buộc)
Performance:    Lazy load — chỉ load ảnh khi navigate đến
                Không load tất cả 30 ảnh cùng lúc
```

### Share popup — quyết định từ review

```
Trigger:        Nút "Share" (chỉ staff thấy)
UI:             Popup nhỏ
                Header: "Link chia sẻ lô hàng"
                Input text: full token URL
                            double-click để select all
                Nút "Copy" → copy clipboard → đổi text thành "Đã copy"
Close:          Click ngoài popup hoặc nút X
MVP scope:      Không có email integration
```

---

## 15. Staff Upload Portal — /tracking/upload/

### Form fields

```
Tên lô hàng       [________________]
Thông tin thêm    [________________]
Tên khách hàng    [________________]
Số container      [________________]
Ngày đóng hàng    [________________]
Loại sản phẩm     [________________]
```

### 4 Drop zones (desktop)

```
🔒 Seal & Door Check
[Drop zone — drag ảnh vào đây]
[img][img][img][img] + counter + error list

🌡️ Temperature Monitoring
[Drop zone]
[img][img][img][img] + counter + error list

📦 Cargo Rows
[Drop zone]
[img][img][img][img] + counter + error list

Uncategory
[Drop zone — fallback, auto-detect sau]
[img][img][img][img] + counter + error list

[Sticky nav: jump to zone khi scroll]
```

### Desktop counter + error display

Counter đặt phía dưới drop zone, error list expand bên dưới counter.

States theo thứ tự:

```
Chưa có ảnh:   "Chưa có ảnh nào"      (muted text)
Đã chọn:       "X ảnh đã chọn"
Đang upload:   "Y / X đã upload"       (update realtime từng file)
Hoàn thành:    "X ảnh đã upload"
Có lỗi:        "Y / X thành công"  +  error list
```

Error list format:

```
✕ IMG_20260101_092312.jpg
  File quá lớn (max 20MB)

✕ container_back.png
  Định dạng không hỗ trợ
```

Tên file gốc + lý do lỗi cụ thể. Không dùng error code hay generic message.

### Upload flow

```
1. Staff điền form metadata
2. Drag ảnh vào đúng zone (hoặc bỏ vào Uncategory)
3. Submit (disabled nếu tất cả zones trống)
4. Plugin:
   a. Tạo skvn_shipment post với status DRAFT
   b. Upload ảnh qua WP media pipeline (ThumbPress xử lý WebP)
   c. Set ALT text theo fallback system (add_attachment priority 5)
   d. Gán _skvn_shipment_id meta cho mỗi attachment
   e. Auto-detect category cho ảnh trong Uncategory zone
   f. Pick ảnh đầu tiên từ Seal & Door → generate blurred-thumb.webp
   g. Generate 32-char token
   h. Set status DRAFT
5. Redirect sang client view của batch vừa tạo
6. Staff thấy 📤 share button → mở share popup → copy link → gửi khách
```

---

## 16. Mobile Upload Portal — quyết định từ review

MVP: Desktop only. Build CSS class structure sẵn để thêm mobile ở milestone sau.

```css
.skvn-tracking-upload--mobile { /* thêm vào milestone sau */ }
```

### Mobile milestone — Option C

```
Layout:    Dọc thay vì 4 zones ngang
Input:     Native file picker — <input type="file" accept="image/*">
           Không có drag and drop
Preview:   KHÔNG render thumbnail preview
Counter:   BẮT BUỘC — hiển thị text counter + error list
```

### Mobile counter + error display (bắt buộc ngay cả khi không có thumbnail)

States:

```
Chưa chọn:     "Chưa có ảnh nào"
Đã chọn:       "X ảnh đã chọn"
Đang upload:   "Y / X đã upload"
Hoàn thành:    "X ảnh đã upload"
Có lỗi:        "Y / X thành công"  +  error list
```

Error list format giống desktop:

```
✕ IMG_20260101_092312.jpg
  File quá lớn (max 20MB)
```

Implementation: feature detection, không UA sniffing.

---

## 17. Media Library Integration

```
Plugin inject tab vào /wp-admin/upload.php:
[ Post - Pages ]  [ Shipment Tracking ]

Default active: Post - Pages
```

Filter logic: `ajax_query_attachments_args` — lọc attachment theo `_skvn_shipment_id`.
Script chỉ enqueue trên `upload.php`.

Rủi ro: WP update có thể thay đổi Media Library UI markup → cần smoke test sau mỗi WP core update.

---

## 18. PHP Security — Bắt buộc

```php
// Input
$batch_title = isset($_POST['batch_title'])
    ? sanitize_text_field(wp_unslash($_POST['batch_title']))
    : '';

$token = isset($_GET['token'])
    ? sanitize_text_field(wp_unslash($_GET['token']))
    : '';

// Output
echo esc_html($batch_title);
echo esc_attr($token);
echo esc_url($share_url);
echo wp_kses_post($content);

// Nonce cho mọi form
wp_nonce_field('skvn_tracking_upload', 'skvn_tracking_nonce');

// Token compare — timing-safe
hash_equals($stored_token, $request_token);
```

---

## 19. Activation / Deactivation Hooks — quyết định từ review

Thiếu activation hook → toàn bộ `/tracking/*` trả 404 ngay sau cài đặt.

```php
// Activation — bắt buộc
register_activation_hook: đăng ký rewrite rules
                          flush_rewrite_rules()
                          tạo wp-content/uploads/shipments/ nếu chưa có
                          tạo custom capability manage_skvn_tracking

// Deactivation
register_deactivation_hook: flush_rewrite_rules()
                            KHÔNG xóa data

// Uninstall
Mặc định: giữ CPT posts, meta, options và uploads/shipments/
Không register destructive uninstall handler tự động.
Data purge là explicit admin action riêng:
capability + nonce + confirmation, xóa posts/meta/files có chủ đích.
```

---

## 20. Scale & Infrastructure

```
Batch size:     ~30 ảnh WebP per batch
Frequency:      2-3 batch/tháng
Storage/năm:    ~1GB (30MB × 3 × 12)
Hosting:        10GB — không cần archive hay lifecycle management
PHP:            8.0 (shared hosting constraint)
```

---

## 21. Scope Boundaries

### Plugin này LÀM

- CPT `skvn_shipment` và meta management
- Batch lifecycle (DRAFT / PUBLISHED / ARCHIVED)
- Staff upload portal với 4 zones (desktop)
- Token generation và access control
- Client view tracking (last viewed + view count)
- Media Library tab injection
- ALT text fallback system
- Blurred thumbnail generation (1 per batch)
- Public grid với load more
- Public view với redirect
- Client view với lightbox
- Share popup (copy-to-clipboard)
- Share button server-side render
- REST API endpoint cho grid pagination
- Routing cho tất cả `/tracking/*` URLs
- Gutenberg page template cho `/tracking/`
- WP admin list view với custom columns

### Plugin này KHÔNG LÀM

- WebP conversion → ThumbPress lo
- Quote form → CF7/CFDB7 sau 0.6.0
- Trang `/contact/` content → WordPress page, staff edit trong Gutenberg
- n8n automation → sau 1.0.0
- Email integration cho share link → defer
- Mobile upload portal → milestone sau MVP
- Archive/zip lifecycle → không cần (hosting 10GB)

### Không đụng vào

- `themes/generatepress/` — tuyệt đối không
- `skvn-marine` theme — không sửa, chỉ follow visual contract
- WooCommerce data — không liên quan
- External plugins (CF7, Rank Math, Polylang)

---

## 22. Blocking Items — Phải xong trước khi build

```
1. Activation/deactivation hooks
   → 404 toàn bộ /tracking/* nếu thiếu flush_rewrite_rules()

2. Cache exclusion rules
   → Security incident nếu token URL bị cache

3. REST API endpoint spec
   → Load more không build được nếu chưa có endpoint

4. Acceptance test ThumbPress hook
   → Upload ảnh thật trên runtime có ThumbPress active
   → Verify add_attachment nhận đúng WebP MIME
   → Bắt buộc trước khi ship 0.1.0
```

---

## 23. Decisions Closed

- Public grid: chronological, newest first; không group trong MVP.
- Uninstall: mặc định giữ posts/meta/files. Không destructive delete tự động.
- Data purge chỉ chạy qua explicit admin action có capability, nonce và confirmation.
- Token: không expiry trong MVP.
- Staff có thể rotate/regenerate token; token cũ mất hiệu lực ngay.
- Public data: generate/store `_skvn_public_snapshot`; public code không đọc private meta.
- Q3 (cũ): Lightbox navigate → cross-category, prev/next toàn bộ ảnh trong batch
- Q4 (cũ): Blur level → medium: `filter: blur(8px); transform: scale(1.05)`
- Q5 (cũ): ThumbPress hook → `add_attachment` priority 5

---

## 24. Design Language

Hướng: **Cold Documentation**
Reference: frame.io (UX pattern — dark, share link, client view, no login)
Secondary: flexport.com (B2B logistics, trustworthy)

---

## 25. Wireframe Status

| Screen | Status |
|---|---|
| Staff upload portal (desktop) | ✅ Có wireframe, đã review |
| Client view (desktop) | ✅ Có wireframe, đã review |
| Public SEO grid | ✅ Có wireframe, đã review |
| Media Library tab | ✅ Có wireframe, đã review |
| Share popup | ✅ Quyết định từ review, chưa wireframe chính thức |
| Mobile (tất cả screens) | ⏳ Defer sang milestone sau MVP |
| Login screen staff | ⏳ Chưa wireframe |
