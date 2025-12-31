# Phase 4: Architecture Refactoring - IMPLEMENTATION ROADMAP

**Created**: December 31, 2025  
**Phase Duration**: 26 weeks (6.5 months)  
**Total Effort**: 310-365 hours  
**Current Status**: Step 1 Complete - Ready for Step 2

---

## High-Level Project Structure

### Parallel Development Strategy

Since this is a large refactoring, we'll use a **strangler pattern** to gradually replace the old architecture:

```
Timeline:
  Week 1-13:  Foundation + Models + Services (Backend)
  Week 5-18:  Controllers (using new models/services)
  Week 14-23: Views (template conversion)
  Week 18-26: Testing + Integration + Deployment
  
Result: Both systems run in parallel until old system is fully decommissioned
```

### Weekly Breakdown

#### **Weeks 1-3: Step 2 - Foundation Setup**

**Goals**:
- Set up modern PHP project structure
- Implement service container
- Create routing system
- Establish configuration management

**Deliverables**:
```
src/
├── Foundation/
│   ├── Application.php          # Main app class
│   ├── ServiceContainer.php     # DI container
│   ├── Router.php               # URL routing
│   ├── Request.php              # HTTP request
│   ├── Response.php             # HTTP response
│   └── FileLoader.php           # Configuration loader
├── Middleware/
│   └── Middleware.php           # Base middleware class
└── Exceptions/
    └── BaseException.php        # Exception handling

config/
├── app.php                      # App configuration
├── database.php                 # Database config
├── services.php                 # Service registration
└── routes.php                   # Route definitions

bootstrap/
├── app.php                      # Application bootstrapping
└── autoload.php                 # PSR-4 autoloader
```

**Key Classes to Create**:

1. **ServiceContainer** - Dependency injection
```php
class ServiceContainer {
  public function register($name, $factory) { }
  public function get($name) { }
  public function bind($abstract, $concrete) { }
  public function singleton($name, $factory) { }
}
```

2. **Router** - URL routing
```php
class Router {
  public function get($path, $handler) { }
  public function post($path, $handler) { }
  public function put($path, $handler) { }
  public function delete($path, $handler) { }
  public function match($method, $path) { }
}
```

3. **Request** - HTTP request handling
```php
class Request {
  public function input($key, $default = null) { }
  public function all() { }
  public function method() { }
  public function path() { }
  public function url() { }
}
```

4. **Response** - HTTP response handling
```php
class Response {
  public function json($data, $status = 200) { }
  public function view($template, $data = []) { }
  public function redirect($url) { }
  public function status($code) { }
}
```

**Effort**: 30-40 hours  
**Risk**: Low (foundation work, limited integration points)

---

#### **Weeks 3-6: Step 3 - Model & Repository Layer**

**Goals**:
- Create database abstraction layer
- Build 31 model classes (one per table)
- Implement repository pattern
- Support relationships (has many, belongs to)

**Deliverables**:

```
src/Models/
├── Model.php                    # Base model class
├── Repository.php               # Base repository
├── Product.php                  # Product model (example)
├── PurchaseOrder.php            # PO model
├── PurchaseRequest.php          # PR model
├── Invoice.php                  # Invoice model
├── Deliver.php                  # Delivery model
├── User.php                     # User model
├── Company.php                  # Company model
├── Brand.php                    # Brand model
├── Store.php                    # Store model
├── Receive.php                  # Receive model
├── ProductType.php              # Product type model
├── SendOutItem.php              # Send out item model
└── ... (17 more models)         # All 31 table models

Database/
├── QueryBuilder.php             # SQL query builder
├── Connection.php               # Database connection
└── Migration.php                # Migration system
```

**Key Features**:

1. **Query Builder** - Chainable SQL building
```php
$products = app('Product')
  ->where('brand_id', $brandId)
  ->whereIn('status', ['active', 'pending'])
  ->orderBy('created_at', 'desc')
  ->limit(10)
  ->get();
```

2. **Relationships** - Data modeling
```php
class Product extends Model {
  public function brand() {
    return $this->belongsTo(Brand::class);
  }
  
  public function orders() {
    return $this->hasMany(PurchaseOrder::class);
  }
}
```

3. **Timestamps** - Automatic handling
```php
// Automatically managed:
- created_at (INSERT)
- updated_at (UPDATE)
```

4. **Soft Deletes** - Logical deletes
```php
$product->delete();         // Sets deleted_at
$products = Product::withTrashed()->get();
```

**Models Created**: 31  
**Effort**: 50-60 hours  
**Risk**: Medium (database compatibility, migration complexity)

---

#### **Weeks 6-10: Step 4 - Service Layer**

**Goals**:
- Extract business logic from pages
- Create service classes
- Implement complex workflows
- Refactor class.hard.php functions

**Deliverables**:

```
src/Services/
├── Service.php                  # Base service class
├── ProductService.php           # Product operations
├── PurchaseOrderService.php     # PO operations
├── PurchaseRequestService.php   # PR operations
├── UserService.php              # User operations
├── AuthService.php              # Authentication
├── ReportService.php            # Reporting
├── AuditService.php             # Audit logging
├── NotificationService.php      # Notifications/email
├── ValidationService.php        # Input validation
├── DocumentService.php          # Document generation
├── ImportService.php            # Data import
├── ExportService.php            # Data export
├── PaymentService.php           # Payment processing
└── SearchService.php            # Search/filtering
```

**Service Examples**:

1. **ProductService** - Product operations
```php
class ProductService extends Service {
  public function create(array $data) { }
  public function update($id, array $data) { }
  public function delete($id) { }
  public function search($filters) { }
  public function getWithBrand($id) { }
  public function checkStock($productId, $qty) { }
  public function reduceStock($productId, $qty) { }
}
```

2. **PurchaseOrderService** - Complex order workflow
```php
class PurchaseOrderService extends Service {
  public function create(CreatePORequest $request) { }
  public function addItem($poId, $itemData) { }
  public function removeItem($poId, $itemId) { }
  public function approve($poId) { }
  public function receive($poId, $receivedQty) { }
  public function invoice($poId) { }
  public function complete($poId) { }
  public function cancel($poId, $reason) { }
}
```

3. **ReportService** - Complex reporting
```php
class ReportService extends Service {
  public function salesByProduct($startDate, $endDate) { }
  public function inventoryStatus() { }
  public function paymentHistory($companyId) { }
  public function auditTrail($entityId, $entityType) { }
  public function profitMargin() { }
}
```

**Logic Migrated From**:
- `product.php` → ProductService
- `po-*.php` → PurchaseOrderService  
- `pr-*.php` → PurchaseRequestService
- `class.hard.php` → Multiple services
- `authorize.php` → AuthService
- `report.php` → ReportService

**Services Created**: 14-15  
**Effort**: 40-50 hours  
**Risk**: Medium-High (large refactoring, business logic extraction)

---

#### **Weeks 10-14: Step 5 - Controller Layer**

**Goals**:
- Create resource controllers for CRUD operations
- Implement request validation
- Format responses (web and JSON)
- Support both web and API

**Deliverables**:

```
src/Controllers/
├── Controller.php               # Base controller
├── ProductController.php        # Product CRUD
├── PurchaseOrderController.php  # PO CRUD
├── PurchaseRequestController.php # PR CRUD
├── InvoiceController.php        # Invoice CRUD
├── UserController.php           # User CRUD
├── CompanyController.php        # Company CRUD
├── BrandController.php          # Brand CRUD
├── AuthController.php           # Authentication
├── ReportController.php         # Reports
├── DashboardController.php      # Dashboard
├── SearchController.php         # Search
├── ExportController.php         # Export/PDF
└── ... (25+ more controllers)
```

**Standard Resource Controller Pattern**:

```php
class ProductController extends Controller {
  // Web routes
  public function index() { }       // GET /products → list view
  public function create() { }      // GET /products/create → form view
  public function store() { }       // POST /products → save
  public function show($id) { }     // GET /products/{id} → detail view
  public function edit($id) { }     // GET /products/{id}/edit → edit form
  public function update($id) { }   // PUT /products/{id} → update
  public function destroy($id) { }  // DELETE /products/{id} → delete
  
  // API routes (JSON responses)
  public function indexApi() { }    // GET /api/products
  public function showApi($id) { }  // GET /api/products/{id}
  public function storeApi() { }    // POST /api/products
  public function updateApi($id) { } // PUT /api/products/{id}
  public function destroyApi($id) { } // DELETE /api/products/{id}
}
```

**Controllers Created**: 35-40  
**Key Methods per Controller**: 10-14  
**Total Methods**: 350-560  
**Effort**: 40-50 hours  
**Risk**: Medium (integration with services and models)

---

#### **Weeks 14-18: Step 6 - View/Template Layer**

**Goals**:
- Implement template engine (Blade or Twig)
- Convert 170+ HTML pages to templates
- Create reusable components
- Separate presentation from logic

**Template Structure**:

```
resources/views/
├── layouts/
│   ├── app.blade.php            # Main layout
│   ├── sidebar.blade.php        # Sidebar component
│   └── header.blade.php         # Header component
│
├── product/
│   ├── index.blade.php          # List products
│   ├── create.blade.php         # Create form
│   ├── edit.blade.php           # Edit form
│   ├── show.blade.php           # Product detail
│   └── partials/
│       └── form.blade.php       # Reusable form
│
├── purchase_order/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── show.blade.php
│   └── partials/
│
├── reports/
│   ├── sales.blade.php
│   ├── inventory.blade.php
│   └── payments.blade.php
│
├── components/
│   ├── form-group.blade.php
│   ├── table.blade.php
│   ├── pagination.blade.php
│   ├── alert.blade.php
│   └── modal.blade.php
│
└── dashboard/
    └── index.blade.php
```

**Template Language**: Blade (Laravel syntax, PHP-like)

Example Blade template:
```blade
@extends('layouts.app')

@section('title', 'Products')

@section('content')
  <h1>Products</h1>
  
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Brand</th>
        <th>Stock</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($products as $product)
        <tr>
          <td>{{ $product->id }}</td>
          <td>{{ $product->name }}</td>
          <td>{{ $product->brand->name }}</td>
          <td>
            @if($product->quantity < 10)
              <span class="badge badge-warning">{{ $product->quantity }}</span>
            @else
              {{ $product->quantity }}
            @endif
          </td>
          <td>
            <a href="/products/{{ $product->id }}/edit" class="btn btn-sm btn-primary">Edit</a>
            <form action="/products/{{ $product->id }}" method="POST" style="display:inline;">
              @method('DELETE')
              @csrf
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="text-center">No products found</td>
        </tr>
      @endforelse
    </tbody>
  </table>
  
  {{ $products->links() }}
@endsection
```

**Views Created**: 200+  
**Effort**: 35-45 hours  
**Risk**: Medium (large number of templates, consistency)

---

#### **Weeks 18-20: Step 7 - Routing & Middleware**

**Goals**:
- Define all web routes
- Define all API routes (RESTful v1)
- Create middleware pipeline
- Integrate authentication

**Route Structure**:

```php
// routes/web.php - Web routes (HTML responses)
Route::group(['middleware' => ['auth']], function() {
  // Dashboard
  Route::get('/', 'DashboardController@index')->name('dashboard');
  
  // Products
  Route::resource('products', 'ProductController');
  
  // Purchase Orders
  Route::resource('purchase-orders', 'PurchaseOrderController');
  Route::post('purchase-orders/{id}/approve', 'PurchaseOrderController@approve');
  Route::post('purchase-orders/{id}/receive', 'PurchaseOrderController@receive');
  
  // Reports
  Route::get('/reports/sales', 'ReportController@sales');
  Route::get('/reports/inventory', 'ReportController@inventory');
  
  // User Management
  Route::resource('users', 'UserController');
  
  // Audit Log
  Route::get('/audit', 'AuditController@index');
});

// routes/api.php - API routes (JSON responses)
Route::group(['prefix' => 'api', 'middleware' => ['auth:api']], function() {
  // Products API
  Route::get('products', 'Api\ProductController@index');
  Route::post('products', 'Api\ProductController@store');
  Route::get('products/{id}', 'Api\ProductController@show');
  Route::put('products/{id}', 'Api\ProductController@update');
  Route::delete('products/{id}', 'Api\ProductController@destroy');
  
  // Purchase Orders API
  Route::get('purchase-orders', 'Api\PurchaseOrderController@index');
  Route::post('purchase-orders', 'Api\PurchaseOrderController@store');
  Route::get('purchase-orders/{id}', 'Api\PurchaseOrderController@show');
  
  // Reports API
  Route::get('reports/sales', 'Api\ReportController@sales');
  Route::get('reports/inventory', 'Api\ReportController@inventory');
});
```

**Middleware Chain**:
```
HTTP Request
    ↓
ExceptionHandling
    ↓
Logging
    ↓
Authentication
    ↓
Authorization
    ↓
Validation
    ↓
CSRF (web routes only)
    ↓
Route Handler (Controller)
    ↓
Response Formatting
    ↓
HTTP Response
```

**Middleware Classes**:
- `AuthMiddleware` - Check authentication
- `CsrfMiddleware` - CSRF token validation
- `ValidationMiddleware` - Input validation
- `AuthorizationMiddleware` - Check permissions
- `LoggingMiddleware` - Request/response logging
- `RateLimitingMiddleware` - Rate limiting
- `ApiVersionMiddleware` - API version handling

**Routes**:
- ~80 web routes (CRUD + custom actions)
- ~40 API endpoints (v1)

**Effort**: 25-30 hours  
**Risk**: Low-Medium (routing logic is straightforward)

---

#### **Weeks 20-23: Step 8 - Testing Infrastructure**

**Goals**:
- Set up PHPUnit
- Create unit tests
- Create feature/integration tests
- Achieve 80%+ code coverage

**Test Structure**:

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── ProductModelTest.php
│   │   ├── PurchaseOrderModelTest.php
│   │   └── ... (31 model tests)
│   ├── Services/
│   │   ├── ProductServiceTest.php
│   │   ├── PurchaseOrderServiceTest.php
│   │   └── ... (15 service tests)
│   └── Validation/
│       ├── CreateProductValidationTest.php
│       └── ... (20 validation tests)
│
├── Feature/
│   ├── ProductControllerTest.php
│   ├── PurchaseOrderControllerTest.php
│   ├── AuthControllerTest.php
│   ├── DashboardTest.php
│   └── ... (35+ feature tests)
│
├── Integration/
│   ├── ProductWorkflowTest.php
│   ├── OrderToInvoiceTest.php
│   └── ... (10 integration tests)
│
├── Api/
│   ├── ProductApiTest.php
│   ├── PurchaseOrderApiTest.php
│   └── ... (15 API tests)
│
├── Fixtures/
│   ├── ProductFixture.php
│   ├── OrderFixture.php
│   └── UserFixture.php
│
└── TestCase.php                 # Base test class
```

**Test Examples**:

Unit Test:
```php
class ProductServiceTest extends TestCase {
  public function test_create_product() {
    $service = new ProductService();
    $product = $service->create([
      'name' => 'Test Product',
      'brand_id' => 1,
      'quantity' => 100,
    ]);
    
    $this->assertNotNull($product->id);
    $this->assertEquals('Test Product', $product->name);
  }
  
  public function test_reduce_stock() {
    $product = Product::find(1);
    $initialStock = $product->quantity;
    
    $service = new ProductService();
    $service->reduceStock($product->id, 10);
    
    $product->refresh();
    $this->assertEquals($initialStock - 10, $product->quantity);
  }
}
```

Feature Test:
```php
class ProductControllerTest extends TestCase {
  public function test_list_products() {
    $response = $this->get('/products');
    $response->assertStatus(200);
    $response->assertSee('Products');
  }
  
  public function test_create_product() {
    $response = $this->post('/products', [
      'name' => 'New Product',
      'brand_id' => 1,
    ]);
    
    $response->assertRedirect('/products');
    $this->assertDatabaseHas('product', ['name' => 'New Product']);
  }
}
```

**Tests to Create**:
- Unit tests: 70+
- Feature tests: 40+
- Integration tests: 10+
- API tests: 15+
- **Total**: 135+ test classes

**Coverage Target**: 80%+

**Effort**: 40-50 hours  
**Risk**: Medium (test maintenance, test data setup)

---

#### **Weeks 23-26: Step 9 - Migration & Compatibility**

**Goals**:
- Run both systems in parallel
- Gradual feature migration
- Zero downtime transition
- Performance testing

**Dual-System Architecture**:

```
Request → Router
    ├─→ /iacc/old_page.php (Legacy)
    │   └─→ Old system response
    │
    └─→ /products (New)
        └─→ ProductController
            └─→ New system response
```

**Migration Strategy**:

1. **Week 23**: Old system passes through new routing (compatibility layer)
2. **Week 24**: 50% of pages switched to new system
3. **Week 25**: 90% of pages switched
4. **Week 26**: Complete migration, legacy code archived

**Compatibility Layer**:
- Request adapter (old $_REQUEST → new Request)
- Response adapter (old echo → new Response)
- Database abstraction (old mysqli → new Model)
- Session compatibility ($\_SESSION works with new auth)

**Performance Testing**:
- Load test both systems
- Compare page load times
- Check memory usage
- Verify query count (N+1 fixes)

**Effort**: 20-25 hours  
**Risk**: Medium-High (critical transition period)

---

#### **Week 26: Step 10 - Documentation & Deployment**

**Goals**:
- Complete documentation
- Deploy to production
- Gather metrics
- Plan next improvements

**Documentation Deliverables**:

```
docs/
├── ARCHITECTURE.md              # System design
├── API.md                        # API documentation
├── DEVELOPER_GUIDE.md           # How to develop
├── DEPLOYMENT.md                # Deployment steps
├── TROUBLESHOOTING.md           # Common issues
├── DATABASE.md                  # Schema documentation
├── TESTING.md                   # How to test
├── CHANGELOG.md                 # What changed
└── openapi.json                 # OpenAPI spec
```

**Deployment Checklist**:
- Database schema migration
- New code deployment
- Service configuration
- Monitoring setup
- Rollback plan
- Performance verification

**Effort**: 15-20 hours  
**Risk**: Low (deployment is straightforward)

---

## Implementation Best Practices

### 1. **Test-Driven Development**
- Write tests before code
- Red → Green → Refactor cycle
- Minimum 80% coverage

### 2. **Code Review**
- Every PR reviewed before merge
- Focus on: logic, security, performance
- Require approval before merge

### 3. **Incremental Delivery**
- Deploy weekly when possible
- Feature flags for incomplete work
- Keep both systems operational

### 4. **Documentation**
- Document as you build
- Keep examples current
- API documentation with tests

### 5. **Performance Monitoring**
- Benchmark before/after
- Track query performance
- Monitor resource usage

### 6. **Security**
- Validate all inputs
- Escape all outputs
- Check authorization
- Use CSRF tokens
- Log all changes

---

## Success Metrics

### Code Quality
- ✅ Cyclomatic complexity < 10
- ✅ Code duplication < 5%
- ✅ Test coverage > 80%
- ✅ 0 security vulnerabilities
- ✅ 0 data loss incidents

### Performance
- ✅ Page load < 500ms
- ✅ API response < 200ms
- ✅ 0 N+1 query problems
- ✅ Memory usage < 50MB/request

### Business
- ✅ 100% feature parity
- ✅ 0 data loss
- ✅ 0 downtime
- ✅ All audit trails preserved

### Team
- ✅ All developers understand new architecture
- ✅ New features take < 4 hours
- ✅ Bugs fixed < 2 hours
- ✅ Zero critical incidents

---

## Risk Management

### Identified Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-----------|--------|-----------|
| Scope creep | 70% | High | Strict step tracking, separate Phase 5 |
| Timeline slip | 60% | High | 25% buffer, weekly reviews |
| Integration bugs | 40% | Medium | Comprehensive testing, staging |
| Data corruption | 5% | Critical | Backups, transaction support |
| Performance regression | 20% | Medium | Benchmarking, comparison |

### Contingency Plans

**If behind schedule**:
- Skip some unit tests → Focus on feature tests
- Reduce API endpoints (MVP only)
- Defer documentation to Phase 4.5

**If performance issues**:
- Implement query caching
- Add database indexing
- Optimize N+1 queries
- Use eager loading

**If integration fails**:
- Roll back to old system
- Debug integration layer
- Re-test before retry
- Adjust timeline

---

## Resource Requirements

### Team
- 1 Senior Backend Developer (full-time)
- 1 QA Engineer (0.5 FTE, weeks 8+)
- 1 DevOps Engineer (0.25 FTE, weeks 23-26)

### Infrastructure
- Staging environment (copy of production)
- Development database
- Testing/QA database
- Monitoring tools

### Tools
- PHPUnit (testing)
- Git (version control)
- Docker (containerization)
- CI/CD pipeline (GitHub Actions)

---

## Next Phase: Phase 5 (Planned)

After Phase 4 completion:

**Phase 5: Performance Optimization** (60 hours, 12 weeks)
- Query optimization
- Caching implementation
- Database indexing
- API caching
- Frontend optimization

**Phase 6: Enhanced Features** (100+ hours)
- Advanced reporting
- Real-time notifications
- Mobile app support
- Advanced search
- Analytics dashboard

---

*Roadmap Created: December 31, 2025*  
*Phase 4 Status: Ready for Step 2 - Foundation Setup*  
*Next Review: January 7, 2026*
