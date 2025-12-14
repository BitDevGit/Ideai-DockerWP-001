#!/bin/bash
set -euo pipefail

# Smoke test: Nested tree multisite (subdirectory) on Site 3.
#
# What it does:
# - Enables ideai nested-tree feature flags at network scope
# - Creates a small nested tree: /smoke1/ and /smoke1/smoke2/
# - Registers mappings in ideai nested tree table
# - Verifies key URLs via curl using --resolve (no hosts/DNS required)
#
# Usage:
#   ./scripts/dev/nested-tree-smoke.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/docker-compose.flexible.yml"

WP_SERVICE="wordpress3"
HOST="site3.localwp"
PORT="443"
IP="127.0.0.1"

dc_exec() {
  docker compose -f "$COMPOSE_FILE" exec -T "$WP_SERVICE" "$@"
}

echo "== Nested tree smoke test =="

echo "1) Enable feature flags (network-scoped)"
dc_exec wp network meta update 1 ideai_nested_tree_enabled 1 >/dev/null || true
dc_exec wp network meta update 1 ideai_nested_tree_collision_mode strict >/dev/null || true

echo "2) Create smoke sites + mappings (idempotent-ish)"
dc_exec wp eval <<'PHP'
$net = get_network();
$net_id = (int) $net->id;
$domain = $net->domain;
$admin = 1;

function ensure_blog($domain, $internal_path, $title, $net_id, $admin) {
  // If a site already exists at the internal path, reuse it.
  $existing = get_site_by_path($domain, $internal_path);
  if ($existing && isset($existing->blog_id)) {
    return (int) $existing->blog_id;
  }
  $bid = wpmu_create_blog($domain, $internal_path, $title, $admin, [], $net_id);
  if (is_wp_error($bid)) {
    throw new Exception($bid->get_error_message());
  }
  return (int) $bid;
}

$b1 = ensure_blog($domain, '/smoke1--site/', 'Smoke1', $net_id, $admin);
\Ideai\Wp\Platform\NestedTree\upsert_blog_path($b1, '/smoke1/site/', $net_id);

$b2 = ensure_blog($domain, '/smoke1--site--smoke2/', 'Smoke2', $net_id, $admin);
\Ideai\Wp\Platform\NestedTree\upsert_blog_path($b2, '/smoke1/site/smoke2/', $net_id);

echo "Created/updated blogs:\n";
echo " - /smoke1/site/ => {$b1}\n";
echo " - /smoke1/site/smoke2/ => {$b2}\n";
PHP

echo "3) Curl checks (no DNS needed)"

curl_check () {
  local url="$1"
  echo "--- $url"
  curl -skI --resolve "${HOST}:${PORT}:${IP}" "$url" | egrep -i 'HTTP/|location:|content-type:|x-redirect-by:' || true
  echo ""
}

curl_check "https://${HOST}/smoke1/site/"
curl_check "https://${HOST}/smoke1/site/smoke2/"
curl_check "https://${HOST}/smoke1/site/wp-admin/"
curl_check "https://${HOST}/smoke1/site/smoke2/wp-admin/"

echo "✅ Smoke test complete."



