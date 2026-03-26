<?php
namespace App\Controllers;

use App\Models\Voucher;

/**
 * VoucherController - Handles voucher list, create, edit, view
 * Replaces: voucher list views, voc-make.php, voc-view.php, core-function.php case voucher_list
 */
class VoucherController extends BaseController
{
    private Voucher $voucher;

    public function __construct()
    {
        parent::__construct();
        $this->voucher = new Voucher();
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

        $total = $this->voucher->countVouchers($comId, $filters);
        $pagination = paginate($total, $perPage, $page);

        $this->render('voucher/list', [
            'items' => $this->voucher->getVouchers($comId, $filters, $pagination['offset'], $perPage),
            'stats' => $this->voucher->getStats($comId),
            'total_records' => $total, 'pagination' => $pagination,
            'filters' => $filters, 'per_page' => $perPage,
        ]);
    }

    public function make(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $this->render('voucher/make', [
            'voucher' => $id > 0 ? $this->voucher->findVoucher($id, $comId) : null,
            'products' => $id > 0 ? $this->voucher->getVoucherProducts($id) : [],
            'types' => $this->voucher->getTypes($comId),
            'id' => $id,
        ]);
    }

    public function view(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $this->render('voucher/view', [
            'voucher' => $this->voucher->findVoucher($id, $comId),
            'products' => $this->voucher->getVoucherProducts($id),
            'id' => $id,
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', '');
        $comId = $this->getCompanyId();

        if ($method === 'A') {
            $this->voucher->createVoucher($_REQUEST, $comId);
        } elseif ($method === 'E') {
            $this->voucher->updateVoucher($this->inputInt('id', 0), $_REQUEST, $comId);
        } elseif ($method === 'C') {
            // File upload for PO confirmation — legacy pattern
            $po = new \App\Models\PurchaseOrder();
            $po->confirmPO($_REQUEST, $_FILES, $comId);
        }
        $this->redirect('index.php?page=voucher_list');
    }

    public function print(): void
    {
        include __DIR__ . '/../Views/voucher/print.php';
        exit;
    }
}
