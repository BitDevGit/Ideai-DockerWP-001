#!/bin/bash
set -euo pipefail

# Bootstraps semantic labels + creates the nested-tree multisite issues using GitHub CLI.
#
# Usage:
#   ./scripts/github/gh-bootstrap-nested-tree.sh --repo OWNER/REPO
#
# Example:
#   ./scripts/github/gh-bootstrap-nested-tree.sh --repo myorg/Ideai-DockerWP-001
#
# Notes:
# - Requires `gh auth login` already done.

REPO=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --repo) REPO="$2"; shift 2;;
    *) echo "Unknown arg: $1"; exit 1;;
  esac
done

if [[ -z "$REPO" ]]; then
  echo "❌ Missing --repo OWNER/REPO"
  exit 1
fi

if ! command -v gh >/dev/null 2>&1; then
  echo "❌ gh not found. Install GitHub CLI first."
  exit 1
fi

echo "== Creating labels (idempotent-ish; errors are ignored if label exists) =="

create_label () {
  local name="$1" color="$2" desc="$3"
  gh label create "$name" --repo "$REPO" --color "$color" --description "$desc" 2>/dev/null || true
}

# Area
create_label "area:platform" "5319E7" "MU-plugin ideai.wp.plugin.platform (includes IdeAI Network UI)"
create_label "area:nginx"    "0E8A16" "nginx/mkcert/dev routing"
create_label "area:docs"     "C5DEF5" "Documentation"
create_label "area:tests"    "BFDADC" "Tests/scripts/harness"

# Type
create_label "type:feature"  "A2EEEF" "Feature work"
create_label "type:fix"      "D73A4A" "Bugfix"
create_label "type:chore"    "F9D0C4" "Maintenance/refactor"

# Risk
create_label "risk:low"      "0E8A16" "Low risk"
create_label "risk:medium"   "FBCA04" "Medium risk"
create_label "risk:high"     "B60205" "High risk (routing/canonical/cookies)"

# Status
create_label "status:ready"       "0E8A16" "Ready"
create_label "status:blocked"     "B60205" "Blocked"
create_label "status:needs-review" "FBCA04" "Needs review"

# Scope (optional)
create_label "scope:network-admin" "7057FF" "Network admin UI"
create_label "scope:routing"       "7057FF" "Routing/resolution"
create_label "scope:canonical"     "7057FF" "Canonical redirects/URL generation"
create_label "scope:collisions"    "7057FF" "Collision prevention"

echo "== Creating issues =="

create_issue () {
  local title="$1" labels="$2" body="$3"
  gh issue create --repo "$REPO" --title "$title" --label "$labels" --body "$body"
}

tmpl() {
  cat <<'EOF'
Summary

Scope
- In:
- Out:

Acceptance
- [ ]

Risk / notes

Links
EOF
}

create_issue \
  "NestedTree: MU platform plugin skeleton (ideai.wp.plugin.platform)" \
  "type:feature,area:platform,risk:low,status:ready" \
  "$(tmpl)"

create_issue \
  "NestedTree: Per-network feature flags API + UI toggles" \
  "type:feature,area:platform,risk:low,status:ready" \
  "$(tmpl)"

create_issue \
  "NestedTree: Mapping data model (nested path ⇄ blog) + resolver (deepest match)" \
  "type:feature,area:platform,risk:high,status:ready,scope:routing" \
  "$(tmpl)"

create_issue \
  "NestedTree: Request routing (deepest prefix wins) — feature flagged" \
  "type:feature,area:platform,risk:high,status:ready,scope:routing" \
  "$(tmpl)"

create_issue \
  "NestedTree: Outbound URL rewriting (flat ⇄ nested) — feature flagged" \
  "type:feature,area:platform,risk:high,status:ready,scope:canonical" \
  "$(tmpl)"

create_issue \
  "NestedTree: Canonical redirect policy (prevent flattening/loops)" \
  "type:feature,area:platform,risk:high,status:ready,scope:canonical" \
  "$(tmpl)"

create_issue \
  "NestedTree: Collision prevention (nested sites vs Pages) — strict mode" \
  "type:feature,area:platform,risk:medium,status:ready,scope:collisions" \
  "$(tmpl)"

create_issue \
  "NestedTree: Network Admin Tree Editor + integrate into site-new.php" \
  "type:feature,area:platform,risk:medium,status:ready,scope:network-admin" \
  "$(tmpl)"

create_issue \
  "NestedTree: Nginx rules (deep-path wp-admin) + docs/templates" \
  "type:chore,area:nginx,risk:medium,status:ready" \
  "$(tmpl)"

create_issue \
  "NestedTree: Smoke test harness script + curl checks" \
  "type:feature,area:tests,risk:low,status:ready" \
  "$(tmpl)"

create_issue \
  "NestedTree: Documentation + rollback strategy" \
  "type:feature,area:docs,risk:low,status:ready" \
  "$(tmpl)"

echo "✅ Done."


