<?php
namespace App\Controllers;

use App\Models\Company;

/**
 * CompanyController - Handles all company CRUD operations
 * 
 * Replaces:
 *   - company-list.php (list view with stats, search, filters)
 *   - company.php (create/edit form loaded via AJAX or direct)
 *   - company-addr.php (address form - now integrated into main form)
 *   - company-credit.php (credit form)
 *   - credit-list.php (credit list)
 *   - core-function.php case "company" (methods A, E, A2, A3, A4, D)
 */
class CompanyController extends BaseController
{
    private Company $company;

    public function __construct()
    {
        parent::__construct();
        $this->company = new Company();
    }

    /**
     * Display company list with stats and filters
     * Route: ?page=company
     */
    public function index(): void
    {
        $search     = trim($this->input('search', ''));
        $typeFilter = $this->input('type', '');
        $page       = max(1, $this->inputInt('p', 1));
        $perPage    = 15;

        $result = $this->company->getPaginated($search, $typeFilter, $page, $perPage);

        $this->render('company/list', [
            'items'        => $result['items'],
            'total'        => $result['total'],
            'count'        => $result['count'],
            'pagination'   => $result['pagination'],
            'stats'        => $result['stats'],
            'search'       => $search,
            'type_filter'  => $typeFilter,
            'com_id'       => $this->getCompanyId(),
            'query_params' => $_GET,
        ]);
    }

    /**
     * Display company create/edit form
     * Route: ?page=company_form[&id=X]
     * 
     * This replaces the old company.php which was loaded via AJAX.
     * Now renders as a full page within the MVC layout.
     */
    public function form(): void
    {
        $id     = $this->inputInt('id', 0);
        $method = 'A';
        $data   = [];

        if ($id > 0) {
            $found = $this->company->findWithAddress($id);
            if ($found) {
                $data   = $found;
                $method = 'E';
            }
        }

        $this->render('company/form', [
            'data'   => $data,
            'method' => $method,
            'id'     => $id,
        ]);
    }

    /**
     * Handle company create/update/delete (POST)
     * Route: ?page=company_store
     */
    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', 'A');
        $id     = $this->inputInt('id', 0);

        switch ($method) {
            case 'A': // Create company
                $fields = $this->getCompanyFields();
                $newId = $this->company->createCompany($fields);
                
                // Save address if provided
                if ($newId > 0 && !empty(trim($_REQUEST['adr_tax'] ?? ''))) {
                    $this->company->saveAddress($newId, $_REQUEST, 0);
                }
                break;

            case 'E': // Update company
                if ($id > 0) {
                    $fields = $this->getCompanyFields();
                    $this->company->updateCompany($id, $fields);

                    // Save address
                    $addrId = $this->inputInt('addr_id', 0);
                    if (!empty(trim($_REQUEST['adr_tax'] ?? ''))) {
                        $this->company->saveAddress($id, $_REQUEST, $addrId);
                    }
                }
                break;

            case 'A2': // New address version
                $comId = $this->inputInt('com_id', 0);
                if ($comId > 0) {
                    $this->company->addAddressVersion($comId, $_REQUEST);
                }
                break;

            case 'A3': // New credit record
            case 'A4': // Update credit record (version)
                $this->company->saveCredit($_REQUEST);
                break;

            case 'D': // Soft delete
                if ($id > 0) {
                    $this->company->softDeleteCompany($id);
                }
                break;
        }

        $this->redirect('company');
    }

    /**
     * Handle company deletion via GET
     * Route: ?page=company_delete&id=X
     */
    public function delete(): void
    {
        $id = $this->inputInt('id', 0);
        if ($id > 0) {
            $this->company->softDeleteCompany($id);
        }
        $this->redirect('company');
    }

    /**
     * Display credit list for a company (AJAX-loaded)
     * Route: ?page=company_credits&id=X
     */
    public function credits(): void
    {
        $id = $this->inputInt('id', 0);
        if ($id <= 0) {
            echo '<div class="alert alert-warning">Invalid company ID</div>';
            return;
        }

        $credits = $this->company->getCreditRecords($id);
        $availableCustomers = $this->company->getAvailableCustomersForCredit($id);

        $this->render('company/credits', [
            'company_id'          => $id,
            'vendor_credits'      => $credits['vendor_credits'],
            'customer_credits'    => $credits['customer_credits'],
            'available_customers' => $availableCustomers,
        ]);
    }

    /**
     * Extract company fields from request with checkbox handling
     */
    private function getCompanyFields(): array
    {
        return [
            'name_en'  => $_REQUEST['name_en'] ?? '',
            'name_th'  => $_REQUEST['name_th'] ?? '',
            'name_sh'  => $_REQUEST['name_sh'] ?? '',
            'contact'  => $_REQUEST['contact'] ?? '',
            'email'    => $_REQUEST['email'] ?? '',
            'phone'    => $_REQUEST['phone'] ?? '',
            'fax'      => $_REQUEST['fax'] ?? '',
            'tax'      => $_REQUEST['tax'] ?? '',
            'term'     => $_REQUEST['term'] ?? '',
            'customer' => (isset($_REQUEST['customer']) && $_REQUEST['customer'] == '1') ? '1' : '0',
            'vender'   => (isset($_REQUEST['vender']) && $_REQUEST['vender'] == '1') ? '1' : '0',
        ];
    }
}
