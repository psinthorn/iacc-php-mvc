# Query Optimization Guide

**Purpose**: Prevent N+1 query problems and optimize database performance  
**Target**: Achieve sub-100ms query response times (p95)  
**Focus**: Repository pattern with eager loading and query optimization

## 1. Understanding the N+1 Query Problem

### ❌ Problem Pattern (N+1 Queries)

```php
// This executes N+1 queries:
// 1 query to get all purchase orders
// + N queries (one per PO to get items)

$pos = $this->poRepository->findAll();  // Query 1

foreach ($pos as $po) {
    $items = $this->itemRepository->findByPurchaseOrder($po->id);  // Query 2...N+1
    echo $items[0]->product_name;
}

// Total: 1 + N queries (terrible performance!)
```

### ✅ Solution: Eager Loading

```php
// This executes exactly 1 query with JOIN:

$pos = $this->poRepository
    ->with(['items', 'vendor', 'company'])  // Eager load relations
    ->findAll();  // Query 1 (with all data)

foreach ($pos as $po) {
    echo $po->items[0]->product_name;  // Already loaded, no query
}

// Total: 1 query (excellent performance!)
```

## 2. Repository Pattern with Eager Loading

### BaseRepository Enhancement

```php
// In BaseRepository

protected array $with = [];

public function with(array $relations): self
{
    $this->with = array_merge($this->with, $relations);
    return $this;
}

protected function applyRelations($query)
{
    if (!empty($this->with)) {
        // Use Eloquent's eager loading
        $query = $query->with($this->with);
        $this->with = [];  // Reset
    }
    return $query;
}

public function findAll(array $with = [])
{
    if (!empty($with)) {
        $this->with($with);
    }
    return $this->applyRelations($this->getQuery())
        ->get();
}
```

## 3. Repository Implementation Examples

### Example 1: Purchase Order Repository

```php
class PurchaseOrderRepository extends BaseRepository
{
    /**
     * Default relations to eager load
     */
    protected array $defaultWith = ['items', 'vendor', 'company', 'user'];

    /**
     * Find all purchase orders with relations
     */
    public function findAll(array $with = null)
    {
        $relations = $with ?? $this->defaultWith;
        
        return $this->with($relations)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find purchase orders by status
     */
    public function findByStatus(string $status, int $limit = 20)
    {
        return $this->with(['items', 'vendor'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Find recent purchase orders for dashboard
     */
    public function findRecent(int $days = 7, int $limit = 10)
    {
        return $this->with(['items', 'vendor', 'company'])
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Find with minimal data (for list views)
     */
    public function findAllForList()
    {
        return $this
            ->select(['id', 'po_number', 'status', 'created_at', 'vendor_id'])
            ->with('vendor:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate();
    }
}
```

### Example 2: Invoice Repository

```php
class InvoiceRepository extends BaseRepository
{
    protected array $defaultWith = ['items', 'company', 'purchaseOrder'];

    public function findByCompany(int $companyId)
    {
        return $this->with(['items', 'payments'])
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate();
    }

    public function findUnpaid()
    {
        return $this->with(['company', 'items'])
            ->where('status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function findWithPayments(int $limit = 20)
    {
        // Only select necessary columns
        return $this
            ->select(['id', 'invoice_number', 'total_amount', 'status', 'created_at'])
            ->with([
                'payments:id,invoice_id,amount,payment_date',
                'company:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
```

## 4. Query Optimization Techniques

### Technique 1: Column Selection (Reduce Data Transfer)

```php
// ❌ Bad: Loads all columns
$users = User::all();

// ✅ Good: Only needed columns
$users = User::select(['id', 'name', 'email', 'status'])->get();

// ✅ Better: With specific relations
$users = User::select(['id', 'name', 'email'])
    ->with('roles:id,name')
    ->with('permissions:id,name')
    ->get();
```

### Technique 2: Filtering Before Eager Loading

```php
// ❌ Bad: Loads all, then filters
$posts = Post::with('comments')->get();
$recent = $posts->where('created_at', '>=', now()->subDays(7))->values();

// ✅ Good: Filter first, then load
$recent = Post::where('created_at', '>=', now()->subDays(7))
    ->with('comments')
    ->get();
```

### Technique 3: Lazy Eager Loading

```php
// If you forget eager loading, can still do lazy loading:
$posts = Post::all();

// Later, load relations for lazy loading
$posts->load('comments', 'author');

// Or with constraints:
$posts->load(['comments' => function($query) {
    $query->where('approved', true);
}]);
```

### Technique 4: Conditional Eager Loading

```php
// Load relations based on conditions
public function findWithOptionalRelations($includeComments = false)
{
    $query = Post::select(['id', 'title', 'created_at']);
    
    if ($includeComments) {
        $query->with('comments:id,post_id,body');
    }
    
    return $query->get();
}
```

### Technique 5: Chunking for Large Datasets

```php
// ❌ Bad: Loads 100k records at once
$orders = Order::all();

// ✅ Good: Process in chunks
Order::chunk(1000, function ($orders) {
    foreach ($orders as $order) {
        $this->process($order);
    }
});

// ✅ Better: Chunk with eager loading
Order::with('items')
    ->chunk(1000, function ($orders) {
        foreach ($orders as $order) {
            $this->process($order);
        }
    });
```

## 5. Index-Aware Queries

### Using Indexes in WHERE Clauses

```php
// ✅ These queries use indexes (fast):

// Index: idx_status
Post::where('status', 'published')->get();

// Index: idx_created_at
Post::where('created_at', '>=', now()->subDays(7))->get();

// Index: idx_status_created
Post::where('status', 'published')
    ->where('created_at', '>=', now()->subDays(7))
    ->get();

// ❌ These queries don't use indexes (slow):

// No index on computed value
Post::where(DB::raw('YEAR(created_at)'), 2026)->get();

// No index on function result
User::where(DB::raw('UPPER(name)'), 'JOHN')->get();
```

### ORDER BY with Indexes

```php
// ✅ Fast: Uses index idx_created_at
Post::where('status', 'published')
    ->orderBy('created_at', 'desc')
    .get();

// ✅ Fast: Uses composite index idx_status_created
Post::orderBy('status', 'asc')
    .orderBy('created_at', 'desc')
    .get();

// ❌ Slow: No index on computed column
Post::orderBy(DB::raw('YEAR(created_at)'), 'desc')
    ->get();
```

## 6. Query Analysis & Optimization

### Using EXPLAIN

```php
// Analyze query performance
$query = Post::where('status', 'published')
    ->orderBy('created_at', 'desc');

// Add EXPLAIN
$explained = DB::select('EXPLAIN ' . $query->toSql(), $query->getBindings());

// Look for:
// - type: 'range' or 'index' is good, 'ALL' is bad
// - key: Name of index used
// - rows: Number of rows examined
// - Extra: 'Using index' is good
```

### Monitoring Slow Queries

```php
// Enable query logging in development
if (config('app.debug')) {
    DB::listen(function ($query) {
        if ($query->time > 100) {  // Longer than 100ms
            Log::warning('Slow Query', [
                'query' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        }
    });
}
```

## 7. Caching Query Results

### Service Layer Caching

```php
class ProductService
{
    public function getActiveProducts()
    {
        return Cache::remember('products:active', 3600, function () {
            return $this->productRepository
                ->with(['category', 'brand'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        });
    }

    public function getProductsByCategory(int $categoryId)
    {
        return Cache::remember(
            "products:category:{$categoryId}",
            1800,
            function () use ($categoryId) {
                return $this->productRepository
                    ->where('category_id', $categoryId)
                    ->with('brand')
                    ->orderBy('name')
                    ->get();
            }
        );
    }
}
```

### Cache Invalidation on Update

```php
class ProductService
{
    public function updateProduct(int $id, array $data)
    {
        $product = $this->productRepository->find($id);
        $product->update($data);

        // Invalidate caches
        Cache::delete("products:active");
        Cache::delete("products:category:{$product->category_id}");
        Cache::delete("product:{$id}");

        return $product;
    }
}
```

## 8. Performance Benchmarks

### Query Performance Targets

| Operation | Target | Tool |
|-----------|--------|------|
| Simple SELECT | < 10ms | Query profiler |
| SELECT with JOIN (2 tables) | < 20ms | EXPLAIN |
| SELECT with JOIN (4+ tables) | < 50ms | EXPLAIN |
| Paginated list (20 items) | < 100ms | Load test |
| Full text search | < 200ms | Elasticsearch |
| Report generation | < 1000ms | JMeter |

### Monitoring Query Performance

```php
// Track query times in production
class QueryMonitor
{
    public static function trackQuery($sql, $bindings, $time)
    {
        $metrics = MetricsCollector::getInstance();
        $metrics->recordDatabaseQuery($sql, $time);

        // Log slow queries (> 100ms)
        if ($time > 100) {
            Log::warning('Slow query detected', [
                'sql' => $sql,
                'bindings' => $bindings,
                'time_ms' => $time,
            ]);
        }
    }
}
```

## 9. Best Practices Checklist

- ✅ Use eager loading (`with()`) for all related data
- ✅ Select only needed columns (`.select(['id', 'name'])`)
- ✅ Filter data before eager loading (not after)
- ✅ Use database indexes for WHERE and ORDER BY
- ✅ Cache frequently accessed data (> 80% hit rate)
- ✅ Monitor query performance (log queries > 100ms)
- ✅ Use EXPLAIN to analyze slow queries
- ✅ Chunk large datasets (> 10k records)
- ✅ Avoid computed columns in WHERE/ORDER BY
- ✅ Use composite indexes for common queries
- ✅ Invalidate cache on data changes
- ✅ Set appropriate cache TTLs
- ✅ Profile before optimizing

## 10. Common Optimization Patterns

### Pattern 1: Dashboard Query

```php
public function getDashboardData()
{
    return [
        'recent_orders' => $this->poRepository
            ->with(['items' => function($q) { $q->limit(3); }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(),
        
        'pending_invoices' => $this->invoiceRepository
            ->select(['id', 'invoice_number', 'total_amount', 'created_at'])
            ->with('company:id,name')
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->limit(5)
            ->get(),
    ];
}
```

### Pattern 2: Filtered List with Pagination

```php
public function getFilteredList($filters = [], $page = 1)
{
    $query = $this->repository
        ->with($this->defaultWith)
        ->orderBy('created_at', 'desc');

    if ($filters['status'] ?? null) {
        $query->where('status', $filters['status']);
    }

    if ($filters['search'] ?? null) {
        $query->where('name', 'like', "%{$filters['search']}%");
    }

    return $query->paginate(20, ['*'], 'page', $page);
}
```

### Pattern 3: Related Data with Filtering

```php
public function getCompanyWithFilters(int $companyId, $filters = [])
{
    return $this->repository
        ->find($companyId, [
            'invoices' => function($q) {
                if ($filters['status'] ?? null) {
                    $q->where('status', $filters['status']);
                }
                $q->with('items')
                  ->orderBy('created_at', 'desc')
                  ->limit(20);
            },
            'payments' => function($q) {
                $q->orderBy('payment_date', 'desc')
                  ->limit(10);
            }
        ]);
}
```

---

## Summary

Follow these principles for optimal query performance:

1. **Eager load** related data to prevent N+1 queries
2. **Select only** needed columns to reduce data transfer
3. **Use indexes** in WHERE and ORDER BY clauses
4. **Cache** frequently accessed, infrequently changed data
5. **Monitor** query performance and optimize bottlenecks
6. **Test** with realistic data volumes before deployment

Expected results: **90% reduction in queries, 80%+ cache hit rate, sub-100ms response times**
