# AGENTS_ENV.md — Environment & Runtime

> Load khi: WP-CLI command, build, deploy, symlink, dev server task.
> Đọc `.local/ENVIRONMENT.md` trước khi chạy lệnh local nếu file đó tồn tại.

---

## Stack

```
WordPress           core platform
GeneratePress       parent theme — KHÔNG đụng vào
skvn-marine         child theme — không sửa, chỉ follow visual contract
Thumbpress          image optimization — plugin chỉ hook vào
PHP                 8.0 (shared hosting constraint)
```

## Plugin Location

```
wp-content/plugins/skvn-shipment-tracking/
```

## Upload Path

```
wp-content/uploads/shipments/
```

Original files không được serve qua public URL trừ khi valid token.

## WP-CLI Common Commands

```bash
# Activate plugin
wp plugin activate skvn-shipment-tracking

# Check plugin status
wp plugin status skvn-shipment-tracking

# Flush rewrite rules (sau khi thêm CPT hoặc custom routing)
wp rewrite flush

# Check if CPT registered
wp post-type list

# Test attachment meta
wp post meta get <attachment_id> _skvn_shipment_id
```

## Dev Notes

PHP 8.0 — tránh syntax mới hơn 8.0 (no readonly properties without constructor promotion từ 8.1+, no enums từ 8.1+).

Shared hosting: không có root access. WP-CLI có thể không available — kiểm tra với human trước khi assume.

## Runtime Boundaries

Plugin không chạy code nào ngoài WordPress request lifecycle.
Không có cron job hay background process trong milestone 0.1.0.
n8n automation → sau 1.0.0, không implement bây giờ.
