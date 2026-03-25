<?php
namespace App\Controllers;

use App\Models\Type;

/**
 * TypeController - Handles all product type CRUD operations
 * 
 * Replaces:
 *   - type-list.php (list view with inline form)
 *   - core-function.php case "type" (form processing)
 * 
 * Special: manages brand associations via map_type_to_brand junction table
 */
class TypeController extends BaseController
{
    private Type $type;

    public function __construct()
    {
        parent::__construct();
        $this->type = new Type();
    }

    /**
     * Display type list with inline create/edit form
     * Route: ?page=type
     */
    public function index(): void
    {
        $search      = trim($this->input('search', ''));
        $currentPage = max(1, $this->inputInt('p', 1));
        $catId       = $this->inputInt('cat_id', 0);
        $perPage     = 15;

        $result = $this->type->getPaginated($search, $currentPage, $perPage, $catId);

        // Edit mode
        $editId   = $this->inputInt('edit', 0);
        $editData = null;
        $editBrandIds = [];
        if ($editId > 0) {
            $editData = $this->type->find($editId);
            if ($editData) {
                $editBrandIds = $this->type->getAssociatedBrandIds($editId);
            }
        }
        $showForm = isset($_GET['new']) || $editData !== null;

        // Dropdown data
        $categories = $this->type->getCategories();
        $brands     = $this->type->getAllBrands();

        $this->render('type/list', [
            'items'          => $result['items'],
            'total_items'    => $result['total'],
            'item_count'     => $result['count'],
            'pagination'     => $result['pagination'],
            'search'         => $search,
            'cat_id'         => $catId,
            'edit_data'      => $editData,
            'edit_brand_ids' => $editBrandIds,
            'show_form'      => $showForm,
            'categories'     => $categories,
            'brands'         => $brands,
            'query_params'   => $_GET,
        ]);
    }

    /**
     * Handle type create/update (POST)
     * Route: ?page=type_store
     */
    public function store(): void
    {
        $this->verifyCsrf();

        $method    = $this->input('method', 'A');
        $id        = $this->inputInt('id', 0);
        $typeName  = $this->inputStr('type_name', '');
        $catId     = $this->inputInt('cat_id', 0);
        $des       = $this->inputStr('des', '');
        $companyId = $this->getCompanyId();

        // Extract brand IDs from POST (any key not in known fields is a brand_id checkbox)
        $knownFields = ['type_name', 'cat_id', 'des', 'method', 'page', 'id', 'csrf_token'];
        $brandIds = [];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, $knownFields) && is_numeric($key)) {
                $brandIds[] = intval($key);
            }
        }

        switch ($method) {
            case 'A': // Add
                $newId = $this->type->create([
                    'company_id' => $companyId,
                    'name'       => $typeName,
                    'des'        => $des,
                    'cat_id'     => $catId,
                ]);
                if ($newId) {
                    $this->type->syncBrands($newId, $brandIds);
                }
                break;

            case 'E': // Edit
                if ($id > 0) {
                    $this->type->update($id, [
                        'name'   => $typeName,
                        'cat_id' => $catId,
                        'des'    => $des,
                    ]);
                    $this->type->syncBrands($id, $brandIds);
                }
                break;

            case 'D': // Delete
                if ($id > 0) {
                    $this->type->deleteWithBrands($id);
                }
                break;
        }

        $this->redirect('type');
    }

    /**
     * Handle type deletion via GET
     * Route: ?page=type_delete&id=X
     */
    public function delete(): void
    {
        $id = $this->inputInt('id', 0);
        if ($id > 0) {
            $this->type->deleteWithBrands($id);
        }
        $this->redirect('type');
    }
}
