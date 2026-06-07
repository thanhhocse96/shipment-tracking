# AGENTS_PIPELINE.md — Image Pipeline & Thumbpress

> Load khi: Thumbpress hook, image upload, ALT text, blurred thumbnail task.

---

## Pipeline Flow

```
Upload → Thumbpress (WebP + optimize) → Plugin hook → Set ALT → Gán category → Gán batch meta
```

Plugin hook VÀO Thumbpress — không tự xử lý WebP.

## Thumbpress Hook

Source review chốt `add_attachment` priority 5 cho convert-on-upload:

```php
add_action( 'add_attachment', 'skvn_tracking_process_uploaded_attachment', 5 );
```

Guard MIME `image/webp` và path `/shipments/`. Fallback cho manual/bulk
conversion: `thumbpress_file_meta_refreshed`.

Vẫn bắt buộc upload acceptance test trên runtime có ThumbPress trước khi ship
milestone 0.1.0.

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
Display: blur(8px) + scale(1.05)
```

## Folder Structure

```
wp-content/uploads/shipments/
└── lot-2401-salmon-jp/
    ├── original/              ← WebP gốc, KHÔNG public URL
    └── blurred-thumb.webp     ← public, dùng cho grid và public view
```

Expected operating scale:

```
~30 WebP/batch
2-3 batches/month
~1 GB/year
10 GB hosting
```

Không implement archive/zip lifecycle trong V1.

## Photo Category — Auto-detect

| Zone | Auto-detect từ filename |
|---|---|
| Seal & Door Check 🔒 | chứa "seal" hoặc "door" |
| Temperature Monitoring 🌡️ | chứa "data logger" hoặc "temp" |
| Cargo Rows 📦 | base name là số nguyên thuần |
| Uncategory | không match rule nào |

Priority: manual override (staff kéo vào zone) > auto-detect.

"Số thuần" → prefix "row-" tự động. "28 (3)" → label "Row 28 - 3".

## Confirmed UI Decisions

- Mobile upload defer khỏi MVP; native picker, no preview, counter/error required.
- Lightbox navigation cross-category trong toàn batch.
- Blurred display level: `blur(8px)` + `scale(1.05)`.
