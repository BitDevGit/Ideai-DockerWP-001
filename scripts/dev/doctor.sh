#!/bin/bash
set -euo pipefail

# Doctor for the local WP dev environment.
# Checks:
# - docker compose services are up
# - ports 80/443 are bound
# - required hostnames resolve (via /etc/hosts)
#
# Usage:
#   ./scripts/dev/doctor.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DOMAINS_JSON="$ROOT_DIR/config/local-domains.json"
COMPOSE_FILE="$ROOT_DIR/docker-compose.flexible.yml"

if [[ ! -f "$DOMAINS_JSON" ]]; then
  echo "❌ Missing $DOMAINS_JSON"
  exit 1
fi

if ! command -v python3 >/dev/null 2>&1; then
  echo "❌ python3 is required for this script"
  exit 1
fi

REQUIRED_HOSTS=""
while IFS= read -r line; do
  [[ -z "$line" ]] && continue
  REQUIRED_HOSTS="${REQUIRED_HOSTS}${line}"$'\n'
done < <(python3 - <<PY
import json, pathlib
cfg = json.loads(pathlib.Path(r"$DOMAINS_JSON").read_text())
for s in cfg["sites"]:
    print(s["host"])
PY
)

echo "=== Local Dev Doctor ==="
echo ""

if ! command -v docker >/dev/null 2>&1; then
  echo "❌ docker not found"
  exit 1
fi

echo "## Docker containers"
if [[ -f "$COMPOSE_FILE" ]]; then
  (cd "$ROOT_DIR" && docker compose -f "$COMPOSE_FILE" ps) || true
else
  echo "⚠️  Missing $COMPOSE_FILE"
fi
echo ""

echo "## Port checks"
if command -v lsof >/dev/null 2>&1; then
  for p in 80 443; do
    if lsof -nP -iTCP:$p -sTCP:LISTEN >/dev/null 2>&1; then
      echo "✅ Port $p is listening"
    else
      echo "❌ Port $p is NOT listening"
    fi
  done
else
  echo "⚠️  lsof not found; skipping port checks"
fi
echo ""

echo "## Hostname resolution (via /etc/hosts)"
missing=""
while IFS= read -r h; do
  [[ -z "$h" ]] && continue
  if grep -qE "(^|\\s)$h(\\s|$)" /etc/hosts 2>/dev/null; then
    echo "✅ $h present in /etc/hosts"
  else
    echo "❌ $h missing from /etc/hosts"
    missing="${missing}${h}"$'\n'
  fi
done <<< "$REQUIRED_HOSTS"

if [[ -n "${missing//[$'\n']}" ]]; then
  echo ""
  echo "## Fix (copy/paste)"
  echo "Run this to add missing entries:"
  printf "sudo sh -c 'printf \"\\n"
  while IFS= read -r h; do
    [[ -z "$h" ]] && continue
    printf "127.0.0.1  %s\\n" "$h"
  done <<< "$missing"
  printf "\" >> /etc/hosts'\n"
  echo ""
  echo "Then flush DNS cache (macOS):"
  echo "  sudo dscacheutil -flushcache"
  echo "  sudo killall -HUP mDNSResponder"
  echo ""
else
  echo ""
  echo "✅ All required hosts are present."
fi


