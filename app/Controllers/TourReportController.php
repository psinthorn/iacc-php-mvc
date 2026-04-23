<?php
namespace App\Controllers;

use App\Models\TourReport;

class TourReportController extends BaseController
{
    private TourReport $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new TourReport();
    }

    private function guardModule(): void
    {
        if (!isModuleEnabled($this->user['com_id'], 'tour_operator')) {
            $this->redirect('main');
        }
    }

    // ─── Filter Page ───────────────────────────────────────────

    public function index(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];

        $activities = $this->reportModel->getTourActivities($comId);

        $this->render('tour-report/index', [
            'activities' => $activities,
        ]);
    }

    // ─── Check-in List PDF ─────────────────────────────────────

    public function checkinPrint(): void
    {
        $this->guardModule();

        include __DIR__ . '/../Views/tour-report/checkin-print.php';
        exit;
    }

    // ─── Pickup Report PDF ─────────────────────────────────────

    public function pickupPrint(): void
    {
        $this->guardModule();

        include __DIR__ . '/../Views/tour-report/pickup-print.php';
        exit;
    }

    // ─── Passenger Accident Insurance PDF ─────────────────────

    public function insurancePrint(): void
    {
        $this->guardModule();

        include __DIR__ . '/../Views/tour-report/insurance-print.php';
        exit;
    }
}
