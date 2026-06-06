# AGENTS.md — skvn-shipment-tracking

> Đọc file này đầu tiên mỗi session.
> Đây là agent protocol cho plugin `skvn-shipment-tracking`.

---

## 1. Startup Checklist

Mỗi lần bắt đầu task, thực hiện theo thứ tự:

1. Đọc `AGENTS.md` (file này) — xác định current milestone
2. Đọc `.context/MILESTONES.md` — xác nhận scope và checklist của milestone hiện tại
3. Đọc `.context/TENSIONS_OPEN.md` — luôn đọc đầy đủ, không filter
4. Đọc module context liên quan đến task (xem Reference Files bên dưới)
5. Đọc `.local/ENVIRONMENT.md` nếu tồn tại — trước khi chạy bất kỳ lệnh local nào

Source hierarchy khi tài liệu mâu thuẫn:

1. Human-provided plugin fact/design document
2. `AGENTS.md` + `AGENTS_*.md`
3. `.context/` cho milestone/tension state hiện tại
4. `docs/` và tooling kế thừa

Tài liệu copy từ `skvn-marine` không được override identity, prefix, scope hoặc
ownership boundary của plugin này.

---

## 2. Current Milestone

```
0.1.0 — Media Library Tab + Thumbpress Hook
```

Source of truth: `.context/MILESTONES.md`

---

## 3. Reference Files — Đọc khi cần, không load mặc định

1. Architecture, prefix, PHP security rules: `AGENTS_ARCH.md`
2. Image pipeline, Thumbpress, ALT text: `AGENTS_PIPELINE.md`
3. Module-specific rules (upload portal, public grid, client view): `AGENTS_MODULES.md`
4. Environment, WP-CLI, dev server: `AGENTS_ENV.md`

---

## 4. Workflow — Mỗi task

```
Bước 0 — Xác định ref files cần load

| Task liên quan đến...                       | Load thêm             |
|---------------------------------------------|-----------------------|
| PHP file mới, hook, filter, CPT             | AGENTS_ARCH.md        |
| Thumbpress hook, image upload, ALT text     | AGENTS_PIPELINE.md    |
| Media Library tab JS/PHP                    | AGENTS_ARCH.md        |
| Upload portal, public grid, client view     | AGENTS_MODULES.md     |
| WP-CLI, build, deploy, symlink              | AGENTS_ENV.md         |
| Không rõ loại hoặc cross-module             | AGENTS_ARCH.md trước  |

Rule bắt buộc:
- Task đề cập tên surface cụ thể → load section đó trong AGENTS_MODULES.md, không load cả file
- Không chắc có cần load không → load, đừng skip

Bước 1 — Đọc TENSIONS_OPEN.md
Bước 2 — Đọc module context nếu cần
Bước 3 — Implement
Bước 4 — Verification gate (xem Section 7)
```

---

## 5. Architecture Boundaries — Tóm tắt

Plugin slug: `skvn-shipment-tracking`
PHP prefix: `skvn_tracking_`
CSS prefix: `skvn-tracking-`
Text domain: `skvn-shipment-tracking`
Upload folder: `wp-content/uploads/shipments/`
CPT: `skvn_shipment` (`public: false`, `rewrite: false`)

Không đụng vào:
- `themes/generatepress/` — tuyệt đối không
- `skvn-marine` theme — không sửa, chỉ follow visual contract
- WooCommerce data
- External plugins (CF7, Rank Math, Polylang, Thumbpress internals)
- Trang `/contact/` và Quote Flow

WebP conversion là của Thumbpress — plugin không tự xử lý.

---

## 6. Invariants Bắt Buộc

```
[manual] sections trong .context/*.md không bao giờ được overwrite.
load stdout phải clean — warning/error ra stderr.
Agent không tự archive tension entries.
Agent không tự promote milestone.
Agent không tự approve scope mới.
PHP prefix skvn_tracking_ — không dùng prefix khác.
Thumbpress xử lý WebP — plugin chỉ hook vào pipeline, không tự convert.
blurred-thumb.webp: CHỈ 1 file per batch — không blur toàn bộ ảnh.
Share button: server-side render PHP — không CSS hide, không JS toggle.
Original files không được có public URL trừ khi request có valid token.
Public tracking surfaces không được expose client name.
`/tracking/` là surface duy nhất INDEX; các URL tracking còn lại NOINDEX.
```

---

## 7. Verification Gate — Cuối mỗi task

```bash
# Kiểm tra consistency
python ../context-mapping/cli.py check-consistency .

# Kiểm tra PHP syntax
php -l <file-vừa-sửa>.php

# Kiểm tra prefix đúng
grep -rn "function [^s]" *.php | grep -v "skvn_tracking_"
```

Nếu bất kỳ check nào fail → fix trước khi commit.

---

## 8. Tension Detection

Khi thấy dấu hiệu sau → tạo entry trong `.context/TENSIONS_OPEN.md`:

- Đang cân nhắc hai approach khác nhau
- Có invariant conflict với requirement mới
- Scope chưa rõ (feature thuộc milestone này hay milestone sau?)
- Thumbpress hook behavior chưa verified

Tạo entry xong → hỏi human quyết định. Không tự resolve.

---

## 9. Self-Check Cuối Session

Trước khi kết thúc, tự hỏi:

- Vừa đưa ra quyết định nào cần capture vào `.context/`?
- Có tension nào mới phát hiện không?
- Có constraint cũ nào hết hiệu lực không?
- Verification gate pass chưa?
