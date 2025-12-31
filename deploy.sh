#!/bin/bash

#
# iACC Application Deployment Script
# 
# Usage: ./deploy.sh [staging|production] [version]
# Example: ./deploy.sh production v1.0.1
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-staging}
VERSION=${2:-$(git describe --tags --always)}
DEPLOY_DIR="/var/www/iacc"
BACKUP_DIR="/var/backups/iacc"
LOG_FILE="/var/log/iacc/deploy.log"

echo -e "${YELLOW}[INFO]${NC} Starting deployment for $ENVIRONMENT environment"
echo -e "${YELLOW}[INFO]${NC} Version: $VERSION"
echo -e "${YELLOW}[INFO]${NC} Time: $(date)"

# Create log directory
mkdir -p "$(dirname "$LOG_FILE")"

# Log function
log() {
    echo -e "$1" | tee -a "$LOG_FILE"
}

# Error handler
error_exit() {
    log "${RED}[ERROR]${NC} $1"
    exit 1
}

# Check prerequisites
check_prerequisites() {
    log "${YELLOW}[CHECK]${NC} Verifying prerequisites..."
    
    [[ -x "$(command -v git)" ]] || error_exit "git not found"
    [[ -x "$(command -v php)" ]] || error_exit "php not found"
    [[ -x "$(command -v composer)" ]] || error_exit "composer not found"
    [[ -d "$DEPLOY_DIR" ]] || error_exit "Deploy directory not found: $DEPLOY_DIR"
    
    log "${GREEN}[OK]${NC} All prerequisites met"
}

# Create backup
create_backup() {
    log "${YELLOW}[BACKUP]${NC} Creating backup..."
    
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    if tar -czf "$BACKUP_FILE" -C "$DEPLOY_DIR" . 2>/dev/null; then
        log "${GREEN}[OK]${NC} Backup created: $BACKUP_FILE"
    else
        error_exit "Failed to create backup"
    fi
}

# Update code
update_code() {
    log "${YELLOW}[GIT]${NC} Updating code..."
    
    cd "$DEPLOY_DIR"
    
    # Stash any local changes
    git stash || true
    
    # Fetch latest
    git fetch origin || error_exit "Failed to fetch from origin"
    
    # Checkout version
    git checkout "$VERSION" || error_exit "Failed to checkout version: $VERSION"
    
    log "${GREEN}[OK]${NC} Code updated to $VERSION"
}

# Install dependencies
install_dependencies() {
    log "${YELLOW}[COMPOSER]${NC} Installing dependencies..."
    
    cd "$DEPLOY_DIR"
    
    # Use production mode for production environment
    if [[ "$ENVIRONMENT" == "production" ]]; then
        composer install --no-dev --optimize-autoloader || error_exit "Composer install failed"
    else
        composer install || error_exit "Composer install failed"
    fi
    
    log "${GREEN}[OK]${NC} Dependencies installed"
}

# Set environment
set_environment() {
    log "${YELLOW}[ENV]${NC} Setting environment variables..."
    
    cd "$DEPLOY_DIR"
    
    # Copy environment file
    if [[ ! -f ".env" ]]; then
        if [[ -f ".env.$ENVIRONMENT" ]]; then
            cp ".env.$ENVIRONMENT" ".env"
        else
            cp ".env.example" ".env"
        fi
    fi
    
    # Update version in .env
    sed -i "s/^APP_VERSION=.*/APP_VERSION=$VERSION/" ".env"
    sed -i "s/^APP_START_TIME=.*/APP_START_TIME=$(date +%s)/" ".env"
    
    log "${GREEN}[OK]${NC} Environment configured"
}

# Run database migrations
run_migrations() {
    log "${YELLOW}[DB]${NC} Running database migrations..."
    
    cd "$DEPLOY_DIR"
    
    php bin/migration.php migrate || error_exit "Database migrations failed"
    
    log "${GREEN}[OK]${NC} Database migrations completed"
}

# Clear caches
clear_caches() {
    log "${YELLOW}[CACHE]${NC} Clearing caches..."
    
    cd "$DEPLOY_DIR"
    
    # Clear application cache
    rm -rf storage/cache/* || true
    
    # Clear view cache
    rm -rf storage/views/* || true
    
    # Redis cache (if configured)
    php bin/cache.php clear:all || true
    
    log "${GREEN}[OK]${NC} Caches cleared"
}

# Compile assets (if needed)
compile_assets() {
    log "${YELLOW}[ASSETS]${NC} Compiling assets..."
    
    cd "$DEPLOY_DIR"
    
    if [[ -f "webpack.config.js" ]] || [[ -f "gulpfile.js" ]]; then
        npm install || true
        npm run build || true
    fi
    
    log "${GREEN}[OK]${NC} Assets compiled"
}

# Run tests
run_tests() {
    if [[ "$ENVIRONMENT" == "production" ]]; then
        log "${YELLOW}[TEST]${NC} Running smoke tests..."
        
        cd "$DEPLOY_DIR"
        
        php vendor/bin/phpunit tests/Integration/DeploymentTest.php || error_exit "Smoke tests failed"
        
        log "${GREEN}[OK]${NC} Smoke tests passed"
    fi
}

# Health check
health_check() {
    log "${YELLOW}[HEALTH]${NC} Performing health check..."
    
    # Wait for service to be ready
    for i in {1..30}; do
        if curl -sf http://localhost/health > /dev/null 2>&1; then
            log "${GREEN}[OK]${NC} Health check passed"
            return 0
        fi
        sleep 1
    done
    
    error_exit "Health check failed - service not responding"
}

# Rollback on error
rollback() {
    if [[ -z "$BACKUP_FILE" ]]; then
        log "${RED}[ERROR]${NC} No backup file for rollback"
        return 1
    fi
    
    log "${YELLOW}[ROLLBACK]${NC} Rolling back to previous version..."
    
    cd "$DEPLOY_DIR"
    
    # Remove current deployment
    find . -maxdepth 1 -not -name '.env*' -not -name 'storage' -delete
    
    # Restore backup
    tar -xzf "$BACKUP_FILE" -C "$DEPLOY_DIR"
    
    log "${GREEN}[OK]${NC} Rolled back to backup: $BACKUP_FILE"
}

# Main deployment flow
main() {
    # Trap errors and rollback
    trap 'error_exit "Deployment failed"; rollback' ERR
    
    check_prerequisites
    create_backup
    update_code
    install_dependencies
    set_environment
    run_migrations
    clear_caches
    compile_assets
    
    # Reload application
    if [[ "$ENVIRONMENT" == "production" ]]; then
        log "${YELLOW}[RELOAD]${NC} Reloading application..."
        systemctl reload iacc || true
    fi
    
    run_tests
    health_check
    
    log "${GREEN}[SUCCESS]${NC} Deployment completed successfully!"
    log "${GREEN}[SUCCESS]${NC} Version: $VERSION"
    log "${GREEN}[SUCCESS]${NC} Time: $(date)"
}

# Run main function
main "$@"
