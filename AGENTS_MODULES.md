# AGENTS_MODULES.md — Module Rules (Surfaces)

> Load section liên quan đến task — không cần load cả file.

---

## Media Library Tabs

Inject 2 tab vào `/wp-admin/upload.php`:

```
[ Post - Pages ]  [ Shipment Tracking ]
```

Default active: **Post - Pages**.

Filter dùng `ajax_query_attachments_args`. Meta key phân biệt: `_skvn_shipment_id`.

Tab **Post - Pages** → attachments KHÔNG CÓ `_skvn_shipment_id`.
Tab **Shipment Tracking** → attachments CÓ `_skvn_shipment_id`.

Rủi ro: WP update thay đổi Media Library UI markup → JS selector cần update. Ghi chú selector đang dùng trong comment code.

---

## Staff Upload Portal — /tracking/upload/

4 drop zones: Seal & Door Check, Temperature Monitoring, Cargo Rows, Uncategory.

Form fields:

```
Tên batch/thư mục
Thông tin thêm
Tên khách hàng
Số container
Ngày đóng hàng
Loại sản phẩm
```

Desktop portal có sticky zone navigation. Mobile layout chưa có wireframe,
không tự quyết định contract mới.

Upload flow (tóm tắt):
1. Staff điền form metadata
2. Drag ảnh vào zone
3. Submit → plugin tạo CPT post, upload qua WP media pipeline
4. Thumbpress xử lý WebP
5. Plugin hook → set ALT, gán category, gán `_skvn_shipment_id`
6. Generate blurred-thumb.webp (1 cái)
7. Generate 32-char token
8. Redirect sang client view

Login check: `is_user_logged_in()`. Không login → form login inline, KHÔNG redirect `/wp-login.php`.

---

## Public Grid — /tracking/

```
INDEX: YES (SEO surface chính)
Layout: 4 col desktop / 3 col 1024px / 2 col tablet
Load: infinite scroll
Card: blurred thumbnail + batch title + product type + năm
Client name: KHÔNG hiển thị
Bấm card: redirect /contact/ — không mở batch
```

Grid grouping còn OPEN: chronological hay group theo product type.

---

## Public View — /tracking/[slug]/

```
NOINDEX
Tất cả ảnh: blurred thumbnail
Metadata: redacted (xem Section 4 của spec)
Bấm ảnh: redirect /contact/
Không lightbox, không share button
```

---

## Client View — /tracking/[slug]/?token=xxx

```
NOINDEX
Valid token: full resolution, client name visible
Invalid token: redirect sang public view

Sidebar: 4 category tabs
Gallery: ảnh theo category được chọn
Share button: server-side render, chỉ staff thấy
Lightbox: thumbnail strip + keyboard nav + mobile swipe
```

Lightbox cross-category hay chỉ current category còn OPEN.

---

## Redact Logic

```
Client name:      "Yamamoto Trading Co."  →  "*** *** ***"
Container number: "CSNU1234567"           →  "Redacted"
Closing date:     "2024-01-15"            →  "2024"
```

---

## SEO / Index Rules

```
/tracking/                → INDEX
/tracking/[slug]/         → NOINDEX
/tracking/[slug]/?token=  → NOINDEX
/tracking/upload/         → NOINDEX
```

## Scope Boundaries

Plugin không sở hữu nội dung `/contact/`, Quote Flow, CF7/CFDB7 hay n8n.
Public card/image chỉ redirect sang `/contact/`.
