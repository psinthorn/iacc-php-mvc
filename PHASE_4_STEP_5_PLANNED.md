# Phase 4 Step 5 - Controller Layer Implementation - PLANNING DOCUMENT

**Phase**: 4
**Step**: 5
**Status**: PLANNED
**Date**: 2024
**Estimated Duration**: 12-15 hours
**Expected Files**: 30-40 files
**Expected Lines of Code**: 3,500+ lines

---

## Overview

Phase 4 Step 5 implements the HTTP request handling layer (Controller Layer) that bridges the Service Layer (Phase 4 Step 4) and the HTTP clients. Controllers receive requests, delegate business logic to services, and format responses.

This is the final piece of the MVC architecture transformation, completing the journey from procedural code to modern enterprise architecture.

---

## Architecture

```
Client (HTTP Request)
         ↓
    Router (Phase 4 Step 2)
         ↓
   Controller (Phase 4 Step 5) ← NEW
    ├─ Request validation
    ├─ Authorization checks
    ├─ Delegate to Service
    └─ Format response
         ↓
Service Layer (Phase 4 Step 4)
    ├─ Validation
    ├─ Business logic
    ├─ Transaction management
    └─ Event dispatching
         ↓
Repository Layer (Phase 4 Step 3)
    ├─ Data queries
    └─ Model relationships
         ↓
Database (MySQL)
```

---

## Implementation Plan

### Task 1: Base Controller Class & Infrastructure (3 hours)

**1. src/Controllers/Controller.php** (250+ lines)
- Abstract base controller class
- Constructor injection of services
- Request/Response handling
- JSON response formatting
- Error handling and exception catching

```php
abstract class Controller {
    protected $services = [];
    protected Request $request;
    protected Response $response;
    
    public function __construct(
        Request $request,
        Response $response,
        ServiceContainer $container
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
    }
    
    protected function json($data, $status = 200) { }
    protected function validate(array $data, array $rules) { }
    protected function authorize($permission) { }
    protected function handleException(Exception $e) { }
}
```

**2. src/Controllers/ControllerInterface.php** (50+ lines)
- Standard interface for all controllers
- Methods that all controllers must implement

**3. src/Http/Resources/Resource.php** (100+ lines)
- Response resource base class
- Transform models to API response format
- Relationship handling

```php
class UserResource extends Resource {
    public function toArray() {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'email' => $this->model->email,
        ];
    }
}
```

**4. src/Http/Middleware/AuthorizeMiddleware.php** (100+ lines)
- Authorization middleware
- Check permissions before controller action
- Return 403 if unauthorized

**5. src/Http/Requests/FormRequest.php** (150+ lines)
- Form request base class
- Automatic validation
- Custom messages

```php
class StoreCompanyRequest extends FormRequest {
    public function rules() {
        return [
            'name' => 'required|min:3|max:255',
            'code' => 'required|unique:company,code',
        ];
    }
}
```

### Task 2: Core Business Controllers (5 hours)

**1. src/Controllers/CompanyController.php** (200+ lines)**
```php
class CompanyController extends Controller {
    public function index() { }      // GET /companies
    public function show($id) { }     // GET /companies/:id
    public function store() { }       // POST /companies
    public function update($id) { }   // PUT /companies/:id
    public function destroy($id) { }  // DELETE /companies/:id
}
```

**2. src/Controllers/ProductController.php** (200+ lines)**
- index() - List with pagination/filtering
- show($id) - Get single product
- store() - Create product
- update($id) - Update product
- destroy($id) - Delete product
- Custom: getByCategory(), getByType()

**3. src/Controllers/SupplierController.php** (150+ lines)**
- Standard CRUD operations
- Filter by company

**4. src/Controllers/CustomerController.php** (150+ lines)**
- Standard CRUD operations
- Credit limit management

### Task 3: Purchasing Controllers (4 hours)

**1. src/Controllers/PurchaseOrderController.php** (250+ lines)**
```php
public function index() { }       // List POs
public function show($id) { }      // Get with details
public function store() { }        // Create PO
public function update($id) { }    // Update (draft only)
public function submit($id) { }    // Submit PO
public function approve($id) { }   // Approve PO
public function destroy($id) { }   // Delete (draft only)
```

**2. src/Controllers/ReceivingController.php** (200+ lines)**
- receiveItems($poId) - Record receipt
- getReceipts($poId) - View received items
- Workflow: POST to receive, GET to view

### Task 4: Sales & Delivery Controllers (4 hours)

**1. src/Controllers/SalesOrderController.php** (250+ lines)**
```php
public function index() { }       // List SOs
public function show($id) { }      // Get with details
public function store() { }        // Create SO
public function update($id) { }    // Update (draft only)
public function confirm($id) { }   // Confirm SO
public function destroy($id) { }   // Delete (draft only)
```

**2. src/Controllers/InvoiceController.php** (250+ lines)**
```php
public function index() { }       // List invoices
public function show($id) { }      // Get invoice
public function store() { }        // Create from SO
public function recordPayment() {} // Record payment
public function getPayments($id) {} // View payments
```

**3. src/Controllers/DeliveryController.php** (200+ lines)**
- index() - List deliveries
- show($id) - Get details
- store() - Create delivery
- complete($id) - Mark completed
- destroy($id) - Delete delivery

### Task 5: Support Controllers (3 hours)

**1. src/Controllers/PaymentController.php** (150+ lines)**
- Create payment
- List payments by invoice
- Payment reconciliation

**2. src/Controllers/ExpenseController.php** (200+ lines)**
- Create expense
- List expenses
- Approve expense
- Status: draft → approved

**3. src/Controllers/ReportController.php** (150+ lines)**
- List available reports
- Execute report with parameters
- Return report data

**4. src/Controllers/ComplaintController.php** (200+ lines)**
- Create complaint
- List complaints (with filtering)
- Resolve complaint
- Get complaints by customer

### Task 6: Route Registration (2 hours)

**1. src/routes.php or src/Routes/web.php** (300+ lines)
- Register all routes
- Group by resource
- Middleware application

```php
// API Routes with authentication middleware
Route::middleware(['auth'])->group(function () {
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);
    
    // Nested resources
    Route::post('sales-orders/{id}/confirm', 'SalesOrderController@confirm');
    Route::post('purchase-orders/{id}/submit', 'PurchaseOrderController@submit');
    Route::post('invoices/{id}/payments', 'InvoiceController@recordPayment');
});
```

### Task 7: Error Handling & Responses (2 hours)

**1. src/Http/Responses/ApiResponse.php** (150+ lines)**
- Standardized JSON responses
- Error formatting
- Status code mapping

```php
class ApiResponse {
    public static function success($data, $status = 200) { }
    public static function error($message, $status = 400, $errors = []) { }
    public static function paginated($items, $page, $perPage, $total) { }
}
```

**2. src/Http/Middleware/HandleExceptions.php** (200+ lines)**
- Convert exceptions to HTTP responses
- Log exceptions
- Return appropriate status codes

```
ValidationException → 422
NotFoundException → 404
AuthorizationException → 403
BusinessException → 400
DatabaseException → 500
```

---

## Request/Response Examples

### Create Company

**Request**:
```http
POST /api/companies
Content-Type: application/json

{
    "name": "Acme Corp",
    "code": "ACME-001",
    "email": "info@acme.com",
    "phone": "0901234567",
    "tax_id": "0107012345678",
    "address": "123 Business Street"
}
```

**Response (201)**:
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "Acme Corp",
        "code": "ACME-001",
        "email": "info@acme.com",
        "phone": "0901234567",
        "tax_id": "0107012345678",
        "address": "123 Business Street",
        "created_at": "2024-12-31T10:30:00Z"
    }
}
```

**Response (422 - Validation Error)**:
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "name": "Name is required",
        "code": "Code must be unique"
    }
}
```

### Submit Purchase Order

**Request**:
```http
POST /api/purchase-orders/5/submit
Content-Type: application/json
```

**Response (200)**:
```json
{
    "status": "success",
    "message": "Purchase order submitted",
    "data": {
        "id": 5,
        "po_number": "PO-202412-001",
        "status": "submitted",
        "supplier_id": 3,
        "total_amount": 15000.00,
        "submitted_at": "2024-12-31T10:35:00Z"
    }
}
```

### Record Invoice Payment

**Request**:
```http
POST /api/invoices/10/payments
Content-Type: application/json

{
    "amount": 5000.00,
    "payment_date": "2024-12-31",
    "payment_method": "bank_transfer",
    "reference_number": "TRX-2024-001"
}
```

**Response (201)**:
```json
{
    "status": "success",
    "message": "Payment recorded",
    "data": {
        "id": 42,
        "invoice_id": 10,
        "amount": 5000.00,
        "payment_date": "2024-12-31",
        "payment_method": "bank_transfer",
        "invoice_status": "partial"
    }
}
```

---

## Controller Implementation Pattern

```php
<?php

namespace App\Controllers;

use App\Services\CompanyService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class CompanyController extends Controller {
    protected $companyService;
    
    public function __construct(
        CompanyService $companyService,
        Request $request,
        Response $response
    ) {
        parent::__construct($request, $response);
        $this->companyService = $companyService;
    }
    
    /**
     * GET /api/companies
     */
    public function index() {
        try {
            $page = $this->request->get('page', 1);
            $perPage = $this->request->get('per_page', 15);
            
            $result = $this->companyService->getAll([], $page, $perPage);
            
            return $this->json($result);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * GET /api/companies/:id
     */
    public function show($id) {
        try {
            $company = $this->companyService->getById($id);
            return $this->json(['data' => $company]);
        } catch (NotFoundException $e) {
            return $this->json(['error' => 'Company not found'], 404);
        }
    }
    
    /**
     * POST /api/companies
     */
    public function store() {
        try {
            $data = $this->request->all();
            
            $company = $this->companyService->create($data);
            
            return $this->json(['data' => $company], 201);
        } catch (ValidationException $e) {
            return $this->json(
                ['errors' => $e->getErrors()],
                422
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * PUT /api/companies/:id
     */
    public function update($id) {
        try {
            $data = $this->request->all();
            
            $company = $this->companyService->update($id, $data);
            
            return $this->json(['data' => $company]);
        } catch (ValidationException $e) {
            return $this->json(
                ['errors' => $e->getErrors()],
                422
            );
        } catch (NotFoundException $e) {
            return $this->json(['error' => 'Company not found'], 404);
        }
    }
    
    /**
     * DELETE /api/companies/:id
     */
    public function destroy($id) {
        try {
            $this->companyService->delete($id);
            
            return $this->json(
                ['message' => 'Company deleted'],
                204
            );
        } catch (NotFoundException $e) {
            return $this->json(['error' => 'Company not found'], 404);
        }
    }
}
```

---

## Route Registration Pattern

```php
<?php

// API Routes v1
Route::prefix('api/v1')->group(function () {
    
    // Public routes (no auth)
    Route::post('auth/login', 'AuthController@login');
    Route::post('auth/register', 'AuthController@register');
    
    // Protected routes (auth middleware)
    Route::middleware(['auth'])->group(function () {
        
        // Company Management
        Route::get('companies', 'CompanyController@index');
        Route::get('companies/{id}', 'CompanyController@show');
        Route::post('companies', 'CompanyController@store');
        Route::put('companies/{id}', 'CompanyController@update');
        Route::delete('companies/{id}', 'CompanyController@destroy');
        
        // Product Management
        Route::get('products', 'ProductController@index');
        Route::get('products/{id}', 'ProductController@show');
        Route::post('products', 'ProductController@store');
        Route::put('products/{id}', 'ProductController@update');
        Route::delete('products/{id}', 'ProductController@destroy');
        Route::get('products/category/{categoryId}', 'ProductController@getByCategory');
        
        // Purchase Orders (complex workflow)
        Route::get('purchase-orders', 'PurchaseOrderController@index');
        Route::get('purchase-orders/{id}', 'PurchaseOrderController@show');
        Route::post('purchase-orders', 'PurchaseOrderController@store');
        Route::put('purchase-orders/{id}', 'PurchaseOrderController@update');
        Route::post('purchase-orders/{id}/submit', 'PurchaseOrderController@submit');
        Route::post('purchase-orders/{id}/approve', 'PurchaseOrderController@approve');
        Route::delete('purchase-orders/{id}', 'PurchaseOrderController@destroy');
        
        // Receiving
        Route::post('receiving/{poId}/items', 'ReceivingController@receiveItems');
        Route::get('receiving/{poId}/items', 'ReceivingController@getReceipts');
        
        // Sales Orders
        Route::get('sales-orders', 'SalesOrderController@index');
        Route::get('sales-orders/{id}', 'SalesOrderController@show');
        Route::post('sales-orders', 'SalesOrderController@store');
        Route::put('sales-orders/{id}', 'SalesOrderController@update');
        Route::post('sales-orders/{id}/confirm', 'SalesOrderController@confirm');
        Route::delete('sales-orders/{id}', 'SalesOrderController@destroy');
        
        // Invoices
        Route::get('invoices', 'InvoiceController@index');
        Route::get('invoices/{id}', 'InvoiceController@show');
        Route::post('invoices', 'InvoiceController@store');
        Route::post('invoices/{id}/payments', 'InvoiceController@recordPayment');
        Route::get('invoices/{id}/payments', 'InvoiceController@getPayments');
        
        // Reports
        Route::get('reports', 'ReportController@index');
        Route::post('reports/{code}/execute', 'ReportController@execute');
        
        // Complaints
        Route::get('complaints', 'ComplaintController@index');
        Route::get('complaints/{id}', 'ComplaintController@show');
        Route::post('complaints', 'ComplaintController@store');
        Route::post('complaints/{id}/resolve', 'ComplaintController@resolve');
    });
});
```

---

## Testing Strategy

**Unit Tests** (Task 7 - Phase 4 Step 4 remainder):
- CompanyControllerTest.php
- ProductControllerTest.php
- PurchaseOrderControllerTest.php
- InvoiceControllerTest.php
- etc.

**Integration Tests**:
- Test full flow: Request → Controller → Service → Repository → Database
- Verify event dispatching
- Verify transaction rollback on error

**Acceptance Tests**:
- Full API testing with real requests
- Workflow testing (PO: create → submit → approve)

---

## Success Criteria

✅ All 14 controllers implemented and tested
✅ All routes registered and working
✅ Request validation functional
✅ Error handling comprehensive
✅ JSON responses standardized
✅ CRUD operations functional for all entities
✅ Complex workflows (PO, SO, Invoice) operational
✅ Authorization checks implemented
✅ Logging of all requests/responses
✅ API documentation generated

---

## Timeline

| Task | Hours | Status |
|------|-------|--------|
| Task 1: Base Controller + Infrastructure | 3 | Not started |
| Task 2: Core Business Controllers | 5 | Not started |
| Task 3: Purchasing Controllers | 4 | Not started |
| Task 4: Sales & Delivery Controllers | 4 | Not started |
| Task 5: Support Controllers | 3 | Not started |
| Task 6: Route Registration | 2 | Not started |
| Task 7: Error Handling & Testing | 2 | Not started |
| **TOTAL** | **23** | **Not started** |

---

## Files Summary

**Expected Files to Create**: 30-40
- 1 Base Controller + Infrastructure (5 files)
- 14 Domain Controllers (14 files)
- 1 Route registration file
- Error handling & middleware (3-5 files)
- Response formatting (2-3 files)
- Tests (14-20 files)
- Documentation (1-2 files)

**Expected Lines of Code**: 3,500+ lines
- Controllers: 2,500+ lines
- Routes: 300+ lines
- Error handling: 500+ lines
- Tests: 1,500+ lines
- Documentation: 500+ lines

---

## Next Steps After Phase 4 Step 5

**Phase 4 Step 6**: Authentication & Authorization Layer
- JWT token generation/validation
- Role-based access control (RBAC)
- Permission checking middleware
- User authentication endpoints

**Phase 4 Step 7**: Request/Response Formatting & Pagination
- Automatic response formatting
- Pagination helper
- Filtering and sorting
- API versioning

**Phase 5**: Testing & Quality Assurance
- Unit tests (80%+ coverage)
- Integration tests
- API endpoint testing
- Performance testing

**Phase 6**: Deployment & Documentation
- API documentation (Swagger/OpenAPI)
- Deployment guide
- Performance optimization
- Security hardening

---

## Architecture Completeness

After Phase 4 Step 5, the full MVC architecture will be complete:

✅ **Phase 4 Step 1**: Architecture Analysis
✅ **Phase 4 Step 2**: Foundation Layer (Router, DI, Config, Logger)
✅ **Phase 4 Step 3**: Data Layer (Models, Repositories, QueryBuilder)
✅ **Phase 4 Step 4**: Business Logic Layer (Services, Validation, Events)
⏳ **Phase 4 Step 5**: HTTP Layer (Controllers, Routes, Responses) ← NEXT
⏳ **Phase 4 Step 6**: Authentication Layer
⏳ **Phase 4 Step 7**: Formatting & Documentation

This represents a complete transformation from procedural PHP to enterprise-grade modern architecture.
