<?php
namespace App\Controllers;

use App\Models\PaymentMethod;

/**
 * PaymentMethodController - Handles all payment method CRUD operations
 * 
 * Replaces:
 *   - payment-method-list.php (list view)
 *   - payment-method.php (form + POST handling)
 */
class PaymentMethodController extends BaseController
{
    private PaymentMethod $paymentMethod;

    public function __construct()
    {
        parent::__construct();
        $this->paymentMethod = new PaymentMethod();
    }

    /**
     * Display payment method list
     * Route: ?page=payment_method_list
     */
    public function index(): void
    {
        $search = trim($this->input('search', ''));
        $type   = $this->input('type', '');
        $status = $this->input('status', '');

        $items = $this->paymentMethod->getFiltered($search, $type, $status);
        $stats = $this->paymentMethod->getStats();

        $this->render('payment-method/list', [
            'items'   => $items,
            'stats'   => $stats,
            'search'  => $search,
            'type'    => $type,
            'status'  => $status,
        ]);
    }

    /**
     * Display payment method form (add/edit)
     * Route: ?page=payment_method&mode=A or ?page=payment_method&mode=E&id=X
     */
    public function form(): void
    {
        $mode = $this->input('mode', 'A');
        $id   = $this->inputInt('id', 0);
        $data = null;

        if ($mode === 'E' && $id > 0) {
            $data = $this->paymentMethod->find($id);
            if (!$data) {
                $this->redirect('payment_method_list');
                return;
            }
        }

        // Defaults for new
        if (!$data) {
            $data = [
                'code'        => '',
                'name'        => '',
                'name_th'     => '',
                'icon'        => 'fa-money',
                'description' => '',
                'is_gateway'  => 0,
                'is_active'   => 1,
                'sort_order'  => $this->paymentMethod->getNextSortOrder(),
            ];
        }

        $this->render('payment-method/form', [
            'data' => $data,
            'mode' => $mode,
            'id'   => $id,
        ]);
    }

    /**
     * Handle payment method create/update (POST)
     * Route: ?page=payment_method_store
     */
    public function store(): void
    {
        $this->verifyCsrf();

        $mode      = $this->input('mode', 'A');
        $id        = $this->inputInt('id', 0);
        $companyId = $this->getCompanyId();

        $data = [
            'code'        => $this->inputStr('code', ''),
            'name'        => $this->inputStr('name', ''),
            'name_th'     => $this->inputStr('name_th', ''),
            'icon'        => $this->inputStr('icon', 'fa-money'),
            'description' => $this->inputStr('description', ''),
            'is_gateway'  => isset($_POST['is_gateway']) ? 1 : 0,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'  => $this->inputInt('sort_order', 0),
        ];

        if ($mode === 'E' && $id > 0) {
            $this->paymentMethod->update($id, $data);
            $_SESSION['flash_success'] = 'Payment method updated successfully.';
        } else {
            $data['company_id'] = $companyId;
            $this->paymentMethod->create($data);
            $_SESSION['flash_success'] = 'Payment method created successfully.';
        }

        $this->redirect('payment_method_list');
    }

    /**
     * Handle payment method deletion via GET
     * Route: ?page=payment_method_delete&id=X
     */
    public function delete(): void
    {
        $id = $this->inputInt('id', 0);
        if ($id > 0) {
            $this->paymentMethod->delete($id);
            $_SESSION['flash_success'] = 'Payment method deleted successfully.';
        }
        $this->redirect('payment_method_list');
    }

    /**
     * Toggle active status via GET
     * Route: ?page=payment_method_toggle&id=X
     */
    public function toggle(): void
    {
        $id = $this->inputInt('id', 0);
        if ($id > 0) {
            $this->paymentMethod->toggleActive($id);
        }
        $this->redirect('payment_method_list');
    }
}
