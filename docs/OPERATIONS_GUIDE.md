# Phase 6: Operations Guide

**Version**: 1.0  
**Date**: January 10, 2026  
**Status**: PRODUCTION OPERATIONS MANUAL  
**Audience**: DevOps Engineers, System Administrators, SREs

---

## Table of Contents

1. [Daily Operations](#daily-operations)
2. [Monitoring & Observability](#monitoring--observability)
3. [Database Administration](#database-administration)
4. [Cache Management](#cache-management)
5. [Log Management & Analysis](#log-management--analysis)
6. [Backup & Recovery](#backup--recovery)
7. [Performance Monitoring](#performance-monitoring)
8. [Incident Response](#incident-response)
9. [Maintenance Windows](#maintenance-windows)
10. [Capacity Planning](#capacity-planning)

---

## Daily Operations

### Morning Health Check (08:00 UTC)

```bash
#!/bin/bash
# Quick daily health check script

echo "=== Daily Health Check ==="
date

# 1. API Health
echo -e "\n1. API Health Check:"
curl -s https://example.com/api/health | jq '.status'

# 2. Database Status
echo -e "\n2. Database Status:"
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema='iacc_production';"

# 3. Redis Status
echo -e "\n3. Redis Status:"
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO stats | grep -E "total_commands|connected_clients"

# 4. Disk Space
echo -e "\n4. Disk Space Usage:"
df -h / | tail -1

# 5. Docker Container Status
echo -e "\n5. Container Status:"
docker-compose -f /srv/applications/app/docker-compose.prod.yml ps

# 6. Error Logs
echo -e "\n6. Recent Errors (last hour):"
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app --since 1h | grep -i error | tail -5

echo -e "\n=== Health Check Complete ==="
```

### Start-of-Week Review (Monday)

```bash
# 1. Review weekend incidents
# - Check Slack alerts channel for any issues
# - Review PagerDuty incident log
# - Update incident tracking system

# 2. Analyze weekly metrics
# - Peak load times
# - Error rates by endpoint
# - Database query performance
# - Cache hit rates
# - Cost analysis (if cloud-based)

# 3. Performance report
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan report:weekly --send-email=devops@example.com

# 4. Dependency updates check
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    composer outdated --strict

# 5. Security updates
# - Check for security vulnerabilities
# - Apply critical patches immediately
# - Schedule non-critical updates for maintenance window
```

### End-of-Day Checklist (17:00 UTC)

```bash
#!/bin/bash
# End of day checklist

echo "=== End of Day Checklist ==="

# 1. Error rate check
ERRORS=$(curl -s 'http://prometheus:9090/api/v1/query?query=rate(http_requests_total{status=~"5.."}[1h])' | jq '.data.result[0].value[1]')
if (( $(echo "$ERRORS > 0.001" | bc -l) )); then
    echo "⚠️ High error rate detected: $ERRORS"
fi

# 2. Backup verification
LATEST_BACKUP=$(ls -t backups/ | head -1)
echo "Latest backup: $LATEST_BACKUP"

# 3. Disk space warning
USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $USAGE -gt 80 ]; then
    echo "⚠️ Disk usage: $USAGE%"
fi

# 4. Memory usage
docker stats --no-stream --format "{{.Container}}\t{{.MemUsage}}"

# 5. Log rotation status
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    tail -1 storage/logs/application.log

echo "=== Checklist Complete ==="
```

---

## Monitoring & Observability

### Access Monitoring Dashboards

**Prometheus Metrics**:
```
URL: http://your-server:9090
- Graphs for any metric
- Real-time data
- 15-day retention
```

**Grafana Dashboards**:
```
URL: https://example.com/monitoring/grafana
- API Performance Dashboard
- Database Health Dashboard
- Infrastructure Dashboard
- Business Metrics Dashboard
```

**Kibana Logs**:
```
URL: https://example.com/monitoring/kibana
- Full-text search on logs
- Log analysis and correlation
- Alert creation
- Reporting
```

**Jaeger Distributed Tracing** (if enabled):
```
URL: http://your-server:6831
- Request tracing across services
- Latency analysis
- Error debugging
```

### Key Metrics to Monitor

```yaml
# Response Time SLOs
HTTP Response Time (p95): < 100ms
Database Query (p95): < 50ms
Page Load (TTFB): < 200ms

# Reliability SLOs
Availability: > 99.9%
Error Rate: < 0.1%
Failed Deployments: 0%

# Resource Utilization
CPU Usage: < 70% average
Memory Usage: < 75% average
Disk Usage: < 80%
Network Bandwidth: < 60% capacity

# Application Metrics
Cache Hit Rate: > 80%
Throughput: 500+ req/s
User Concurrent Sessions: < 1000
Database Connections: < 80% of max
```

### Creating Custom Dashboards

```bash
# Access Grafana API to create dashboard
curl -X POST http://localhost:3000/api/dashboards/db \
  -H "Authorization: Bearer $GRAFANA_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d @dashboard.json

# Dashboard should include:
# - HTTP request rate (by endpoint)
# - Response time distribution (p50, p95, p99)
# - Error rate breakdown
# - Database query performance
# - Cache statistics
# - System resource usage
```

---

## Database Administration

### Regular Database Maintenance

```bash
# Weekly table optimization
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "OPTIMIZE TABLE purchase_orders, invoices, users;"

# Check table sizes
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb 
        FROM information_schema.TABLES 
        WHERE table_schema = 'iacc_production' 
        ORDER BY size_mb DESC;"

# Check index fragmentation
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT table_name, index_name, seq_in_index, column_name 
        FROM information_schema.STATISTICS 
        WHERE table_schema = 'iacc_production' 
        LIMIT 20;"
```

### Query Performance Analysis

```bash
# Enable slow query log
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u root -p \
    -e "SET GLOBAL slow_query_log = 'ON'; 
        SET GLOBAL long_query_time = 1;"

# Analyze slow queries
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SHOW CREATE TEMPORARY TABLE performance_schema.events_statements_summary_by_digest\G"

# Use pt-query-digest for analysis
docker run --rm \
    -v /var/log/mysql:/logs:ro \
    percona/percona-toolkit \
    pt-query-digest /logs/slow.log | head -50
```

### Database Replication Status (if using replication)

```bash
# Check replication lag
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u repl_user -p"$REPL_PASSWORD" \
    -e "SHOW SLAVE STATUS\G" | grep -E "Seconds_Behind_Master|Relay_Log"

# Monitor replication errors
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u repl_user -p"$REPL_PASSWORD" \
    -e "SHOW SLAVE STATUS\G" | grep -E "Last_Error"

# Skip replication error (if safe to do)
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u root -p \
    -e "SET GLOBAL SQL_SLAVE_SKIP_COUNTER = 1; START SLAVE;"
```

### Backup Verification

```bash
# Test backup can be restored (weekly)
# 1. Create test database
mysql -h backup-host -u root -p \
    -e "CREATE DATABASE iacc_test;"

# 2. Restore from backup
mysql -h backup-host -u root -p iacc_test < latest_backup.sql

# 3. Run integrity check
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u root -p iacc_test \
    -e "ANALYZE TABLE purchase_orders, invoices, users;"

# 4. Verify row counts match
mysql -h backup-host -u root -p iacc_test \
    -e "SELECT COUNT(*) FROM purchase_orders;"

# 5. Delete test database
mysql -h backup-host -u root -p \
    -e "DROP DATABASE iacc_test;"

echo "✅ Backup restoration test completed successfully"
```

---

## Cache Management

### Monitor Cache Performance

```bash
# Check Redis memory usage
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO memory | grep -E "used_memory|used_memory_human"

# Check cache hit rate
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO stats | grep -E "hits|misses"
# Calculate hit rate: hits / (hits + misses)

# List top keys by size
redis-cli -h redis-host -a "$REDIS_PASSWORD" --bigkeys

# Analyze key patterns
redis-cli -h redis-host -a "$REDIS_PASSWORD" KEYS "*" | head -100
```

### Cache Invalidation Strategies

```bash
# Clear entire cache (use with caution)
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan cache:clear

# Clear specific cache tags
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan cache:tags products --clear

# Warm up cache with initial data
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan cache:warmup

# Monitor cache warming progress
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app -f | grep -i "warming"
```

### Redis Health Check

```bash
# Check Redis connectivity
redis-cli -h redis-host -a "$REDIS_PASSWORD" ping
# Should return: PONG

# Check database number in use
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO keyspace

# Monitor Redis commands in real-time
redis-cli -h redis-host -a "$REDIS_PASSWORD" MONITOR

# Check if eviction policy is needed
redis-cli -h redis-host -a "$REDIS_PASSWORD" CONFIG GET maxmemory-policy
# Recommended: allkeys-lru (evict least recently used keys)

# Set memory limit and eviction
redis-cli -h redis-host -a "$REDIS_PASSWORD" CONFIG SET maxmemory 2gb
redis-cli -h redis-host -a "$REDIS_PASSWORD" CONFIG SET maxmemory-policy "allkeys-lru"
```

### Cache Debugging

```bash
# Check if specific key exists
redis-cli -h redis-host -a "$REDIS_PASSWORD" EXISTS cache:user:123

# Get key value (be careful with sensitive data)
redis-cli -h redis-host -a "$REDIS_PASSWORD" GET cache:config:roles

# Get key TTL
redis-cli -h redis-host -a "$REDIS_PASSWORD" TTL cache:user:123

# Delete specific key
redis-cli -h redis-host -a "$REDIS_PASSWORD" DEL cache:user:123

# Export all keys for analysis
redis-cli -h redis-host -a "$REDIS_PASSWORD" --rdb /tmp/redis-export.rdb
```

---

## Log Management & Analysis

### Access Application Logs

```bash
# Real-time application logs
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app -f

# Last 100 lines
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app --tail 100

# Logs from last hour
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app --since 1h

# Logs with timestamps (ISO format)
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app --timestamps

# Search for errors in logs
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app | grep -i error
```

### Elasticsearch Log Analysis

```bash
# Query logs using Elasticsearch API
curl -X GET "localhost:9200/logs-*/_search" \
  -H 'Content-Type: application/json' \
  -d'{
    "query": {
      "range": {
        "timestamp": {
          "gte": "now-1h"
        }
      }
    }
  }'

# Search for error logs in last 24 hours
curl -X GET "localhost:9200/logs-*/_search" \
  -H 'Content-Type: application/json' \
  -d'{
    "query": {
      "bool": {
        "must": [
          { "match": { "level": "ERROR" } },
          { "range": { "timestamp": { "gte": "now-24h" } } }
        ]
      }
    }
  }' | jq '.hits.hits | length'

# Log aggregation by error type
curl -X GET "localhost:9200/logs-*/_search" \
  -H 'Content-Type: application/json' \
  -d'{
    "aggs": {
      "errors_by_type": {
        "terms": { "field": "error_type.keyword" }
      }
    }
  }'
```

### Log Retention & Rotation

```bash
# Configure log rotation (logrotate)
sudo tee /etc/logrotate.d/iacc-app > /dev/null <<EOF
/var/log/docker/containers/app/*-json.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 0644 root root
}
EOF

# Apply log rotation
sudo logrotate -f /etc/logrotate.d/iacc-app

# Verify rotation schedule
cat /etc/logrotate.d/iacc-app
```

### Structured Logging Queries

```bash
# Find slowest API endpoints
curl -X GET "localhost:9200/logs-*/_search?size=0" \
  -H 'Content-Type: application/json' \
  -d'{
    "aggs": {
      "slowest_endpoints": {
        "terms": { 
          "field": "request.path.keyword",
          "size": 10,
          "order": { "avg_response_time": "desc" }
        },
        "aggs": {
          "avg_response_time": { "avg": { "field": "response.time_ms" } }
        }
      }
    }
  }'

# Find failed database queries
curl -X GET "localhost:9200/logs-*/_search" \
  -H 'Content-Type: application/json' \
  -d'{
    "query": {
      "bool": {
        "must": [
          { "match": { "service": "database" } },
          { "match": { "status": "failed" } }
        ]
      }
    },
    "size": 50
  }'

# Analyze error rate by hour
curl -X GET "localhost:9200/logs-*/_search?size=0" \
  -H 'Content-Type: application/json' \
  -d'{
    "aggs": {
      "errors_per_hour": {
        "date_histogram": {
          "field": "timestamp",
          "fixed_interval": "1h"
        }
      }
    }
  }'
```

---

## Backup & Recovery

### Automated Daily Backup

```bash
#!/bin/bash
# /srv/applications/app/backup.sh (run via cron daily at 02:00 UTC)

BACKUP_DIR="/srv/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="iacc_production"
SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"

mkdir -p $BACKUP_DIR

# Create database backup
echo "Starting database backup: $TIMESTAMP"
mysqldump -h $DATABASE_HOST \
          -u $DATABASE_USER \
          -p"$DATABASE_PASSWORD" \
          $DB_NAME \
          | gzip > $BACKUP_DIR/db_$TIMESTAMP.sql.gz

if [ $? -eq 0 ]; then
    echo "✅ Database backup successful"
    
    # Upload to S3 (or other cloud storage)
    aws s3 cp $BACKUP_DIR/db_$TIMESTAMP.sql.gz \
        s3://iacc-backups/production/ \
        --region us-east-1 \
        --storage-class GLACIER_IR
    
    # Keep only last 30 days locally
    find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
    
    # Send success notification
    curl -X POST $SLACK_WEBHOOK \
        -H 'Content-Type: application/json' \
        -d '{
            "text": "✅ Backup Successful",
            "blocks": [{
                "type": "section",
                "text": {
                    "type": "mrkdwn",
                    "text": "Database backup completed: '$TIMESTAMP'\nSize: '$(du -h $BACKUP_DIR/db_$TIMESTAMP.sql.gz | cut -f1)'"
                }
            }]
        }'
else
    echo "❌ Database backup failed"
    
    # Send failure notification
    curl -X POST $SLACK_WEBHOOK \
        -H 'Content-Type: application/json' \
        -d '{
            "text": "❌ Backup Failed",
            "attachments": [{
                "color": "danger",
                "text": "Database backup failed on '$TIMESTAMP'"
            }]
        }'
    
    exit 1
fi
```

**Add to crontab**:
```bash
crontab -e
# Add line: 0 2 * * * /srv/applications/app/backup.sh >> /var/log/backup.log 2>&1
```

### Restore from Backup

```bash
#!/bin/bash
# Restore database from backup

BACKUP_FILE=$1  # e.g., backup_20260110_020000.sql.gz

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "⚠️ This will overwrite the current database!"
read -p "Are you sure? Type 'yes' to continue: " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Restore cancelled"
    exit 0
fi

# Create backup of current database (safety measure)
mysqldump -h $DATABASE_HOST \
          -u $DATABASE_USER \
          -p"$DATABASE_PASSWORD" \
          iacc_production \
          | gzip > /srv/backups/pre_restore_$(date +%Y%m%d_%H%M%S).sql.gz

# Restore from backup
echo "Starting restore from: $BACKUP_FILE"
gunzip -c "$BACKUP_FILE" | \
    mysql -h $DATABASE_HOST \
          -u $DATABASE_USER \
          -p"$DATABASE_PASSWORD" \
          iacc_production

if [ $? -eq 0 ]; then
    echo "✅ Restore successful"
    
    # Verify data integrity
    docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
        php artisan db:seed --class=VerificationSeeder
    
    echo "✅ Data verification passed"
    
    # Restart application
    docker-compose -f /srv/applications/app/docker-compose.prod.yml restart app
    
    echo "✅ Application restarted"
else
    echo "❌ Restore failed - check error logs"
    exit 1
fi
```

### Backup Verification (Weekly)

```bash
#!/bin/bash
# Run weekly to verify backup integrity

LATEST_BACKUP=$(ls -t /srv/backups/db_*.sql.gz | head -1)

echo "Verifying backup: $LATEST_BACKUP"

# Check if file is valid gzip
if gzip -t "$LATEST_BACKUP" 2>/dev/null; then
    echo "✅ Backup file integrity valid"
else
    echo "❌ Backup file is corrupted"
    exit 1
fi

# Check file size
FILE_SIZE=$(du -h "$LATEST_BACKUP" | cut -f1)
echo "Backup size: $FILE_SIZE"

# Try to read SQL dump without decompressing (test only)
if gunzip -c "$LATEST_BACKUP" | head -100 | grep -q "CREATE TABLE"; then
    echo "✅ Backup contains valid SQL statements"
else
    echo "❌ Backup appears to be invalid"
    exit 1
fi

# Check backup age
BACKUP_AGE=$(( ($(date +%s) - $(stat -c %Y "$LATEST_BACKUP")) / 86400 ))
echo "Backup age: $BACKUP_AGE days"

if [ $BACKUP_AGE -gt 7 ]; then
    echo "⚠️ Warning: Backup is older than 7 days"
fi

echo "✅ Backup verification complete"
```

---

## Performance Monitoring

### Real-Time Performance Dashboard

```bash
#!/bin/bash
# Real-time performance monitoring

watch -n 5 '
    echo "=== Performance Metrics ==="
    echo ""
    echo "1. HTTP Request Rate (per second):"
    curl -s "http://prometheus:9090/api/v1/query?query=rate(http_requests_total[1m])" | jq ".data.result[-1].value[1]"
    
    echo ""
    echo "2. API Response Time (p95 in ms):"
    curl -s "http://prometheus:9090/api/v1/query?query=histogram_quantile(0.95,http_request_duration_seconds)*1000" | jq ".data.result[0].value[1]"
    
    echo ""
    echo "3. Error Rate (%):"
    curl -s "http://prometheus:9090/api/v1/query?query=(rate(http_requests_total{status=~\"5..\"}[5m]) / rate(http_requests_total[5m]))*100" | jq ".data.result[0].value[1]"
    
    echo ""
    echo "4. Cache Hit Rate (%):"
    redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO stats | grep keyspace_hits | cut -d: -f2
    
    echo ""
    echo "5. Database Connections:"
    docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db mysql -u app_user -p"$DATABASE_PASSWORD" -e "SHOW PROCESSLIST;" | wc -l
'
```

### Performance Baselines

```bash
# Establish performance baseline (run weekly)

# 1. API Response Time
RESPONSE_TIME=$(curl -o /dev/null -s -w "%{time_total}\n" https://example.com/api/products)
echo "API Response Time: ${RESPONSE_TIME}s"

# 2. Throughput Test
AB_RESULT=$(ab -n 1000 -c 10 https://example.com/api/products 2>/dev/null | grep "Requests per second")
echo "Throughput: $AB_RESULT"

# 3. Database Query Performance
QUERY_TIME=$(docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT COUNT(*) FROM purchase_orders;" -vvv 2>&1 | grep "Query OK" | awk '{print $4}')
echo "Database Query Time: $QUERY_TIME"

# 4. Cache Hit Ratio
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO stats | grep -E "keyspace_hits|keyspace_misses"

# Store baseline for comparison
echo "Response: $RESPONSE_TIME, Query: $QUERY_TIME" >> /srv/performance_baseline.txt
```

### Identify Performance Bottlenecks

```bash
# 1. Slow API Endpoints
curl -s 'http://prometheus:9090/api/v1/query?query=histogram_quantile(0.95,http_request_duration_seconds) by (path)' | \
    jq '.data.result | sort_by(.value[1]|tonumber) | reverse | .[0:10]'

# 2. Slow Database Queries
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app | \
    grep "Query" | \
    awk '{print $NF}' | \
    sort -rn | \
    head -10

# 3. High Memory Usage
docker stats --no-stream --format "{{.Container}}\t{{.MemUsage}}" | sort -t / -k1 -nr

# 4. High CPU Usage
docker stats --no-stream --format "{{.Container}}\t{{.CPUPerc}}" | sort -t % -k1 -nr

# 5. Cache Misses
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO stats | grep "keyspace_misses"

# 6. Network Issues
docker exec app ping -c 5 redis-host | grep loss
```

---

## Incident Response

### Incident Categories

| Severity | Response Time | Examples |
|----------|---------------|----------|
| P1 (Critical) | 5 minutes | Database down, payment failures, data loss |
| P2 (High) | 30 minutes | API errors, performance degradation |
| P3 (Medium) | 2 hours | Minor UI issues, slow reports |
| P4 (Low) | Next business day | Documentation updates, minor bugs |

### P1 Incident Response

```bash
#!/bin/bash
# P1 Incident - Application Down

# 1. ALERT TEAM (immediately)
curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
    -H 'Content-Type: application/json' \
    -d '{
        "text": "🚨 P1 INCIDENT: Application Down",
        "blocks": [
            {
                "type": "section",
                "text": {
                    "type": "mrkdwn",
                    "text": "⚠️ *CRITICAL*\nApplication is down or unreachable\nTime: '$(date)'\nOn-call Engineer: Please page immediately"
                }
            }
        ]
    }'

# 2. GATHER INFORMATION
echo "=== Incident Information ==="
date
echo "Current time: $(date -u)"

# Check if app is running
echo -e "\n=== Docker Status ==="
docker-compose -f /srv/applications/app/docker-compose.prod.yml ps

# Check logs for errors
echo -e "\n=== Recent Errors ==="
docker-compose -f /srv/applications/app/docker-compose.prod.yml logs app --tail 50 | grep -i error

# Check database connectivity
echo -e "\n=== Database Status ==="
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" -e "SELECT 1;"

# 3. ATTEMPT RECOVERY
echo -e "\n=== Attempting Recovery ==="

# Restart application
docker-compose -f /srv/applications/app/docker-compose.prod.yml restart app

# Wait for health check
sleep 10
HEALTH=$(curl -s https://example.com/api/health | jq '.status')
echo "Health status: $HEALTH"

if [ "$HEALTH" == "\"healthy\"" ]; then
    echo "✅ Application recovered"
else
    echo "❌ Application still down, escalating to Level 2"
    # Escalate to senior engineer
fi

# 4. POST-INCIDENT DOCUMENTATION
INCIDENT_LOG="incidents/P1_$(date +%Y%m%d_%H%M%S).log"
mkdir -p incidents
echo "Incident logged to: $INCIDENT_LOG"
```

### P2 Incident Response

```bash
# P2 Incident - Degraded Performance

# 1. Assess Impact
ERRORS=$(curl -s 'http://prometheus:9090/api/v1/query?query=rate(http_requests_total{status=~"5.."}[5m])' | jq '.data.result[0].value[1]')
RESPONSE_TIME=$(curl -s 'http://prometheus:9090/api/v1/query?query=histogram_quantile(0.95,http_request_duration_seconds)*1000' | jq '.data.result[0].value[1]')

echo "Error rate: $ERRORS req/s"
echo "Response time (p95): ${RESPONSE_TIME}ms"

# 2. Identify Root Cause
# - Check slowest queries
# - Check cache hit rate
# - Check resource utilization

# 3. Apply Temporary Fix
# - Increase container resources
# - Clear stale cache
# - Scale horizontally

# 4. Schedule Permanent Fix
# - Post-incident analysis
# - Code review of recent changes
# - Performance optimization
```

### P3 Incident Response

```bash
# P3 Incident - Minor Issue

# Can be addressed during business hours
# - Document issue thoroughly
# - Notify relevant team
# - Add to sprint backlog
# - Schedule fix in next iteration
```

### Post-Incident Review Template

```markdown
# Post-Incident Review

**Incident ID**: P1-20260110-001
**Date**: January 10, 2026
**Severity**: P1 (Critical)
**Duration**: 23 minutes (detected to resolved)

## Timeline
- 14:32 UTC: Alert triggered (error rate > 1%)
- 14:37 UTC: On-call engineer notified
- 14:42 UTC: Root cause identified (Redis connection pool exhausted)
- 14:45 UTC: Incident resolved (Redis restarted, connections reset)
- 14:55 UTC: All metrics normalized

## Root Cause
Redis memory limit reached due to cache key accumulation. Eviction policy (allkeys-lru) not removing expired keys fast enough.

## Impact
- API unavailable for 23 minutes
- ~450 failed requests
- ~150 users affected
- Revenue impact: ~$2,500

## Resolution
1. Restarted Redis service
2. Reduced cache TTLs for hot data
3. Implemented cache cleanup job

## Prevention
1. Lower Redis memory warning threshold (80% → 70%)
2. Implement automatic cache cleanup
3. Better monitoring of cache usage
4. Load test with realistic cache size

## Action Items
- [ ] Implement cache cleanup job (Owner: Dev Team, Due: Jan 15)
- [ ] Lower monitoring thresholds (Owner: DevOps, Due: Jan 12)
- [ ] Update runbook with cache troubleshooting (Owner: DevOps, Due: Jan 12)
- [ ] Post-mortem meeting (Team, Due: Jan 15)

## Metrics
- Detection time: 5 minutes (good)
- MTTR: 13 minutes (could be faster)
- Root cause identification: 5 minutes
- Resolution implementation: 3 minutes
```

---

## Maintenance Windows

### Scheduled Maintenance (Monthly, First Tuesday, 02:00 UTC)

```bash
#!/bin/bash
# Monthly maintenance script

echo "=== Monthly Maintenance Window ==="
START_TIME=$(date +%s)

# 1. Database Maintenance
echo -e "\n1. Database Optimization..."
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "OPTIMIZE TABLE purchase_orders, invoices, users, audit_log;"

# 2. Index Rebuild
echo -e "\n2. Index Analysis..."
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "ANALYZE TABLE purchase_orders, invoices, users;"

# 3. Log Cleanup
echo -e "\n3. Cleaning old logs..."
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan log:cleanup --days=30

# 4. Clear old audit trails (keep 90 days)
echo -e "\n4. Archiving audit logs..."
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"

# 5. Cache Warmup
echo -e "\n5. Warming cache..."
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan cache:warmup

# 6. Database Backup
echo -e "\n6. Creating backup..."
bash /srv/applications/app/backup.sh

# 7. Security Updates (if critical)
echo -e "\n7. Checking for security updates..."
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    composer audit

END_TIME=$(date +%s)
DURATION=$(( (END_TIME - START_TIME) / 60 ))

echo -e "\n=== Maintenance Complete ==="
echo "Duration: ${DURATION} minutes"

# Send notification
curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
    -H 'Content-Type: application/json' \
    -d '{
        "text": "✅ Monthly Maintenance Complete",
        "blocks": [{
            "type": "section",
            "text": {
                "type": "mrkdwn",
                "text": "Monthly maintenance completed in '${DURATION}' minutes"
            }
        }]
    }'
```

### Deployment Maintenance

```bash
# Schedule before deploying new code

# 1. Announce maintenance window
# - Send email to users 24 hours before
# - Post in-app notification
# - Update status page

# 2. Pause background jobs
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan horizon:pause

# 3. Enable maintenance mode
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan down --message="Maintenance in progress, back in 30 minutes"

# 4. Run migrations
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan migrate --force

# 5. Clear caches
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan route:clear

# 6. Disable maintenance mode
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan up

# 7. Resume background jobs
docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T app \
    php artisan horizon:continue

# 8. Verify everything is working
curl https://example.com/api/health
```

---

## Capacity Planning

### Monthly Capacity Report

```bash
#!/bin/bash
# Generate monthly capacity report

echo "=== Monthly Capacity Report ==="
echo "Date: $(date +%Y-%m-%d)"

# 1. Database Growth
CURRENT_SIZE=$(docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.TABLES;" | tail -1)

echo -e "\n1. Database Size: ${CURRENT_SIZE} MB"
echo "   Growth rate: $(( (CURRENT_SIZE - PREVIOUS_SIZE) / 30 )) MB/day"

# 2. Disk Usage
DISK_USED=$(df -h / | awk 'NR==2 {print $3}')
DISK_TOTAL=$(df -h / | awk 'NR==2 {print $2}')
DISK_PERCENT=$(df -h / | awk 'NR==2 {print $5}')

echo -e "\n2. Disk Usage: ${DISK_USED}/${DISK_TOTAL} (${DISK_PERCENT})"

# 3. Data Growth Projection
if [ "$DISK_PERCENT" -gt 85 ]; then
    echo "   ⚠️ Warning: Disk usage above 85%"
    echo "   Recommended: Scale up within 30 days"
fi

# 4. User Growth
CURRENT_USERS=$(docker-compose -f /srv/applications/app/docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT COUNT(*) FROM users;" | tail -1)

echo -e "\n4. Active Users: $CURRENT_USERS"
echo "   Growth rate: $(( (CURRENT_USERS - PREVIOUS_USERS) / 30 )) users/day"

# 5. Capacity Projection (next 6 months)
echo -e "\n5. 6-Month Projection:"
echo "   Database: $(( CURRENT_SIZE + 180 )) MB"
echo "   Disk: $(( DISK_PERCENT + 10 ))%"
echo "   Users: $(( CURRENT_USERS + 180 ))"

# 6. Recommendations
echo -e "\n6. Recommendations:"
if [ "$DISK_PERCENT" -gt 70 ]; then
    echo "   - Plan disk expansion"
fi
if [ "$CURRENT_SIZE" -gt 1000 ]; then
    echo "   - Consider database sharding"
fi
if [ "$CURRENT_USERS" -gt 5000 ]; then
    echo "   - Scale to multi-region deployment"
fi
```

### Cost Analysis

```bash
# AWS Cost Analysis Example
aws ce get-cost-and-usage \
    --time-period Start=2026-01-01,End=2026-01-31 \
    --granularity MONTHLY \
    --metrics "BlendedCost" \
    --group-by Type=DIMENSION,Key=SERVICE \
    --query 'ResultsByTime[0].Groups[*].[Keys[0],Metrics.BlendedCost.Amount]' \
    --output table

# Results to include in capacity report:
# - Compute costs (EC2, containers)
# - Database costs (RDS)
# - Storage costs (S3)
# - Network costs
# - Total monthly cost
# - Cost per user/transaction
```

---

## Emergency Contacts

| Role | Name | Phone | Email | Slack |
|------|------|-------|-------|-------|
| Lead DevOps | John Smith | +1-XXX-XXX-XXXX | john@example.com | @john |
| Backup DevOps | Jane Doe | +1-XXX-XXX-XXXX | jane@example.com | @jane |
| Database Admin | Bob Johnson | +1-XXX-XXX-XXXX | bob@example.com | @bob |
| Lead Developer | Alice Williams | +1-XXX-XXX-XXXX | alice@example.com | @alice |

---

## Quick Reference

```bash
# Health Check
curl https://example.com/api/health | jq '.'

# View Logs
docker-compose -f docker-compose.prod.yml logs app -f

# Restart Service
docker-compose -f docker-compose.prod.yml restart app

# Scale Service
docker-compose -f docker-compose.prod.yml up -d --scale app=3

# Database Status
mysql -h $DATABASE_HOST -u $DATABASE_USER -p"$DATABASE_PASSWORD" -e "SHOW DATABASES;"

# Cache Status
redis-cli -h $REDIS_HOST -a "$REDIS_PASSWORD" PING

# Create Backup
bash /srv/applications/app/backup.sh

# View Metrics
open http://your-server:9090  # Prometheus
open http://your-server:3000  # Grafana

# Follow Logs
docker-compose -f docker-compose.prod.yml logs -f --tail=100
```

---

**Last Updated**: January 10, 2026  
**Version**: 1.0 (Production Ready)  
**Next Review**: January 17, 2026
