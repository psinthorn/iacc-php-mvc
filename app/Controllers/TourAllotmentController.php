<?php
namespace App\Controllers;

use App\Models\TourAllotment;
use App\Models\TourBooking;

class TourAllotmentController extends BaseController
{
    private TourAllotment $allotmentModel;
    private TourBooking $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->allotmentModel = new TourAllotment();
        $this->bookingModel = new TourBooking();
    }

    private function guardModule(): void
    {
        if (!isModuleEnabled($this->user['com_id'], 'tour_operator')) {
            $this->redirect('main');
        }
    }

    // ─── Allotment Calendar (Monthly View) ────────────────────

    public function index(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];

        $month = intval($_GET['month'] ?? date('n'));
        $year  = intval($_GET['year'] ?? date('Y'));
        if ($month < 1 || $month > 12) $month = intval(date('n'));
        if ($year < 2020 || $year > 2030) $year = intval(date('Y'));

        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));

        $allotments = $this->allotmentModel->getAllotmentsByDateRange($comId, $from, $to);
        $fleets = $this->allotmentModel->getFleets($comId);

        // Fetch confirmed bookings grouped by date for inline display
        $bookingsByDate = $this->getBookingsByDateRange($comId, $from, $to);

        $this->render('tour-booking/allotments', [
            'allotments'     => $allotments,
            'fleets'         => $fleets,
            'bookingsByDate' => $bookingsByDate,
            'month'          => $month,
            'year'           => $year,
            'from'           => $from,
            'to'             => $to,
        ]);
    }

    // ─── Date Detail ──────────────────────────────────────────

    public function dateDetail(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];
        $date  = trim($_GET['date'] ?? '');

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->redirect('tour_allotment_list');
            return;
        }

        $summary    = $this->allotmentModel->getAllotmentByDate($comId, $date);
        $details    = $this->allotmentModel->getAllotmentDetailByDate($comId, $date);
        $bookings   = $this->allotmentModel->getConfirmedBookingsForDate($comId, $date);

        // Get audit logs for all allotment rows on this date
        $logs = [];
        foreach ($details as $d) {
            $logs = array_merge($logs, $this->allotmentModel->getAuditLog(intval($d['id'])));
        }
        // Sort by created_at desc
        usort($logs, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        $msg = trim($_GET['msg'] ?? '');

        $this->render('tour-booking/allotment-date', [
            'date'     => $date,
            'summary'  => $summary,
            'details'  => $details,
            'bookings' => $bookings,
            'logs'     => $logs,
            'msg'      => $msg,
        ]);
    }

    // ─── Manual Set Capacity ──────────────────────────────────

    public function manualSet(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $date      = trim($_POST['travel_date'] ?? '');
        $newTotal  = intval($_POST['total_seats'] ?? 0);

        if ($newTotal < 0 || !$date) {
            $this->redirect('tour_allotment_list', ['msg' => 'invalid']);
            return;
        }

        // Ensure allotment exists
        $allotment = $this->allotmentModel->getOrCreateAllotment($comId, $date);
        if (!$allotment) {
            $this->redirect('tour_allotment_list', ['msg' => 'no_fleet']);
            return;
        }

        $this->allotmentModel->manualSetCapacity(intval($allotment['id']), $newTotal, $this->user['id']);
        $this->redirect('tour_allotment_date', ['date' => $date, 'msg' => 'capacity_set']);
    }

    // ─── Close Date ───────────────────────────────────────────

    public function closeDate(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId  = $this->user['com_id'];
        $date   = trim($_POST['travel_date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        $allotment = $this->allotmentModel->getOrCreateAllotment($comId, $date);
        if (!$allotment) {
            $this->redirect('tour_allotment_list', ['msg' => 'no_fleet']);
            return;
        }

        $this->allotmentModel->closeDate(intval($allotment['id']), $reason, $this->user['id']);
        $this->redirect('tour_allotment_date', ['date' => $date, 'msg' => 'date_closed']);
    }

    // ─── Reopen Date ──────────────────────────────────────────

    public function reopenDate(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId = $this->user['com_id'];
        $date  = trim($_POST['travel_date'] ?? '');

        $allotment = $this->allotmentModel->getOrCreateAllotment($comId, $date);
        if (!$allotment) {
            $this->redirect('tour_allotment_list', ['msg' => 'no_fleet']);
            return;
        }

        $this->allotmentModel->reopenDate(intval($allotment['id']), $this->user['id']);
        $this->redirect('tour_allotment_date', ['date' => $date, 'msg' => 'date_reopened']);
    }

    // ─── Recalculate ──────────────────────────────────────────

    public function recalculate(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId = $this->user['com_id'];
        $date  = trim($_POST['travel_date'] ?? '');

        $this->allotmentModel->recalculateBooked($comId, $date);
        $this->redirect('tour_allotment_date', ['date' => $date, 'msg' => 'recalculated']);
    }

    // ─── AJAX: Availability check (standalone) ────────────────

    public function apiAvailability(): void
    {
        $comId = intval($_SESSION['com_id'] ?? 0);
        $date  = trim($_GET['date'] ?? '');

        if (!$comId || !$date) {
            $this->json(['error' => 'Missing params'], 400);
            return;
        }

        $data = $this->allotmentModel->getAllotmentByDate($comId, $date);

        if (!$data) {
            // No allotment yet — check if fleet exists to show default capacity
            $fleet = $this->allotmentModel->getDefaultFleet($comId);
            if ($fleet) {
                $total = intval($fleet['capacity']) * intval($fleet['unit_count']);
                $this->json([
                    'total_seats'   => $total,
                    'booked_seats'  => 0,
                    'available'     => $total,
                    'is_closed'     => false,
                    'is_overbooked' => false,
                    'has_allotment' => false,
                ]);
            } else {
                $this->json(['has_allotment' => false, 'no_fleet' => true]);
            }
            return;
        }

        $data['has_allotment'] = true;
        $this->json($data);
    }

    // ─── Helper: bookings grouped by date ────────────────────

    private function getBookingsByDateRange(int $comId, string $from, string $to): array
    {
        // 1. Bookings with customer name (ordered by latest first)
        $sql = sprintf(
            "SELECT b.id, b.booking_number, b.travel_date, b.status,
                    b.pax_adult, b.pax_child, b.pax_infant,
                    (b.pax_adult + b.pax_child) AS seat_pax,
                    COALESCE(c.name_en, c.name_th, 'Walk-in') AS customer_name,
                    b.created_at
             FROM tour_bookings b
             LEFT JOIN company c ON b.customer_id = c.id
             WHERE b.company_id = %d
               AND b.travel_date BETWEEN '%s' AND '%s'
               AND b.deleted_at IS NULL
             ORDER BY b.travel_date ASC, b.created_at DESC",
            intval($comId), sql_escape($from), sql_escape($to)
        );
        $result = mysqli_query($this->allotmentModel->getConnection(), $sql);
        $bookings = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $bookings[$row['travel_date']][] = $row;
        }

        // 2. Model/product breakdown per date (pax grouped by model)
        // Use type.name + model description for readable labels
        $sql2 = sprintf(
            "SELECT b.travel_date,
                    COALESCE(
                        NULLIF(m.des, ''),
                        NULLIF(m.model_name, ''),
                        NULLIF(bi.description, ''),
                        'Trip'
                    ) AS model_name,
                    SUM(b.pax_adult + b.pax_child) AS model_pax
             FROM tour_bookings b
             JOIN tour_booking_items bi ON bi.booking_id = b.id
             LEFT JOIN model m ON bi.model_id = m.id
             LEFT JOIN type t ON m.type_id = t.id
             WHERE b.company_id = %d
               AND b.travel_date BETWEEN '%s' AND '%s'
               AND b.status IN ('confirmed', 'completed')
               AND b.deleted_at IS NULL
             GROUP BY b.travel_date, model_name
             ORDER BY b.travel_date ASC, model_pax DESC",
            intval($comId), sql_escape($from), sql_escape($to)
        );
        $result2 = mysqli_query($this->allotmentModel->getConnection(), $sql2);
        $models = [];
        while ($result2 && $row = mysqli_fetch_assoc($result2)) {
            $models[$row['travel_date']][] = $row;
        }

        return ['bookings' => $bookings, 'models' => $models];
    }

    // ─── Fleet CRUD ───────────────────────────────────────────

    public function fleetIndex(): void
    {
        $this->guardModule();
        $comId  = $this->user['com_id'];
        $fleets = $this->allotmentModel->getFleets($comId);
        $msg    = trim($_GET['msg'] ?? '');

        $this->render('tour-booking/fleets', [
            'fleets' => $fleets,
            'msg'    => $msg,
        ]);
    }

    public function fleetMake(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];
        $id    = intval($_GET['id'] ?? 0);

        $fleet = $id ? $this->allotmentModel->findFleet($id, $comId) : null;

        $this->render('tour-booking/fleets', [
            'fleets'      => $this->allotmentModel->getFleets($comId),
            'editFleet'   => $fleet,
            'msg'         => '',
        ]);
    }

    public function fleetStore(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId = $this->user['com_id'];
        $id    = intval($_POST['id'] ?? 0);

        $data = [
            'company_id' => $comId,
            'fleet_name' => trim($_POST['fleet_name'] ?? ''),
            'fleet_type' => trim($_POST['fleet_type'] ?? 'speedboat'),
            'capacity'   => intval($_POST['capacity'] ?? 38),
            'unit_count' => intval($_POST['unit_count'] ?? 1),
            'is_active'  => intval($_POST['is_active'] ?? 1),
            'notes'      => trim($_POST['notes'] ?? ''),
        ];

        if (empty($data['fleet_name'])) {
            $this->redirect('tour_fleet_list', ['msg' => 'name_required']);
            return;
        }

        if ($id) {
            $this->allotmentModel->updateFleet($id, $data, $comId);
            $msg = 'fleet_updated';
        } else {
            $this->allotmentModel->createFleet($data);
            $msg = 'fleet_created';
        }

        $this->redirect('tour_fleet_list', ['msg' => $msg]);
    }

    public function fleetDelete(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId = $this->user['com_id'];
        $id    = intval($_POST['id'] ?? 0);

        $this->allotmentModel->deleteFleet($id, $comId);
        $this->redirect('tour_fleet_list', ['msg' => 'fleet_deleted']);
    }
}
