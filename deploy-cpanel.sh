#!/bin/bash

#
# iACC cPanel Deployment Script
# 
# This script prepares a deployment package for cPanel hosting
# It creates a clean ZIP file ready for upload
#
# Usage: ./deploy-cpanel.sh [version]
# Example: ./deploy-cpanel.sh v4.8
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
VERSION=${1:-$(git describe --tags --always 2>/dev/null || echo "v4.8")}
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"
BUILD_DIR="$PROJECT_DIR/build"
DEPLOY_DIR="$BUILD_DIR/iacc-deploy"
OUTPUT_FILE="$BUILD_DIR/iacc-cpanel-$VERSION.zip"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     iACC cPanel Deployment Package Builder                 ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}[INFO]${NC} Version: $VERSION"
echo -e "${YELLOW}[INFO]${NC} Time: $(date)"
echo ""

# Clean previous build
echo -e "${YELLOW}[1/6]${NC} Cleaning previous build..."
rm -rf "$BUILD_DIR"
mkdir -p "$DEPLOY_DIR"
echo -e "${GREEN}  ✓${NC} Build directory ready"

# Copy files (excluding unwanted)
echo -e "${YELLOW}[2/6]${NC} Copying project files..."

# Files/folders to exclude
EXCLUDES=(
    ".git"
    ".gitignore"
    ".DS_Store"
    ".venv"
    ".env"
    "docker"
    "docker-compose.yml"
    "docker-compose.prod.yml"
    "Dockerfile"
    "*.bak"
    "*.backup"
    "build"
    "node_modules"
    "vendor"
    "TODAY_WORK_SUMMARY*.txt"
    "phpstan.neon"
    "deploy-cpanel.sh"
    ".htaccess"
    "inc/sys.configs.php"
    "inc/docker-settings.json"
    "logs/*.log"
    "cache/*"
    "backups/*"
)

# Build rsync exclude pattern
EXCLUDE_ARGS=""
for pattern in "${EXCLUDES[@]}"; do
    EXCLUDE_ARGS="$EXCLUDE_ARGS --exclude=$pattern"
done

# Copy files using rsync
rsync -av --progress $EXCLUDE_ARGS "$PROJECT_DIR/" "$DEPLOY_DIR/" > /dev/null

echo -e "${GREEN}  ✓${NC} Files copied"

# Prepare production config
echo -e "${YELLOW}[3/6]${NC} Preparing production configuration..."

# Rename cpanel config to be the main config
if [ -f "$DEPLOY_DIR/inc/sys.configs.cpanel.php" ]; then
    cp "$DEPLOY_DIR/inc/sys.configs.cpanel.php" "$DEPLOY_DIR/inc/sys.configs.php"
    echo -e "${GREEN}  ✓${NC} Production config ready"
else
    echo -e "${RED}  ✗${NC} Warning: sys.configs.cpanel.php not found"
fi

# Rename cpanel htaccess
if [ -f "$DEPLOY_DIR/.htaccess.cpanel" ]; then
    cp "$DEPLOY_DIR/.htaccess.cpanel" "$DEPLOY_DIR/.htaccess"
    echo -e "${GREEN}  ✓${NC} Production .htaccess ready"
else
    echo -e "${RED}  ✗${NC} Warning: .htaccess.cpanel not found"
fi

# Create required directories
echo -e "${YELLOW}[4/6]${NC} Creating required directories..."
mkdir -p "$DEPLOY_DIR/logs"
mkdir -p "$DEPLOY_DIR/cache"
mkdir -p "$DEPLOY_DIR/backups"
mkdir -p "$DEPLOY_DIR/file"
mkdir -p "$DEPLOY_DIR/upload"

# Add .htaccess to protect sensitive directories
echo "Deny from all" > "$DEPLOY_DIR/logs/.htaccess"
echo "Deny from all" > "$DEPLOY_DIR/cache/.htaccess"
echo "Deny from all" > "$DEPLOY_DIR/backups/.htaccess"
echo "Deny from all" > "$DEPLOY_DIR/inc/.htaccess"

# Create placeholder files
touch "$DEPLOY_DIR/logs/.gitkeep"
touch "$DEPLOY_DIR/cache/.gitkeep"
touch "$DEPLOY_DIR/backups/.gitkeep"

echo -e "${GREEN}  ✓${NC} Directories created"

# Create version file
echo -e "${YELLOW}[5/6]${NC} Creating version info..."
cat > "$DEPLOY_DIR/version.txt" << EOF
iACC Accounting Management System
Version: $VERSION
Build Date: $(date '+%Y-%m-%d %H:%M:%S')
Build From: $(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
Branch: $(git branch --show-current 2>/dev/null || echo "unknown")
EOF
echo -e "${GREEN}  ✓${NC} Version info created"

# Create ZIP package
echo -e "${YELLOW}[6/6]${NC} Creating deployment package..."
cd "$BUILD_DIR"
zip -r "$OUTPUT_FILE" "iacc-deploy" -x "*.DS_Store" > /dev/null
echo -e "${GREEN}  ✓${NC} Package created"

# Cleanup
rm -rf "$DEPLOY_DIR"

# Summary
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     Deployment Package Ready!                              ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  📦 Package: ${BLUE}$OUTPUT_FILE${NC}"
echo -e "  📏 Size: $(du -h "$OUTPUT_FILE" | cut -f1)"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "  1. Upload $OUTPUT_FILE to cPanel File Manager"
echo "  2. Extract to public_html directory"
echo "  3. Edit inc/sys.configs.php with your database credentials"
echo "  4. Import database SQL file via phpMyAdmin"
echo "  5. Test the application"
echo ""
echo -e "  📖 See ${BLUE}docs/CPANEL_DEPLOYMENT_GUIDE.md${NC} for detailed instructions"
echo ""
