<?php
namespace App\Controllers;

use App\Models\TourLocation;

/**
 * TourLocationController — Tour Location CRUD
 * 
 * Routes:
 *   tour_location_list   → index()  — List locations
 *   tour_location_make   → make()   — Create/edit form
 *   tour_location_store  → store()  — POST: save location
 *   tour_location_delete → delete() — POST: soft delete
 */
class TourLocationController extends BaseController
{
    private TourLocation $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new TourLocation();
    }

    private function guardModule(): bool
    {
        if (!isModuleEnabled($this->getCompanyId(), 'tour_operator')) {
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * List locations
     */
    public function index(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $filters = [
            'search'        => $this->inputStr('search'),
            'location_type' => $this->inputStr('location_type'),
        ];

        $locations = $this->model->getLocations($comId, $filters);
        $stats = $this->model->getStats($comId);
        $message = $_GET['msg'] ?? '';

        $this->render('tour-location/list', compact('locations', 'stats', 'filters', 'message'));
    }

    /**
     * Create / Edit form
     */
    public function make(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');
        $location = null;

        if ($id > 0) {
            $location = $this->model->findLocation($id, $comId);
            if (!$location) {
                $this->redirect('tour_location_list', ['msg' => 'not_found']);
                return;
            }
        }

        $message = $_GET['msg'] ?? '';
        $this->render('tour-location/make', compact('location', 'message'));
    }

    /**
     * POST: Save location (create or update)
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_location_list');
            return;
        }
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $id = intval($_POST['id'] ?? 0);

        $data = [
            'company_id'    => $comId,
            'name'          => trim($_POST['name'] ?? ''),
            'location_type' => $_POST['location_type'] ?? 'pickup',
            'address'       => trim($_POST['address'] ?? ''),
            'notes'         => trim($_POST['notes'] ?? ''),
        ];

        if (empty($data['name'])) {
            $this->redirect('tour_location_make', ['msg' => 'error']);
            return;
        }

        if ($id > 0) {
            $this->model->updateLocation($id, $data, $comId);
            $msg = 'updated';
        } else {
            $this->model->createLocation($data);
            $msg = 'created';
        }

        $this->redirect('tour_location_list', ['msg' => $msg]);
    }

    /**
     * POST: Soft delete location
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_location_list');
            return;
        }
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $id = intval($_POST['id'] ?? 0);
        $comId = $this->getCompanyId();

        if ($id > 0) {
            $this->model->deleteLocation($id, $comId);
        }

        $this->redirect('tour_location_list', ['msg' => 'deleted']);
    }
}
