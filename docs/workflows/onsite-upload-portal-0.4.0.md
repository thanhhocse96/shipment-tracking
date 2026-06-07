# Onsite Test Checklist - Upload Portal 0.4.0

## Scope

Milestone `0.4.0` verifies routing, authorization shell and local Upload Portal
state only. Do not expect real batch creation, media upload, XHR progress or
client redirect; those start in `0.5.0`.

## Setup

1. Install and activate the packaged plugin artifact:

```bash
wp plugin install build/skvn-shipment-tracking.zip --force --activate
wp rewrite flush
```

2. Confirm version and capability:

```bash
wp plugin status skvn-shipment-tracking
wp cap list administrator | grep manage_skvn_tracking
```

3. Open a private/incognito browser for logged-out checks and a normal browser
for staff checks.

## Routing And Headers

Run:

```bash
curl -I https://example.com/tracking/upload/
curl -I https://example.com/tracking/sample-shipment/
```

Expected:

- `/tracking/upload/` returns `200` when logged out, not a redirect to
  `/wp-login.php`.
- `/tracking/upload/` includes `X-Robots-Tag: noindex, nofollow, noarchive`.
- `/tracking/upload/` includes private/no-store cache headers.
- `/tracking/sample-shipment/` includes `X-Robots-Tag: noindex, nofollow,
  noarchive`.
- `/tracking/sample-shipment/` renders only the safe foundation message and no
  private shipment metadata.

## Logged-Out Upload Route

Open:

```text
/tracking/upload/
```

Expected:

- Inline WordPress login form is visible on the page.
- Browser URL stays on `/tracking/upload/`.
- Page source does not contain `skvnTrackingUploadPortal`.
- No upload nonce or portal file-zone configuration is printed for logged-out
  visitors.

## Staff Authorization

Log in with an account that has `manage_skvn_tracking`, then open:

```text
/tracking/upload/
```

Expected:

- Metadata shell is visible.
- Fields are present:
  - Batch or folder name
  - Additional information
  - Client name
  - Container number
  - Closing date
  - Product type
- Four zones are visible:
  - Seal & Door Check
  - Temperature Monitoring
  - Cargo Rows
  - Uncategorized
- Sticky zone navigation links jump to each zone.
- Form contains a `skvn_tracking_nonce` field.
- Page source contains `skvnTrackingUploadPortal` only for the authorized staff
  request.

## Capability Denial

Log in with a non-staff account without `manage_skvn_tracking`, then open:

```text
/tracking/upload/
```

Expected:

- HTTP status is `403`.
- Access denied message is shown.
- No portal config, nonce-controlled upload shell or file controls are printed.

## Local File Interaction

Use small WebP, JPEG and PNG files.

Expected:

- Submit starts disabled.
- Adding one valid image enables submit and reset.
- Zone counters and total count update immediately.
- Preview thumbnails appear for selected images.
- First four previews show in the zone.
- Adding more than four images shows a `View more (+N)` button.
- The gallery modal opens, traps focus, closes by close button, backdrop and
  `Escape`, then returns focus to the opener.
- Removing a file updates counters and enables re-adding the same file.
- Reset removes all previews, clears errors and disables submit again.

## Validation

Try:

- A PDF or text file renamed normally.
- An image larger than 20 MB.
- The same valid image twice.

Expected:

- Invalid files are not added.
- Error list includes the original filename and a specific reason.
- Duplicate fingerprint is blocked within the current batch.
- Dismissing errors clears the error list without changing valid files.

## Zone Assignment

Expected:

- Dropping or selecting a file in Seal, Temperature or Cargo keeps that manual
  zone even if the filename suggests another category.
- Selecting from Uncategorized may use filename detection for first assignment.
- Changing the zone dropdown on a preview is treated as manual assignment.
- Dragging a preview to another zone moves it and updates both counters.

## Submit Boundary

After adding a valid image, click `Create draft`.

Expected:

- No network upload is attempted.
- No fake progress bar appears.
- A notice explains that the upload endpoint will be connected in `0.5.0`.
- The selected files and local previews remain in place.

## Leak Check

Inspect page source and browser devtools.

Expected:

- Public/logged-out HTML contains no private client name, container number,
  token or original attachment URL.
- Staff upload HTML contains no direct original attachment URL.
- Console has no JavaScript errors during picker, drag/drop, preview, remove,
  reset or modal use.
