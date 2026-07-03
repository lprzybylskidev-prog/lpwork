#!/usr/bin/env sh
set -eu

LPWORK_RELEASE_ARCHIVE_URL="https://github.com/lprzybylskidev-prog/lpwork/archive/refs/tags/v1.0.0.zip"
LPWORK_RELEASE_ARCHIVE_PLACEHOLDER="__LPWORK_RELEASE_ARCHIVE_URL__"

usage() {
    printf '%s\n' "Usage: install-lpwork.sh <application-name> [target-parent-directory]"
}

fail() {
    printf '%s\n' "LPWork installer: $1" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "required command [$1] is not available."
}

if [ "$LPWORK_RELEASE_ARCHIVE_URL" = "$LPWORK_RELEASE_ARCHIVE_PLACEHOLDER" ] || [ -z "$LPWORK_RELEASE_ARCHIVE_URL" ]; then
    fail "no immutable LPWork release archive URL is configured. Fill LPWORK_RELEASE_ARCHIVE_URL with a tagged release archive before distributing this installer."
fi

case "$LPWORK_RELEASE_ARCHIVE_URL" in
    *"/archive/refs/tags/"*|*"releases/download/"*|*"codeload.github.com/"*"/tar.gz/refs/tags/"*|*"codeload.github.com/"*"/zip/refs/tags/"*) ;;
    *) fail "release archive URL must point at an immutable tag archive, not a moving branch or local source." ;;
esac

if [ "${1:-}" = "" ] || [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    usage
    exit 0
fi

APP_NAME=$1
TARGET_PARENT=${2:-$(pwd)}

case "$APP_NAME" in
    *"/"*|*"\\"*|""|"."|"..") fail "application name must be a single directory name." ;;
esac

require_command mktemp
require_command cp
require_command rm
require_command mkdir

TARGET_PARENT=$(cd "$TARGET_PARENT" 2>/dev/null && pwd) || fail "target parent directory does not exist."
TARGET_DIR="$TARGET_PARENT/$APP_NAME"

[ ! -e "$TARGET_DIR" ] || fail "target directory [$TARGET_DIR] already exists."

TMP_DIR=$(mktemp -d "${TMPDIR:-/tmp}/lpwork-install.XXXXXX")
cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT INT TERM

ARCHIVE="$TMP_DIR/lpwork-release"
EXTRACT_DIR="$TMP_DIR/extracted"
mkdir -p "$EXTRACT_DIR"

download() {
    if command -v curl >/dev/null 2>&1; then
        curl -fsSL "$LPWORK_RELEASE_ARCHIVE_URL" -o "$ARCHIVE"
        return
    fi

    if command -v wget >/dev/null 2>&1; then
        wget -q "$LPWORK_RELEASE_ARCHIVE_URL" -O "$ARCHIVE"
        return
    fi

    fail "install curl or wget to download the release archive."
}

extract_archive() {
    case "$LPWORK_RELEASE_ARCHIVE_URL" in
        *.tar.gz|*.tgz|*"codeload.github.com/"*"/tar.gz/"*)
            require_command tar
            tar -xzf "$ARCHIVE" -C "$EXTRACT_DIR"
            ;;
        *.zip|*"codeload.github.com/"*"/zip/"*|*"/archive/refs/tags/"*)
            require_command unzip
            unzip -q "$ARCHIVE" -d "$EXTRACT_DIR"
            ;;
        *)
            fail "release archive must be a .zip, .tar.gz, or GitHub tagged archive URL."
            ;;
    esac
}

release_root() {
    set -- "$EXTRACT_DIR"/*
    [ "$#" -eq 1 ] && [ -d "$1" ] || fail "release archive must contain one top-level project directory."
    printf '%s\n' "$1"
}

remove_path() {
    [ ! -e "$TARGET_DIR/$1" ] || rm -rf "$TARGET_DIR/$1"
}

prepare_application_snapshot() {
    mkdir -p "$TARGET_DIR"
    cp -R "$1"/. "$TARGET_DIR"/

    remove_path ".git"
    remove_path ".git/hooks"
    remove_path ".githooks"
    remove_path "hooks"
    remove_path "lpwork-roadmap"
    remove_path "AGENTS.md"
    remove_path "vendor"
    remove_path "node_modules"
    remove_path "installers"
    remove_path "storage/cache"
    remove_path "storage/log"
    remove_path "storage/playwright"
    remove_path "storage/test-reports"
    remove_path "storage/tmp"
    remove_path "storage/testing"
    remove_path "storage/database.sqlite"
    remove_path ".phpunit.cache"
    remove_path ".phpstan.cache"
    remove_path ".php-cs-fixer.cache"

    [ -f "$TARGET_DIR/docs/.AGENTS.md" ] || fail "release archive is missing docs/.AGENTS.md for generated application guidance."
    cp "$TARGET_DIR/docs/.AGENTS.md" "$TARGET_DIR/AGENTS.md"

    mkdir -p "$TARGET_DIR/storage/cache" "$TARGET_DIR/storage/log" "$TARGET_DIR/storage/framework" "$TARGET_DIR/storage/tmp"
}

download
extract_archive
prepare_application_snapshot "$(release_root)"

printf '%s\n' "LPWork application created at: $TARGET_DIR"
printf '%s\n' "Open it in VS Code and use the included devcontainer to install PHP, Composer, Node, npm, and browser tooling."
