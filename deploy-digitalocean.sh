#!/bin/bash

#
# iACC DigitalOcean Docker Deployment Script
# 
# This script automates deployment to a DigitalOcean VPS using Docker
#
# Usage: 
#   First time setup:  ./deploy-digitalocean.sh setup
#   Deploy/Update:     ./deploy-digitalocean.sh deploy
#   Rollback:          ./deploy-digitalocean.sh rollback
#   Status:            ./deploy-digitalocean.sh status
#   Logs:              ./deploy-digitalocean.sh logs
#

set -e

# ===========================================
# CONFIGURATION - Update these values
# ===========================================
DROPLET_IP="${DROPLET_IP:-your-droplet-ip}"
SSH_USER="${SSH_USER:-root}"
SSH_KEY="${SSH_KEY:-~/.ssh/id_rsa}"
REMOTE_DIR="/var/www/iacc"
DOCKER_COMPOSE_FILE="docker-compose.prod.yml"
APP_NAME="iacc"
DOMAIN="${DOMAIN:-your-domain.com}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ===========================================
# HELPER FUNCTIONS
# ===========================================

print_header() {
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

ssh_cmd() {
    ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_USER@$DROPLET_IP" "$1"
}

# ===========================================
# SETUP FUNCTION - First time server setup
# ===========================================
setup_server() {
    print_header "Setting up DigitalOcean Droplet"
    
    log_info "Connecting to $DROPLET_IP..."
    
    # Update system and install Docker
    log_info "Installing Docker and dependencies..."
    ssh_cmd "
        set -e
        
        # Update system
        apt-get update && apt-get upgrade -y
        
        # Install prerequisites
        apt-get install -y curl git ufw fail2ban htop
        
        # Install Docker
        if ! command -v docker &> /dev/null; then
            curl -fsSL https://get.docker.com -o get-docker.sh
            sh get-docker.sh
            rm get-docker.sh
        fi
        
        # Install Docker Compose plugin
        apt-get install -y docker-compose-plugin
        
        # Enable Docker service
        systemctl enable docker
        systemctl start docker
        
        echo 'Docker installation complete'
    "
    log_success "Docker installed"
    
    # Setup firewall
    log_info "Configuring firewall..."
    ssh_cmd "
        ufw default deny incoming
        ufw default allow outgoing
        ufw allow OpenSSH
        ufw allow 80/tcp
        ufw allow 443/tcp
        ufw --force enable
    "
    log_success "Firewall configured"
    
    # Create application directory
    log_info "Creating application directory..."
    ssh_cmd "mkdir -p $REMOTE_DIR && mkdir -p $REMOTE_DIR/docker/{nginx/conf.d,nginx/ssl,php,mysql}"
    log_success "Directories created"
    
    # Create nginx config
    log_info "Creating Nginx configuration..."
    ssh_cmd "cat > $REMOTE_DIR/docker/nginx/conf.d/default.conf << 'NGINX_EOF'
server {
    listen 80;
    server_name $DOMAIN;
    root /app;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options \"SAMEORIGIN\" always;
    add_header X-Content-Type-Options \"nosniff\" always;
    add_header X-XSS-Protection \"1; mode=block\" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files \\\$uri \\\$uri/ /index.php?\\\$query_string;
    }

    location ~ \\.php\$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \\\$document_root\\\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location /health {
        return 200 'OK';
        add_header Content-Type text/plain;
    }

    # Deny access to hidden files
    location ~ /\\. {
        deny all;
    }

    # Deny access to sensitive files
    location ~* \\.(env|log|sql|bak)\$ {
        deny all;
    }
}
NGINX_EOF"
    log_success "Nginx configuration created"
    
    # Create nginx main config
    ssh_cmd "cat > $REMOTE_DIR/docker/nginx/nginx.conf << 'NGINX_MAIN_EOF'
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '\$remote_addr - \$remote_user [\$time_local] \"\$request\" '
                    '\$status \$body_bytes_sent \"\$http_referer\" '
                    '\"\$http_user_agent\" \"\$http_x_forwarded_for\"';

    access_log /var/log/nginx/access.log main;

    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    # Security
    server_tokens off;

    include /etc/nginx/conf.d/*.conf;
}
NGINX_MAIN_EOF"
    
    # Create PHP config
    log_info "Creating PHP configuration..."
    ssh_cmd "cat > $REMOTE_DIR/docker/php/php.ini << 'PHP_EOF'
[PHP]
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_time = 300
display_errors = Off
log_errors = On
error_log = /dev/stderr
date.timezone = Asia/Bangkok

[Session]
session.save_handler = files
session.gc_maxlifetime = 1440

[opcache]
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
PHP_EOF"
    log_success "PHP configuration created"
    
    # Create PHP-FPM config
    ssh_cmd "cat > $REMOTE_DIR/docker/php/php-fpm.conf << 'FPM_EOF'
[www]
user = www-data
group = www-data
listen = 9000
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
FPM_EOF"
    
    # Create MySQL config
    ssh_cmd "cat > $REMOTE_DIR/docker/mysql/my.cnf << 'MYSQL_EOF'
[mysqld]
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
innodb_buffer_pool_size = 256M
max_connections = 100
MYSQL_EOF"
    
    log_success "Server setup complete!"
    echo ""
    log_info "Next steps:"
    echo "  1. Update DROPLET_IP in this script"
    echo "  2. Create .env.production file with your secrets"
    echo "  3. Run: ./deploy-digitalocean.sh deploy"
}

# ===========================================
# DEPLOY FUNCTION
# ===========================================
deploy() {
    print_header "Deploying to DigitalOcean"
    
    # Check if .env.production exists
    if [[ ! -f ".env.production" ]]; then
        log_warning ".env.production not found. Creating template..."
        create_env_template
        log_error "Please edit .env.production with your settings and run deploy again"
        exit 1
    fi
    
    # Create backup on remote
    log_info "Creating backup on remote server..."
    ssh_cmd "
        if [ -d $REMOTE_DIR ] && [ -f $REMOTE_DIR/$DOCKER_COMPOSE_FILE ]; then
            cd $REMOTE_DIR
            mkdir -p /var/backups/iacc
            BACKUP_NAME=\"backup-\$(date +%Y%m%d-%H%M%S).tar.gz\"
            tar -czf \"/var/backups/iacc/\$BACKUP_NAME\" --exclude='*.sql' --exclude='mysql_data' . 2>/dev/null || true
            echo \"Backup created: \$BACKUP_NAME\"
        fi
    "
    log_success "Backup created"
    
    # Sync files to remote
    log_info "Syncing files to remote server..."
    rsync -avz --progress \
        --exclude '.git' \
        --exclude 'node_modules' \
        --exclude '.env' \
        --exclude '.env.local' \
        --exclude '*.log' \
        --exclude 'storage/logs/*' \
        --exclude 'storage/cache/*' \
        -e "ssh -i $SSH_KEY" \
        ./ "$SSH_USER@$DROPLET_IP:$REMOTE_DIR/"
    log_success "Files synced"
    
    # Copy production environment file
    log_info "Copying environment file..."
    scp -i "$SSH_KEY" .env.production "$SSH_USER@$DROPLET_IP:$REMOTE_DIR/.env"
    log_success "Environment file copied"
    
    # Build and deploy on remote
    log_info "Building and starting containers..."
    ssh_cmd "
        cd $REMOTE_DIR
        
        # Build the application image
        docker build -t iacc-app:latest .
        
        # Stop existing containers gracefully
        docker compose -f $DOCKER_COMPOSE_FILE down --remove-orphans 2>/dev/null || true
        
        # Start new containers
        docker compose -f $DOCKER_COMPOSE_FILE up -d
        
        # Wait for containers to be healthy
        echo 'Waiting for containers to be ready...'
        sleep 10
        
        # Show status
        docker compose -f $DOCKER_COMPOSE_FILE ps
    "
    log_success "Containers started"
    
    # Health check
    log_info "Running health check..."
    sleep 5
    if ssh_cmd "curl -sf http://localhost/health > /dev/null 2>&1"; then
        log_success "Health check passed!"
    else
        log_warning "Health check failed - checking logs..."
        ssh_cmd "docker compose -f $REMOTE_DIR/$DOCKER_COMPOSE_FILE logs --tail=20"
    fi
    
    print_header "Deployment Complete! 🎉"
    echo -e "  ${GREEN}Application URL:${NC} http://$DROPLET_IP"
    echo -e "  ${GREEN}Domain URL:${NC} http://$DOMAIN"
    echo ""
}

# ===========================================
# ROLLBACK FUNCTION
# ===========================================
rollback() {
    print_header "Rolling Back Deployment"
    
    log_info "Listing available backups..."
    ssh_cmd "ls -la /var/backups/iacc/ | tail -10"
    
    echo ""
    read -p "Enter backup filename to restore (or 'latest' for most recent): " BACKUP_FILE
    
    if [[ "$BACKUP_FILE" == "latest" ]]; then
        BACKUP_FILE=$(ssh_cmd "ls -t /var/backups/iacc/ | head -1")
    fi
    
    log_info "Rolling back to: $BACKUP_FILE"
    ssh_cmd "
        cd $REMOTE_DIR
        docker compose -f $DOCKER_COMPOSE_FILE down
        tar -xzf /var/backups/iacc/$BACKUP_FILE -C $REMOTE_DIR
        docker compose -f $DOCKER_COMPOSE_FILE up -d
    "
    log_success "Rollback complete"
}

# ===========================================
# STATUS FUNCTION
# ===========================================
status() {
    print_header "Container Status"
    ssh_cmd "
        cd $REMOTE_DIR
        docker compose -f $DOCKER_COMPOSE_FILE ps
        echo ''
        echo 'Resource Usage:'
        docker stats --no-stream --format 'table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}'
    "
}

# ===========================================
# LOGS FUNCTION
# ===========================================
logs() {
    SERVICE=${2:-}
    print_header "Container Logs"
    
    if [[ -n "$SERVICE" ]]; then
        ssh_cmd "cd $REMOTE_DIR && docker compose -f $DOCKER_COMPOSE_FILE logs -f --tail=100 $SERVICE"
    else
        ssh_cmd "cd $REMOTE_DIR && docker compose -f $DOCKER_COMPOSE_FILE logs -f --tail=100"
    fi
}

# ===========================================
# CREATE ENV TEMPLATE
# ===========================================
create_env_template() {
    cat > .env.production << 'ENV_EOF'
# ===========================================
# iACC Production Environment Configuration
# ===========================================

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=iacc
DB_USERNAME=iacc_user
DB_PASSWORD=CHANGE_THIS_SECURE_PASSWORD

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=CHANGE_THIS_REDIS_PASSWORD

# Security
JWT_SECRET=CHANGE_THIS_JWT_SECRET_KEY
APP_KEY=CHANGE_THIS_APP_KEY

# Logging
LOG_CHANNEL=stderr

ENV_EOF
    log_success "Created .env.production template"
}

# ===========================================
# SSL SETUP FUNCTION
# ===========================================
setup_ssl() {
    print_header "Setting up SSL with Let's Encrypt"
    
    log_info "Installing Certbot and obtaining certificate..."
    ssh_cmd "
        # Install certbot
        apt-get update
        apt-get install -y certbot
        
        # Stop nginx temporarily
        cd $REMOTE_DIR
        docker compose -f $DOCKER_COMPOSE_FILE stop nginx
        
        # Get certificate
        certbot certonly --standalone -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN
        
        # Copy certificates
        cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem $REMOTE_DIR/docker/nginx/ssl/
        cp /etc/letsencrypt/live/$DOMAIN/privkey.pem $REMOTE_DIR/docker/nginx/ssl/
        
        # Restart nginx
        docker compose -f $DOCKER_COMPOSE_FILE up -d nginx
        
        # Setup auto-renewal
        echo \"0 0 * * * certbot renew --quiet && cp /etc/letsencrypt/live/$DOMAIN/*.pem $REMOTE_DIR/docker/nginx/ssl/ && docker compose -f $REMOTE_DIR/$DOCKER_COMPOSE_FILE restart nginx\" | crontab -
    "
    log_success "SSL certificate installed!"
}

# ===========================================
# DATABASE BACKUP
# ===========================================
backup_database() {
    print_header "Backing up Database"
    
    TIMESTAMP=$(date +%Y%m%d-%H%M%S)
    log_info "Creating database backup..."
    
    ssh_cmd "
        cd $REMOTE_DIR
        docker compose -f $DOCKER_COMPOSE_FILE exec -T mysql mysqldump -u root -proot iacc > /var/backups/iacc/db-$TIMESTAMP.sql
    "
    
    # Download backup locally
    mkdir -p ./backups
    scp -i "$SSH_KEY" "$SSH_USER@$DROPLET_IP:/var/backups/iacc/db-$TIMESTAMP.sql" "./backups/"
    
    log_success "Database backup saved to ./backups/db-$TIMESTAMP.sql"
}

# ===========================================
# MAIN
# ===========================================
print_header "iACC DigitalOcean Deployment Tool"

case "${1:-help}" in
    setup)
        setup_server
        ;;
    deploy)
        deploy
        ;;
    rollback)
        rollback
        ;;
    status)
        status
        ;;
    logs)
        logs "$@"
        ;;
    ssl)
        setup_ssl
        ;;
    backup-db)
        backup_database
        ;;
    *)
        echo "Usage: $0 {setup|deploy|rollback|status|logs|ssl|backup-db}"
        echo ""
        echo "Commands:"
        echo "  setup     - First time server setup (installs Docker, firewall, etc.)"
        echo "  deploy    - Deploy application to server"
        echo "  rollback  - Rollback to a previous deployment"
        echo "  status    - Show container status"
        echo "  logs      - View container logs (optionally specify service: logs nginx)"
        echo "  ssl       - Setup SSL with Let's Encrypt"
        echo "  backup-db - Backup database and download locally"
        echo ""
        echo "Configuration:"
        echo "  Set these environment variables or edit the script:"
        echo "    DROPLET_IP  - Your DigitalOcean droplet IP"
        echo "    SSH_KEY     - Path to your SSH private key"
        echo "    DOMAIN      - Your domain name"
        exit 1
        ;;
esac
