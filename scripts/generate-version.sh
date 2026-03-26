#!/bin/bash
# =============================================================================
# Generate version.json — Run before deploy or during CI
# =============================================================================
# Usage:
#   ./scripts/generate-version.sh              (from project root)
#   ./scripts/generate-version.sh production    (set environment label)
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
OUTPUT_FILE="$PROJECT_ROOT/version.json"

# Get git info
COMMIT_SHA=$(git -C "$PROJECT_ROOT" rev-parse HEAD 2>/dev/null || echo "unknown")
COMMIT_SHORT=$(git -C "$PROJECT_ROOT" rev-parse --short=7 HEAD 2>/dev/null || echo "unknown")
BRANCH=$(git -C "$PROJECT_ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
BUILD_DATE=$(date -u '+%Y-%m-%dT%H:%M:%SZ')
ENVIRONMENT="${1:-development}"

cat > "$OUTPUT_FILE" <<EOF
{
  "version": "5.0-mvc",
  "commit": "${COMMIT_SHA}",
  "commit_short": "${COMMIT_SHORT}",
  "branch": "${BRANCH}",
  "build_date": "${BUILD_DATE}",
  "environment": "${ENVIRONMENT}",
  "deployed_by": "manual"
}
EOF

echo "✅ Generated $OUTPUT_FILE"
cat "$OUTPUT_FILE"
