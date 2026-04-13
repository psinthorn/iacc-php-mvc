<?php
namespace App\Controllers;

use App\Models\TourBooking;
use App\Services\TourBookingService;

class TourBookingController extends BaseController
{
    private TourBooking $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new TourBooking();
    }

    private function guardModule(): void
    {
        if (!isModuleEnabled($this->user['com_id'], 'tour_operator')) {
            $this->redirect('main');
        }
    }

    // ─── List ──────────────────────────────────────────────────

    public function index(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];

        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'status'    => trim($_GET['status'] ?? ''),
            'agent_id'  => intval($_GET['agent_id'] ?? 0),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];

        $page = max(1, intval($_GET['p'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $bookings   = $this->bookingModel->getBookings($comId, $filters, $offset, $limit);
        $totalCount = $this->bookingModel->countBookings($comId, $filters);
        $totalPages = max(1, ceil($totalCount / $limit));
        $stats      = $this->bookingModel->getStats($comId);
        $agents     = $this->bookingModel->getAgentDropdown($comId);

        $this->render('tour-booking/list', [
            'bookings'   => $bookings,
            'stats'      => $stats,
            'agents'     => $agents,
            'filters'    => $filters,
            'page'       => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'message'    => $_GET['msg'] ?? '',
        ]);
    }

    // ─── Create / Edit Form ────────────────────────────────────

    public function make(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];

        $booking = null;
        if (!empty($_GET['id'])) {
            $booking = $this->bookingModel->findBooking(intval($_GET['id']), $comId);
            if (!$booking) {
                $this->redirect('tour_booking_list', ['msg' => 'not_found']);
            }
        }

        $agents    = $this->bookingModel->getAgentDropdown($comId);
        $customers = $this->bookingModel->getCustomerDropdown($comId);
        $locations = $this->bookingModel->getPickupLocations($comId);

        $this->render('tour-booking/make', [
            'booking'   => $booking,
            'agents'    => $agents,
            'customers' => $customers,
            'locations' => $locations,
            'message'   => $_GET['msg'] ?? '',
        ]);
    }

    // ─── View Detail ───────────────────────────────────────────

    public function view(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];

        $id = intval($_GET['id'] ?? 0);
        $booking = $this->bookingModel->findBooking($id, $comId);

        if (!$booking) {
            $this->redirect('tour_booking_list', ['msg' => 'not_found']);
        }

        $this->render('tour-booking/view', [
            'booking' => $booking,
            'message' => $_GET['msg'] ?? '',
        ]);
    }

    // ─── Store (POST) ──────────────────────────────────────────

    public function store(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId  = $this->user['com_id'];
        $isEdit = !empty($_POST['id']);

        // Validate required fields
        $travelDate = trim($_POST['travel_date'] ?? '');
        if (empty($travelDate)) {
            $this->redirect('tour_booking_make', ['msg' => 'missing_date']);
            return;
        }

        // Build data array
        $data = [
            'company_id'         => $comId,
            'customer_id'        => intval($_POST['customer_id'] ?? 0),
            'agent_id'           => intval($_POST['agent_id'] ?? 0),
            'booking_by'         => trim($_POST['booking_by'] ?? ''),
            'travel_date'        => $travelDate,
            'pax_adult'          => intval($_POST['pax_adult'] ?? 0),
            'pax_child'          => intval($_POST['pax_child'] ?? 0),
            'pax_infant'         => intval($_POST['pax_infant'] ?? 0),
            'pickup_location_id' => intval($_POST['pickup_location_id'] ?? 0),
            'pickup_hotel'       => trim($_POST['pickup_hotel'] ?? ''),
            'pickup_room'        => trim($_POST['pickup_room'] ?? ''),
            'pickup_time'        => trim($_POST['pickup_time'] ?? ''),
            'voucher_number'     => trim($_POST['voucher_number'] ?? ''),
            'entrance_fee'       => floatval($_POST['entrance_fee'] ?? 0),
            'subtotal'           => floatval($_POST['subtotal'] ?? 0),
            'discount'           => floatval($_POST['discount'] ?? 0),
            'vat'                => floatval($_POST['vat'] ?? 0),
            'total_amount'       => floatval($_POST['total_amount'] ?? 0),
            'currency'           => trim($_POST['currency'] ?? 'THB'),
            'status'             => trim($_POST['status'] ?? 'draft'),
            'remark'             => trim($_POST['remark'] ?? ''),
            'created_by'         => $this->user['id'],
        ];

        // Parse items from form arrays
        $items = [];
        if (isset($_POST['item_type']) && is_array($_POST['item_type'])) {
            foreach ($_POST['item_type'] as $i => $type) {
                $desc = trim($_POST['item_description'][$i] ?? '');
                if (empty($desc) && empty($type)) continue; // skip empty rows
                $items[] = [
                    'item_type'        => $type,
                    'description'      => $desc,
                    'contract_rate_id' => intval($_POST['item_contract_rate_id'][$i] ?? 0),
                    'rate_label'       => trim($_POST['item_rate_label'][$i] ?? ''),
                    'quantity'         => intval($_POST['item_quantity'][$i] ?? 1),
                    'unit_price'       => floatval($_POST['item_unit_price'][$i] ?? 0),
                    'notes'            => trim($_POST['item_notes'][$i] ?? ''),
                ];
            }
        }

        // Parse passengers from form arrays
        $paxList = [];
        if (isset($_POST['pax_full_name']) && is_array($_POST['pax_full_name'])) {
            foreach ($_POST['pax_full_name'] as $i => $name) {
                $name = trim($name);
                if (empty($name)) continue; // skip empty rows
                $paxList[] = [
                    'pax_type'        => $_POST['pax_type'][$i] ?? 'adult',
                    'full_name'       => $name,
                    'nationality'     => trim($_POST['pax_nationality'][$i] ?? ''),
                    'passport_number' => trim($_POST['pax_passport'][$i] ?? ''),
                    'notes'           => trim($_POST['pax_notes'][$i] ?? ''),
                ];
            }
        }

        // Use transaction for atomicity
        mysqli_begin_transaction($this->bookingModel->getConnection());
        try {
            if ($isEdit) {
                $bookingId = intval($_POST['id']);
                // Verify ownership
                $existing = $this->bookingModel->findBooking($bookingId, $comId);
                if (!$existing) {
                    $this->redirect('tour_booking_list', ['msg' => 'not_found']);
                    return;
                }
                $this->bookingModel->updateBooking($bookingId, $data, $comId);
            } else {
                $data['booking_number'] = $this->bookingModel->generateBookingNumber($comId);
                $bookingId = $this->bookingModel->createBooking($data);
            }

            // Save items and pax
            $this->bookingModel->saveBookingItems($bookingId, $items);
            $this->bookingModel->saveBookingPax($bookingId, $paxList);

            mysqli_commit($this->bookingModel->getConnection());

            $msg = $isEdit ? 'updated' : 'created';
            $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => $msg]);
        } catch (\Exception $e) {
            mysqli_rollback($this->bookingModel->getConnection());
            $this->redirect('tour_booking_make', ['msg' => 'error']);
        }
    }

    // ─── Delete ────────────────────────────────────────────────

    public function delete(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId = $this->user['com_id'];
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0 && $this->bookingModel->deleteBooking($id, $comId)) {
            $this->redirect('tour_booking_list', ['msg' => 'deleted']);
        } else {
            $this->redirect('tour_booking_list', ['msg' => 'not_found']);
        }
    }

    // ─── Generate Documents ───────────────────────────────────

    public function generateDocuments(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId = $this->user['com_id'];
        $id = intval($_POST['id'] ?? 0);

        $booking = $this->bookingModel->findBooking($id, $comId);
        if (!$booking) {
            $this->redirect('tour_booking_list', ['msg' => 'not_found']);
            return;
        }

        $service = new TourBookingService();
        $result = $service->generateDocuments($booking, $comId);

        if ($result['success']) {
            $this->redirect('tour_booking_view', ['id' => $id, 'msg' => 'docs_generated']);
        } else {
            $this->redirect('tour_booking_view', ['id' => $id, 'msg' => 'docs_error']);
        }
    }

    // ─── Print Voucher PDF ─────────────────────────────────────

    public function print(): void
    {
        $this->guardModule();

        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('tour_booking_list');
            return;
        }

        include __DIR__ . '/../Views/tour-booking/print.php';
        exit;
    }

    // ─── Calendar JSON ─────────────────────────────────────────

    public function calendar(): void
    {
        $this->guardModule();

        $comId = $this->user['com_id'];
        $start = trim($_GET['start'] ?? date('Y-m-01'));
        $end   = trim($_GET['end'] ?? date('Y-m-t'));

        $bookings = $this->bookingModel->getCalendarBookings($comId, $start, $end);

        $statusColors = [
            'draft'     => '#94a3b8',
            'confirmed' => '#10b981',
            'completed' => '#3b82f6',
            'cancelled' => '#ef4444',
        ];

        $events = [];
        foreach ($bookings as $b) {
            $events[] = [
                'id'    => $b['id'],
                'title' => $b['booking_number'] . ' - ' . ($b['customer_name'] ?: 'Walk-in'),
                'start' => $b['travel_date'],
                'color' => $statusColors[$b['status']] ?? '#94a3b8',
                'extendedProps' => [
                    'pax'    => $b['total_pax'],
                    'agent'  => $b['agent_name'] ?? '',
                    'status' => $b['status'],
                    'amount' => $b['total_amount'],
                ],
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($events);
        exit;
    }
}
