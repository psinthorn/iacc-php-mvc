# Phase 4 Step 4: Service Layer - Business Logic Extraction

**Status**: IN PROGRESS  
**Timeline**: 35-45 hours  
**Goal**: Extract business logic from controllers into reusable service classes  

## Overview

Phase 4 Step 4 implements the Service Layer - application-specific business logic that orchestrates repositories, manages transactions, and enforces domain rules. Services act as the bridge between HTTP requests (controllers) and data access (repositories).

## Architecture

```
HTTP Request
    ↓
Controller (Route Handler)
    ↓
Service (Business Logic)
    ├─ Validation
    ├─ Authorization
    ├─ Transactions
    ├─ Event Dispatching
    └─ Repository Coordination
    ↓
Repository (Data Access)
    ↓
Model (Data Representation)
    ↓
Database (SQL Execution)
```

## Services to Create (12-15 Services)

### Core Services
1. **CompanyService** - Company management
2. **ProductService** - Product management
3. **InventoryService** - Stock management

### Purchasing Services
4. **PurchaseRequestService** - PR creation/approval
5. **PurchaseOrderService** - PO creation/receiving
6. **SupplierService** - Supplier management

### Sales Services
7. **CustomerService** - Customer management
8. **SalesOrderService** - SO creation/fulfillment
9. **InvoiceService** - Invoice generation/payment
10. **DeliveryService** - Delivery tracking

### Accounting Services
11. **ExpenseService** - Expense management
12. **PaymentService** - Payment processing
13. **VoucherService** - Voucher management

### Support Services
14. **ReportService** - Report generation
15. **ComplaintService** - Complaint management

## Service Layer Patterns

### Base Service Class

```php
namespace App\Services;

abstract class Service
{
    protected $database;
    protected $logger;
    protected $validator;
    protected $eventBus;

    public function __construct(
        Database $database,
        Logger $logger,
        Validator $validator,
        EventBus $eventBus = null
    ) {
        $this->database = $database;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->eventBus = $eventBus;
    }

    /**
     * Begin transaction for operation
     */
    protected function transaction(callable $callback)
    {
        $this->database->beginTransaction();
        try {
            $result = $callback();
            $this->database->commit();
            return $result;
        } catch (Exception $e) {
            $this->database->rollback();
            throw $e;
        }
    }

    /**
     * Dispatch domain event
     */
    protected function dispatch($event)
    {
        if ($this->eventBus) {
            $this->eventBus->dispatch($event);
        }
    }

    /**
     * Log action
     */
    protected function log($action, $data = [])
    {
        $this->logger->info($action, $data);
    }
}
```

### Service Interface

```php
namespace App\Services;

interface ServiceInterface
{
    public function getAll($filters = [], $page = 1, $perPage = 15);
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function restore($id);
}
```

## Example Services

### CompanyService
```php
class CompanyService extends Service
{
    protected $repository;

    public function __construct(
        CompanyRepository $repository,
        Database $database,
        Logger $logger,
        Validator $validator
    ) {
        parent::__construct($database, $logger, $validator);
        $this->repository = $repository;
    }

    /**
     * Create new company with validation
     */
    public function createCompany(array $data)
    {
        // Validate input
        $errors = $this->validator->validate($data, [
            'name' => 'required|string|unique:company,name',
            'code' => 'required|string|unique:company,code',
            'email' => 'required|email',
            'tax_id' => 'required|string|regex:/^[0-9]{13}$/',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create in transaction
        return $this->transaction(function () use ($data) {
            $company = $this->repository->create($data);
            
            // Log action
            $this->log('company_created', [
                'company_id' => $company->id,
                'name' => $company->name,
            ]);

            // Dispatch event
            $this->dispatch(new CompanyCreated($company));

            return $company;
        });
    }

    /**
     * Update company with validation
     */
    public function updateCompany($id, array $data)
    {
        $company = $this->repository->find($id);
        if (!$company) {
            throw new NotFoundException("Company not found");
        }

        // Validate
        $errors = $this->validator->validate($data, [
            'name' => 'string|unique:company,name,' . $id,
            'code' => 'string|unique:company,code,' . $id,
            'email' => 'email',
            'tax_id' => 'regex:/^[0-9]{13}$/',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($id, $data, $company) {
            $company = $this->repository->update($id, $data);

            $this->log('company_updated', [
                'company_id' => $id,
                'changes' => array_keys($data),
            ]);

            $this->dispatch(new CompanyUpdated($company));

            return $company;
        });
    }

    /**
     * Get company with contacts and suppliers
     */
    public function getCompanyDetails($id)
    {
        $company = $this->repository->find($id);
        
        if (!$company) {
            throw new NotFoundException("Company not found");
        }

        // Load relationships
        $company->contacts = $company->contacts();
        $company->suppliers = $company->suppliers();

        return $company;
    }

    /**
     * Delete company (soft delete)
     */
    public function deleteCompany($id)
    {
        $company = $this->repository->find($id);
        
        if (!$company) {
            throw new NotFoundException("Company not found");
        }

        // Check for dependencies
        $contactCount = ContactRepository::where('company_id', $id)->count();
        if ($contactCount > 0) {
            throw new BusinessException(
                "Cannot delete company with {$contactCount} contacts"
            );
        }

        return $this->transaction(function () use ($id) {
            $deleted = $this->repository->delete($id);

            $this->log('company_deleted', ['company_id' => $id]);
            $this->dispatch(new CompanyDeleted($id));

            return $deleted;
        });
    }
}
```

### PurchaseOrderService
```php
class PurchaseOrderService extends Service
{
    protected $poRepository;
    protected $detailRepository;
    protected $inventoryService;
    protected $paymentService;

    public function __construct(
        PurchaseOrderRepository $poRepo,
        PurchaseOrderDetailRepository $detailRepo,
        InventoryService $inventoryService,
        PaymentService $paymentService,
        Database $database,
        Logger $logger,
        Validator $validator
    ) {
        parent::__construct($database, $logger, $validator);
        $this->poRepository = $poRepo;
        $this->detailRepository = $detailRepo;
        $this->inventoryService = $inventoryService;
        $this->paymentService = $paymentService;
    }

    /**
     * Create purchase order with items
     */
    public function createPurchaseOrder(array $data)
    {
        // Validate
        $errors = $this->validator->validate($data, [
            'po_number' => 'required|unique:purchase_order,po_number',
            'supplier_id' => 'required|exists:supplier,id',
            'po_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            // Create PO
            $po = $this->poRepository->create([
                'po_number' => $data['po_number'],
                'supplier_id' => $data['supplier_id'],
                'po_date' => $data['po_date'],
                'total_amount' => 0,
                'status' => 'draft',
            ]);

            $total = 0;

            // Create line items
            foreach ($data['items'] as $item) {
                $amount = $item['quantity'] * $item['unit_price'];
                $total += $amount;

                $this->detailRepository->create([
                    'po_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $amount,
                ]);
            }

            // Update PO total
            $this->poRepository->update($po->id, [
                'total_amount' => $total,
            ]);

            $this->log('po_created', ['po_id' => $po->id, 'total' => $total]);
            $this->dispatch(new PurchaseOrderCreated($po));

            return $po->fresh();
        });
    }

    /**
     * Receive items for purchase order
     */
    public function receiveItems($poId, array $items)
    {
        $po = $this->poRepository->find($poId);
        if (!$po) {
            throw new NotFoundException("PO not found");
        }

        return $this->transaction(function () use ($po, $items) {
            foreach ($items as $item) {
                // Update inventory
                $this->inventoryService->addStock(
                    $item['product_id'],
                    $item['quantity'],
                    'purchase_order_' . $po->id
                );

                // Record receipt
                $receiveRepo = new ReceiveItemRepository($this->database);
                $receiveRepo->create([
                    'po_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_date' => date('Y-m-d H:i:s'),
                ]);
            }

            // Update PO status
            $this->poRepository->update($po->id, [
                'status' => 'received',
            ]);

            $this->log('po_received', ['po_id' => $po->id]);
            $this->dispatch(new PurchaseOrderReceived($po));

            return $po->fresh();
        });
    }

    /**
     * Approve and submit PO
     */
    public function submitPurchaseOrder($poId)
    {
        $po = $this->poRepository->find($poId);
        if (!$po) {
            throw new NotFoundException("PO not found");
        }

        if ($po->status !== 'draft') {
            throw new BusinessException("Can only submit draft POs");
        }

        return $this->transaction(function () use ($po) {
            $this->poRepository->update($po->id, [
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);

            $this->log('po_submitted', ['po_id' => $po->id]);
            $this->dispatch(new PurchaseOrderSubmitted($po));

            return $po->fresh();
        });
    }
}
```

### InvoiceService
```php
class InvoiceService extends Service
{
    protected $invoiceRepository;
    protected $invoiceDetailRepository;
    protected $paymentService;
    protected $emailService;

    /**
     * Create invoice from sales order
     */
    public function createInvoiceFromOrder($soId)
    {
        $soRepo = new SalesOrderRepository($this->database);
        $so = $soRepo->find($soId);

        if (!$so) {
            throw new NotFoundException("Sales order not found");
        }

        return $this->transaction(function () use ($so) {
            // Create invoice
            $invoice = $this->invoiceRepository->create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => date('Y-m-d'),
                'so_id' => $so->id,
                'customer_id' => $so->customer_id,
                'total_amount' => $so->total_amount,
                'payment_status' => 'pending',
                'due_date' => date('Y-m-d', strtotime('+30 days')),
            ]);

            // Copy line items
            $soDetailRepo = new SalesOrderDetailRepository($this->database);
            $details = $soDetailRepo->where('so_id', $so->id);

            foreach ($details as $detail) {
                $this->invoiceDetailRepository->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $detail->product_id,
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'amount' => $detail->amount,
                ]);
            }

            // Update SO status
            $soRepo->update($so->id, ['status' => 'invoiced']);

            $this->log('invoice_created', ['invoice_id' => $invoice->id, 'so_id' => $so->id]);
            $this->dispatch(new InvoiceCreated($invoice));

            // Send to customer
            $this->emailService->sendInvoice($invoice);

            return $invoice;
        });
    }

    /**
     * Record payment for invoice
     */
    public function recordPayment($invoiceId, array $paymentData)
    {
        $invoice = $this->invoiceRepository->find($invoiceId);
        if (!$invoice) {
            throw new NotFoundException("Invoice not found");
        }

        return $this->transaction(function () use ($invoice, $paymentData) {
            // Create payment
            $payment = $this->paymentService->createPayment([
                'invoice_id' => $invoiceId,
                'amount' => $paymentData['amount'],
                'payment_date' => $paymentData['payment_date'],
                'payment_method' => $paymentData['payment_method'],
                'reference_number' => $paymentData['reference_number'],
            ]);

            // Check if fully paid
            $totalPaid = $this->paymentService->getTotalPaid($invoiceId);

            if ($totalPaid >= $invoice->total_amount) {
                $this->invoiceRepository->update($invoiceId, [
                    'payment_status' => 'paid',
                    'paid_date' => date('Y-m-d'),
                ]);

                $this->dispatch(new InvoicePaid($invoice));
            }

            $this->log('payment_recorded', [
                'invoice_id' => $invoiceId,
                'amount' => $paymentData['amount'],
            ]);

            return $payment;
        });
    }

    /**
     * Generate next invoice number
     */
    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $count = $this->invoiceRepository->count() + 1;
        return "INV-{$year}{$month}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
```

## Key Features

### Transaction Management
```php
$this->transaction(function () {
    // Multiple repository operations
    // All succeed or all fail
});
```

### Validation
```php
$errors = $this->validator->validate($data, $rules);
if (!empty($errors)) {
    throw new ValidationException($errors);
}
```

### Event Dispatching
```php
$this->dispatch(new OrderCreated($order));
$this->dispatch(new PaymentReceived($payment));
```

### Logging
```php
$this->log('po_created', ['po_id' => $po->id, 'total' => $total]);
```

### Error Handling
- `ValidationException` - Input validation failed
- `NotFoundException` - Resource not found
- `AuthorizationException` - User not authorized
- `BusinessException` - Business rule violation

## Event System

Services dispatch domain events that can be listened to:

```php
interface Event {}

class OrderCreated implements Event {
    public $order;
    public function __construct($order) {
        $this->order = $order;
    }
}

class EventBus {
    protected $listeners = [];
    
    public function listen($eventClass, callable $callback) {
        $this->listeners[$eventClass][] = $callback;
    }
    
    public function dispatch(Event $event) {
        $class = get_class($event);
        foreach ($this->listeners[$class] ?? [] as $listener) {
            $listener($event);
        }
    }
}
```

## Service Integration with DI Container

```php
// Register in ServiceContainer
$container->bind(CompanyService::class, function () {
    return new CompanyService(
        $container->make(CompanyRepository::class),
        $container->make(Database::class),
        $container->make(Logger::class),
        $container->make(Validator::class)
    );
});

// Inject in controller
class CompanyController {
    public function __construct(CompanyService $service) {
        $this->service = $service;
    }
    
    public function store(Request $request) {
        $company = $this->service->createCompany($request->all());
        return response()->json($company);
    }
}
```

## Task Breakdown

1. **Base Service & Exception Classes** (2 hours)
   - `src/Services/Service.php` - base class
   - `src/Exceptions/` - exception hierarchy
   - `src/Events/Event.php` - event interface
   - `src/Events/EventBus.php` - event dispatcher

2. **Validator Service** (3 hours)
   - Input validation with rules
   - Error collection and formatting
   - Custom validation rules

3. **Core Services** (8 hours)
   - CompanyService
   - ProductService
   - SupplierService
   - CustomerService

4. **Purchasing Services** (8 hours)
   - PurchaseRequestService
   - PurchaseOrderService
   - ReceivingService

5. **Sales Services** (8 hours)
   - SalesOrderService
   - InvoiceService
   - DeliveryService

6. **Support Services** (6 hours)
   - PaymentService
   - ExpenseService
   - ReportService
   - ComplaintService

7. **Unit Tests** (5 hours)
   - Service layer tests
   - Transaction rollback tests
   - Event dispatch verification

8. **Documentation** (2 hours)
   - Service usage guide
   - API documentation
   - Integration examples

## Success Criteria

✅ All 12-15 services created  
✅ Transaction management working  
✅ Validation framework functional  
✅ Event dispatching operational  
✅ Error handling comprehensive  
✅ 80%+ unit test coverage  
✅ Full integration with repositories  
✅ Ready for controller layer (Step 5)

## Deliverables

1. `src/Services/Service.php` - Base service class
2. `src/Services/ServiceInterface.php` - Interface
3. 12-15 Service classes in `src/Services/`
4. `src/Exceptions/` - Exception hierarchy
5. `src/Events/` - Event system
6. `src/Validation/Validator.php` - Validation engine
7. `tests/Unit/Services/` - Service unit tests
8. `PHASE_4_STEP_4_COMPLETION_REPORT.md` - Documentation

## Timeline

| Task | Hours | Status |
|------|-------|--------|
| Base classes & exceptions | 2 | ⏳ |
| Validator service | 3 | ⏳ |
| Core services (3) | 8 | ⏳ |
| Purchasing services (3) | 8 | ⏳ |
| Sales services (3) | 8 | ⏳ |
| Support services (3) | 6 | ⏳ |
| Unit tests | 5 | ⏳ |
| Documentation | 2 | ⏳ |
| **TOTAL** | **42** | **⏳** |

---

**Phase**: 4 Step 4  
**Status**: Planning Complete - Ready to Start  
**Next**: Implementation
