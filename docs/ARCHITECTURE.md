# Phase 6: Architecture & Runbooks

**Version**: 1.0  
**Date**: January 10, 2026  
**Status**: PRODUCTION ARCHITECTURE REFERENCE  
**Audience**: Architects, Senior Engineers, DevOps, SREs

---

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Component Interactions](#component-interactions)
3. [Data Flow](#data-flow)
4. [Security Boundaries](#security-boundaries)
5. [Deployment Topology](#deployment-topology)
6. [Disaster Recovery](#disaster-recovery)
7. [Troubleshooting Runbooks](#troubleshooting-runbooks)
8. [Performance Tuning](#performance-tuning)
9. [Scaling Strategies](#scaling-strategies)
10. [Runbook Reference](#runbook-reference)

---

## System Architecture

### High-Level Overview

```
┌─────────────────────────────────────────────────────────────┐
│                         INTERNET (Users)                    │
└────────────────────────────┬────────────────────────────────┘
                             │
                    ┌────────▼────────┐
                    │  Cloudflare CDN │ (DDoS protection, caching)
                    │   (Optional)    │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼───┐          ┌────▼───┐         ┌────▼────┐
    │Firewall│          │Firewall│         │Firewall │
    │(Nginx) │          │(Nginx) │         │(Nginx)  │
    │Instance│          │Instance│         │Instance │
    └────┬───┘          └────┬───┘         └────┬────┘
         │                   │                   │
         └───────────────────┼───────────────────┘
                             │
                    ┌────────▼────────┐
                    │ Load Balancer   │ (HAProxy/Nginx)
                    │ (Health Checks) │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼───────┐      ┌────▼───────┐     ┌────▼───────┐
    │   App      │      │   App      │     │   App      │
    │   Pod 1    │      │   Pod 2    │     │   Pod N    │
    │(Container) │      │(Container) │     │(Container) │
    └────┬───────┘      └────┬───────┘     └────┬───────┘
         │                   │                   │
         └───────────────────┼───────────────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼───────┐      ┌────▼───────┐     ┌────▼───────┐
    │ Database   │      │   Cache    │     │ Logging    │
    │ Cluster    │      │   Redis    │     │ ELK Stack  │
    │(MySQL)     │      │(Cluster)   │     │(Elastic)   │
    └────────────┘      └────────────┘     └────────────┘
         │
    ┌────▼──────────┐
    │ Backup        │
    │ S3 Storage    │
    └───────────────┘
```

### Core Services

**1. API Application** (`app` container)
- PHP-FPM runtime environment
- MVC framework with routing
- Business logic layer
- API endpoint handlers
- Request validation
- Response formatting

**2. Web Server** (`web` container)
- Nginx reverse proxy
- SSL/TLS termination
- Security headers injection
- Static file serving
- Request logging
- Load balancing (if multiple instances)

**3. Database** (`db` container)
- MySQL 8.0 database
- Multiple tables (31 total)
- Replication support (production)
- Backup mechanisms
- Query optimization (50+ indexes)

**4. Cache** (`redis` container)
- In-memory data store
- Session storage
- Query result caching
- Rate limiting counters
- Real-time data

**5. Logging Stack** (ELK)
- **Elasticsearch**: Centralized log storage
- **Logstash**: Log processing (optional)
- **Kibana**: Log visualization and analysis

**6. Monitoring Stack**
- **Prometheus**: Metrics collection
- **Grafana**: Metrics visualization
- **AlertManager**: Alert routing
- **Node Exporter**: Host metrics

---

## Component Interactions

### Request Processing Flow

```
User Request
    │
    ├─► Cloudflare CDN (if enabled)
    │   └─► Cache hit? Return cached response
    │   └─► Cache miss? Forward to origin
    │
    ├─► Firewall/WAF
    │   └─► Check IP reputation
    │   └─► Apply security rules
    │   └─► Block malicious traffic
    │
    ├─► Load Balancer (HAProxy/Nginx)
    │   └─► Health check endpoints
    │   └─► Route to healthy backend
    │   └─► Session affinity (if needed)
    │
    ├─► Nginx Web Server
    │   ├─► TLS/SSL termination
    │   ├─► Add security headers
    │   ├─► Log request details
    │   └─► Forward to PHP-FPM
    │
    ├─► PHP-FPM Application
    │   ├─► Route request to controller
    │   ├─► Check authentication token
    │   ├─► Validate input parameters
    │   ├─► Check authorization
    │   ├─► Execute business logic
    │   │   ├─► Query cache (Redis) first
    │   │   ├─► If miss, query database
    │   │   ├─► Process results
    │   │   └─► Store in cache
    │   ├─► Format response (JSON)
    │   ├─► Log audit trail
    │   └─► Return response
    │
    ├─► Elasticsearch (Async)
    │   └─► Index logs for analysis
    │
    ├─► Prometheus (Async)
    │   └─► Record metrics
    │
    └─► Client Response
        └─► User receives data
```

### Database Interaction Pattern

```
Application Request
    │
    ├─► Check Redis Cache
    │   └─► Hit? Return cached data (2ms avg)
    │   └─► Miss? Query database
    │
    ├─► Database Query
    │   ├─► Parse query
    │   ├─► Check query cache (InnoDB)
    │   ├─► Execute using indexes (if available)
    │   │   └─► Range scan or point lookup
    │   ├─► Apply WHERE clause
    │   ├─► Apply ORDER BY
    │   ├─► Apply LIMIT/OFFSET
    │   └─► Return result set
    │
    ├─► Application Processing
    │   ├─► Transform results
    │   ├─► Load relationships (eager loading)
    │   └─► Cache in Redis
    │
    └─► Return to User
        └─► All in < 100ms (p95)
```

### Authentication & Authorization Flow

```
API Request
    │
    ├─► Check Authorization Header
    │   └─► Must contain "Bearer <token>"
    │
    ├─► Token Validation (JWT)
    │   ├─► Verify signature (HS256)
    │   ├─► Check expiration (1 hour)
    │   ├─► Extract user ID and permissions
    │   └─► Load user from cache/database
    │
    ├─► Resource Authorization
    │   ├─► Check user role
    │   ├─► Check resource permissions
    │   ├─► Apply row-level security
    │   └─► Verify tenant isolation
    │
    ├─► Request Processing
    │   └─► Execute with user context
    │
    └─► Audit Log
        └─► Record WHO/WHAT/WHEN
```

---

## Data Flow

### Write Operation (Create/Update/Delete)

```
POST /api/purchase-orders
├─ Request Body: { vendor_id, items, total_amount }
│
├─ Validation
│  ├─ Validate schema
│  ├─ Check business rules
│  └─ Verify permissions
│
├─ Database Transaction BEGIN
│  ├─ INSERT into purchase_orders
│  ├─ INSERT into purchase_order_items (multiple)
│  ├─ UPDATE vendor_balance
│  ├─ INSERT into audit_log
│  └─ COMMIT/ROLLBACK
│
├─ Cache Invalidation
│  ├─ Delete: cache:po:*
│  ├─ Delete: cache:vendor:{vendor_id}
│  └─ Delete: cache:dashboard:*
│
├─ Event Publishing (optional)
│  └─ Emit: PurchaseOrderCreated event
│
├─ Asynchronous Processing
│  ├─ Generate PDF (if needed)
│  ├─ Send notifications
│  └─ Update analytics
│
├─ Logging
│  ├─ Structured log entry
│  ├─ Forward to Elasticsearch
│  └─ Update Prometheus metrics
│
└─ Response
   └─ Return created object with ID
```

### Read Operation (List/Get)

```
GET /api/purchase-orders?page=1&per_page=50
├─ Check Cache (Redis)
│  ├─ Key: cache:po:list:page:1:per_page:50
│  ├─ Hit: Return cached data (2ms)
│  └─ Miss: Query database
│
├─ Database Query
│  ├─ FROM purchase_orders
│  ├─ WHERE status = 'active' (if filtered)
│  ├─ ORDER BY created_at DESC (using index)
│  ├─ LIMIT 50 OFFSET 0
│  ├─ Join with vendors (eager load)
│  └─ Join with items (eager load)
│
├─ Response Formatting
│  ├─ Transform to JSON
│  ├─ Include relationships
│  └─ Add metadata (total count, page)
│
├─ Caching
│  ├─ Store in Redis
│  ├─ Set TTL: 30 minutes
│  └─ Add cache tag: po-list
│
├─ Logging
│  ├─ Record query time (21ms)
│  ├─ Record cache hit/miss
│  └─ Update metrics
│
└─ Response
   └─ Return JSON array with pagination
```

### Asynchronous Processing

```
Background Job Queue
├─ PDF Generation
│  └─ Generate invoice PDF (5-10 seconds)
│
├─ Email Notifications
│  └─ Send to: vendor, accounting, management
│
├─ Webhook Triggers
│  └─ Notify external systems
│
├─ Report Generation
│  └─ Nightly: Aggregate daily reports
│
├─ Data Synchronization
│  └─ Sync with accounting software
│
└─ Analytics Updates
   └─ Update dashboard data
```

---

## Security Boundaries

### Network Security Model

```
┌─────────────────────────────────────────────────┐
│            PUBLIC INTERNET                       │
│  (Untrusted, arbitrary traffic)                 │
└────────────────────┬────────────────────────────┘
                     │
        ┌────────────▼───────────┐
        │   Firewall/WAF         │
        │  (IP filtering,        │
        │   DDoS protection)     │
        └────────────┬───────────┘
                     │
    ┌────────────────┴────────────────┐
    │                                 │
┌───▼──────────────────────────────┐ │
│    DMZ (Demilitarized Zone)      │ │
│  - Nginx Web Servers             │ │
│  - Load Balancers                │ │
│  - API Gateway                   │ │
│  (Ports: 80, 443 only)           │ │
└───┬──────────────────────────────┘ │
    │                                │
    │  ┌────────────────────────────▼─────┐
    │  │  Private Network                  │
    │  │  (Internal Traffic Only)          │
    │  │                                   │
    │  │  ┌──────────────────────────┐    │
    │  │  │  Application Servers     │    │
    │  │  │  (PHP-FPM containers)    │    │
    │  │  │  (No direct internet)    │    │
    │  │  └────────┬─────────────────┘    │
    │  │           │                      │
    │  │  ┌────────▼──────────────────┐   │
    │  │  │  Database Servers        │   │
    │  │  │  (Internal only)         │   │
    │  │  │  (Port 3306 restricted)  │   │
    │  │  └──────────────────────────┘   │
    │  │                                  │
    │  │  ┌──────────────────────────┐   │
    │  │  │  Redis Cache             │   │
    │  │  │  (Internal only)         │   │
    │  │  │  (Port 6379 restricted)  │   │
    │  │  └──────────────────────────┘   │
    │  │                                  │
    │  └──────────────────────────────────┘
    │
    └─► Logging/Monitoring Servers
        (May be external, but read-only logs)
```

### Data Security Boundaries

```
User Data Classification
├─ Public Data
│  └─ Product catalog, pricing
│  └─ Not encrypted (no sensitive info)
│
├─ Internal Data
│  └─ Purchase orders, vendor info
│  └─ In-transit encryption (TLS)
│  └─ At-rest: Database encryption (optional)
│
├─ Sensitive Data
│  ├─ User passwords
│  │  └─ Bcrypt hash (never plain text)
│  │
│  ├─ Payment information
│  │  └─ PCI-DSS compliance
│  │  └─ Encrypted fields in database
│  │  └─ Separate payment service (tokenization)
│  │
│  ├─ API credentials
│  │  └─ In .env file (not in git)
│  │  └─ Loaded via environment variables
│  │  └─ Never logged or displayed
│  │
│  └─ Audit trails
│     └─ Encrypted backup (optional)
│     └─ 90-day retention
│     └─ Access controlled
│
└─ User Specific
   └─ Row-level security enforced
   └─ Users see only their own data
   └─ API filters by tenant_id
```

### Authentication & Authorization Boundaries

```
Request Boundary
├─ NO Authentication
│  ├─ POST /auth/login (anyone can attempt)
│  ├─ GET /health (monitoring systems)
│  └─ GET /docs (documentation)
│
├─ AUTHENTICATED (token required)
│  └─ Most API endpoints
│  └─ Token expires in 1 hour
│  └─ Refresh token mechanism available
│
└─ AUTHORIZED (token + permissions)
   ├─ Admin-only: Users, Roles, Security settings
   ├─ Accounting: Invoices, Payments, Reports
   ├─ Vendor: Own purchase orders and items
   └─ Read-only: View-only reports for users
```

---

## Deployment Topology

### Single-Server Deployment (Small Scale)

```
Single Physical/Cloud Server
├─ OS: Ubuntu 20.04 LTS
├─ CPU: 4 cores
├─ RAM: 8GB
└─ Disk: 100GB SSD

Docker Containers:
├─ nginx (web server + load balancer)
├─ php-fpm (application runtime)
├─ mysql (database)
├─ redis (cache)
├─ elasticsearch (logging)
├─ kibana (log viewer)
├─ prometheus (metrics)
└─ grafana (dashboards)

Capabilities:
├─ ~100 concurrent users
├─ ~500 req/s throughput
├─ Single point of failure ❌
└─ No redundancy ❌
```

### High-Availability Deployment (Production)

```
┌─────────────────────────────────────────────────┐
│        AWS/Cloud Region (us-east-1)             │
│  Multi-AZ for redundancy                        │
└─────────────────────────────────────────────────┘

┌────────────────────────────────────────┐
│         Load Balancer (ELB/ALB)        │
│  - Auto-scaling group integration      │
│  - Health check monitoring             │
│  - SSL termination (optional)          │
└──────────────┬───────────────────────┘
               │
     ┌─────────┼─────────┐
     │         │         │
┌────▼─┐  ┌────▼─┐  ┌────▼─┐
│ App  │  │ App  │  │ App  │  (ASG: 3-10 instances)
│ Pod1 │  │ Pod2 │  │ PodN │  (Auto-scale by CPU/memory)
└────┬─┘  └────┬─┘  └────┬─┘
     │         │         │
     └─────────┼─────────┘
               │
┌──────────────┴────────────────┐
│                               │
┌────────────────┐  ┌───────────▼─────┐
│  RDS MySQL     │  │  ElastiCache    │
│  (Multi-AZ)    │  │  Redis Cluster  │
│  (Read replicas)│  │  (Multi-node)   │
└────────────────┘  └─────────────────┘

┌──────────────────────────────────────┐
│  Logging & Monitoring (separate)     │
├──────────────────────────────────────┤
│ - Elasticsearch cluster               │
│ - Kibana instances                    │
│ - Prometheus + Grafana                │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  Backup & Disaster Recovery          │
├──────────────────────────────────────┤
│ - S3 (automated database backups)    │
│ - Cross-region backup replication    │
│ - Snapshot management                │
└──────────────────────────────────────┘

Capabilities:
├─ ~1000+ concurrent users
├─ ~500+ req/s throughput
├─ Zero-downtime deployment
├─ Auto-scaling on demand
├─ Data redundancy
├─ Geographic failover
└─ 99.99% availability SLA
```

### Kubernetes Deployment (Enterprise Scale)

```
┌──────────────────────────────────────────┐
│     Kubernetes Cluster (3+ nodes)        │
│  - EKS (AWS), GKE (Google), AKS (Azure) │
│  - Auto-scaling node groups              │
└──────────────────────────────────────────┘

Namespace: production
├─ Deployment: app
│  ├─ Replicas: 5-20 (auto-scaling)
│  ├─ Pod template:
│  │  ├─ nginx sidecar
│  │  └─ php-fpm container
│  ├─ Resource requests/limits
│  ├─ Health checks (liveness/readiness)
│  └─ Volume mounts
│
├─ Service: api (ClusterIP + LoadBalancer)
├─ Ingress: HTTPS routing rules
├─ ConfigMap: configuration data
├─ Secret: sensitive credentials
├─ HPA: Horizontal Pod Autoscaler
│  └─ Scale on CPU 70%, memory 80%
│
├─ StatefulSet: mysql
│  ├─ Persistent volumes (managed storage)
│  ├─ Headless service
│  └─ Ordered startup/shutdown
│
├─ StatefulSet: redis
│  ├─ Persistent volumes
│  ├─ Replication factor: 3
│  └─ Sentinel for failover
│
├─ CronJob: backup
│  └─ Daily at 02:00 UTC
│
└─ Monitoring
   ├─ Prometheus scrape config
   ├─ Alerting rules
   └─ Service monitor for each service

Capabilities:
├─ Unlimited concurrent users (scales horizontally)
├─ 1000+ req/s throughput
├─ Zero-downtime rolling updates
├─ Container-level isolation
├─ Resource sharing across teams
├─ Multi-tenancy support
├─ Declarative infrastructure
└─ 99.99%+ availability
```

---

## Disaster Recovery

### RTO & RPO Targets

| Scenario | RTO | RPO | Strategy |
|----------|-----|-----|----------|
| Application pod crashed | < 1 min | 0 | Auto-restart by Kubernetes |
| Database node failed | < 5 min | 0 | Failover to replica |
| Entire region down | < 30 min | < 1 hour | Cross-region restore |
| Data corruption | < 1 hour | < 1 hour | Point-in-time restore |
| Ransomware attack | < 4 hours | < 1 hour | Restore from immutable backups |

### Backup Strategy

```bash
# Daily Database Backup
├─ Schedule: 02:00 UTC daily
├─ Retention: 30 days locally, 90 days in archive
├─ Location: S3 standard → S3 Glacier after 30 days
├─ Compression: gzip (50% reduction)
├─ Encryption: AES-256
├─ Verification: Test restore weekly
└─ Alert on failure: PagerDuty

# Continuous Replication
├─ MySQL master-slave replication
├─ Continuous to standby database
├─ Lag: < 1 second (monitored)
├─ Failover: Automatic (MHA or Orchestrator)
└─ Verification: Slave relay logs checked hourly

# Application Code
├─ Git repository with full history
├─ Tagging: Release v1.0.1, v1.0.2, etc.
├─ Branches: main (prod), staging, feature branches
├─ Retention: Unlimited (GitHub)
└─ Backup: Mirrored to GitLab (secondary)

# Configuration
├─ IaC: docker-compose.prod.yml
├─ Terraform: Infrastructure as code
├─ Backed up with code
└─ Versioned and immutable
```

### Failover Procedures

**Database Failover (Automated)**:
```bash
# Monitoring detects master down
├─ Health check failed 3x in 30 seconds
├─ Orchestrator initiates failover
│  ├─ Promotes best replica to master
│  ├─ Redirects slaves to new master
│  ├─ Updates DNS/VIP
│  └─ Notifies PagerDuty
├─ Application detects connection change
├─ Reconnects to new master
└─ Operations: Monitor slave relay logs
```

**Application Failover (Auto-restart)**:
```bash
# Kubernetes detects pod unhealthy
├─ Readiness probe failed
├─ Liveness probe failed
├─ Pod terminates
├─ Kubernetes restarts pod
├─ Load balancer removes unhealthy backend
├─ Routes traffic to healthy pods
└─ RTO: < 30 seconds typically
```

**Regional Failover (Manual)**:
```bash
# Entire region disaster
├─ Activate DR site (different region)
├─ Restore from latest backup
│  ├─ Expected RTO: 30-60 minutes
│  ├─ Expected RPO: < 1 hour
│  └─ Manual process (requires ops engineer)
├─ Update DNS to point to DR site
├─ Verify application functionality
└─ Document incident and lessons learned
```

---

## Troubleshooting Runbooks

### Runbook: API Response Time Degradation

**Symptoms**:
- User reports slow responses
- Prometheus shows p95 > 200ms (normal: < 100ms)
- Error rate may be increasing

**Diagnosis**:
```bash
# Step 1: Identify affected endpoints
curl -s 'http://prometheus:9090/api/v1/query?query=histogram_quantile(0.95,http_request_duration_seconds) by (path)' | \
    jq '.data.result | sort_by(.value[1]|tonumber) | reverse'

# Step 2: Check database query performance
docker-compose -f docker-compose.prod.yml logs app | grep "Query:" | tail -20

# Step 3: Check cache hit rate
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO stats | grep keyspace_hits

# Step 4: Check database connections
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" -e "SHOW PROCESSLIST;" | wc -l

# Step 5: Check application errors
docker-compose -f docker-compose.prod.yml logs app --since 10m | grep -i error
```

**Common Causes & Fixes**:

| Cause | Symptom | Fix |
|-------|---------|-----|
| N+1 Queries | Slow endpoint with many items | Update endpoint to eager load relationships |
| Missing Index | Slow WHERE/ORDER BY | Add index: ALTER TABLE ADD INDEX idx_field(field) |
| Cache Expired | Many cache misses | Check Redis memory, increase TTL |
| DB Connection Pool | "Max connections" error | Increase pool size or optimize connection usage |
| Memory Leak | Memory growing continuously | Restart containers, profile PHP memory |

### Runbook: High CPU Usage

**Symptoms**:
- CPU usage > 80%
- Response times degraded
- Possible intermittent timeouts

**Diagnosis**:
```bash
# Step 1: Identify CPU-consuming process
docker stats --no-stream | sort -t % -k3 -nr | head -10

# Step 2: Profile PHP CPU usage
docker-compose -f docker-compose.prod.yml exec app php -r 'xdebug_code_coverage_started();'

# Step 3: Check for infinite loops or heavy operations
docker-compose -f docker-compose.prod.yml logs app --tail 100 | grep -E "loop|while|recursive"

# Step 4: Monitor system load
uptime
cat /proc/loadavg
```

**Common Causes & Fixes**:

| Cause | Fix |
|-------|-----|
| Optimization level too high | Lower PHP opcode cache optimization |
| Background job backlog | Process pending jobs: php artisan queue:work |
| JSON serialization of large data | Paginate responses, return only needed fields |
| Report generation during peak hours | Schedule reports off-peak (02:00 UTC) |
| Missing database indexes | Identify slow queries, add indexes |

### Runbook: Database Connection Pool Exhausted

**Symptoms**:
- "Too many connections" error in logs
- API returns 503 Service Unavailable
- "SQLSTATE[HY000]: General error: 1040"

**Diagnosis**:
```bash
# Check current connections
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SHOW PROCESSLIST;"

# Check configured max connections
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" \
    -e "SHOW VARIABLES LIKE 'max_connections';"

# Check current peak connections
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u root -p \
    -e "SHOW GLOBAL STATUS LIKE 'Threads_connected';"
```

**Resolution Steps**:

```bash
# Step 1: Kill idle connections
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u root -p -e "KILL QUERY <process_id>;"

# Step 2: Increase pool size
# Edit .env: DATABASE_MAX_CONNECTIONS=100
# Then: docker-compose -f docker-compose.prod.yml restart app

# Step 3: Optimize connection usage
# In app code:
# - Close connections after use
# - Use connection pooling
# - Reduce long-running transactions

# Step 4: Scale horizontally (if available)
docker-compose -f docker-compose.prod.yml up -d --scale app=5
```

### Runbook: Redis Connection Failure

**Symptoms**:
- Cache misses all requests
- API still responds but slower
- "REDIS ERROR: Connection refused" in logs

**Diagnosis**:
```bash
# Check Redis connectivity
redis-cli -h redis-host -a "$REDIS_PASSWORD" ping

# Check if Redis container is running
docker-compose -f docker-compose.prod.yml ps | grep redis

# Check Redis logs
docker-compose -f docker-compose.prod.yml logs redis --tail 50

# Check network connectivity
docker-compose -f docker-compose.prod.yml exec app \
    nc -zv redis-host 6379
```

**Resolution Steps**:

```bash
# Step 1: Restart Redis
docker-compose -f docker-compose.prod.yml restart redis

# Step 2: Clear stale data if needed
redis-cli -h redis-host -a "$REDIS_PASSWORD" FLUSHDB

# Step 3: Verify connection from app
docker-compose -f docker-compose.prod.yml exec app \
    php -r "echo redis_ping();"

# Step 4: Monitor Redis
docker-compose -f docker-compose.prod.yml logs redis -f

# Step 5: If still failing, check:
# - Redis password is correct in .env
# - Redis host/port is correct
# - Network connectivity between app and redis
```

### Runbook: Disk Space Critical

**Symptoms**:
- Disk usage > 95%
- Write operations fail
- "No space left on device" errors

**Diagnosis**:
```bash
# Check disk usage
df -h /

# Identify large directories
du -sh /* | sort -rh | head -10

# Check Docker image/container sizes
docker system df

# Check database size
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "SELECT table_name, ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.TABLES ORDER BY size_mb DESC;"
```

**Resolution Steps**:

```bash
# Step 1: Clean Docker unused resources
docker system prune -a --volumes

# Step 2: Remove old logs
docker-compose -f docker-compose.prod.yml exec app \
    php artisan log:cleanup --days=7

# Step 3: Archive old database records
# Move audit logs older than 90 days to archive table
docker-compose -f docker-compose.prod.yml exec -T db \
    mysql -u app_user -p"$DATABASE_PASSWORD" iacc_production \
    -e "DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"

# Step 4: Expand volume (if using cloud storage)
# AWS: Create snapshot, expand EBS volume, extend filesystem
# GCP: Resize persistent disk

# Step 5: Implement retention policies
# Update backup retention
# Update log rotation (daily → hourly for large logs)
```

---

## Performance Tuning

### Database Tuning

```sql
-- Connection Pool Optimization
SET GLOBAL max_connections = 100;
SET GLOBAL max_user_connections = 50;

-- Buffer Pool (50-75% of RAM)
SET GLOBAL innodb_buffer_pool_size = 4G;  -- For 8GB RAM server

-- Query Cache (deprecated in MySQL 8.0, use Redis instead)
-- Skip

-- Thread Cache
SET GLOBAL thread_cache_size = 10;

-- Table Cache
SET GLOBAL table_open_cache = 1024;
SET GLOBAL table_definition_cache = 2048;

-- Slow Query Log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;  -- Log queries > 1 second
SET GLOBAL log_queries_not_using_indexes = 'ON';

-- Binary Logging (for replication)
SET GLOBAL binlog_format = 'ROW';
SET GLOBAL binlog_expire_logs_seconds = 604800;  -- 7 days

-- ANALYZE for better query plans
ANALYZE TABLE purchase_orders;
ANALYZE TABLE invoices;
ANALYZE TABLE users;

-- Rebuild fragmented indexes (if > 10% fragmented)
OPTIMIZE TABLE purchase_orders;
```

### PHP Performance Tuning

```ini
# php.ini / Docker env

# Execution time
max_execution_time=30
max_input_time=60

# Memory limit
memory_limit=256M

# Upload handling
upload_max_filesize=100M
post_max_size=100M

# OPCache (code acceleration)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production: don't revalidate
opcache.revalidate_freq=60  # If revalidate, check every 60 seconds

# JIT Compilation (PHP 8.0+)
opcache.jit=tracing  # More aggressive compilation
opcache.jit_buffer_size=256M

# Session handling
session.gc_maxlifetime=86400
session.save_handler=redis  # Use Redis, not files
```

### Nginx Performance Tuning

```nginx
# nginx.conf

# Worker processes (usually = CPU count)
worker_processes auto;
worker_connections 1024;  # Total = workers × connections

# Caching
proxy_cache_path /var/cache/nginx levels=1:2 keys_zone=api_cache:10m max_size=1g inactive=60m;
proxy_cache_valid 200 10m;
proxy_cache_use_stale error timeout updating http_500 http_502 http_503 http_504;

# Connection reuse
upstream app {
    keepalive 32;
}

server {
    # Gzip compression
    gzip on;
    gzip_min_length 1000;
    gzip_types text/plain text/css application/json application/javascript;

    # Client timeouts
    client_body_timeout 12;
    client_header_timeout 12;
    keepalive_timeout 15;
    send_timeout 10;

    # Large request handling
    client_max_body_size 100M;

    location ~ /api/ {
        proxy_cache api_cache;
        proxy_cache_key "$scheme$host$request_uri$http_accept_encoding";
        proxy_pass http://app;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }
}
```

### Redis Performance Tuning

```bash
# redis.conf

# Memory management
maxmemory 2gb
maxmemory-policy allkeys-lru  # Evict least-recently-used keys

# Persistence (optional, impacts performance)
save ""  # Disable RDB snapshots if only caching
appendonly no  # Disable AOF logs

# Connection handling
tcp-backlog 511
timeout 0

# Monitor commands
CONFIG SET slowlog-log-slower-than 10000  # Log queries > 10ms
CONFIG SET slowlog-max-len 128

# Replication (if using)
slave-read-only yes
slave-priority 100
```

---

## Scaling Strategies

### Horizontal Scaling (Add More Servers)

**When to scale**:
- CPU > 70% consistently
- Memory > 75%
- Response time p95 > 150ms
- Error rate > 0.5%

**Steps**:

```bash
# 1. Add new instance to load balancer
docker-compose -f docker-compose.prod.yml up -d --scale app=3

# 2. Update load balancer configuration
# Nginx upstream config now includes:
# - app1:9000
# - app2:9000
# - app3:9000

# 3. Verify new instances are healthy
curl http://instance2:9000/health
curl http://instance3:9000/health

# 4. Monitor traffic distribution
docker stats --no-stream | grep app

# 5. Set up auto-scaling (Kubernetes)
kubectl autoscale deployment app --min=3 --max=10 --cpu-percent=70
```

**Expected improvements**:
- Throughput: 500 → 1500+ req/s
- Concurrent users: 100 → 300+
- Response time: Reduces by 2-3x

### Vertical Scaling (More Powerful Server)

**When to use**:
- Database bottleneck (need more RAM)
- Single-instance deployment
- Budget allows

**Steps**:

```bash
# 1. Plan outage window
# 2. Create data snapshot/backup
# 3. Stop containers
docker-compose -f docker-compose.prod.yml down -v

# 4. Upgrade infrastructure (AWS: change instance type)
# 5. Start containers on new hardware
docker-compose -f docker-compose.prod.yml up -d

# 6. Verify performance improvement
# Run load test: ab -n 10000 -c 100 https://example.com/api/products

# 7. Monitor for issues
docker logs -f app
```

**Limitations**:
- Single point of failure
- Long downtime during upgrade
- Expensive per-unit cost

### Database Scaling

**Read Replicas**:
```bash
# MySQL replication to read-only replicas
# 1. Configure master server
CHANGE MASTER TO MASTER_HOST='primary-db', MASTER_LOG_FILE='...' ;

# 2. Application uses replicas for SELECT queries
# UPDATE queries still go to master
# WRITE queries go to master

# Expected improvement:
# - Read throughput: 500 → 2000+ req/s
# - Response time for reports: 10s → 1-2s
```

**Sharding**:
```
User ID 1-50M → Shard 1 (DB1)
User ID 50M-100M → Shard 2 (DB2)
User ID 100M-150M → Shard 3 (DB3)

// In application:
shard_id = user_id % 3
execute_query_on_shard(shard_id, query)
```

**Cache Layer**:
```bash
# Use Redis cluster for better scalability
# 3-9 nodes in cluster mode
# Automatic data distribution
# Better resilience

redis-cli cluster nodes
# Expected improvement:
# - Cache throughput: 10K → 100K+ ops/sec
```

---

## Runbook Reference

### Quick Access Runbooks

| Scenario | Link | RTO |
|----------|------|-----|
| API Down | [Runbook](#runbook-api-response-time-degradation) | 5 min |
| High CPU | [Runbook](#runbook-high-cpu-usage) | 15 min |
| No DB Connection | [Runbook](#runbook-database-connection-pool-exhausted) | 10 min |
| Redis Failure | [Runbook](#runbook-redis-connection-failure) | 5 min |
| Disk Full | [Runbook](#runbook-disk-space-critical) | 20 min |
| Memory Leak | [Runbook](#runbook-high-cpu-usage) | 30 min |
| Slow Queries | QUERY_OPTIMIZATION_GUIDE.md | 60 min |
| DDoS Attack | Contact ISP/CDN provider | Varies |

### Command Reference

```bash
# Quick health check
curl -s https://example.com/api/health | jq '.'

# View all services
docker-compose -f docker-compose.prod.yml ps

# Real-time logs
docker-compose -f docker-compose.prod.yml logs -f --tail=50

# Database status
mysql -h $DATABASE_HOST -u root -p -e "SHOW PROCESSLIST;"

# Redis status
redis-cli -h redis-host -a "$REDIS_PASSWORD" INFO stats

# Restart service
docker-compose -f docker-compose.prod.yml restart app

# Check metrics
curl http://localhost:9090/api/v1/query?query=up

# Execute backup
bash /srv/applications/app/backup.sh

# Monitor performance
watch -n 5 'curl http://prometheus:9090/api/v1/query?query=rate(http_requests_total[1m])'
```

---

## Related Documentation

- **Deployment**: See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- **Operations**: See [OPERATIONS_GUIDE.md](OPERATIONS_GUIDE.md)
- **Performance**: See [PERFORMANCE_OPTIMIZATION.md](PERFORMANCE_OPTIMIZATION.md)
- **Security**: See [SECURITY_AUDIT_REPORT.md](SECURITY_AUDIT_REPORT.md)
- **Load Testing**: See [LOAD_TESTING_GUIDE.md](LOAD_TESTING_GUIDE.md)

---

**Last Updated**: January 10, 2026  
**Version**: 1.0 (Production Ready)  
**Owner**: Architecture Team  
**Next Review**: January 17, 2026
