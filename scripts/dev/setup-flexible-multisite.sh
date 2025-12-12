#!/bin/bash

# Setup script for flexible multi-site WordPress development
# Adds optional hosts entries for site1.localwp, site2.localwp, site3.localwp
# Note: subdomain multisite requires either:
# - adding individual subdomains to /etc/hosts (e.g. sub1.site2.localwp), or
# - using a local DNS resolver (e.g. dnsmasq) to wildcard *.site2.localwp.

set -e

echo "🚀 Flexible Multi-Site WordPress Setup"
echo ""

if [[ "$OSTYPE" != "darwin"* ]]; then
    echo "❌ This script is for macOS. Add hosts entries manually."
    exit 1
fi

HOSTS_FILE="/etc/hosts"
BEGIN_MARKER="# BEGIN IDEAI LOCALWP"
END_MARKER="# END IDEAI LOCALWP"

echo "📝 Syncing /etc/hosts (idempotent, no duplicates)..."

sudo python3 - <<PY
import pathlib, re
hosts_path = pathlib.Path("$HOSTS_FILE")
s = hosts_path.read_text() if hosts_path.exists() else ""
begin = re.escape("$BEGIN_MARKER")
end = re.escape("$END_MARKER")
s = re.sub(rf"(?ms)^({begin})\\n.*?^({end})\\n?", "", s)
if s and not s.endswith("\\n"):
    s += "\\n"
block = "\\n".join([
  "$BEGIN_MARKER",
  "# Managed by scripts/dev/setup-flexible-multisite.sh (safe to re-run)",
  "127.0.0.1  site1.localwp",
  "127.0.0.1  site2.localwp",
  "127.0.0.1  site3.localwp",
  "$END_MARKER",
  "",
])
hosts_path.write_text(s + block)
PY

echo "🧹 Flushing DNS cache (macOS)..."
sudo dscacheutil -flushcache || true
sudo killall -HUP mDNSResponder || true

echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Start: docker-compose -f docker-compose.flexible.yml up -d"
echo "   2. Access:"
echo "      - https://site1.localwp"
echo "      - https://site2.localwp"
echo "      - https://site3.localwp"
echo "   3. Configure WordPress (normal or multisite)"
echo ""


