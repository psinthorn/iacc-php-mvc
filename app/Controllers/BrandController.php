<?php
namespace App\Controllers;

use App\Models\Brand;

/**
 * BrandController - Handles all brand CRUD operations
 * 
 * Replaces:
 *   - brand-list.php (list view with inline form)
 *   - brand.php (standalone form)
 *   - core-function.php case "brand" (form processing)
 */
class BrandController extends BaseController
{
    private Brand $brand;

    public function __construct()
    {
        parent::__construct();
        $this->brand = new Brand();
    }

    /**
     * Display brand list with inline create/edit form
     * Route: ?page=brand
     */
    public function index(): void
    {
        $search      = trim($this->input('search', ''));
        $status      = $this->input('status', '');
        $currentPage = max(1, $this->inputInt('p', 1));
        $perPage     = 15;

        $result = $this->brand->getPaginated($search, $currentPage, $perPage, $status);

        // Edit mode
        $editId   = $this->inputInt('edit', 0);
        $editData = null;
        if ($editId > 0) {
            $editData = $this->brand->find($editId);
        }
        $showForm = isset($_GET['new']) || $editData !== null;

        // Get vendor dropdown data
        $companyId  = $this->getCompanyId();
        $ownCompany = $this->brand->getOwnCompany($companyId);
        $vendors    = $this->brand->getVendors($companyId);

        $this->render('brand/list', [
            'items'        => $result['items'],
            'total_items'  => $result['total'],
            'item_count'   => $result['count'],
            'pagination'   => $result['pagination'],
            'stats'        => $this->brand->getStats(),
            'search'       => $search,
            'status'       => $status,
            'edit_data'    => $editData,
            'show_form'    => $showForm,
            'own_company'  => $ownCompany,
            'vendors'      => $vendors,
            'query_params' => $_GET,
        ]);
    }

    /**
     * Display standalone brand form
     * Route: ?page=brand_form
     */
    public function form(): void
    {
        $brandId  = $this->inputInt('id', 0);
        $data     = null;
        $method   = 'A';

        if ($brandId > 0) {
            $data = $this->brand->find($brandId);
            if ($data) {
                $method = 'E';
            }
        }

        $companyId = $this->getCompanyId();
        $vendors   = $this->brand->getVendors($companyId);

        $this->render('brand/form', [
            'data'      => $data,
            'method'    => $method,
            'brand_id'  => $brandId,
            'vendors'   => $vendors,
        ]);
    }

    /**
     * Handle brand create/update (POST)
     * Route: ?page=brand_store
     */
    public function store(): void
    {
        $this->verifyCsrf();

        $method    = $this->input('method', 'A');
        $id        = $this->inputInt('id', 0);
        $brandName = $this->inputStr('brand_name', '');
        $des       = $this->inputStr('des', '');
        $venId     = $this->inputInt('ven_id', 0);
        $companyId = $this->getCompanyId();

        // Handle logo upload
        $logo = $this->brand->handleLogoUpload();

        switch ($method) {
            case 'A': // Add
                $data = [
                    'company_id' => $companyId,
                    'brand_name' => $brandName,
                    'des'        => $des,
                    'logo'       => $logo ?? '',
                    'ven_id'     => $venId,
                ];
                $this->brand->create($data);
                break;

            case 'E': // Edit
                if ($id > 0) {
                    $data = [
                        'brand_name' => $brandName,
                        'des'        => $des,
                        'ven_id'     => $venId,
                    ];
                    if ($logo !== null) {
                        $data['logo'] = $logo;
                    }
                    $this->brand->update($id, $data);
                }
                break;

            case 'D': // Delete
                if ($id > 0) {
                    $this->brand->delete($id);
                    // Also delete brand mappings
                    $this->query("DELETE FROM map_type_to_brand WHERE brand_id='" . \sql_int($id) . "'");
                }
                break;
        }

        $this->redirect('brand');
    }

    /**
     * AJAX toggle is_active
     * Route: ?page=brand_toggle  POST {id, active, csrf_token}
     */
    public function toggle(): void
    {
        $this->verifyCsrf();
        $id     = $this->inputInt('id', 0);
        $active = intval($this->input('active', '1'));
        $ok     = $id > 0 && $this->brand->toggle($id, $active);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'active' => $active]);
        exit;
    }

    /**
     * Handle brand deletion via GET
     * Route: ?page=brand_delete&id=X
     */
    public function delete(): void
    {
        $id = $this->inputInt('id', 0);
        if ($id > 0) {
            $this->brand->delete($id);
            $this->query("DELETE FROM map_type_to_brand WHERE brand_id='" . \sql_int($id) . "'");
        }
        $this->redirect('brand');
    }
}
