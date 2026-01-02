# Phase 6 Task 5: Performance Optimization

**Status**: In Progress (0 of 8 hours started)  
**Started**: January 10, 2026  
**Estimated Completion**: January 12, 2026  

## Task Overview

Performance optimization focuses on improving response times, reducing database load, and optimizing resource usage. This task includes database indexing, caching strategy, query optimization, and load testing.

## Subtask 1: Database Indexing & Query Analysis (2 hours)

### Current Database Index Status

#### Tables Requiring Indexing

**High Priority (Frequently Queried)**:

1. **users** table
   - Primary Key: `id` ✅
   - Foreign Keys: None
   - Search Fields: `email` (auth), `username`
   - **Missing Indexes**: 
     - UNIQUE INDEX on `email` (for login queries)
     - INDEX on `username` (for search)
     - INDEX on `status` (for filtering active users)

2. **companies** table
   - Primary Key: `id` ✅
   - Search Fields: `name`, `code`, `tax_id`
   - Filter Fields: `type`, `status`, `created_at`
   - **Missing Indexes**:
     - INDEX on `name` (company list search)
     - INDEX on `code` (quick lookup)
     - INDEX on `type` (filtering by type)
     - INDEX on `status` (active companies)
     - INDEX on `created_at` (recent companies)

3. **products** table
   - Primary Key: `id` ✅
   - Foreign Keys: `category_id`, `brand_id` (need indexes!)
   - Search Fields: `name`, `code`, `sku`
   - **Missing Indexes**:
     - INDEX on `category_id` (FK)
     - INDEX on `brand_id` (FK)
     - INDEX on `name` (product search)
     - INDEX on `sku` (quick lookup)
     - COMPOSITE INDEX on `category_id, status` (filtered lists)

4. **purchase_orders** table
   - Primary Key: `id` ✅
   - Foreign Keys: `company_id`, `vendor_id`, `user_id` (need indexes!)
   - Search/Filter Fields: `po_number`, `status`, `created_at`
   - **Missing Indexes**:
     - INDEX on `company_id` (FK)
     - INDEX on `vendor_id` (FK)
     - INDEX on `user_id` (FK)
     - INDEX on `status` (PO status filtering)
     - INDEX on `po_number` (search)
     - COMPOSITE INDEX on `created_at DESC, status` (recent POs)
     - COMPOSITE INDEX on `status, created_at` (status filtering with date range)

5. **purchase_order_items** table
   - Primary Key: `id` ✅
   - Foreign Keys: `purchase_order_id`, `product_id` (need indexes!)
   - **Missing Indexes**:
     - INDEX on `purchase_order_id` (line items lookup)
     - INDEX on `product_id` (FK)

6. **invoices** table
   - Primary Key: `id` ✅
   - Foreign Keys: `purchase_order_id`, `company_id`, `user_id` (need indexes!)
   - Search/Filter: `invoice_number`, `status`, `created_at`
   - **Missing Indexes**:
     - INDEX on `purchase_order_id` (FK)
     - INDEX on `company_id` (FK)
     - INDEX on `user_id` (FK)
     - INDEX on `status` (invoice status filtering)
     - INDEX on `invoice_number` (search)
     - COMPOSITE INDEX on `created_at DESC, status` (recent invoices)

7. **payments** table
   - Primary Key: `id` ✅
   - Foreign Keys: `invoice_id`, `company_id`, `user_id` (need indexes!)
   - Search/Filter: `status`, `payment_date`, `created_at`
   - **Missing Indexes**:
     - INDEX on `invoice_id` (FK)
     - INDEX on `company_id` (FK)
     - INDEX on `user_id` (FK)
     - INDEX on `status` (payment status)
     - INDEX on `payment_date` (payment filtering)

8. **audit_log** table (Phase 3)
   - Primary Key: `id` ✅
   - Foreign Keys: `user_id`, `table_name` (need indexes!)
   - **Missing Indexes**:
     - INDEX on `user_id` (audit by user)
     - INDEX on `table_name` (audit by table)
     - COMPOSITE INDEX on `table_name, record_id` (record history)
     - COMPOSITE INDEX on `created_at DESC` (recent changes)

### Index Creation Plan

```sql
-- High Priority (Create Immediately)

-- Users table
ALTER TABLE users ADD UNIQUE INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_username (username);
ALTER TABLE users ADD INDEX idx_status (status);

-- Companies table
ALTER TABLE companies ADD INDEX idx_name (name);
ALTER TABLE companies ADD INDEX idx_code (code);
ALTER TABLE companies ADD INDEX idx_type (type);
ALTER TABLE companies ADD INDEX idx_status (status);
ALTER TABLE companies ADD INDEX idx_created_at (created_at DESC);

-- Products table
ALTER TABLE products ADD INDEX idx_category_id (category_id);
ALTER TABLE products ADD INDEX idx_brand_id (brand_id);
ALTER TABLE products ADD INDEX idx_name (name);
ALTER TABLE products ADD INDEX idx_sku (sku);
ALTER TABLE products ADD INDEX idx_category_status (category_id, status);

-- Purchase Orders table
ALTER TABLE purchase_orders ADD INDEX idx_company_id (company_id);
ALTER TABLE purchase_orders ADD INDEX idx_vendor_id (vendor_id);
ALTER TABLE purchase_orders ADD INDEX idx_user_id (user_id);
ALTER TABLE purchase_orders ADD INDEX idx_status (status);
ALTER TABLE purchase_orders ADD INDEX idx_po_number (po_number);
ALTER TABLE purchase_orders ADD INDEX idx_status_created (status, created_at DESC);

-- Purchase Order Items table
ALTER TABLE purchase_order_items ADD INDEX idx_purchase_order_id (purchase_order_id);
ALTER TABLE purchase_order_items ADD INDEX idx_product_id (product_id);

-- Invoices table
ALTER TABLE invoices ADD INDEX idx_purchase_order_id (purchase_order_id);
ALTER TABLE invoices ADD INDEX idx_company_id (company_id);
ALTER TABLE invoices ADD INDEX idx_user_id (user_id);
ALTER TABLE invoices ADD INDEX idx_status (status);
ALTER TABLE invoices ADD INDEX idx_invoice_number (invoice_number);
ALTER TABLE invoices ADD INDEX idx_status_created (status, created_at DESC);

-- Payments table
ALTER TABLE payments ADD INDEX idx_invoice_id (invoice_id);
ALTER TABLE payments ADD INDEX idx_company_id (company_id);
ALTER TABLE payments ADD INDEX idx_user_id (user_id);
ALTER TABLE payments ADD INDEX idx_status (status);
ALTER TABLE payments ADD INDEX idx_payment_date (payment_date);

-- Audit Log table
ALTER TABLE audit_log ADD INDEX idx_user_id (user_id);
ALTER TABLE audit_log ADD INDEX idx_table_name (table_name);
ALTER TABLE audit_log ADD INDEX idx_table_record (table_name, record_id);
ALTER TABLE audit_log ADD INDEX idx_created_at (created_at DESC);
```

### Expected Performance Impact

- **SELECT queries**: 10-50x faster on indexed columns
- **JOIN operations**: 5-20x faster with FK indexes
- **Filtering**: 10-100x faster on indexed WHERE clauses
- **Sorting**: 5-10x faster with ORDER BY indexes
- **Storage overhead**: ~5-10% additional space (acceptable trade-off)

## Subtask 2: Redis Caching Implementation (2 hours)

### Cache Architecture

```
Application Layer
        ↓
CacheManager (Abstraction)
        ↓
    ├─ RedisCache (Production)
    ├─ ArrayCache (Development/Testing)
    └─ NullCache (Disabled)
        ↓
Data Layer (Queries)
```

### Cache Strategy

**Query Result Caching**:
```
Cache Key Pattern: "query:{repository}:{method}:{params_hash}"
Examples:
- "query:company:findAll:{filter_hash}" → 1 hour TTL
- "query:user:findByEmail:user@example.com" → 6 hours TTL
- "query:product:findByCategory:5" → 30 min TTL
```

**Session Caching**:
```
Cache Key: "session:{user_id}"
TTL: 30 minutes (synchronized with session timeout)
Data: User object, permissions, roles
```

**Application Data Caching**:
```
"config:app" → 24 hours
"permissions:all" → 12 hours
"roles:all" → 12 hours
"company:{id}" → 6 hours
"product:{id}" → 6 hours
"user:{id}" → 2 hours (frequently updated)
```

### Cache Invalidation Strategy

**Manual Invalidation Triggers**:
- After INSERT: Clear collection cache (e.g., "query:company:findAll:*")
- After UPDATE: Clear item cache + collection cache
- After DELETE: Clear item cache + collection cache

**Time-Based Expiration**:
- Frequently changing data: 15-30 minutes
- Moderately changing data: 1-6 hours
- Static data: 12-24 hours

**Cache Warming** (on deployment):
- Pre-load configuration data
- Pre-load user roles and permissions
- Pre-load top 100 companies (common usage)
- Pre-load all product categories

## Subtask 3: Query Optimization (2 hours)

### N+1 Query Problem Solutions

**Current Problem Pattern**:
```php
// BAD: N+1 queries (1 PO query + N item queries)
$pos = $repository->findAll();
foreach ($pos as $po) {
    $items = $itemRepository->findByPurchaseOrder($po->id);
}
```

**Optimized with Eager Loading**:
```php
// GOOD: Single query with JOIN (1 query total)
$pos = $repository->with(['items', 'vendor', 'company'])->findAll();
```

### Repository Query Optimization

**Pattern 1: Eager Load Relations**
```php
// ProductRepository
public function findByCategory($categoryId, $with = [])
{
    $defaults = ['category', 'brand', 'supplier'];
    $relations = array_merge($defaults, $with);
    
    return $this->with($relations)
        ->where('category_id', $categoryId)
        ->get();
}
```

**Pattern 2: Index-Aware Queries**
```php
// Use indexes in WHERE clauses
public function findActiveByStatus($status)
{
    // Uses INDEX idx_status
    return $this->where('status', $status)
        ->orderBy('created_at', 'desc')
        ->paginate();
}
```

**Pattern 3: Select Only Needed Columns**
```php
// Don't load unnecessary data
public function findForListing()
{
    return $this->select(['id', 'name', 'code', 'status', 'created_at'])
        ->with('category:id,name')
        ->get();
}
```

### Query Analysis Tools

**Enable Slow Query Log** (production):
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.5;  -- Log queries > 500ms
```

**EXPLAIN Analysis**:
```sql
EXPLAIN SELECT ... FROM purchase_orders 
WHERE status = 'pending' AND created_at > '2026-01-01'
ORDER BY created_at DESC;

-- Look for: type=range, key=idx_status_created, rows < 1000
```

## Subtask 4: HTTP & Asset Optimization (1 hour)

### Gzip Compression (Already in Nginx Config)

Configured in `docker/nginx/conf.d/default.conf`:
```nginx
gzip on;
gzip_types text/plain text/css text/javascript application/json;
gzip_min_length 1000;
gzip_vary on;
```

**Expected Compression**:
- JSON responses: 70-80% reduction
- HTML: 60-70% reduction
- CSS/JS: 60-80% reduction

### Static Asset Caching (Already in Nginx Config)

```nginx
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
}
```

### Browser Caching Headers

Already implemented in Nginx configuration:
- Immutable assets: 30-day cache
- Dynamic content: No-cache, must-revalidate
- ETag support for cache validation

### CDN Integration (Ready for Implementation)

Document for CDN setup:
- CloudFlare for global caching
- Origin shield for origin protection
- Cache purge strategy on deployment
- Image optimization with CloudFlare's Mirage

## Subtask 5: Load Testing Framework (1 hour)

### Load Testing Scenarios

**Tool**: Apache JMeter or equivalent

**Scenario 1: API Login Load Test**
```
Duration: 5 minutes
Users: 100 concurrent
Ramp-up: 30 seconds
Request: POST /api/auth/login
Response Assertion: 200 OK
Throughput target: 100+ requests/sec
```

**Scenario 2: Product List Load Test**
```
Duration: 5 minutes
Users: 500 concurrent
Ramp-up: 60 seconds
Request: GET /api/products?page=1&limit=20
Response Assertion: 200 OK, < 100ms
Throughput target: 500+ requests/sec
```

**Scenario 3: Purchase Order Workflow**
```
Duration: 10 minutes
Users: 50 concurrent
Ramp-up: 30 seconds
Flow:
1. Login
2. Create PO
3. Add Items
4. Submit
5. Approve
6. Receive
Response target: Each step < 200ms
```

### Performance Benchmarks (Target)

| Operation | Target | Tool |
|-----------|--------|------|
| API Response (p95) | < 100ms | New Relic/Datadog |
| Database Query (p95) | < 50ms | MySQL slow query log |
| Page Load (TTFB) | < 200ms | Lighthouse |
| Concurrent Users | 1000+ | JMeter |
| Throughput | 500+ req/s | JMeter |
| Error Rate | < 0.1% | Monitoring |

## Implementation Checklist

- [ ] Create database indexes (SQL migration)
- [ ] Test index performance with EXPLAIN
- [ ] Create CacheManager class
- [ ] Create RedisCache implementation
- [ ] Create ArrayCache for testing
- [ ] Implement cache invalidation logic
- [ ] Add cache warming to deployment
- [ ] Update repositories with eager loading
- [ ] Add query optimization guide
- [ ] Verify Nginx gzip and caching headers
- [ ] Create JMeter test plans
- [ ] Run load tests and document results
- [ ] Create performance monitoring dashboard (Grafana)
- [ ] Document performance best practices

## Files to Create/Modify

**New Files**:
- `src/Cache/CacheManager.php` - Cache abstraction
- `src/Cache/RedisCache.php` - Redis implementation
- `src/Cache/ArrayCache.php` - In-memory cache
- `src/Cache/NullCache.php` - No-op cache
- `src/Cache/CacheInterface.php` - Interface
- `src/Config/CacheConfig.php` - Cache configuration
- `docker/mysql/init/02-indexes.sql` - Index creation
- `performance/jmeter/load-test.jmx` - JMeter test plan
- `PERFORMANCE_BENCHMARKS.md` - Results documentation

**Modified Files**:
- `src/Repositories/BaseRepository.php` - Add eager loading
- `.env.example` - Add cache configuration
- `docker-compose.prod.yml` - Add cache configuration

## Success Criteria

✅ All identified indexes created and verified  
✅ Query response time: p95 < 100ms  
✅ Cache hit rate: > 80% for hot data  
✅ Concurrent users supported: 1000+  
✅ Throughput: > 500 requests/second  
✅ Database query time: p95 < 50ms  
✅ Memory usage: < 512MB (Redis)  
✅ API response time: p95 < 200ms including cache miss  

## Timeline

- **Hour 1**: Database indexing analysis and creation
- **Hour 2**: Redis cache implementation
- **Hour 3**: Query optimization and eager loading
- **Hour 4**: Load testing and results analysis
- **Remaining 4 hours**: Task 6-7 (Security, Documentation)

## Next Steps

1. Create database index migration script
2. Implement CacheManager and RedisCache
3. Update repositories with eager loading patterns
4. Create load test scenarios
5. Run benchmarks and document results
