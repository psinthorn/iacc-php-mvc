<?php
namespace App\Controllers;

use App\Models\TourBooking;
use App\Models\TourBookingPayment;
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
        $types     = $this->bookingModel->getProductTypes($comId);
        $models    = $this->bookingModel->getProductModels($comId);

        // Group models by type_id for JS cascade
        $modelsByType = [];
        foreach ($models as $m) {
            $tid = $m['type_id'];
            if (!isset($modelsByType[$tid])) $modelsByType[$tid] = [];
            $modelsByType[$tid][] = $m;
        }

        $this->render('tour-booking/make', [
            'booking'        => $booking,
            'agents'         => $agents,
            'customers'      => $customers,
            'locations'      => $locations,
            'types'          => $types,
            'models_by_type' => $modelsByType,
            'message'        => $_GET['msg'] ?? '',
        ]);
    }

    // ─── View Detail ───────────────────────────────────────────

    public function view(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];
        $paymentModel = new TourBookingPayment();

        $id = intval($_GET['id'] ?? 0);
        $booking = $this->bookingModel->findBooking($id, $comId);

        if (!$booking) {
            $this->redirect('tour_booking_list', ['msg' => 'not_found']);
        }

        $paymentSummary = $paymentModel->getBookingPaymentSummary(intval($booking['id']), $comId);

        $this->render('tour-booking/view', [
            'booking'        => $booking,
            'paymentSummary' => $paymentSummary,
            'message'        => $_GET['msg'] ?? '',
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
        $bookingDate = trim($_POST['booking_date'] ?? date('Y-m-d'));
        if (empty($travelDate)) {
            $this->redirect('tour_booking_make', ['msg' => 'missing_date']);
            return;
        }

        // Build data array
        $data = [
            'company_id'         => $comId,
            'customer_id'        => intval($_POST['customer_id'] ?? 0),
            'agent_id'           => intval($_POST['agent_id'] ?? 0),
            'sales_rep_id'       => intval($_POST['sales_rep_id'] ?? 0),
            'booking_by'         => trim($_POST['booking_by'] ?? ''),
            'booking_date'       => $bookingDate,
            'travel_date'        => $travelDate,
            'pax_adult'          => intval($_POST['pax_adult'] ?? 0),
            'pax_child'          => intval($_POST['pax_child'] ?? 0),
            'pax_infant'         => intval($_POST['pax_infant'] ?? 0),
            'pickup_location_id' => intval($_POST['pickup_location_id'] ?? 0),
            'pickup_hotel'       => trim($_POST['pickup_hotel'] ?? ''),
            'pickup_room'        => trim($_POST['pickup_room'] ?? ''),
            'pickup_time'        => trim($_POST['pickup_time'] ?? ''),
            'driver_name'        => trim($_POST['driver_name'] ?? ''),
            'vehicle_no'         => trim($_POST['vehicle_no'] ?? ''),
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

                // Parse pax lines from JSON
                $paxLinesJson = $_POST['item_pax_lines'][$i] ?? '[]';
                $paxLines = json_decode($paxLinesJson, true) ?: [];

                $priceThai = 0;
                $priceForeign = 0;
                $qtyThai = 0;
                $qtyForeigner = 0;
                $amount = floatval($_POST['item_amount'][$i] ?? 0);

                foreach ($paxLines as $pl) {
                    $qty = intval($pl['qty'] ?? 0);
                    $price = floatval($pl['price'] ?? 0);
                    $nat = $pl['nat'] ?? 'thai';
                    if ($nat === 'thai') {
                        $qtyThai += $qty;
                        if ($price > $priceThai) $priceThai = $price;
                    } else {
                        $qtyForeigner += $qty;
                        if ($price > $priceForeign) $priceForeign = $price;
                    }
                }

                $items[] = [
                    'item_type'        => $type,
                    'description'      => $desc,
                    'contract_rate_id' => intval($_POST['item_contract_rate_id'][$i] ?? 0),
                    'rate_label'       => trim($_POST['item_rate_label'][$i] ?? ''),
                    'price_thai'       => $priceThai,
                    'price_foreigner'  => $priceForeign,
                    'qty_thai'         => $qtyThai,
                    'qty_foreigner'    => $qtyForeigner,
                    'notes'            => trim($_POST['item_notes'][$i] ?? ''),
                    'product_type_id'  => intval($_POST['item_product_type_id'][$i] ?? 0),
                    'model_id'         => intval($_POST['item_model_id'][$i] ?? 0),
                    'pax_lines_json'   => $paxLinesJson,
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

            // Save items
            $this->bookingModel->saveBookingItems($bookingId, $items);

            // Save per-booking contact info (module-isolated)
            $contactData = [
                'contact_name'       => trim($_POST['contact_name'] ?? ''),
                'mobile'             => trim($_POST['contact_mobile'] ?? ''),
                'email'              => trim($_POST['contact_email'] ?? ''),
                'gender'             => trim($_POST['contact_gender'] ?? ''),
                'nationality'        => trim($_POST['contact_nationality'] ?? ''),
                'contact_messengers' => trim($_POST['contact_messengers'] ?? ''),
            ];
            $this->bookingModel->saveBookingContact($bookingId, $contactData);

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

    // ─── Customer Search (AJAX) ────────────────────────────────

    public function customerSearch(): void
    {
        $this->guardModule();

        $comId = $this->user['com_id'];
        $term  = trim($_GET['q'] ?? '');

        $results = [];
        if (strlen($term) >= 1) {
            $results = $this->bookingModel->searchCustomers($comId, $term);
        }

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    // ─── Customer Quick Create (AJAX POST) ─────────────────────

    public function customerCreate(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId      = $this->user['com_id'];
        $name       = trim($_POST['name'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $messengers = trim($_POST['messengers'] ?? '');

        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Name is required']);
            exit;
        }

        $id = $this->bookingModel->quickCreateCustomer($comId, $name, $phone, $messengers);

        header('Content-Type: application/json');
        echo json_encode([
            'success'    => $id > 0,
            'id'         => $id,
            'name'       => $name,
            'phone'      => $phone,
            'messengers' => $messengers,
        ]);
        exit;
    }

    // ─── Product Search (AJAX) ─────────────────────────────────

    public function productSearch(): void
    {
        $this->guardModule();

        $comId = $this->user['com_id'];
        $term  = trim($_GET['q'] ?? '');

        $results = [];
        if (strlen($term) >= 1) {
            $results = $this->bookingModel->searchProducts($comId, $term);
        }

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    // ─── Staff Search for Booking By (AJAX) ────────────────────

    public function staffSearch(): void
    {
        $this->guardModule();

        $comId = $this->user['com_id'];
        $term  = trim($_GET['q'] ?? '');

        $results = [];
        if (strlen($term) >= 1) {
            $results = $this->bookingModel->searchStaff($comId, $term);
        }

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    // ─── Agent Search (AJAX) ───────────────────────────────────

    public function agentSearch(): void
    {
        $this->guardModule();

        $comId = $this->user['com_id'];
        $term  = trim($_GET['q'] ?? '');

        $results = [];
        if (strlen($term) >= 1) {
            $results = $this->bookingModel->searchAgents($comId, $term);
        }

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    // ─── Sales Rep Search (AJAX) ───────────────────────────────

    public function salesRepSearch(): void
    {
        $this->guardModule();

        $comId = $this->user['com_id'];
        $term  = trim($_GET['q'] ?? '');

        $results = [];
        if (strlen($term) >= 1) {
            $results = $this->bookingModel->searchAgents($comId, $term);
        }

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    // ─── Sales Rep Quick Create (AJAX POST) ───────────────────

    public function salesRepCreate(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId      = $this->user['com_id'];
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $messengers = trim($_POST['messengers'] ?? '');

        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Name is required']);
            exit;
        }

        $id = $this->bookingModel->quickCreateAgent($comId, $name, $email, $phone, $messengers);

        header('Content-Type: application/json');
        echo json_encode([
            'success'    => $id > 0,
            'id'         => $id,
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'messengers' => $messengers,
        ]);
        exit;
    }
}
