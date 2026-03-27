<?php
namespace App\Controllers;

use App\Models\PurchaseOrder;

/**
 * PurchaseOrderController - Handles PO list, create, edit, view, delivery
 * Replaces: po-list.php, po-make.php, po-edit.php, po-view.php, po-deliv.php, core-function.php case po_list
 */
class PurchaseOrderController extends BaseController
{
    private PurchaseOrder $po;

    public function __construct()
    {
        parent::__construct();
        $this->po = new PurchaseOrder();
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
            'date_from' => $this->input('date_from', ''),
            'date_to' => $this->input('date_to', ''),
        ];

        $totalOut = $this->po->countPOs($comId, 'out', $filters);
        $totalIn = $this->po->countPOs($comId, 'in', $filters);
        $total = $totalOut + $totalIn;
        $pagination = paginate($total, $perPage, $page);

        $this->render('po/list', [
            'items_out' => $this->po->getPOs($comId, 'out', $filters, $pagination['offset'], $perPage),
            'items_in' => $this->po->getPOs($comId, 'in', $filters, $pagination['offset'], $perPage),
            'total_out' => $totalOut, 'total_in' => $totalIn, 'total_records' => $total,
            'pagination' => $pagination, 'filters' => $filters, 'per_page' => $perPage,
            'query_params' => array_diff_key($_GET, ['pg' => '']),
        ]);
    }

    public function make(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $pr = $this->po->getPOForMake($id, $comId);
        $credit = $pr ? $this->po->getCredit($comId, intval($pr['cus_id'])) : null;

        $this->render('po/make', [
            'pr' => $pr, 'id' => $id,
            'types' => $this->po->getTypes($comId),
            'models' => $this->po->getModels(),
            'brands' => $this->po->getBrands($comId),
            'companies' => $this->po->getCompanies(),
            'tmp_products' => $pr ? $this->po->getTmpProducts($id) : [],
            'credit' => $credit,
        ]);
    }

    public function edit(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $poData = $this->po->getPOForEdit($id, $comId);

        $this->render('po/edit', [
            'po' => $poData, 'id' => $id,
            'products' => $poData ? $this->po->getProducts($id) : [],
            'types' => $this->po->getTypes($comId),
            'models' => $this->po->getModels(),
            'brands' => $this->po->getBrands($comId),
            'companies' => $this->po->getCompanies(),
        ]);
    }

    public function view(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $poData = $this->po->getPODetail($id, $comId);

        $this->render('po/view', [
            'po' => $poData, 'id' => $id,
            'products' => $poData ? $this->po->getProducts($id) : [],
            'has_labour' => $poData ? $this->po->hasLabour($id) : false,
            'payment_methods' => $poData ? $this->po->getPaymentMethods(intval($poData['ven_id'])) : [],
        ]);
    }

    public function delivery(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $action = $this->input('action', 'c');
        $poData = $this->po->getPOForDelivery($id, $comId);
        $products = $poData ? $this->po->getProductsForDelivery($id) : [];

        $this->render('po/delivery', [
            'po' => $poData, 'id' => $id, 'action' => $action,
            'products' => $products,
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', '');
        $comId = $this->getCompanyId();

        switch ($method) {
            case 'D':
                $status = $this->po->cancelPO($this->inputInt('id', 0), $comId);
                if ($status === '1') {
                    $this->redirect('index.php?page=qa_list');
                }
                $this->redirect('index.php?page=po_list');
                break;

            case 'A':
                $this->po->createPO(array_merge($_REQUEST, ['com_id' => $comId]), $comId);
                $this->redirect('index.php?page=qa_list');
                break;

            case 'E':
                $this->po->editPO(array_merge($_REQUEST, ['com_id' => $comId]), $comId);
                $this->redirect('index.php?page=qa_list');
                break;

            case 'C':
                $this->po->confirmPO($_REQUEST, $_FILES, $comId);
                $this->redirect('index.php?page=po_list');
                break;

            default:
                $this->redirect('index.php?page=po_list');
        }
    }
}
