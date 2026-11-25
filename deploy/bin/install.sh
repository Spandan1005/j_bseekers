#!/usr/bin/bash
set -euo pipefail

tarball="$1"
repo="$2"

here="$(dirname "$0")"
exclude="$here/../exclude.txt"

if [[ -z "$tarball" || -z "$repo" ]];
then
    echo "usage: install.sh <tarball> <repo>" >&2
    exit 2
fi

if [ ! -f "$tarball" ];
then
    echo "$tarball not found" >&2
    exit 2
fi

if [ ! -f "$exclude" ];
then
    echo "$exclude not found" >&2
    exit 2
fi

if [ ! -d "$repo" ];
then
    echo "$repo not a directory" >&2
    exit 2
fi

repo_path="$(cd "$repo" && pwd)"

case "$repo_path" in
	/|/home|/root|/usr|/etc|/var) echo "refusing to deploy onto $repo_path" >&2 && exit 2 ;;
esac

temp="/tmp/j_bseekers"
rm -rf "$temp"
mkdir -p "$temp"
tar -xzf "$tarball" -C "$temp"

rsync -a --delete --exclude-from="$exclude" "$temp"/ "$repo"/

rm -rf "$temp"

echo "install.sh: successful install of $(basename "$tarball") to $repo"
