#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
revision="${1:-HEAD}"
output="${2:-$repo_root/storage/app/property-release.tar.gz}"
work_dir="$(mktemp -d "${TMPDIR:-/tmp}/property-release.XXXXXX")"
release_dir="$work_dir/release"

cleanup() {
    rm -rf "$work_dir"
}

trap cleanup EXIT

cd "$repo_root"

if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Tracked changes must be committed before building a release." >&2
    exit 1
fi

revision="$(git rev-parse --verify "${revision}^{commit}")"

if [[ ! -f public/build/manifest.json ]]; then
    echo "Run the production frontend build before packaging." >&2
    exit 1
fi

mkdir -p "$release_dir"
git archive "$revision" | tar -xf - -C "$release_dir"

if [[ -e "$release_dir/public/build" ]]; then
    echo "The selected commit unexpectedly tracks public/build." >&2
    exit 1
fi

mkdir -p "$release_dir/public"
cp -R public/build "$release_dir/public/build"

# The web server must be able to read .htaccess and public assets. A restrictive
# local umask must never leak 0600 modes into the uploaded release.
find "$release_dir" -type d -exec chmod 755 {} +
find "$release_dir" -type f -exec chmod 644 {} +

mkdir -p "$(dirname "$output")"
tar -czf "$output" -C "$release_dir" .
chmod 600 "$output"

manifest_hash="$(shasum -a 256 "$release_dir/public/build/manifest.json" | awk '{print $1}')"
archive_size="$(wc -c < "$output" | tr -d ' ')"

printf 'revision=%s\narchive=%s\narchive_bytes=%s\nmanifest_sha256=%s\n' \
    "$revision" \
    "$output" \
    "$archive_size" \
    "$manifest_hash"
