# PLUGIN — skvn-shipment-tracking

> Tài liệu thiết kế và scope. Đọc file này trước khi bắt đầu bất kỳ task nào liên quan đến plugin này.

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
Thumbpress          → WebP conversion và image optimization
                      Plugin tracking hook VÀO Thumbpress, không tự xử lý WebP
Frontend behavior   → Vanilla TypeScript, không React / UI framework runtime
```

Plugin slug:        `skvn-shipment-tracking`
PHP prefix:         `skvn_tracking_`
CSS prefix:         `skvn-tracking-`
Text domain:        `skvn-shipment-tracking`
Upload folder:      `wp-content/uploads/shipments/`

### Frontend implementation decision

```
Language:       Vanilla TypeScript
React:          KHÔNG dùng
Runtime:        Browser DOM APIs + WordPress APIs
Build output:   JavaScript compile/bundle để WordPress enqueue
```

Tailwind chưa phải dependency bắt buộc. Nếu được approve riêng, chỉ dùng ở
build-time và phải scope/preflight-safe để không ảnh hưởng WordPress admin hoặc
`skvn-marine`.

---

## 3. Surfaces — 4 URLs, 1 Plugin

```
/tracking/upload/              Staff portal   — tạo batch, upload ảnh, 4 zones
/tracking/                     Public grid    — infinite scroll, blur thumbnail, index
/tracking/[slug]/              Public view    — blur, redacted metadata, noindex
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
Public grid  → bấm vào card     → redirect /contact/ (không mở batch)
Public view  → bấm vào ảnh blur → redirect /contact/ (không lightbox)
```

Trang `/contact/` phải giải thích:
- Shipment tracking process là gì
- COA (Certificate of Analysis) là gì
- Contact form / Request a Quote (thuộc scope Quote Flow 0.6.0, không build trong plugin này)

---

## 4. Custom Post Type — skvn_shipment

```
post_type:    skvn_shipment
public:       false (không dùng default WP template)
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

### Redact logic

```
Client name:      "Yamamoto Trading Co."  →  "*** *** ***"   (mask hoàn toàn)
Container number: "CSNU1234567"           →  "Redacted"
Closing date:     "2024-01-15"            →  "2024"          (chỉ hiện năm)
```

---

## 5. Photo Categories — Auto-detect + Manual Override

### 4 zones trong upload portal

| Zone | Emoji | Auto-detect rule từ filename |
|---|---|---|
| Seal & Door Check | 🔒 | filename chứa "seal" hoặc "door" |
| Temperature Monitoring | 🌡️ | filename chứa "data logger" hoặc "temp" |
| Cargo Rows | 📦 | filename là số thuần (VD: "28", "29") |
| Uncategory | — | Không match rule nào |

### Priority

```
Staff kéo ảnh vào zone cụ thể  →  dùng zone đó (manual override)
Staff không kéo / dùng zone Uncategory  →  auto-detect từ filename
```

### Filename examples thực tế

```
28 (3).jpg                    →  Cargo Rows (row 28, ảnh 3)
28 (4).jpg                    →  Cargo Rows
data logger in carton (1).jpg →  Temperature Monitoring
data logger in carton (2).jpg →  Temperature Monitoring
right door.jpg                →  Seal & Door Check
seal cont (1).jpg             →  Seal & Door Check
seal cont (2).jpg             →  Seal & Door Check
seal cont (3).jpg             →  Seal & Door Check
```

### Filename parse — "số thuần" = Cargo Row

```
Base name là số nguyên  →  prefix "row-" tự động
"28 (3)"  →  label "Row 28 - 3"
```

---

## 6. ALT Text — Fallback System

```
Priority 1: Staff nhập caption per ảnh        →  dùng caption đó
Priority 2: Không có caption                   →  [batch-slug] - [category] - [cleaned-filename]
```

### Cleaned filename

```
Strip extension
Replace _ và - thành space
Strip số thứ tự trong ngoặc → giữ nguyên hoặc format lại
Lowercase

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

`skvn_marine_auto_set_image_alt_from_title()` trong theme chỉ fill ALT khi empty.
Plugin phải set ALT **trước** khi theme hook chạy, hoặc set với higher priority.
Không để hai hook tranh nhau.

---

## 7. Image Pipeline

```
Upload → Thumbpress (WebP + optimize) → Plugin hook → Set ALT → Gán category → Gán batch meta
```

### Blurred thumbnail — 1 file per batch

```
KHÔNG blur tất cả 30 ảnh.
CHỈ generate 1 blurred thumbnail per batch.

Source: ảnh đầu tiên của category "Seal & Door Check"
        (hoặc ảnh đầu tiên bất kỳ nếu không có Seal & Door)
Output: wp-content/uploads/shipments/[batch-slug]/blurred-thumb.webp

Dùng cho:
  - Card trong public grid /tracking/
  - Header image trong public view /tracking/[slug]/
```

### Folder structure

```
wp-content/uploads/shipments/
└── lot-2401-salmon-jp/
    ├── original/              ← 30 ảnh WebP gốc (không public URL)
    └── blurred-thumb.webp     ← 1 file public
```

Original files **không được serve qua public URL** trừ khi request có valid token.

---

## 8. Access Control

### Token-based access

```
Token: 32-char random hex, generate khi tạo batch
URL:   /tracking/[slug]/?token=abc123...

Valid token   →  full resolution, client name visible, noindex header
Invalid token →  redirect sang public view (blur, redacted)
No token      →  public view
```

### Share button

```
Render: Server-side PHP (không CSS hide, không JS toggle)

is_user_logged_in() && current_user_can('manage_skvn_tracking')
    → render <button class="skvn-tracking-share">📤</button>
    → click → copy full token URL vào clipboard

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

## 9. Media Library Integration

### Tab injection

Plugin inject 2 tabs vào `/wp-admin/upload.php`:

```
[ Post - Pages ]  [ Shipment Tracking ]
```

Default active: **Post - Pages**

### Filter logic

```php
add_filter( 'ajax_query_attachments_args', function( $query ) {
    $tab = isset($_REQUEST['skvn_tab']) ? $_REQUEST['skvn_tab'] : 'posts';

    if ( $tab === 'posts' ) {
        $query['meta_query'] = [[
            'key'     => '_skvn_shipment_id',
            'compare' => 'NOT EXISTS'
        ]];
    } elseif ( $tab === 'shipment' ) {
        $query['meta_query'] = [[
            'key'     => '_skvn_shipment_id',
            'compare' => 'EXISTS'
        ]];
    }

    return $query;
});
```

Script chỉ enqueue trên `upload.php`:

```php
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook !== 'upload.php' ) return;
    wp_enqueue_script( 'skvn-tracking-media-tabs', ... );
});
```

### Rủi ro

WP update có thể thay đổi Media Library UI markup → JS selector cần update. Không thường xuyên nhưng cần biết.

---

## 10. Public Grid — /tracking/

```
Layout:     Grid 4 col (desktop 1280px+) / 3 col (1024px) / 2 col (tablet)
Load:       Infinite scroll
Card:       Blurred thumbnail + batch title + product type + năm đóng hàng
            Client name: KHÔNG hiển thị
            Metadata: Redacted theo quy tắc Section 4
Bấm card:   Redirect /contact/ (không mở batch)
Index:      YES — đây là surface SEO chính
```

### Card metadata hiển thị

```
[Blurred thumbnail]
LOT-2401-SALMON-JP
Frozen Grouper Fish Fillet
2024
```

---

## 11. Public View — /tracking/[slug]/

```
Giống Client View về layout nhưng:
- Tất cả ảnh hiển thị dưới dạng blurred thumbnail
- Metadata redacted (xem Section 4)
- Bấm ảnh → redirect /contact/
- Không có lightbox
- Không có share button
- NOINDEX header
```

---

## 12. Client View — /tracking/[slug]/?token=xxx

```
Layout desktop:
┌─ Sidebar ──────┬─ Gallery ───────────────────────────┐
│ 🔒 Seal & Door │ [img][img][img][img]  File name      │
│ 🌡️ Temperature │ [img][img][img][img]  File name      │
│ 📦 Cargo Rows  │                                      │
│    Uncategory  │                                      │
└────────────────┴─────────────────────────────────────┘
                  📤  ← chỉ staff thấy (server-render)

Bấm ảnh → Lightbox:
┌─────────────────────────────────────────┐
│              [Full image]               │
│                                         │
│  [thumb][thumb][thumb][thumb]  (strip)  │
│  Tên thư mục | 🔒 Seal & Door Check    │
└─────────────────────────────────────────┘
```

### Lightbox requirements

- Thumbnail strip navigation
- Footer: filename + category label
- Keyboard navigation (arrow keys)
- Mobile: swipe gesture

---

## 13. Staff Upload Portal — /tracking/upload/

```
┌─ Form ──────────────────────────────────────┐
│  Tên thư mục       [________________]       │
│  Thông tin thêm    [________________]       │
│  Tên khách hàng    [________________]       │
│  Số container      [________________]       │
│  Ngày đóng hàng    [________________]       │
│  Loại sản phẩm     [________________]       │
│                                             │
│  🔒 Seal & Door Check                       │
│  [Drop zone - drag ảnh vào đây]             │
│  [img][img][img][img]                       │
│                                             │
│  🌡️ Temperature Monitoring                  │
│  [Drop zone]                                │
│  [img][img][img][img]                       │
│                                             │
│  📦 Cargo Rows                              │
│  [Drop zone]                                │
│  [img][img][img][img]                       │
│                                             │
│  Uncategory                                 │
│  [Drop zone - fallback, auto-detect sau]    │
│  [img][img][img][img]                       │
│                                             │
│  [Sticky nav: jump to zone khi scroll]      │
└─────────────────────────────────────────────┘
```

### Upload flow

```
1. Staff điền form metadata
2. Drag ảnh vào đúng zone (hoặc bỏ vào Uncategory)
3. Submit
4. Plugin:
   a. Tạo skvn_shipment post
   b. Upload ảnh qua WP media pipeline (Thumbpress xử lý WebP)
   c. Set ALT text theo fallback system
   d. Gán _skvn_shipment_id meta cho mỗi attachment
   e. Auto-detect category cho ảnh trong Uncategory zone
   f. Pick ảnh đầu tiên từ Seal & Door → generate blurred-thumb.webp
   g. Generate 32-char token
5. Redirect sang client view của batch vừa tạo
6. Staff thấy 📤 share button → copy link → gửi khách
```

---

## 14. PHP Security — Bắt buộc

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
```

---

## 15. Scope Boundaries

### Plugin này LÀM

- CPT `skvn_shipment` và meta management
- Staff upload portal với 4 zones
- Token generation và access control
- Media Library tab injection
- ALT text fallback system
- Blurred thumbnail generation (1 per batch)
- Public grid với infinite scroll
- Public view với redirect
- Client view với lightbox
- Share button server-side render
- Routing cho tất cả `/tracking/*` URLs

### Plugin này KHÔNG LÀM

- WebP conversion → Thumbpress lo
- Quote form → CF7/CFDB7 sau 0.6.0
- Trang `/contact/` content → WordPress page, staff edit trong Gutenberg
- n8n automation → sau 1.0.0
- Archive/zip lifecycle → không cần (hosting 10GB, ~1GB/năm)
- Subdomain → không cần ở V1

### Không đụng vào

- `themes/generatepress/` — tuyệt đối không
- `skvn-marine` theme — không sửa, chỉ follow visual contract
- WooCommerce data — không liên quan
- External plugins (CF7, Rank Math, Polylang)

---

## 16. Scale & Infrastructure

```
Batch size:     ~30 ảnh WebP per batch
Frequency:      2-3 batch/tháng
Storage/năm:    ~1GB (30MB × 3 × 12)
Hosting:        10GB — không cần archive hay lifecycle management
PHP:            8.0 (shared hosting constraint)
```

---

## 17. Open Questions (chưa quyết định)

| # | Câu hỏi | Ghi chú |
|---|---|---|
| 1 | Public grid: chronological hay group by product type? | Option B (group) tốt hơn SEO nhưng cần field product type |
| 2 | Mobile layout cho client view và public grid | Chưa wireframe |
| 3 | Lightbox: navigate cross-category hay chỉ trong category? | Chưa quyết định |
| 4 | Blur level cho blurred thumbnail | Để sau |
| 5 | Thumbpress hook cụ thể để biết khi WebP xong | Cần verify trước khi build pipeline |

---

## 18. Wireframe Status

| Screen | Status |
|---|---|
| Sale Site upload portal (desktop) | ✅ Có wireframe, đã review |
| Sale + Client view (desktop) | ✅ Có wireframe, đã review |
| Public SEO grid + Request a Quote | ✅ Có wireframe, đã review |
| Media Library tab | ✅ Có wireframe, đã review |
| Mobile (tất cả screens) | ⏳ Chưa wireframe |
| Login screen staff | ⏳ Chưa wireframe |

---

## 19. Design Language

Hướng: **Cold Documentation**
Reference: frame.io (UX pattern gần nhất — dark, share link, client view, no login)
Secondary: flexport.com (B2B logistics, trustworthy)

Chưa có Tailwind/HTML artifact từ MetaAI. Brief đã chuẩn bị sẵn để paste.

Frontend implementation đã chốt: Vanilla TypeScript, không React.
