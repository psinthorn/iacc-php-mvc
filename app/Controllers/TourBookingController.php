<?php
namespace App\Controllers;

use App\Models\TourBooking;
use App\Models\TourBookingPayment;
use App\Models\TourAllotment;
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

        // Allotment dashboard: upcoming 7 days
        $allotmentModel = new TourAllotment();
        $allotmentDays  = $allotmentModel->getUpcomingAllotmentSummary($comId, 7);

        $this->render('tour-booking/list', [
            'bookings'      => $bookings,
            'stats'         => $stats,
            'agents'        => $agents,
            'filters'       => $filters,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalCount'    => $totalCount,
            'message'       => $_GET['msg'] ?? '',
            'allotmentDays' => $allotmentDays,
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

            // ── Allotment Integration ──────────────────────────
            $allotmentModel = new TourAllotment();
            $newStatus = $data['status'];
            $seatPax   = intval($data['pax_adult']) + intval($data['pax_child']); // infants don't take seats
            $allotmentWarning = '';

            if ($isEdit) {
                $oldStatus = $existing['status'] ?? 'draft';
                $oldDate   = $existing['travel_date'] ?? '';

                // Handle travel date change while confirmed
                if ($oldDate !== $travelDate && in_array($oldStatus, ['confirmed', 'completed'])) {
                    $oldPax = intval($existing['pax_adult']) + intval($existing['pax_child']);
                    $dateResult = $allotmentModel->handleDateChange(
                        $comId, $bookingId, $oldDate, $travelDate, $newStatus, $seatPax, $this->user['id']
                    );
                    if ($dateResult['is_overbooked']) {
                        $allotmentWarning = 'overbooking';
                    }
                    if ($dateResult['is_closed']) {
                        $allotmentWarning = 'date_closed';
                    }
                } else {
                    // Handle status change
                    $statusResult = $allotmentModel->handleStatusChange(
                        $comId, $bookingId, $travelDate, $oldStatus, $newStatus, $seatPax, $this->user['id']
                    );
                    if ($statusResult['is_overbooked']) {
                        $allotmentWarning = 'overbooking';
                    }
                    if ($statusResult['is_closed'] && in_array($newStatus, ['confirmed', 'completed'])) {
                        $allotmentWarning = 'date_closed';
                    }
                }

                // Handle pax change while confirmed (release old, book new)
                if ($oldStatus === $newStatus && in_array($newStatus, ['confirmed', 'completed']) && $oldDate === $travelDate) {
                    $oldPax = intval($existing['pax_adult']) + intval($existing['pax_child']);
                    if ($oldPax !== $seatPax) {
                        $allotment = $allotmentModel->getOrCreateAllotment($comId, $travelDate);
                        if ($allotment) {
                            $allotmentModel->releaseSeats(intval($allotment['id']), $bookingId, $oldPax, $this->user['id']);
                            $bookResult = $allotmentModel->bookSeats(intval($allotment['id']), $bookingId, $seatPax, $this->user['id']);
                            if ($bookResult['is_overbooked']) {
                                $allotmentWarning = 'overbooking';
                            }
                        }
                    }
                }
            } else {
                // New booking: if created directly as confirmed, book seats
                if (in_array($newStatus, ['confirmed', 'completed'])) {
                    $statusResult = $allotmentModel->handleStatusChange(
                        $comId, $bookingId, $travelDate, null, $newStatus, $seatPax, $this->user['id']
                    );
                    if ($statusResult['is_overbooked']) {
                        $allotmentWarning = 'overbooking';
                    }
                    if ($statusResult['is_closed']) {
                        $allotmentWarning = 'date_closed';
                    }
                }
            }

            if ($allotmentWarning) {
                $_SESSION['allotment_warning'] = $allotmentWarning;
            }
            // ── End Allotment Integration ──────────────────────

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

        // Release allotment seats if booking was confirmed
        if ($id > 0) {
            $existing = $this->bookingModel->findBooking($id, $comId);
            if ($existing && in_array($existing['status'], ['confirmed', 'completed'])) {
                $allotmentModel = new TourAllotment();
                $seatPax = intval($existing['pax_adult']) + intval($existing['pax_child']);
                $allotmentModel->handleStatusChange(
                    $comId, $id, $existing['travel_date'],
                    $existing['status'], 'cancelled', $seatPax, $this->user['id']
                );
            }
        }

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

        $id    = intval($_GET['id'] ?? 0);
        $comId = $this->user['com_id'];

        if ($id <= 0) {
            $this->redirect('tour_booking_list');
            return;
        }

        $booking = $this->bookingModel->findBooking($id, $comId);
        if (!$booking) {
            $this->redirect('tour_booking_list', ['msg' => 'not_found']);
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

    // ─── CSV Import ───────────────────────────────────────────────

    /**
     * GET: Upload form + downloadable template
     * POST: Parse CSV, validate rows, store preview in session, redirect to preview
     */
    public function csvImport(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            // Download template
            if (($_POST['action'] ?? '') === 'download_template') {
                $this->downloadCsvTemplate();
            }

            // Upload + parse
            $file = $_FILES['csv_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $error = 'No file uploaded or upload error.';
                $this->render('tour-booking/csv-import', compact('error'));
                return;
            }
            if (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
                $error = 'File must be a .csv file.';
                $this->render('tour-booking/csv-import', compact('error'));
                return;
            }

            $rows   = $this->parseCsv($file['tmp_name']);
            $parsed = $this->validateRows($rows, $comId);

            // Store in session for preview
            $_SESSION['csv_import_preview'] = $parsed;
            $_SESSION['csv_import_comid']   = $comId;

            header('Location: index.php?page=tour_booking_csv_preview');
            exit;
        }

        $this->render('tour-booking/csv-import', []);
    }

    /**
     * GET: Show preview table (valid + invalid rows)
     * POST: Confirm — insert valid rows
     */
    public function csvPreview(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId  = intval($_SESSION['csv_import_comid'] ?? 0);
        $parsed = $_SESSION['csv_import_preview']   ?? null;

        if (!$parsed || $comId !== $this->user['com_id']) {
            header('Location: index.php?page=tour_booking_csv_import');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
            $result = $this->importRows($parsed['valid'], $comId);
            unset($_SESSION['csv_import_preview'], $_SESSION['csv_import_comid']);
            $this->render('tour-booking/csv-import-done', compact('result'));
            return;
        }

        $this->render('tour-booking/csv-preview', compact('parsed'));
    }

    /**
     * GET standalone: Stream a template CSV file.
     */
    public function csvTemplate(): void
    {
        $this->downloadCsvTemplate();
    }

    // ─── CSV helpers ───────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($fh = fopen($path, 'r')) === false) return $rows;

        // Detect and strip BOM
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fh);

        $headers = null;
        while (($line = fgetcsv($fh, 2000, ',')) !== false) {
            if ($headers === null) {
                $headers = array_map(fn($h) => strtolower(trim($h)), $line);
                continue;
            }
            if (count($line) < 2) continue;
            $rows[] = array_combine($headers, array_pad($line, count($headers), ''));
        }
        fclose($fh);
        return $rows;
    }

    private function validateRows(array $rows, int $comId): array
    {
        $valid   = [];
        $invalid = [];

        // Build name→id lookup maps for customers + agents
        $customers = $this->buildNameIdMap($comId, 'customer');
        $agents    = $this->buildNameIdMap($comId, 'agent');

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // 1-indexed + header row
            $errs   = [];

            $travelDate = trim($row['travel_date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $travelDate)) {
                // Try d/m/Y and d-m-Y
                $parsed = date_create_from_format('d/m/Y', $travelDate)
                       ?: date_create_from_format('d-m-Y', $travelDate);
                $travelDate = $parsed ? $parsed->format('Y-m-d') : '';
            }
            if (empty($travelDate)) $errs[] = 'travel_date missing or invalid (use YYYY-MM-DD)';

            $amount = floatval(str_replace(',', '', $row['total_amount'] ?? '0'));
            if ($amount < 0) $errs[] = 'total_amount must be >= 0';

            $paxAdult  = max(0, intval($row['pax_adult']  ?? $row['pax'] ?? 1));
            $paxChild  = max(0, intval($row['pax_child']  ?? 0));
            $paxInfant = max(0, intval($row['pax_infant'] ?? 0));
            if ($paxAdult + $paxChild + $paxInfant < 1) $errs[] = 'pax must be >= 1';

            $status = trim(strtolower($row['status'] ?? 'draft'));
            if (!in_array($status, ['draft', 'confirmed', 'completed', 'cancelled'], true)) {
                $status = 'draft';
            }

            $customerName = trim($row['customer'] ?? $row['customer_name'] ?? '');
            $agentName    = trim($row['agent']    ?? $row['agent_name']    ?? '');
            $customerId   = $customers[strtolower($customerName)] ?? 0;
            $agentId      = $agents[strtolower($agentName)]       ?? 0;

            $clean = [
                'row_num'      => $rowNum,
                'booking_date' => trim($row['booking_date'] ?? date('Y-m-d')),
                'travel_date'  => $travelDate,
                'booking_by'   => trim($row['booking_by'] ?? $row['lead_name'] ?? ''),
                'customer_id'  => $customerId,
                'customer_name'=> $customerName,
                'agent_id'     => $agentId,
                'agent_name'   => $agentName,
                'pax_adult'    => $paxAdult,
                'pax_child'    => $paxChild,
                'pax_infant'   => $paxInfant,
                'total_amount' => $amount,
                'currency'     => strtoupper(trim($row['currency'] ?? 'THB')) ?: 'THB',
                'status'       => $status,
                'remark'       => trim($row['remark'] ?? $row['notes'] ?? ''),
                'pickup_hotel' => trim($row['pickup_hotel'] ?? ''),
                'pickup_time'  => trim($row['pickup_time']  ?? ''),
            ];

            if (!empty($errs)) {
                $invalid[] = array_merge($clean, ['errors' => $errs]);
            } else {
                $valid[] = $clean;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    private function buildNameIdMap(int $comId, string $type): array
    {
        $map   = [];
        $types = $type === 'customer' ? "'customer','individual'" : "'agent','supplier'";
        $res   = mysqli_query($this->conn,
            "SELECT id, LOWER(TRIM(COALESCE(name_en, name_th, ''))) AS name
             FROM company WHERE company_id = $comId AND company_type IN ($types) AND deleted_at IS NULL"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            if ($row['name'] !== '') $map[$row['name']] = (int)$row['id'];
        }
        return $map;
    }

    private function importRows(array $valid, int $comId): array
    {
        $inserted = 0;
        $failed   = 0;
        $errors   = [];
        $userId   = intval($this->user['id']);

        foreach ($valid as $row) {
            $bookingNum = $this->bookingModel->generateBookingNumber($comId);
            $bookingDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['booking_date'])
                ? $row['booking_date'] : date('Y-m-d');

            $id = $this->bookingModel->createBooking([
                'company_id'    => $comId,
                'booking_number'=> $bookingNum,
                'booking_date'  => $bookingDate,
                'travel_date'   => $row['travel_date'],
                'booking_by'    => $row['booking_by'],
                'customer_id'   => $row['customer_id'],
                'agent_id'      => $row['agent_id'],
                'pax_adult'     => $row['pax_adult'],
                'pax_child'     => $row['pax_child'],
                'pax_infant'    => $row['pax_infant'],
                'pickup_hotel'  => $row['pickup_hotel'],
                'pickup_time'   => $row['pickup_time'],
                'total_amount'  => $row['total_amount'],
                'currency'      => $row['currency'],
                'status'        => $row['status'],
                'remark'        => $row['remark'],
                'created_by'    => $userId,
            ]);

            if ($id > 0) {
                $inserted++;
            } else {
                $failed++;
                $errors[] = "Row {$row['row_num']}: DB insert failed";
            }
        }

        return compact('inserted', 'failed', 'errors');
    }

    private function downloadCsvTemplate(): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="tour_bookings_import_template.csv"');
        header('Cache-Control: no-cache');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'travel_date','booking_date','booking_by',
            'pax_adult','pax_child','pax_infant',
            'total_amount','currency','status',
            'customer','agent','pickup_hotel','pickup_time','remark',
        ]);
        fputcsv($out, [
            '2026-05-01','2026-04-25','John Smith',
            '2','1','0',
            '5500.00','THB','confirmed',
            '','','Amari Hotel','07:30','Airport transfer included',
        ]);
        fclose($out);
        exit;
    }
}
