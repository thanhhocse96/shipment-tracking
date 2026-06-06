#!/usr/bin/env bash
set -euo pipefail

wp_root="/mnt/d/Github/minhhaifish"
content_file="/mnt/d/Github/skvn-marine/.local/frontpage-testing-1-content.html"

existing="$(
	wp post list \
		--path="$wp_root" \
		--post_type=page \
		--name=frontpage-testing-1 \
		--field=ID \
		--allow-root | head -1
)"

if [ -n "$existing" ]; then
	echo "EXISTING_PAGE_ID=$existing"
	exit 0
fi

wp post create \
	--path="$wp_root" \
	--post_type=page \
	--post_status=draft \
	--post_title="Frontpage Testing 1" \
	--post_name="frontpage-testing-1" \
	--post_content="$(cat "$content_file")" \
	--porcelain \
	--allow-root
