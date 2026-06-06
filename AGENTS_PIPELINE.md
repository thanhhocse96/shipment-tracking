# AGENTS_PIPELINE.md — Image Pipeline & Thumbpress

> Load khi: Thumbpress hook, image upload, ALT text, blurred thumbnail task.

---

## Pipeline Flow

```
Upload → Thumbpress (WebP + optimize) → Plugin hook → Set ALT → Gán category → Gán batch meta
```

Plugin hook VÀO Thumbpress — không tự xử lý WebP.

## Thumbpress Hook — UNRESOLVED

Hook name chưa verified. Xem `TENSIONS_OPEN.md`.

Candidates cần inspect:
- `thumbpress_after_convert`
- `add_attachment` (WP native — cần verify timing vs Thumbpress)

Cách verify: dùng `add_action('all', function($tag) { error_log($tag); })` khi upload test image để capture hook sequence.

Không implement ALT text pipeline (milestone 0.2.0) trước khi resolve tension này.

## ALT Text — Fallback System

```
Priority 1: Staff nhập caption per ảnh       → dùng caption đó
Priority 2: Không có caption                  → [batch-slug] - [category] - [cleaned-filename]
```

### Cleaned filename rules

```
Strip extension
Replace _ và - thành space
Strip số thứ tự trong ngoặc → format lại
Lowercase

"seal cont (1).jpg"         →  "seal cont 1"
"data logger in carton (2)" →  "data logger in carton 2"
"28 (3)"                    →  "row 28 3"
```

### ALT output example

```
lot-2401-salmon-jp - seal & door check - seal cont 1
lot-2401-salmon-jp - temperature monitoring - data logger in carton 2
lot-2401-salmon-jp - cargo rows - row 28 3
```

## Blurred Thumbnail — 1 File Per Batch

```
KHÔNG blur tất cả ảnh.
CHỈ 1 blurred-thumb.webp per batch.

Source: ảnh đầu tiên của "Seal & Door Check"
        (fallback: ảnh đầu tiên bất kỳ nếu không có Seal & Door)
Output: wp-content/uploads/shipments/[batch-slug]/blurred-thumb.webp
```

## Folder Structure

```
wp-content/uploads/shipments/
└── lot-2401-salmon-jp/
    ├── original/              ← WebP gốc, KHÔNG public URL
    └── blurred-thumb.webp     ← public, dùng cho grid và public view
```

## Photo Category — Auto-detect

| Zone | Auto-detect từ filename |
|---|---|
| Seal & Door Check 🔒 | chứa "seal" hoặc "door" |
| Temperature Monitoring 🌡️ | chứa "data logger" hoặc "temp" |
| Cargo Rows 📦 | base name là số nguyên thuần |
| Uncategory | không match rule nào |

Priority: manual override (staff kéo vào zone) > auto-detect.

"Số thuần" → prefix "row-" tự động. "28 (3)" → label "Row 28 - 3".
