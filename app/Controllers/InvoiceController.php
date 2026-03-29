<?php
namespace App\Controllers;

use App\Models\Invoice;

/**
 * InvoiceController - Handles Invoice, Tax Invoice, and Quotation pages
 * 
 * Replaces:
 *   - compl-list.php (Invoice list)
 *   - compl-view.php (Invoice detail)
 *   - compl-list2.php (Tax Invoice list)
 *   - qa-list.php (Quotation list)
 *   - core-function.php cases: compl_list, compl_view, compl_list2
 */
class InvoiceController extends BaseController
{
    private Invoice $invoice;

    public function __construct()
    {
        parent::__construct();
        $this->invoice = new Invoice();
    }

    /**
     * Invoice List (compl_list)
     */
    public function index(): void
    {
        require_once __DIR__ . '/../../inc/pagination.php';
        $comId = $this->getCompanyId();
        $page = max(1, $this->inputInt('pg', 1));
        $perPage = $this->inputInt('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        $filters = [
            'search' => $this->input('search', ''),
            'date_from' => $this->input('date_from', ''),
            'date_to' => $this->input('date_to', ''),
            'status' => $this->input('status', ''),
        ];
        $datePreset = $this->input('date_preset', '');
        if (!empty($datePreset)) {
            if ($datePreset === 'all') { $filters['date_from'] = ''; $filters['date_to'] = ''; }
            else { $dr = get_date_range($datePreset); $filters['date_from'] = $dr['from']; $filters['date_to'] = $dr['to']; }
        }

        $totalOut = $this->invoice->countInvoices($comId, 'out', $filters);
        $totalIn = $this->invoice->countInvoices($comId, 'in', $filters);
        $total = $totalOut + $totalIn;
        $pagination = paginate($total, $perPage, $page);

        $itemsOut = $this->invoice->getInvoices($comId, 'out', $filters, $pagination['offset'], $perPage);
        $itemsIn = $this->invoice->getInvoices($comId, 'in', $filters, $pagination['offset'], $perPage);

        $this->render('invoice/list', [
            'items_out' => $itemsOut, 'items_in' => $itemsIn,
            'total_out' => $totalOut, 'total_in' => $totalIn, 'total_records' => $total,
            'pagination' => $pagination, 'filters' => $filters,
            'date_preset' => $datePreset, 'per_page' => $perPage,
            'query_params' => array_diff_key($_GET, ['pg' => '']),
        ]);
    }

    /**
     * Invoice Detail (compl_view)
     */
    public function view(): void
    {
        $id = $this->inputInt('id', 0);
        $comId = $this->getCompanyId();
        $data = $this->invoice->getInvoiceDetail($id, $comId);

        $viewData = ['hasData' => false, 'id' => $id];

        if ($data) {
            $vendor = $this->invoice->getCompanyName($data['ven_id']);
            $customer = $this->invoice->getCompanyName($data['cus_id']);
            $hasLabour = $this->invoice->hasLabour($id);
            $rawProducts = $this->invoice->getProducts($id);

            $products = [];
            $summary = 0;
            foreach ($rawProducts as $p) {
                if ($hasLabour) {
                    $equip = $p['price'] * $p['quantity'];
                    $l1 = $p['valuelabour'] * $p['activelabour'];
                    $labour = $l1 * $p['quantity'];
                    $total = $equip + $labour;
                } else {
                    $total = $p['price'] * $p['quantity'];
                    $equip = $total; $l1 = 0; $labour = 0;
                }
                $summary += $total;
                $products[] = array_merge($p, ['equip' => $equip, 'labour1' => $l1, 'labour' => $labour, 'total' => $total]);
            }

            $disc = $summary * $data['dis'] / 100;
            $subt = $summary - $disc;
            $overh = 0;
            if ($data['over'] > 0) { $overh = $subt * $data['over'] / 100; $subt += $overh; }
            $vat = $subt * $data['vat'] / 100;
            $totalnet = $subt + $vat;

            $payments = $this->invoice->getPayments($id);
            $payTotal = $this->invoice->getPaymentTotal($id);
            $accu = $totalnet - $payTotal;
            if ($accu < 0.000000000001) $accu = 0;
            $refpo = $this->invoice->getPoRef($id);
            $paymentMethods = $this->invoice->getPaymentMethods($comId);

            // Split group sibling invoices
            $splitSiblings = [];
            if (!empty($data['split_group_id'])) {
                $splitSiblings = $this->invoice->getSplitGroupInvoices(intval($data['split_group_id']));
            }

            $viewData = array_merge($viewData, [
                'hasData' => true, 'data' => $data,
                'vendor' => $vendor, 'customer' => $customer,
                'hasLabour' => $hasLabour, 'products' => $products,
                'summary' => $summary, 'disc' => $disc, 'subt' => $subt,
                'overh' => $overh, 'vat' => $vat, 'totalnet' => $totalnet,
                'payments' => $payments, 'accu' => $accu, 'refpo' => $refpo,
                'payment_methods' => $paymentMethods, 'com_id' => $comId,
                'split_siblings' => $splitSiblings,
            ]);
        }
        $this->render('invoice/view', $viewData);
    }

    /**
     * Tax Invoice List (compl_list2)
     */
    public function taxList(): void
    {
        require_once __DIR__ . '/../../inc/pagination.php';
        $comId = $this->getCompanyId();
        $page = max(1, $this->inputInt('pg', 1));
        $perPage = $this->inputInt('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        $filters = [
            'search' => $this->input('search', ''),
            'date_from' => $this->input('date_from', ''),
            'date_to' => $this->input('date_to', ''),
        ];
        $datePreset = $this->input('date_preset', '');
        if (!empty($datePreset)) {
            if ($datePreset === 'all') { $filters['date_from'] = ''; $filters['date_to'] = ''; }
            else { $dr = get_date_range($datePreset); $filters['date_from'] = $dr['from']; $filters['date_to'] = $dr['to']; }
        }

        $totalOut = $this->invoice->countTaxInvoices($comId, 'out', $filters);
        $totalIn = $this->invoice->countTaxInvoices($comId, 'in', $filters);
        $total = $totalOut + $totalIn;
        $pagination = paginate($total, $perPage, $page);

        $itemsOut = $this->invoice->getTaxInvoices($comId, 'out', $filters, $pagination['offset'], $perPage);
        $itemsIn = $this->invoice->getTaxInvoices($comId, 'in', $filters, $pagination['offset'], $perPage);

        $this->render('invoice/tax-list', [
            'items_out' => $itemsOut, 'items_in' => $itemsIn,
            'total_out' => $totalOut, 'total_in' => $totalIn, 'total_records' => $total,
            'pagination' => $pagination, 'filters' => $filters,
            'date_preset' => $datePreset, 'per_page' => $perPage,
            'query_params' => array_diff_key($_GET, ['pg' => '']),
        ]);
    }

    /**
     * Quotation List (qa_list)
     */
    public function quotations(): void
    {
        require_once __DIR__ . '/../../inc/pagination.php';
        $comId = $this->getCompanyId();
        $pageOut = max(1, $this->inputInt('pg_out', 1));
        $pageIn = max(1, $this->inputInt('pg_in', 1));
        $perPage = 15;

        $filters = [
            'search' => $this->input('search', ''),
            'date_from' => $this->input('date_from', ''),
            'date_to' => $this->input('date_to', ''),
        ];

        $totalOut = $this->invoice->countQuotations($comId, 'out', $filters);
        $totalIn = $this->invoice->countQuotations($comId, 'in', $filters);
        $paginationOut = paginate($totalOut, $perPage, $pageOut);
        $paginationIn = paginate($totalIn, $perPage, $pageIn);

        $itemsOut = $this->invoice->getQuotations($comId, 'out', $filters, $paginationOut['offset'], $perPage);
        $itemsIn = $this->invoice->getQuotations($comId, 'in', $filters, $paginationIn['offset'], $perPage);

        // Calculate totals for each quotation
        foreach ($itemsOut as &$item) {
            $calc = $this->invoice->calculatePoTotal($item['id']);
            $s = $calc['summary']; $d = $s * $item['dis'] / 100; $st = $s - $d;
            $oh = $st * $item['over'] / 100; $st += $oh;
            $v = $st * $item['vat'] / 100;
            $item['subtotal'] = $st; $item['grandtotal'] = $st + $v;
        }
        foreach ($itemsIn as &$item) {
            $calc = $this->invoice->calculatePoTotal($item['id']);
            $s = $calc['summary']; $d = $s * $item['dis'] / 100; $st = $s - $d;
            $oh = $st * $item['over'] / 100; $st += $oh;
            $v = $st * $item['vat'] / 100;
            $item['subtotal'] = $st; $item['grandtotal'] = $st + $v;
        }
        unset($item);

        $this->render('invoice/quotations', [
            'items_out' => $itemsOut, 'items_in' => $itemsIn,
            'total_out' => $totalOut, 'total_in' => $totalIn,
            'pagination_out' => $paginationOut, 'pagination_in' => $paginationIn,
            'filters' => $filters,
            'query_params' => array_diff_key($_GET, ['pg_out' => '', 'pg_in' => '']),
        ]);
    }

    /**
     * Handle Invoice/TaxInvoice store actions
     * Replaces core-function.php cases: compl_list, compl_view, compl_list2
     */
    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', '');
        $page = $this->input('source_page', $this->input('page', 'compl_list'));

        switch ($page) {
            case 'compl_list':
                if ($method === 'C') {
                    $this->invoice->recordPayment(
                        $this->getCompanyId(),
                        $this->inputInt('po_id', 0),
                        $this->input('payment', ''),
                        $this->input('remark', ''),
                        $this->input('volumn', '0')
                    );
                }
                $this->redirect('index.php?page=compl_view&id=' . $this->inputInt('po_id', 0));
                break;

            case 'compl_view':
                if ($method === 'S') {
                    $this->invoice->updatePaymentMethod($this->inputInt('ref', 0), $this->input('payby', ''));
                }
                $this->redirect('index.php?page=compl_view&id=' . $this->inputInt('id', 0));
                break;

            case 'compl_list2':
                if ($method === 'V') {
                    $this->invoice->voidInvoice($this->inputInt('id', 0));
                } elseif ($method === 'C') {
                    $this->invoice->completeTaxInvoice($this->inputInt('id', 0));
                }
                $this->redirect('index.php?page=compl_list2');
                break;
        }
        $this->redirect('index.php?page=compl_list');
    }

    /**
     * AJAX endpoint: return invoices in a split group as JSON
     */
    public function splitGroupJson(): void
    {
        header('Content-Type: application/json');
        $splitGroupId = $this->inputInt('split_group_id', 0);
        if ($splitGroupId <= 0) {
            echo json_encode([]);
            return;
        }
        $invoices = $this->invoice->getSplitGroupInvoices($splitGroupId);
        echo json_encode($invoices);
    }
}
