#!/usr/bin/env bash
set -euo pipefail

WP_ROOT="/mnt/d/Github/minhhaifish"
CONTENT_FILE="/mnt/d/Github/skvn-marine/.local/pattern-ui-test-content.html"
SLUG="pattern-ui-test-0-2-0"
TITLE="Pattern UI Test 0.2.0"

existing="$(wp --path="$WP_ROOT" --allow-root post list --post_type=page --name="$SLUG" --field=ID | head -1)"

if [ -n "$existing" ]; then
	wp --path="$WP_ROOT" --allow-root post update "$existing" "$CONTENT_FILE" \
		--post_title="$TITLE" \
		--post_name="$SLUG" \
		--post_status=publish >/dev/null
	echo "UPDATED:$existing"
else
	created="$(wp --path="$WP_ROOT" --allow-root post create "$CONTENT_FILE" \
		--post_type=page \
		--post_title="$TITLE" \
		--post_name="$SLUG" \
		--post_status=publish \
		--porcelain)"
	echo "CREATED:$created"
fi
