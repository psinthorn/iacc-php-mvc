<?php
namespace App\Controllers;

use App\Models\ProductModel;

/**
 * ModelController - Handles all product model CRUD operations
 * 
 * Replaces:
 *   - mo-list.php (list view with inline form)
 *   - model.php (AJAX brand loader)
 *   - core-function.php case "mo_list" (form processing)
 */
class ModelController extends BaseController
{
    private ProductModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new ProductModel();
    }

    /**
     * Display model list with inline create/edit form
     * Route: ?page=mo_list
     */
    public function index(): void
    {
        $search      = trim($this->input('search', ''));
        $status      = $this->input('status', '');
        $currentPage = max(1, $this->inputInt('p', 1));
        $typeId      = $this->inputInt('type_id', 0);
        $brandId     = $this->inputInt('brand_id', 0);
        $perPage     = 15;

        $result = $this->model->getPaginated($search, $currentPage, $perPage, $typeId, $brandId, $status);

        // Edit mode
        $editId   = $this->inputInt('edit', 0);
        $editData = null;
        if ($editId > 0) {
            $editData = $this->model->find($editId);
        }
        $showForm = isset($_GET['new']) || $editData !== null;

        // Dropdown data
        $types  = $this->model->getTypes();
        $brands = $this->model->getBrands();

        $this->render('model/list', [
            'items'        => $result['items'],
            'total_items'  => $result['total'],
            'item_count'   => $result['count'],
            'pagination'   => $result['pagination'],
            'stats'        => $this->model->getStats(),
            'search'       => $search,
            'status'       => $status,
            'type_id'      => $typeId,
            'brand_id'     => $brandId,
            'edit_data'    => $editData,
            'show_form'    => $showForm,
            'types'        => $types,
            'brands'       => $brands,
            'query_params' => $_GET,
        ]);
    }

    /**
     * Handle model create/update (POST)
     * Route: ?page=mo_list_store
     */
    public function store(): void
    {
        $this->verifyCsrf();

        $method    = $this->input('method', 'A');
        $id        = $this->inputInt('p_id', 0);
        $modelName = $this->inputStr('model_name', '');
        $typeId    = $this->inputInt('type', 0);
        $brandId   = $this->inputInt('brand', 0);
        $price     = floatval($_REQUEST['price'] ?? 0);
        $des       = $this->inputStr('des', '');
        $companyId = $this->getCompanyId();
        // v6.6 #135 follow-up — LINE catalog visibility toggle. HTML
        // unchecked checkboxes don't submit, so absence means "uncheck"
        // (i.e. hide from carousel). New rows default to visible.
        $isCustomerBookable = isset($_POST['is_customer_bookable']) ? 1 : 0;

        switch ($method) {
            case 'A': // Add
                $this->model->create([
                    'company_id'           => $companyId,
                    'type_id'              => $typeId,
                    'brand_id'             => $brandId,
                    'model_name'           => $modelName,
                    'des'                  => $des,
                    'price'                => $price,
                    'is_customer_bookable' => $isCustomerBookable,
                ]);
                break;

            case 'E': // Edit
                if ($id > 0) {
                    $data = [
                        'model_name'           => $modelName,
                        'des'                  => $des,
                        'price'                => $price,
                        'is_customer_bookable' => $isCustomerBookable,
                    ];
                    if ($typeId > 0)  $data['type_id']  = $typeId;
                    if ($brandId > 0) $data['brand_id'] = $brandId;
                    $this->model->update($id, $data);
                }
                break;

            case 'D': // Delete (with product check)
                if ($id > 0) {
                    $check = $this->model->canDelete($id);
                    if ($check['can_delete']) {
                        $this->model->delete($id);
                        $_SESSION['flash_success'] = 'Model deleted successfully.';
                    } else {
                        $_SESSION['flash_error'] = 'Cannot delete this model. It is being used by ' . $check['product_count'] . ' product(s).';
                    }
                }
                break;
        }

        // Preserve filter params for redirect
        $redirectParams = [];
        if (!empty($_REQUEST['type_id'])) $redirectParams['type_id'] = intval($_REQUEST['type_id']);
        if (!empty($_REQUEST['brand_id'])) $redirectParams['brand_id'] = intval($_REQUEST['brand_id']);
        if (!empty($_REQUEST['search'])) $redirectParams['search'] = $_REQUEST['search'];

        $this->redirect('mo_list', $redirectParams);
    }

    /**
     * Handle model deletion via GET
     * Route: ?page=mo_list_delete&p_id=X
     */
    public function delete(): void
    {
        $id = $this->inputInt('p_id', 0);
        if ($id > 0) {
            $check = $this->model->canDelete($id);
            if ($check['can_delete']) {
                $this->model->delete($id);
                $_SESSION['flash_success'] = 'Model deleted successfully.';
            } else {
                $_SESSION['flash_error'] = 'Cannot delete this model. It is being used by ' . $check['product_count'] . ' product(s).';
            }
        }

        $redirectParams = [];
        if (!empty($_REQUEST['type_id'])) $redirectParams['type_id'] = intval($_REQUEST['type_id']);
        if (!empty($_REQUEST['brand_id'])) $redirectParams['brand_id'] = intval($_REQUEST['brand_id']);
        if (!empty($_REQUEST['search'])) $redirectParams['search'] = $_REQUEST['search'];

        $this->redirect('mo_list', $redirectParams);
    }

    /**
     * AJAX toggle is_active
     * Route: ?page=mo_list_toggle  POST {id, active, csrf_token}
     */
    public function toggle(): void
    {
        $this->verifyCsrf();
        $id     = $this->inputInt('id', 0);
        $active = intval($this->input('active', '1'));
        $ok     = $id > 0 && $this->model->toggle($id, $active);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'active' => $active]);
        exit;
    }

    /**
     * AJAX: Get brands for a given type
     * Route: ?page=mo_list_brands&q=TYPE_ID
     * 
     * Returns HTML <option> elements (replaces model.php AJAX handler)
     */
    public function getBrands(): void
    {
        $typeId = $this->inputInt('q', 0);
        $brands = $this->model->getBrandsForType($typeId);

        $html = '<option value="">-- Select Brand --</option>';
        foreach ($brands as $b) {
            $html .= '<option value="' . intval($b['id']) . '">' . htmlspecialchars($b['brand_name']) . '</option>';
        }

        echo $html;
        exit;
    }
}
