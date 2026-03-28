<?php
namespace App\Controllers;

use App\Models\Billing;

/**
 * BillingController - Handles billing note list, create, edit, delete
 * Replaces: billing.php, billing-make.php, core-function.php case billing
 */
class BillingController extends BaseController
{
    private Billing $billing;

    public function __construct()
    {
        parent::__construct();
        $this->billing = new Billing();
    }

    public function index(): void
    {
        require_once __DIR__ . '/../../inc/pagination.php';
        $comId = $this->getCompanyId();
        $page = max(1, $this->inputInt('pg', 1));
        $perPage = $this->inputInt('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        $filters = [
            'search' => $this->input('search', ''),
            'status' => $this->input('status', ''),
            'date_from' => $this->input('date_from', ''),
            'date_to' => $this->input('date_to', ''),
        ];

        $total = $this->billing->countBillingItems($comId, $filters);
        $pagination = paginate($total, $perPage, $page);

        $this->render('billing/list', [
            'items' => $this->billing->getBillingItems($comId, $filters, $pagination['offset'], $perPage),
            'stats' => $this->billing->getStats($comId),
            'total_records' => $total, 'pagination' => $pagination,
            'filters' => $filters, 'per_page' => $perPage,
        ]);
    }

    public function make(): void
    {
        $comId = $this->getCompanyId();
        $invId = $this->inputInt('inv_id', 0);
        $customerId = $this->inputInt('customer_id', 0);

        // Determine customer: from inv_id param, customer_id param, or null
        $customer = null;
        if ($invId > 0) {
            $customer = $this->billing->getCustomerFromInvoice($invId);
        } elseif ($customerId > 0) {
            $customer = $this->billing->getCustomerById($customerId);
        }

        $this->render('billing/make', [
            'customer' => $customer,
            'inv_id' => $invId,
            'customers' => $this->billing->getCustomersWithUnbilledInvoices($comId),
            'unbilled' => $customer ? $this->billing->getUnbilledInvoices(intval($customer['id']), $comId) : [],
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', '');
        $comId = $this->getCompanyId();

        if ($method === 'A') {
            $this->billing->createBilling($_REQUEST, $comId);
        } elseif ($method === 'E') {
            $this->billing->updateBilling($this->inputInt('bil_id', 0), $_REQUEST);
        } elseif ($method === 'D') {
            $this->billing->deleteBilling($this->inputInt('bil_id', 0));
        }
        $this->redirect('index.php?page=billing');
    }
}
