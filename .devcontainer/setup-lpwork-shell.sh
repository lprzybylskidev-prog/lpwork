#!/usr/bin/env bash
set -euo pipefail

BASHRC="${HOME}/.bashrc"
BIN_DIR="${HOME}/.local/bin"
BIN_PATH="${BIN_DIR}/lpwork"
WORKSPACE_LPWORK="/workspace/lpwork"
MARKER_START="# >>> lpwork shell helpers >>>"
MARKER_END="# <<< lpwork shell helpers <<<"

touch "${BASHRC}"
mkdir -p "${BIN_DIR}"

if [ -f "${WORKSPACE_LPWORK}" ]; then
    chmod +x "${WORKSPACE_LPWORK}"
    ln -sf "${WORKSPACE_LPWORK}" "${BIN_PATH}"
fi

sed -i "/^${MARKER_START}$/,/^${MARKER_END}$/d" "${BASHRC}"

cat >> "${BASHRC}" <<'BASH'

# >>> lpwork shell helpers >>>
case ":${PATH}:" in
    *":${HOME}/.local/bin:"*) ;;
    *) export PATH="${HOME}/.local/bin:${PATH}" ;;
esac

if command -v lpwork >/dev/null 2>&1 && [ -n "${BASH_VERSION:-}" ]; then
    source <(lpwork completion:generate bash 2>/dev/null || true)
fi
# <<< lpwork shell helpers <<<
BASH
