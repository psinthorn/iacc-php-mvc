<?php
namespace App\Controllers;

use App\Models\Category;

/**
 * CategoryController - Handles all category CRUD operations
 * 
 * Replaces:
 *   - category-list.php (list view with inline form)
 *   - category.php (standalone form — kept as legacy fallback)
 *   - core-function.php case "category" (form processing)
 */
class CategoryController extends BaseController
{
    private Category $category;

    public function __construct()
    {
        parent::__construct();
        $this->category = new Category();
    }

    /**
     * Display category list with inline create/edit form
     * Route: ?page=category
     */
    public function index(): void
    {
        // Get search/pagination params
        $search      = trim($this->input('search', ''));
        $status      = $this->input('status', '');
        $currentPage = max(1, $this->inputInt('p', 1));
        $perPage     = 15;

        // Fetch paginated data
        $result = $this->category->getPaginated($search, $currentPage, $perPage, $status);

        // Check for edit mode
        $editId   = $this->inputInt('edit', 0);
        $editData = null;
        if ($editId > 0) {
            $editData = $this->category->find($editId);
        }
        $showForm = isset($_GET['new']) || $editData !== null;

        $this->render('category/list', [
            'items'       => $result['items'],
            'total_items' => $result['total'],
            'item_count'  => $result['count'],
            'pagination'  => $result['pagination'],
            'stats'       => $this->category->getStats(),
            'search'      => $search,
            'status'      => $status,
            'edit_data'   => $editData,
            'show_form'   => $showForm,
            'query_params'=> $_GET,
        ]);
    }

    /**
     * Display standalone category form (legacy support)
     * Route: ?page=category_form
     */
    public function form(): void
    {
        $catId = $this->inputInt('id', 0);
        $data  = null;
        $method = 'A';

        if ($catId > 0) {
            $data = $this->category->find($catId);
            if ($data) {
                $method = 'E';
            }
        }

        $this->render('category/form', [
            'data'   => $data,
            'method' => $method,
            'cat_id' => $catId,
        ]);
    }

    /**
     * Handle category create/update/delete (POST)
     * Route: ?page=category_store (POST from form)
     * 
     * Replaces core-function.php case "category"
     */
    public function store(): void
    {
        $this->verifyCsrf();

        $method    = $this->input('method', 'A');
        $id        = $this->inputInt('id', 0);
        $catName   = $this->inputStr('cat_name', '');
        $des       = $this->inputStr('des', '');
        $companyId = $this->getCompanyId();

        switch ($method) {
            case 'A': // Add
                $this->category->create([
                    'company_id' => $companyId,
                    'cat_name'   => $catName,
                    'des'        => $des,
                ]);
                break;

            case 'E': // Edit
                if ($id > 0) {
                    $this->category->update($id, [
                        'cat_name' => $catName,
                        'des'      => $des,
                    ]);
                }
                break;

            case 'D': // Delete
                if ($id > 0) {
                    $this->category->delete($id);
                }
                break;
        }

        $this->redirect('category');
    }

    /**
     * AJAX toggle is_active
     * Route: ?page=category_toggle  POST {id, active, csrf_token}
     */
    public function toggle(): void
    {
        $this->verifyCsrf();
        $id     = $this->inputInt('id', 0);
        $active = intval($this->input('active', '1'));
        $ok     = $id > 0 && $this->category->toggle($id, $active);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'active' => $active]);
        exit;
    }

    /**
     * Handle category deletion via GET (for delete links)
     * Route: ?page=category_delete&id=X
     */
    public function delete(): void
    {
        $id = $this->inputInt('id', 0);
        if ($id > 0) {
            $this->category->delete($id);
        }
        $this->redirect('category');
    }
}
