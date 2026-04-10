<?php
namespace App\Controllers;

use App\Models\Delivery;

/**
 * DeliveryController - Handles delivery notes list, create, edit, view
 * Replaces: deliv-list.php, deliv-make.php, deliv-edit.php, deliv-view.php, core-function.php case deliv_list
 */
class DeliveryController extends BaseController
{
    private Delivery $delivery;

    public function __construct()
    {
        parent::__construct();
        $this->delivery = new Delivery();
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

        $totalOut = $this->delivery->countDeliveries($comId, 'out', $filters);
        $totalIn = $this->delivery->countDeliveries($comId, 'in', $filters);
        $total = $totalOut + $totalIn;
        $pagination = paginate($total, $perPage, $page);

        $this->render('delivery/list', [
            'items_out' => $this->delivery->getDeliveries($comId, 'out', $filters, $pagination['offset'], $perPage),
            'items_in' => $this->delivery->getDeliveries($comId, 'in', $filters, $pagination['offset'], $perPage),
            'sendouts_out' => $this->delivery->getSendouts($comId, 'out'),
            'sendouts_in' => $this->delivery->getSendouts($comId, 'in'),
            'total_out' => $totalOut, 'total_in' => $totalIn, 'total_records' => $total,
            'pagination' => $pagination, 'filters' => $filters, 'per_page' => $perPage,
        ]);
    }

    public function make(): void
    {
        $comId = $this->getCompanyId();
        $this->render('delivery/make', [
            'customers' => $this->delivery->getCustomers($comId),
            'types' => (new \App\Models\PurchaseOrder())->getTypes($comId),
        ]);
    }

    public function edit(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $mode = $this->input('modep', '');
        $detail = $this->delivery->getDeliveryDetail($id, $comId, $mode);
        $products = $detail ? $this->delivery->getDeliveryProducts(
            $mode === 'ad' ? intval($detail['id']) : intval($detail['po_id'] ?? 0), $mode) : [];

        $this->render('delivery/edit', [
            'detail' => $detail, 'products' => $products,
            'id' => $id, 'mode' => $mode,
            'customers' => $this->delivery->getCustomers($comId),
        ]);
    }

    public function view(): void
    {
        $comId = $this->getCompanyId();
        $id = $this->inputInt('id', 0);
        $mode = $this->input('modep', '');
        $detail = $this->delivery->getDeliveryDetail($id, $comId, $mode);

        $products = [];
        if ($detail) {
            if ($mode === 'ad') {
                $products = $this->delivery->getDeliveryProducts(intval($detail['id']), 'ad');
            } else {
                $products = $this->delivery->getDeliveryProducts(intval($detail['po_id'] ?? 0));
            }
        }

        $this->render('delivery/view', [
            'detail' => $detail, 'products' => $products,
            'id' => $id, 'mode' => $mode,
        ]);
    }

    public function print(): void
    {
        include __DIR__ . '/../Views/delivery/print.php';
        exit;
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', '');
        $comId = $this->getCompanyId();

        // Check for duplicate serial numbers
        $sns = $_REQUEST['sn'] ?? [];
        $flag = 0;
        $ctsn = count($sns);
        for ($i = 0; $i < $ctsn; $i++) {
            for ($j = $i + 1; $j < $ctsn; $j++) {
                if ($sns[$i] == $sns[$j]) $flag++;
            }
        }

        switch ($method) {
            case 'c':
                if ($flag == 0) {
                    $this->delivery->createDelivery($_REQUEST, $comId);
                }
                $this->redirect('index.php?page=deliv_list');
                break;

            case 'm':
                if ($flag == 0) {
                    // From stock delivery — similar to create but uses existing store items
                    $this->delivery->createDelivery($_REQUEST, $comId);
                }
                $this->redirect('index.php?page=deliv_list');
                break;

            case 'ED':
                $this->delivery->editDelivery($_REQUEST, $comId);
                $this->redirect('index.php?page=deliv_list');
                break;

            case 'R':
                $this->delivery->receiveDelivery($_REQUEST, $comId);
                $this->redirect('index.php?page=compl_list');
                break;

            case 'R2':
                $this->delivery->receiveStandalone($_REQUEST, $comId);
                $this->redirect('index.php?page=deliv_list');
                break;

            case 'AD':
                $this->delivery->createSendout($_REQUEST, $comId);
                $this->redirect('index.php?page=deliv_list');
                break;

            default:
                $this->redirect('index.php?page=deliv_list');
        }
    }
}
