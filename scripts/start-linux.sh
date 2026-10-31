#!/usr/bin/env bash
set -euo pipefail

if [[ "$(uname -s)" != Linux ]]; then
    printf '%s\n' 'Run this script in Linux or WSL.' >&2
    exit 1
fi
if [[ "$EUID" -eq 0 ]]; then
    printf '%s\n' 'Run the web server as your normal Linux user, not root.' >&2
    exit 1
fi
if ! command -v php >/dev/null 2>&1; then
    printf '%s\n' 'PHP CLI is missing. Install php-cli before starting the server.' >&2
    exit 1
fi

project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd -- "$project_dir"
export DETECTOR_PORT="${DETECTOR_PORT:-8766}"
export DETECTOR_RUNTIME_DIR="${DETECTOR_RUNTIME_DIR:-$project_dir/runtime/linux}"
export DETECTOR_ENABLE_SUDO=1
export DETECTOR_MODE="${DETECTOR_MODE:-preview}"

printf 'DetectorGUI (Linux): http://127.0.0.1:%s\n' "$DETECTOR_PORT"
printf 'Server user: %s. Check sudo is limited to /usr/bin/id -u.\n' "$(id -un)"
printf '%s\n' 'Press Ctrl+C to stop. Detector acquisition remains in preview mode by default.'
exec php -S "127.0.0.1:$DETECTOR_PORT" -t public public/router.php
