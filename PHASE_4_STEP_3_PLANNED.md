# Phase 4 Step 3: Database Models & Repository Pattern - Planned

**Status**: PLANNING  
**Timeline**: January 7-20, 2026 (Weeks 2-3)  
**Effort**: 30-40 hours  
**Blocks**: All Phase 4 Step 2 foundation work  

## Overview

Phase 4 Step 3 will implement the data access layer with:
- 31 Model classes (one per database table)
- Repository pattern for data abstraction
- Base Model class with common functionality
- Query builder for dynamic SQL construction
- Relationship mapping (has_many, belongs_to, etc.)
- Eager loading support
- Model validation

## Architecture

### Base Model Class
```php
namespace App\Models;

abstract class Model {
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $attributes = [];
    protected $relations = [];
    
    // Common methods
    public static function find($id)
    public static function all()
    public static function where($column, $operator, $value)
    public static function first()
    public function save()
    public function update(array $attributes)
    public function delete()
    public function toArray()
}
```

### Repository Pattern
```php
namespace App\Repositories;

interface RepositoryInterface {
    public function all();
    public function find($id);
    public function create(array $attributes);
    public function update($id, array $attributes);
    public function delete($id);
}

class Repository implements RepositoryInterface {
    protected $model;
    
    public function __construct(Model $model) {
        $this->model = $model;
    }
    
    // Implementation...
}
```

## 31 Models to Create

### Core Business
1. **Company** - Vendor/Company information
2. **Contact** - Company contacts
3. **Product** - Product inventory
4. **ProductType** - Product classification
5. **Category** - Product categories
6. **Brand** - Brand/Department

### Purchasing
7. **PurchaseRequest** - Purchase requests (pr)
8. **PurchaseOrder** - Purchase orders (po)
9. **PurchaseOrderDetail** - Line items
10. **ReceiveItem** - Received items
11. **Supplier** - Supplier management

### Sales
12. **Customer** - Customer information
13. **SalesOrder** - Sales orders
14. **SalesOrderDetail** - Sales line items
15. **Invoice** - Customer invoices
16. **InvoiceDetail** - Invoice line items

### Inventory
17. **StockMovement** - Inventory transactions
18. **Warehouse** - Warehouse locations
19. **Location** - Stock locations
20. **Stock** - Current stock levels

### Accounting
21. **Payment** - Payment records
22. **Expense** - Expense tracking
23. **ExpenseDetail** - Expense line items
24. **Delivery** - Delivery tracking
25. **DeliveryDetail** - Delivery items

### Vouchers & Documents
26. **Voucher** - General vouchers
27. **VoucherDetail** - Voucher line items
28. **ComplaintTicket** - Complaint tracking
29. **Report** - Report definitions
30. **AuditLog** - Audit trail (already exists)

### System
31. **User** - User accounts (if needed)

## Tasks for Step 3

### 1. Create Base Model Class
- File: `src/Models/Model.php`
- Features:
  - Constructor that accepts database connection
  - Attribute management
  - Relationship support
  - Timestamps (created_at, updated_at)
  - Soft deletes (optional)
  - Attribute casting
  - Validation hooks

### 2. Implement Query Builder
- File: `src/Foundation/QueryBuilder.php`
- Methods:
  - `where()`, `orWhere()`, `whereIn()`, `whereBetween()`
  - `select()`, `limit()`, `offset()`, `orderBy()`
  - `join()`, `leftJoin()`, `rightJoin()`
  - `groupBy()`, `having()`
  - `get()`, `first()`, `count()`, `exists()`

### 3. Create Repository Base Class
- File: `src/Repositories/Repository.php`
- Interface: `src/Repositories/RepositoryInterface.php`
- Methods: CRUD operations, common queries
- Generic implementation reusable by all models

### 4. Generate 31 Model Classes
- Directory: `src/Models/`
- Each model:
  - Extends Base Model
  - Defines table name
  - Defines relationships
  - Defines validation rules
  - Defines fillable attributes
  - Includes docblock for properties

### 5. Create 31 Repository Classes
- Directory: `src/Repositories/`
- Each repository:
  - Extends Repository base class
  - Implements business-specific queries
  - Handles domain logic
  - Optimizes queries for use cases

### 6. Database Connection Layer
- File: `src/Foundation/Database.php`
- PDO wrapper with:
  - Connection pooling
  - Query execution
  - Transaction support
  - Error handling

### 7. Migration System (Optional)
- File: `src/Foundation/Migration.php`
- Version control for schema changes
- Rollback support
- Track applied migrations

## Expected Code

### Model Example
```php
namespace App\Models;

class PurchaseOrder extends Model {
    protected $table = 'purchase_order';
    protected $fillable = [
        'po_number', 'vendor_id', 'order_date', 
        'due_date', 'total', 'status'
    ];
    protected $dates = ['order_date', 'due_date'];
    
    public function vendor() {
        return $this->belongsTo(Company::class, 'vendor_id');
    }
    
    public function items() {
        return $this->hasMany(PurchaseOrderDetail::class, 'po_id');
    }
    
    public function getStatusLabel() {
        // ...
    }
}
```

### Repository Example
```php
namespace App\Repositories;

class PurchaseOrderRepository extends Repository {
    public function getPendingOrders() {
        return $this->model->where('status', '=', 'pending')
            ->orderBy('due_date')
            ->get();
    }
    
    public function findWithDetails($id) {
        return $this->model->with('items', 'vendor')
            ->find($id);
    }
}
```

## Service Layer Integration

Phase 4 Step 4 will create services that use repositories:
```php
class PurchaseOrderService {
    private $repository;
    
    public function __construct(PurchaseOrderRepository $repo) {
        $this->repository = $repo;
    }
    
    public function createOrder(array $data) {
        // Validation
        // Business logic
        // Create order with items
        // Return result
    }
}
```

## Success Criteria

✅ All 31 models created and working  
✅ Base Model class complete with relationships  
✅ Repository pattern implemented  
✅ Query builder supporting common operations  
✅ Database connection handling  
✅ All models validated via automated tests  
✅ Zero data loss from legacy system  
✅ Full backward compatibility  

## Timeline

| Task | Duration | Days |
|------|----------|------|
| Base Model class | 4 hours | 1 |
| Query builder | 6 hours | 1.5 |
| Repository base class | 3 hours | 1 |
| Generate 31 models | 8 hours | 2 |
| Create 31 repositories | 5 hours | 1.5 |
| Database connection | 2 hours | 0.5 |
| Testing & refinement | 3 hours | 1 |
| Documentation | 2 hours | 0.5 |
| **Total** | **33 hours** | **9 days** |

## Dependencies

✅ Phase 4 Step 2: Foundation (ServiceContainer, Config, Logger)  
✅ Existing database schema with 31 tables  
✅ PHP 7.4+ PDO extension  

## Deliverables

1. `src/Models/Model.php` - Base model class
2. `src/Foundation/QueryBuilder.php` - Query builder
3. `src/Repositories/Repository.php` - Repository base class
4. `src/Repositories/RepositoryInterface.php` - Interface
5. 31 Model classes in `src/Models/`
6. 31 Repository classes in `src/Repositories/`
7. `src/Foundation/Database.php` - Database connection
8. `tests/Unit/Models/` - Model tests
9. `tests/Unit/Repositories/` - Repository tests
10. `PHASE_4_STEP_3_COMPLETION_REPORT.md` - Documentation

## Next Step

After Phase 4 Step 3 completes, Phase 4 Step 4 will:
- Create Service layer classes (12-15 services)
- Extract business logic from controllers
- Implement application use cases
- Add transaction support
- Create service interfaces

---

**Prepared for**: Phase 4 Step 3 Implementation  
**Ready to start**: January 7, 2026  
**Estimated completion**: January 20, 2026
