# Upload Portal Contracts

## Status

Version `1.0`, introduced for milestone `0.4.0`.

These schemas define the future network boundary. Milestone `0.4.0` does not
register either endpoint and does not simulate a successful upload.

## Create Draft

Future authenticated request:

```text
POST /wp-json/skvn-tracking/v1/drafts
```

Request:

```json
{
  "schema_version": "1.0",
  "nonce": "<wordpress-rest-nonce>",
  "metadata": {
    "batch_title": "string",
    "batch_notes": "string",
    "client_name": "string",
    "container_number": "string",
    "closing_date": "YYYY-MM-DD",
    "product_type": "string"
  },
  "file_count": 12
}
```

Success response:

```json
{
  "schema_version": "1.0",
  "draft_id": 123,
  "slug": "shipment-slug",
  "status": "draft"
}
```

## Upload File

Future authenticated multipart request:

```text
POST /wp-json/skvn-tracking/v1/drafts/<draft_id>/files
```

Fields:

```text
schema_version = 1.0
nonce          = <wordpress-rest-nonce>
client_file_id = <portal generated id>
zone           = seal | temperature | cargo | uncategorized
manual_zone    = true | false
file           = <binary>
```

Success response:

```json
{
  "schema_version": "1.0",
  "client_file_id": "string",
  "attachment_id": 456,
  "zone": "seal",
  "status": "success"
}
```

Error response:

```json
{
  "schema_version": "1.0",
  "client_file_id": "string",
  "filename": "original-name.jpg",
  "code": "invalid_mime",
  "message": "Safe staff-facing message.",
  "status": "error",
  "retryable": false
}
```

## Server Requirements

- Require an authenticated user with `manage_skvn_tracking`.
- Verify the request nonce.
- Revalidate MIME, extension and the 20 MB size limit.
- Sanitize every metadata value.
- Treat `zone`, filename and client MIME as untrusted.
- Preserve explicit manual assignment over filename detection.
- Keep the shipment in `draft` after partial failure.
- Return no token until the full upload workflow succeeds.
