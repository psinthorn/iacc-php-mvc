<?php
namespace App\Controllers;

use App\Models\Receipt;

/**
 * ReceiptController - Handles receipt list, create, edit, view
 * Replaces: rep-list.php, rep-make.php, rep-view.php, core-function.php case receipt_list
 */
class ReceiptController extends BaseController
{
    private Receipt $receipt;

    public function __construct()
    {
        parent::__construct();
        $this->receipt = new Receipt();
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

        $total = $this->receipt->countReceipts($comId, $filters);
        $pagination = paginate($total, $perPage, $page);

        $this->render('receipt/list', [
            'items' => $this->receipt->getReceipts($comId, $filters, $pagination['offset'], $perPage),
            'stats' => $this->receipt->getStats($comId),
            'total_records' => $total, 'pagination' => $pagination,
            'filters' => $filters, 'per_page' => $perPage,
        ]);
    }

    public function make(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $this->render('receipt/make', [
            'receipt' => $id > 0 ? $this->receipt->findReceipt($id, $comId) : null,
            'products' => $id > 0 ? $this->receipt->getReceiptProducts($id) : [],
            'quotations' => $this->receipt->getQuotations($comId),
            'invoices' => $this->receipt->getInvoices($comId),
            'types' => (new \App\Models\Voucher())->getTypes($comId),
            'id' => $id,
        ]);
    }

    public function view(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $receipt = $this->receipt->findReceipt($id, $comId);
        $products = [];
        if ($receipt) {
            if ($receipt['source_type'] === 'invoice' && $receipt['invoice_id']) {
                $products = $this->receipt->getSourceProducts(intval($receipt['invoice_id']), 'invoice');
            } elseif ($receipt['source_type'] === 'quotation' && $receipt['quotation_id']) {
                $products = $this->receipt->getSourceProducts(intval($receipt['quotation_id']), 'quotation');
            } else {
                $products = $this->receipt->getReceiptProducts($id);
            }
        }
        $this->render('receipt/view', [
            'receipt' => $receipt, 'products' => $products, 'id' => $id,
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', '');
        $comId = $this->getCompanyId();

        if ($method === 'A') {
            $this->receipt->createReceipt($_REQUEST, $comId);
        } elseif ($method === 'E') {
            $this->receipt->updateReceipt($this->inputInt('id', 0), $_REQUEST, $comId);
        }
        $this->redirect('index.php?page=receipt_list');
    }

    /**
     * Print receipt as PDF via mPDF
     * Standalone output — bypasses layout
     */
    public function print(): void
    {
        include __DIR__ . '/../../views/receipt/print.php';
        exit;
    }
}
