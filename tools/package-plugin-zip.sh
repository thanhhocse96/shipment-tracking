#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
source_dir="$repo_root/build/skvn-shipment-tracking"
output_zip="$repo_root/build/skvn-shipment-tracking.zip"

if [[ ! -d "$source_dir" ]]; then
	echo "Missing plugin artifact: $source_dir" >&2
	echo "Run node tools/build-deploy-artifact.mjs first." >&2
	exit 1
fi

rm -f "$output_zip"

if command -v zip >/dev/null 2>&1; then
	(
		cd "$(dirname "$source_dir")"
		zip -qr "$output_zip" "$(basename "$source_dir")"
	)
elif command -v python3 >/dev/null 2>&1; then
	python3 - "$source_dir" "$output_zip" <<'PY'
import os
import sys
import zipfile

source_dir, output_zip = sys.argv[1], sys.argv[2]
root_name = os.path.basename(source_dir.rstrip(os.sep))

with zipfile.ZipFile(output_zip, "w", zipfile.ZIP_DEFLATED) as archive:
    for current_root, _, files in os.walk(source_dir):
        for filename in files:
            full_path = os.path.join(current_root, filename)
            rel_path = os.path.relpath(full_path, source_dir)
            archive.write(full_path, os.path.join(root_name, rel_path))
PY
else
	echo "Missing zip tool. Install zip or ensure python3 is available." >&2
	exit 1
fi

echo "Created: $output_zip"
