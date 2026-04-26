<?php
namespace App\Controllers;

use App\Models\TourCheckin;
use App\Models\TourBooking;

/**
 * TourCheckinController
 *
 * Public routes (no session auth):
 *   tour_checkin         GET  — customer check-in landing (token from QR)
 *   tour_checkin_submit  POST — process customer check-in
 *   tour_checkin_done    GET  — success screen
 *
 * Staff routes (session auth required):
 *   tour_checkin_staff          GET  — live staff dashboard
 *   tour_checkin_staff_override POST — mark checked in manually
 *   tour_checkin_reset          POST — reset check-in status
 *   tour_checkin_regen          POST — regenerate token (invalidates old QR)
 */
class TourCheckinController extends BaseController
{
    private TourCheckin $checkinModel;
    private TourBooking $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->checkinModel = new TourCheckin();
        $this->bookingModel = new TourBooking();
    }

    private function guardModule(): void
    {
        if (!isModuleEnabled($this->user['com_id'], 'tour_operator')) {
            $this->redirect('main');
        }
    }

    // ─── Public: Customer Check-In Landing ────────────────────

    /**
     * GET tour_checkin?id={bookingId}&token={token}
     * Shows the mobile check-in page or an error screen.
     */
    public function index(): void
    {
        $bookingId = intval($_GET['id']    ?? 0);
        $token     = trim($_GET['token']   ?? '');

        if (!$bookingId || !$token) {
            $this->renderPublic('tour-checkin/invalid', ['reason' => 'missing']);
            return;
        }

        // Rate limit by IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($this->checkinModel->isRateLimited($ip)) {
            http_response_code(429);
            $this->renderPublic('tour-checkin/invalid', ['reason' => 'rate_limited']);
            return;
        }

        $booking = $this->checkinModel->findByToken($token);

        if (!$booking || intval($booking['id']) !== $bookingId) {
            $this->renderPublic('tour-checkin/invalid', ['reason' => 'not_found']);
            return;
        }

        $validation = $this->checkinModel->validateToken($booking);
        if (!$validation['valid']) {
            $this->renderPublic('tour-checkin/invalid', [
                'reason'  => $validation['reason'],
                'booking' => $booking,
            ]);
            return;
        }

        if ($booking['checkin_status'] == 1) {
            $this->renderPublic('tour-checkin/already', ['booking' => $booking]);
            return;
        }

        $this->renderPublic('tour-checkin/checkin', [
            'booking' => $booking,
            'token'   => $token,
        ]);
    }

    /**
     * POST tour_checkin_submit
     * Processes the customer check-in tap.
     */
    public function submit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectPublic('tour_checkin');
            return;
        }

        $bookingId = intval($_POST['id']    ?? 0);
        $token     = trim($_POST['token']   ?? '');
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua        = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        if (!$bookingId || !$token) {
            $this->renderPublic('tour-checkin/invalid', ['reason' => 'missing']);
            return;
        }

        if ($this->checkinModel->isRateLimited($ip)) {
            http_response_code(429);
            $this->renderPublic('tour-checkin/invalid', ['reason' => 'rate_limited']);
            return;
        }

        $booking = $this->checkinModel->findByToken($token);

        if (!$booking || intval($booking['id']) !== $bookingId) {
            $this->renderPublic('tour-checkin/invalid', ['reason' => 'not_found']);
            return;
        }

        $validation = $this->checkinModel->validateToken($booking);
        if (!$validation['valid']) {
            $this->renderPublic('tour-checkin/invalid', [
                'reason'  => $validation['reason'],
                'booking' => $booking,
            ]);
            return;
        }

        if ($booking['checkin_status'] == 1) {
            $this->renderPublic('tour-checkin/already', ['booking' => $booking]);
            return;
        }

        $this->checkinModel->markCheckedIn($bookingId, $ip, $ua);

        // Reload booking to get fresh checkin_at
        $booking = $this->checkinModel->findByToken($token);
        $this->renderPublic('tour-checkin/success', ['booking' => $booking]);
    }

    /**
     * Render a view without the main authenticated layout.
     * Uses a minimal public wrapper.
     */
    private function renderPublic(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            http_response_code(404);
            echo '<p>View not found: ' . htmlspecialchars($view) . '</p>';
        }
        exit;
    }

    private function redirectPublic(string $page): void
    {
        header('Location: index.php?page=' . $page);
        exit;
    }

    // ─── Staff: Live Check-In Dashboard ───────────────────────

    /**
     * GET tour_checkin_staff?date=YYYY-MM-DD
     */
    public function staffDashboard(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];
        $date  = trim($_GET['date'] ?? date('Y-m-d'));

        // Clamp to valid date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $bookings = $this->checkinModel->getStaffDashboard($comId, $date);
        $summary  = $this->checkinModel->getDashboardSummary($comId, $date);

        $this->render('tour-checkin/staff-dashboard', [
            'bookings' => $bookings,
            'summary'  => $summary,
            'date'     => $date,
        ]);
    }

    /**
     * POST tour_checkin_override  {booking_id}
     */
    public function staffOverride(): void
    {
        $this->guardModule();
        $this->requirePost();

        $bookingId = intval($_POST['booking_id'] ?? 0);
        $staffId   = intval($this->user['id'] ?? 0);
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!$bookingId) {
            $this->jsonError('Missing booking_id');
            return;
        }

        $checkinAt = $this->checkinModel->staffOverride($bookingId, $staffId, $ip);

        if ($checkinAt === null) {
            $this->jsonError('Check-in failed — booking not found or already deleted');
            return;
        }

        $this->jsonSuccess([
            'message'    => 'Checked in',
            'checkin_at' => $checkinAt,
            'time_label' => date('H:i', strtotime($checkinAt)),
        ]);
    }

    /**
     * POST tour_checkin_reset  {booking_id}
     */
    public function resetCheckin(): void
    {
        $this->guardModule();
        $this->requirePost();

        $bookingId = intval($_POST['booking_id'] ?? 0);
        $staffId   = intval($this->user['id'] ?? 0);
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!$bookingId) {
            $this->jsonError('Missing booking_id');
            return;
        }

        $this->checkinModel->resetCheckin($bookingId, $staffId, $ip);
        $this->jsonSuccess(['message' => 'Reset']);
    }

    /**
     * POST tour_checkin_regen  {booking_id}
     * Regenerates token — invalidates old QR code.
     */
    public function regenToken(): void
    {
        $this->guardModule();
        $this->requirePost();

        $bookingId = intval($_POST['booking_id'] ?? 0);
        $token     = '';

        if ($bookingId) {
            $token = $this->bookingModel->regenerateCheckinToken($bookingId);
        }

        $url = $token
            ? 'index.php?page=tour_checkin&id=' . $bookingId . '&token=' . $token
            : '';

        $this->jsonSuccess(['token' => $token, 'url' => $url]);
    }

    // ─── Helpers ──────────────────────────────────────────────

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }
    }

    private function jsonSuccess(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
        exit;
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
