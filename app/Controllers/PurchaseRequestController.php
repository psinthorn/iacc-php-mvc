<?php
namespace App\Controllers;

use App\Models\PurchaseRequest;

/**
 * PurchaseRequestController - Handles PR list, create, view
 * Replaces: pr-list.php, pr-create.php, pr-make.php, core-function.php case pr_list
 */
class PurchaseRequestController extends BaseController
{
    private PurchaseRequest $pr;

    public function __construct()
    {
        parent::__construct();
        $this->pr = new PurchaseRequest();
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
            'status' => $this->input('status', ''),
        ];

        $totalOut = $this->pr->countPRs($comId, 'out', $filters);
        $totalIn = $this->pr->countPRs($comId, 'in', $filters);
        $total = $totalOut + $totalIn;
        $pagination = paginate($total, $perPage, $page);

        $this->render('pr/list', [
            'items_out' => $this->pr->getPRs($comId, 'out', $filters, $pagination['offset'], $perPage),
            'items_in' => $this->pr->getPRs($comId, 'in', $filters, $pagination['offset'], $perPage),
            'total_out' => $totalOut, 'total_in' => $totalIn, 'total_records' => $total,
            'pagination' => $pagination, 'filters' => $filters, 'per_page' => $perPage,
            'query_params' => array_diff_key($_GET, ['pg' => '']),
        ]);
    }

    public function create(): void
    {
        $comId = $this->getCompanyId();
        $this->render('pr/create', [
            'customers' => $this->pr->getCustomers(),
            'categories' => $this->pr->getCategoriesWithTypes($comId),
            'com_id' => $comId,
        ]);
    }

    public function view(): void
    {
        $id = $this->inputInt('id', 0);
        $comId = $this->getCompanyId();
        $pr = $this->pr->getPRDetail($id, $comId);
        $products = $pr ? $this->pr->getTmpProducts($id) : [];
        $this->render('pr/view', ['pr' => $pr, 'products' => $products, 'id' => $id]);
    }

    public function store(): void
    {
        $method = $this->input('method', '');
        $comId = $this->getCompanyId();

        // Cancel via GET (from list page cancel button with csrf_token in URL)
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $method === 'D') {
            if (!csrf_verify()) {
                die('CSRF token validation failed.');
            }
            $this->pr->cancelPR($this->inputInt('id', 0), $comId);
            $this->redirect('index.php?page=pr_list');
            return;
        }

        // Other GET requests → redirect to create form
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?page=pr_make');
            return;
        }

        $this->verifyCsrf();

        if ($method === 'D') {
            $this->pr->cancelPR($this->inputInt('id', 0), $comId);
            $this->redirect('index.php?page=pr_list');
        } elseif ($method === 'A') {
            $this->pr->createPR(array_merge($_REQUEST, ['user_id' => $this->user['id']]), $comId);
            $this->redirect('index.php?page=pr_list');
        }
        $this->redirect('index.php?page=pr_list');
    }
}
