#!/bin/bash
set -euo pipefail

# hosts-sync.sh (localwp)
# Purpose:
# - Ensure /etc/hosts contains the right base domains for this repo
# - Optionally add site2 subdomains (for subdomain multisite)
# - Keep changes isolated between marker lines so it's safe and repeatable ("tidy")
#
# Usage:
#   curl -fsSL https://localhost/hosts-sync.sh | sudo bash
#   curl -fsSL https://localhost/hosts-sync.sh | sudo bash -s -- sub1 sub2 demo
#
# Notes:
# - Requires sudo/root.
# - /etc/hosts does not support wildcards; pass each subdomain label you want.

if [[ -z "${BASH_VERSION:-}" ]]; then
  echo "❌ This script must be run with bash."
  exit 1
fi

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  echo "❌ This script must be run as root (use sudo)."
  exit 1
fi

HOSTS_FILE="/etc/hosts"
BEGIN_MARKER="# BEGIN IDEAI LOCALWP"
END_MARKER="# END IDEAI LOCALWP"

BASE_DOMAIN_1="site1.localwp"
BASE_DOMAIN_2="site2.localwp"
BASE_DOMAIN_3="site3.localwp"

BASE_IP="127.0.0.1"

sanitize_label() {
  # Only allow safe hostname labels: a-z 0-9 and hyphen, must start/end alnum, 1..63 chars.
  local s="$1"
  s="$(echo "$s" | tr '[:upper:]' '[:lower:]')"
  if [[ ! "$s" =~ ^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$ ]]; then
    return 1
  fi
  printf "%s" "$s"
}

# Ensure SUBS is always defined even under strict modes / older shells.
declare -a SUBS
SUBS=()
for raw in "$@"; do
  raw="${raw//,/ }"
  for part in $raw; do
    [[ -z "$part" ]] && continue
    if label="$(sanitize_label "$part")"; then
      SUBS+=("$label")
    else
      echo "⚠️  Skipping invalid subdomain label: $part"
    fi
  done
done

# De-dup labels
# Guard array reads against environments where nounset treats empty arrays strangely.
set +u
if (( ${#SUBS[@]} > 0 )); then
  uniq_subs=()
  seen=" "
  for s in "${SUBS[@]}"; do
    if [[ "$seen" != *" $s "* ]]; then
      uniq_subs+=("$s")
      seen="$seen$s "
    fi
  done
  SUBS=("${uniq_subs[@]}")
fi
set -u

tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT

{
  # Copy /etc/hosts without existing managed block
  python3 - <<PY > "$tmp"
import pathlib, re
hosts_path = pathlib.Path("$HOSTS_FILE")
s = hosts_path.read_text() if hosts_path.exists() else ""
begin = re.escape("$BEGIN_MARKER")
end = re.escape("$END_MARKER")
s = re.sub(rf"(?ms)^({begin})\\n.*?^({end})\\n?", "", s)
print(s, end="" if s.endswith("\\n") or s == "" else "\\n")
PY

  # Append new managed block
  {
    echo "$BEGIN_MARKER"
    echo "# Managed by vendor/local-dev-dashboard/dashboard/hosts-sync.sh (safe to re-run)"
    echo "$BASE_IP  $BASE_DOMAIN_1"
    echo "$BASE_IP  $BASE_DOMAIN_2"
    echo "$BASE_IP  $BASE_DOMAIN_3"
    set +u
    for s in "${SUBS[@]}"; do
      echo "$BASE_IP  $s.$BASE_DOMAIN_2"
    done
    set -u
    echo "$END_MARKER"
  } >> "$tmp"
}

cp "$tmp" "$HOSTS_FILE"

# Flush DNS cache (macOS)
if command -v dscacheutil >/dev/null 2>&1; then
  dscacheutil -flushcache || true
fi
if command -v killall >/dev/null 2>&1; then
  killall -HUP mDNSResponder 2>/dev/null || true
fi

echo "✅ Updated /etc/hosts for localwp domains."
echo "   - $BASE_DOMAIN_1"
echo "   - $BASE_DOMAIN_2"
echo "   - $BASE_DOMAIN_3"
set +u
if (( ${#SUBS[@]} > 0 )); then
  echo "   - Added ${#SUBS[@]} subdomain(s) under $BASE_DOMAIN_2"
fi
set -u


