# Phase 6: Production Deployment Guide

**Version**: 1.0  
**Date**: January 10, 2026  
**Status**: READY FOR PRODUCTION DEPLOYMENT  
**Target Environment**: Production (Linux Server, Docker, K8s-ready)

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Environment Setup](#environment-setup)
4. [Database Migration](#database-migration)
5. [Application Deployment](#application-deployment)
6. [SSL/TLS Configuration](#ssltls-configuration)
7. [Verification & Testing](#verification--testing)
8. [Rollback Procedures](#rollback-procedures)
9. [Post-Deployment Validation](#post-deployment-validation)
10. [Troubleshooting](#troubleshooting)
11. [Monitoring & Alerts](#monitoring--alerts)

---

## Prerequisites

### System Requirements

**Hardware**:
- 2+ CPU cores
- 4GB RAM minimum (8GB recommended)
- 50GB SSD storage (scalable)
- High-speed network connection (100Mbps+)

**Software**:
- Ubuntu 20.04 LTS or CentOS 7+
- Docker 20.10+ and Docker Compose 1.29+
- Git 2.25+
- OpenSSL 1.1+
- MySQL 8.0 client tools

**Network**:
- Ports 80, 443 open to internet
- Database server access (private network)
- Redis server access (private network)
- NFS mount for shared files (optional)

### Required Credentials

```bash
# Before deployment, ensure you have:
- GitHub repository access (SSH key or token)
- Docker Hub account credentials
- AWS/Cloud provider credentials (if using cloud storage)
- SSL/TLS certificate and private key
- Database root password
- Redis password
- SMTP credentials (for email notifications)
- API keys for third-party services
```

### Domain & DNS

1. Point domain to server IP:
   ```
   example.com    A    <server-ip>
   *.example.com  A    <server-ip>
   ```

2. Verify DNS resolution:
   ```bash
   nslookup example.com
   # Should resolve to your server IP
   ```

---

## Pre-Deployment Checklist

### Code Quality
- [ ] All tests passing (./vendor/bin/phpunit)
- [ ] Code coverage minimum 70%
- [ ] No security vulnerabilities (run security audit)
- [ ] No deprecated functions used
- [ ] All code review comments addressed
- [ ] CHANGELOG.md updated with release notes

### Security
- [ ] SSL/TLS certificate obtained and validated
- [ ] Secrets not in repository (.env.example exists)
- [ ] .gitignore configured properly
- [ ] No hardcoded API keys or credentials
- [ ] Security headers configured in Nginx
- [ ] Rate limiting configured
- [ ] CORS policy defined
- [ ] WAF rules configured (if applicable)

### Infrastructure
- [ ] Docker images built and tested
- [ ] docker-compose.prod.yml validated
- [ ] Database schema migrations reviewed
- [ ] Backup strategy defined and tested
- [ ] Monitoring stack configured
- [ ] Log aggregation configured
- [ ] CDN configured (if using)
- [ ] Load balancer configured (if using)

### Documentation
- [ ] Deployment runbook completed
- [ ] Architecture diagram reviewed
- [ ] Operations guide prepared
- [ ] Disaster recovery plan finalized
- [ ] Team trained on deployment
- [ ] On-call escalation procedures defined

### Third-Party Services
- [ ] Database cluster created and access verified
- [ ] Redis cluster created and access verified
- [ ] SMTP service configured
- [ ] Backup storage configured
- [ ] Monitoring dashboards created
- [ ] Alert channels configured

---

## Environment Setup

### Step 1: Prepare the Server

```bash
#!/bin/bash
# ssh into your production server
ssh -i ~/.ssh/id_rsa ubuntu@your-server-ip

# Update system packages
sudo apt-get update
sudo apt-get upgrade -y

# Install required tools
sudo apt-get install -y \
    git \
    curl \
    wget \
    htop \
    vim \
    openssl \
    mysql-client \
    redis-tools \
    ntp \
    fail2ban

# Configure timezone
sudo timedatectl set-timezone UTC
```

### Step 2: Install Docker

```bash
# Remove old Docker versions
sudo apt-get remove -y docker docker.io containerd runc

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Verify installation
docker --version  # Docker version 20.10+
docker-compose --version  # Docker Compose 1.29+
```

### Step 3: Clone Repository

```bash
# Create application directory
sudo mkdir -p /srv/applications
sudo chown $USER:$USER /srv/applications

cd /srv/applications

# Clone repository (use deploy key for production)
git clone git@github.com:psinthorn/iacc-php-mvc.git app
cd app

# Checkout production branch
git checkout main
git pull origin main
```

### Step 4: Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit with production values
nano .env

# Required variables for production:
# APP_ENV=production
# APP_DEBUG=false
# DATABASE_HOST=prod-db-server
# DATABASE_USER=app_user
# DATABASE_PASSWORD=<strong-password>
# REDIS_HOST=prod-redis-server
# REDIS_PASSWORD=<redis-password>
# CACHE_DRIVER=redis
# LOG_CHANNEL=stack
# MAIL_DRIVER=smtp
# MAIL_HOST=smtp.mailtrap.io
# JWT_SECRET=<generated-secret>
```

**Generate JWT Secret**:
```bash
# Generate 32-byte random secret
openssl rand -base64 32
# Copy output to JWT_SECRET in .env
```

### Step 5: Prepare Docker Volumes

```bash
# Create persistent volumes
docker volume create app-data
docker volume create db-data
docker volume create redis-data

# Set proper permissions
sudo chown -R 1000:1000 /var/lib/docker/volumes/app-data/_data
sudo chown -R 999:999 /var/lib/docker/volumes/db-data/_data
sudo chown -R 999:999 /var/lib/docker/volumes/redis-data/_data
```

---

## Database Migration

### Step 1: Verify Database Connection

```bash
# Test connection to database server
mysql -h database-host \
      -u root \
      -p \
      -e "SELECT 1;"

# Should return: 1
```

### Step 2: Create Application Database

```bash
# Connect as root
mysql -h database-host -u root -p

# Create database
CREATE DATABASE iacc_production 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

# Create application user
CREATE USER 'app_user'@'%' 
    IDENTIFIED BY '<strong-password>';

# Grant privileges (restrict to app server IP for security)
GRANT ALL PRIVILEGES ON iacc_production.* 
    TO 'app_user'@'app-server-ip' 
    IDENTIFIED BY '<strong-password>';

# For development, allow all hosts:
GRANT ALL PRIVILEGES ON iacc_production.* 
    TO 'app_user'@'%' 
    IDENTIFIED BY '<strong-password>';

FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Run Migrations

```bash
# Start database container
docker-compose -f docker-compose.prod.yml up -d db

# Wait for database to be ready (30-60 seconds)
sleep 30

# Execute migrations
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan migrate:fresh --seed --force

# Verify migration success
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan migrate:status

# Expected output: All migrations showing "Ran"
```

### Step 4: Create Required Indexes

```bash
# Execute performance indexes migration
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production < \
    docker/mysql/migrations/03-performance-indexes.sql

# Verify indexes created
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SHOW INDEX FROM purchase_orders;" | wc -l

# Should show 10+ indexes for purchase_orders
```

### Step 5: Warm Up Cache

```bash
# Clear any stale cache
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan cache:clear

# Warm up cache with initial data
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan cache:warmup

# Verify cache populated
redis-cli -h redis-host -a "$REDIS_PASSWORD" dbsize
# Should show significant number of keys
```

---

## Application Deployment

### Step 1: Build Docker Images

```bash
# Build images for production
docker-compose -f docker-compose.prod.yml build

# Example build output:
# Building app
# Step 1/20 : FROM php:8.1-fpm-alpine
# ...
# Successfully tagged iacc-php-mvc:latest
```

### Step 2: Start Services

```bash
# Start all services in background
docker-compose -f docker-compose.prod.yml up -d

# Verify all containers running
docker-compose -f docker-compose.prod.yml ps

# Expected output:
# NAME                STATUS
# app                 Up 5 seconds
# web                 Up 5 seconds
# db                  Up 5 seconds
# redis               Up 5 seconds
# elasticsearch       Up 5 seconds
# kibana              Up 5 seconds
```

### Step 3: Verify Application Health

```bash
# Check application logs
docker-compose -f docker-compose.prod.yml logs app | tail -20

# Test health endpoint
curl http://localhost/api/health

# Expected response:
# {
#   "status": "healthy",
#   "timestamp": "2026-01-10T12:00:00Z",
#   "version": "1.0.0",
#   "uptime": "42 seconds"
# }
```

### Step 4: Install PHP Dependencies

```bash
# Install Composer dependencies (if not in Docker image)
docker-compose -f docker-compose.prod.yml exec -T app \
    composer install --no-dev --optimize-autoloader

# Clear any previous caches
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan cache:clear
```

---

## SSL/TLS Configuration

### Step 1: Obtain SSL Certificate

**Using Let's Encrypt (Free)**:

```bash
# Install Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot certonly --standalone \
    -d example.com \
    -d www.example.com \
    --agree-tos \
    --email admin@example.com

# Certificate stored at: /etc/letsencrypt/live/example.com/
```

**Using Purchased Certificate**:

```bash
# Copy certificate files to server
scp -i ~/.ssh/id_rsa \
    /path/to/certificate.crt \
    ubuntu@your-server-ip:/tmp/

scp -i ~/.ssh/id_rsa \
    /path/to/private.key \
    ubuntu@your-server-ip:/tmp/

# Move to proper location
sudo mkdir -p /etc/ssl/certs/iacc
sudo mv /tmp/certificate.crt /etc/ssl/certs/iacc/
sudo mv /tmp/private.key /etc/ssl/private/iacc/
sudo chmod 600 /etc/ssl/private/iacc/private.key
```

### Step 2: Configure Nginx with TLS

```nginx
# /etc/docker/nginx/conf.d/default.conf (already configured)

server {
    listen 80;
    server_name example.com www.example.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com www.example.com;

    # SSL Certificates
    ssl_certificate /etc/ssl/certs/iacc/certificate.crt;
    ssl_certificate_key /etc/ssl/private/iacc/private.key;

    # TLS Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_session_tickets off;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'" always;

    # Application configuration...
}
```

### Step 3: Test SSL Configuration

```bash
# Test SSL certificate validity
openssl x509 -in /etc/ssl/certs/iacc/certificate.crt -text -noout

# Test TLS connection
openssl s_client -connect example.com:443

# Online SSL test (A+ rating target)
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=example.com
```

### Step 4: Configure Certificate Auto-Renewal (Let's Encrypt)

```bash
# Create renewal hook script
sudo tee /etc/letsencrypt/renewal-hooks/post/reload-docker.sh > /dev/null <<EOF
#!/bin/bash
docker-compose -f /srv/applications/app/docker-compose.prod.yml \
    exec -T web nginx -s reload
EOF

sudo chmod +x /etc/letsencrypt/renewal-hooks/post/reload-docker.sh

# Test renewal
sudo certbot renew --dry-run

# Enable automatic renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Verify renewal is active
sudo systemctl status certbot.timer
```

---

## Verification & Testing

### Step 1: Database Connectivity

```bash
# Test database connection from app container
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan db:seed

# Expected output: Seeding completed successfully
```

### Step 2: API Endpoint Testing

```bash
# Test authentication endpoint
curl -X POST https://example.com/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@example.com","password":"password"}'

# Expected response:
# {
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
#   "user": { ... }
# }

# Test product list endpoint
curl -X GET https://example.com/api/products \
    -H "Authorization: Bearer <token>"

# Expected response:
# [
#   { "id": 1, "name": "Product 1", ... },
#   { "id": 2, "name": "Product 2", ... }
# ]
```

### Step 3: Health Check Monitoring

```bash
# Test comprehensive health check
curl https://example.com/api/health/detailed

# Expected response:
# {
#   "status": "healthy",
#   "components": {
#     "database": { "status": "up", "response_time": "12ms" },
#     "redis": { "status": "up", "response_time": "2ms" },
#     "elasticsearch": { "status": "up", "response_time": "45ms" }
#   }
# }
```

### Step 4: Load Testing

```bash
# Run basic load test (10 concurrent users, 5 minutes)
# Using Apache Bench
ab -n 1000 -c 10 https://example.com/api/products

# Expected results:
# Requests per second: 500+
# Time per request: <100ms (mean)
# Error rate: 0%

# For comprehensive load testing, see LOAD_TESTING_GUIDE.md
```

### Step 5: Security Verification

```bash
# Test HTTPS redirect
curl -I http://example.com
# Should return 301 redirect to https://

# Verify security headers
curl -I https://example.com | grep -E 'Strict-Transport|X-Frame|X-Content'

# Expected headers:
# Strict-Transport-Security: max-age=31536000
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# X-XSS-Protection: 1; mode=block

# Check TLS version
openssl s_client -connect example.com:443 -tls1_2
# Should support TLS 1.2 and 1.3 only
```

---

## Rollback Procedures

### Quick Rollback (Last 24 hours)

```bash
# Stop current deployment
docker-compose -f docker-compose.prod.yml down

# Restore previous version
git revert HEAD --no-edit
git pull origin main

# Rebuild and restart
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d

# Verify health
curl https://example.com/api/health
```

### Database Rollback (Last Migration)

```bash
# If migrations caused issues:
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan migrate:rollback

# Verify rollback success
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan migrate:status

# Restart application
docker-compose -f docker-compose.prod.yml restart app
```

### Full Restore from Backup

```bash
# Stop all services
docker-compose -f docker-compose.prod.yml down -v

# Restore database from backup
mysql -h database-host -u root -p iacc_production < backup_2026_01_10.sql

# Restore application files
tar -xzf app_backup_2026_01_10.tar.gz -C /srv/applications/

# Restore Redis cache (if separate)
# Restart all services
docker-compose -f docker-compose.prod.yml up -d

# Verify restoration
curl https://example.com/api/health
```

### Zero-Downtime Deployment (Advanced)

```bash
# Using blue-green deployment strategy:

# 1. Start new version in separate containers
docker-compose -f docker-compose.prod.yml.blue build
docker-compose -f docker-compose.prod.yml.blue up -d

# 2. Wait for health checks to pass
sleep 10
curl https://blue.example.com/api/health

# 3. Switch load balancer/Nginx to new version
# Update Nginx upstream configuration
sudo sed -i 's/green/blue/g' /etc/docker/nginx/conf.d/default.conf
docker-compose -f docker-compose.prod.yml exec -T web nginx -s reload

# 4. Monitor new deployment
sleep 30
curl https://example.com/api/health

# 5. Stop old version once stable
docker-compose -f docker-compose.prod.yml.green down

# 6. Prepare for next deployment (swap colors)
# Rename docker-compose.prod.yml.blue -> docker-compose.prod.yml.green
# and docker-compose.prod.yml.green -> docker-compose.prod.yml.blue
```

---

## Post-Deployment Validation

### Step 1: Monitoring Integration

```bash
# Verify Prometheus is scraping metrics
curl http://localhost:9090/api/v1/query?query=up

# Check Grafana dashboard loads
open https://example.com/monitoring/grafana

# Verify log aggregation (Elasticsearch)
curl http://localhost:9200/_cat/indices
```

### Step 2: Backup Verification

```bash
# Test backup creation
docker-compose -f docker-compose.prod.yml exec -T app \
    bash /app/backup.sh

# Verify backup file created
ls -lh backups/ | tail -5

# Test restore process
# (Do not execute in production, only on test environment)
```

### Step 3: Security Audit

```bash
# Run security scan
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan security:audit

# Check for security vulnerabilities in dependencies
docker-compose -f docker-compose.prod.yml exec -T app \
    composer audit

# Verify audit logging is working
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 5;"
```

### Step 4: Performance Baseline

```bash
# Record baseline metrics
docker run --rm \
    -e "TARGET_URL=https://example.com" \
    -e "CONCURRENCY=50" \
    -e "DURATION=300" \
    loadimpact/k6 run scripts/load-test.js

# Expected baseline (post-optimization):
# HTTP response time p95: < 100ms
# Throughput: 500+ req/s
# Error rate: < 0.1%
# Cache hit rate: > 80%
```

### Step 5: Team Notification

```bash
# Send deployment notification
curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
    -H 'Content-Type: application/json' \
    -d '{
        "text": "Production Deployment Successful",
        "blocks": [
            {
                "type": "section",
                "text": {
                    "type": "mrkdwn",
                    "text": "*Deployment Complete*\nVersion: '$(git rev-parse --short HEAD)'\nTime: '$(date)'\nStatus: ✅ Healthy"
                }
            }
        ]
    }'
```

---

## Troubleshooting

### Issue: Application won't start

**Symptoms**: HTTP 500 or connection refused

```bash
# Check application logs
docker-compose -f docker-compose.prod.yml logs app -f

# Common causes:
# 1. Database connection failed - verify DATABASE_* variables in .env
# 2. Redis connection failed - verify REDIS_* variables in .env
# 3. Permission issues - check file ownership: sudo chown -R 1000:1000 /srv/applications/app
# 4. Missing environment variables - compare .env with .env.example

# Restart application
docker-compose -f docker-compose.prod.yml restart app
```

### Issue: Database migration failures

**Symptoms**: Tables don't exist or migration errors

```bash
# Check migration status
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan migrate:status

# Reset and remigrate (DANGER: clears all data)
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan migrate:fresh --seed --force

# For partial rollback
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan migrate:rollback --steps=1
```

### Issue: High memory usage

**Symptoms**: Out of memory errors, slow responses

```bash
# Check memory usage by container
docker stats

# Clear application cache
docker-compose -f docker-compose.prod.yml exec -T app \
    php artisan cache:clear

# Restart containers with more memory
docker-compose -f docker-compose.prod.yml down
# Edit docker-compose.prod.yml to increase mem_limit
docker-compose -f docker-compose.prod.yml up -d

# Monitor memory trends
docker stats --no-stream
```

### Issue: Database connection pool exhausted

**Symptoms**: "Max connections exceeded" errors

```bash
# Check current connections
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SHOW PROCESSLIST;" | wc -l

# Kill idle connections
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u root -p -e "KILL QUERY <process_id>;"

# Increase connection pool in .env
# DATABASE_MAX_CONNECTIONS=50

# Restart application
docker-compose -f docker-compose.prod.yml restart app
```

### Issue: SSL certificate expired

**Symptoms**: Browser security warnings, HTTPS fails

```bash
# Check certificate expiration
openssl x509 -enddate -noout -in /etc/ssl/certs/iacc/certificate.crt

# If using Let's Encrypt
sudo certbot renew --force-renewal

# If using purchased certificate, obtain new one and restart Nginx
docker-compose -f docker-compose.prod.yml exec -T web nginx -s reload

# Verify certificate is valid
curl -I https://example.com
```

### Issue: High CPU usage

**Symptoms**: Server slow, high load average

```bash
# Identify heavy-consuming process
top -b -n 1 | head -20

# Check application slow queries
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;"

# Restart heavy process
docker-compose -f docker-compose.prod.yml restart app

# See PERFORMANCE_OPTIMIZATION.md for detailed tuning
```

---

## Monitoring & Alerts

### Configure Prometheus Alerts

```yaml
# /etc/prometheus/rules.yml
groups:
  - name: iacc_alerts
    interval: 30s
    rules:
      - alert: APIResponseTimeHigh
        expr: histogram_quantile(0.95, http_request_duration_seconds) > 0.1
        for: 5m
        
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.001
        for: 5m
        
      - alert: DatabaseConnectionPoolExhausted
        expr: mysql_global_status_threads_connected > 80
        for: 2m
        
      - alert: CacheHitRateDropped
        expr: redis_key_count < 1000
        for: 5m
        
      - alert: DiskUsageHigh
        expr: node_filesystem_avail_bytes / node_filesystem_size_bytes < 0.1
        for: 10m
```

### Configure Alert Notifications

```bash
# Slack notifications
# In Prometheus alertmanager config:
global:
  resolve_timeout: 5m

route:
  receiver: 'slack'
  group_by: ['alertname', 'cluster', 'service']
  repeat_interval: 12h

receivers:
  - name: 'slack'
    slack_configs:
      - api_url: 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL'
        channel: '#alerts'
        title: 'Alert: {{ .GroupLabels.alertname }}'
        text: '{{ .CommonAnnotations.description }}'
```

### Daily Health Checks

```bash
#!/bin/bash
# Run daily at 08:00 UTC via cron

SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"

# Check all services
STATUS="✅"
RESULTS=""

# Health endpoint
if ! curl -s https://example.com/api/health | grep -q "healthy"; then
    STATUS="❌"
    RESULTS="$RESULTS\n❌ API health check failed"
fi

# Database connectivity
if ! docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan db:seed &>/dev/null; then
    STATUS="❌"
    RESULTS="$RESULTS\n❌ Database connectivity failed"
fi

# Disk space
if [ $(df -h / | awk 'NR==2 {print $5}' | sed 's/%//') -gt 90 ]; then
    STATUS="⚠️"
    RESULTS="$RESULTS\n⚠️ Disk usage above 90%"
fi

# Send notification
curl -X POST $SLACK_WEBHOOK \
    -H 'Content-Type: application/json' \
    -d "{
        \"text\": \"Daily Health Check: $STATUS\",
        \"blocks\": [{
            \"type\": \"section\",
            \"text\": {
                \"type\": \"mrkdwn\",
                \"text\": \"*Daily Health Check*\n$RESULTS\"
            }
        }]
    }"
```

---

## Deployment Checklist Summary

**Before Deployment**:
- [ ] All tests passing (100+ tests)
- [ ] Security audit approved
- [ ] Performance targets set (p95 < 100ms)
- [ ] Backup strategy tested
- [ ] Team trained
- [ ] Incident response plan ready

**During Deployment**:
- [ ] Monitor application logs in real-time
- [ ] Watch health check endpoints
- [ ] Monitor resource usage (CPU, memory, disk)
- [ ] Check database replication lag (if applicable)
- [ ] Verify cache population

**After Deployment**:
- [ ] Run full health checks
- [ ] Verify all endpoints responding
- [ ] Check audit logs for errors
- [ ] Review metrics baseline
- [ ] Test rollback procedure (dry-run)
- [ ] Send team notification
- [ ] Schedule post-deployment review

---

## Additional Resources

- **Performance Optimization**: See [PERFORMANCE_OPTIMIZATION.md](PERFORMANCE_OPTIMIZATION.md)
- **Load Testing**: See [LOAD_TESTING_GUIDE.md](LOAD_TESTING_GUIDE.md)
- **Security**: See [SECURITY_AUDIT_REPORT.md](SECURITY_AUDIT_REPORT.md)
- **Operations**: See [OPERATIONS_GUIDE.md](OPERATIONS_GUIDE.md) (coming next)
- **Architecture**: See [ARCHITECTURE.md](ARCHITECTURE.md) (coming next)

---

**Deployment Contact**: DevOps Team  
**Support Escalation**: On-call Engineer  
**Last Updated**: January 10, 2026  
**Version**: 1.0 (Production Ready)
