#!/bin/bash

set -euo pipefail

source_dir="${PDF_TEMPLATE_SOURCE_DIR:-/opt/invoiceshelf/pdf-templates}"
target_dir="${PDF_TEMPLATE_TARGET_DIR:-/var/www/html/storage/app/templates/pdf}"

if [ ! -d "$source_dir" ]; then
    echo "Bundled PDF templates not found: $source_dir" >&2
    exit 1
fi

echo "**** Syncing bundled PDF templates ****"
mkdir -p "$target_dir"
cp -R "$source_dir"/. "$target_dir"/
