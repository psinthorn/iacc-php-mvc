#!/bin/bash
# PHPStan analysis script for iAcc system
# Usage: ./scripts/phpstan.sh [path]

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default path to analyze
PATH_TO_ANALYZE="${1:-inc iacc}"

echo "🔍 Running PHPStan analysis..."
echo "📁 Path: $PATH_TO_ANALYZE"
echo ""

# Run PHPStan via Docker
docker run --rm \
    -v "$PROJECT_DIR:/app" \
    -w /app \
    ghcr.io/phpstan/phpstan:1-php7.4 \
    analyse $PATH_TO_ANALYZE \
    --level=1 \
    --memory-limit=512M \
    --no-progress \
    2>&1

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo ""
    echo "✅ No errors found!"
else
    echo ""
    echo "⚠️  Issues found. Review and fix as needed."
fi

exit $EXIT_CODE
