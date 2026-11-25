#!/usr/bin/bash
set -euo pipefail

version="$1"
repo="$2"

if [ -z "$version" ];
then
    echo "usage: buildPackage.sh <version> [output-dir]" >&2
    exit 2
fi

if [ -z "$repo" ];
then
    repo="."
fi

mkdir -p "$repo"
tarball="$repo/v$version.tar.gz"

git archive --format=tar.gz -o "$tarball" HEAD

sha256sum "$tarball" | awk '{print $1}' > "$tarball.sha256"

echo "built $tarball"
echo "sha256 $(cat "$tarball.sha256")"
