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

        $datePreset = $this->input('date_preset', '');
        if (!empty($datePreset)) {
            if ($datePreset === 'all') { $filters['date_from'] = ''; $filters['date_to'] = ''; }
            else { $dr = get_date_range($datePreset); $filters['date_from'] = $dr['from']; $filters['date_to'] = $dr['to']; }
        }

        $total = $this->billing->countBillingItems($comId, $filters);
        $pagination = paginate($total, $perPage, $page);

        $this->render('billing/list', [
            'items' => $this->billing->getBillingItems($comId, $filters, $pagination['offset'], $perPage),
            'stats' => $this->billing->getStats($comId),
            'total_records' => $total, 'pagination' => $pagination,
            'filters' => $filters, 'per_page' => $perPage,
            'date_preset' => $datePreset,
        ]);
    }

    public function make(): void
    {
        require_once __DIR__ . '/../../inc/pagination.php';
        $comId = $this->getCompanyId();
        $poId = $this->inputInt('po_id', 0);
        $invId = $this->inputInt('inv_id', 0);
        $customerId = $this->inputInt('customer_id', 0);

        // Search & date range filters
        $search   = $this->input('search', '');
        $dateFrom = $this->input('date_from', '');
        $dateTo   = $this->input('date_to', '');

        // Date presets override manual date range
        $datePreset = $this->input('date_preset', '');
        if (!empty($datePreset)) {
            if ($datePreset === 'all') { $dateFrom = ''; $dateTo = ''; }
            else { $dr = get_date_range($datePreset); $dateFrom = $dr['from']; $dateTo = $dr['to']; }
        }

        // Pagination
        $page    = max(1, $this->inputInt('pg', 1));
        $perPage = $this->inputInt('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        // Determine customer: from po_id param, inv_id param, customer_id param, or null
        $customer = null;
        if ($poId > 0) {
            $customer = $this->billing->getCustomerFromPO($poId);
        } elseif ($invId > 0) {
            $customer = $this->billing->getCustomerFromPO($invId);
        } elseif ($customerId > 0) {
            $customer = $this->billing->getCustomerById($customerId);
        }

        $totalRecords = 0;
        $unbilled = [];
        $pagination = null;
        if ($customer) {
            $custId = intval($customer['id']);
            $totalRecords = $this->billing->countUnbilledInvoices($custId, $comId, $dateFrom, $dateTo, $search);
            $pagination = paginate($totalRecords, $perPage, $page);
            $unbilled = $this->billing->getUnbilledInvoices($custId, $comId, $dateFrom, $dateTo, $search, $pagination['offset'], $perPage);
        }

        $this->render('billing/make', [
            'customer' => $customer,
            'po_id' => $poId > 0 ? $poId : $invId,
            'customers' => $this->billing->getCustomersWithUnbilledInvoices($comId),
            'unbilled' => $unbilled,
            'pagination' => $pagination,
            'total_records' => $totalRecords,
            'per_page' => $perPage,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_preset' => $datePreset,
            'search' => $search,
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

    /**
     * View a single billing note within admin shell
     */
    public function view(): void
    {
        $comId = $this->getCompanyId();
        $bilId = $this->inputInt('id', 0);

        $billing = $this->billing->getBillingById($bilId);
        if (!$billing) {
            $this->redirect('index.php?page=billing');
            return;
        }

        $invoices = $this->billing->getBillingInvoices($bilId);
        $vendor = $this->billing->getCompanyWithAddress($comId);
        $customer = $this->billing->getCompanyWithAddress(intval($billing['customer_id']));

        // Calculate total from items
        $totalAmount = 0;
        foreach ($invoices as $inv) {
            $totalAmount += floatval($inv['amount']);
        }
        // Use billing price if set, otherwise calculated total
        $amount = floatval($billing['price']) > 0 ? floatval($billing['price']) : $totalAmount;

        $this->render('billing/view', [
            'billing' => $billing,
            'invoices' => $invoices,
            'vendor' => $vendor,
            'customer' => $customer,
            'amount' => $amount,
        ]);
    }

    /**
     * Print/Download a billing note (standalone page)
     */
    public function print(): void
    {
        $comId = $this->getCompanyId();
        $bilId = $this->inputInt('id', 0);

        $billing = $this->billing->getBillingById($bilId);
        if (!$billing) {
            die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Billing Note Not Found</h2></div>');
        }

        $invoices = $this->billing->getBillingInvoices($bilId);
        $vendor = $this->billing->getCompanyWithAddress($comId);
        $customer = $this->billing->getCompanyWithAddress(intval($billing['customer_id']));

        $totalAmount = 0;
        foreach ($invoices as $inv) {
            $totalAmount += floatval($inv['amount']);
        }
        $amount = floatval($billing['price']) > 0 ? floatval($billing['price']) : $totalAmount;

        include __DIR__ . '/../Views/billing/print.php';
        exit;
    }
}