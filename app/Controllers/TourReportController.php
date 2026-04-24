<?php
namespace App\Controllers;

use App\Models\TourReport;
use App\Models\TourBooking;

class TourReportController extends BaseController
{
    private TourReport $reportModel;
    private TourBooking $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel  = new TourReport();
        $this->bookingModel = new TourBooking();
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

        // KPI defaults: current month
        $kpiFrom = $_GET['kpi_from'] ?? date('Y-m-01');
        $kpiTo   = $_GET['kpi_to']   ?? date('Y-m-d');
        $kpi     = $this->bookingModel->getKpiByRange($comId, $kpiFrom, $kpiTo);

        $this->render('tour-report/index', [
            'activities' => $activities,
            'kpi'        => $kpi,
            'kpi_from'   => $kpiFrom,
            'kpi_to'     => $kpiTo,
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
