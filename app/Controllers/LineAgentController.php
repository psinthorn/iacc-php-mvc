<?php
namespace App\Controllers;

/**
 * LineAgentController — v6.3 #120
 *
 * Two responsibilities:
 *
 *   1. Web admin UI for binding LINE OA agent accounts to iACC users
 *      (routes: line_agent_bindings, line_agent_bind_save).
 *
 *   2. Webhook ingestion path for bound-agent text messages — invoked
 *      by line-webhook.php when a text event arrives from a line_user
 *      with user_type='agent' AND linked_user_id IS NOT NULL.
 *
 * The webhook hook is NOT a routed page; it's called as a static-style
 * service entry. See line-webhook.php near the message-event branch.
 */
class LineAgentController extends BaseController
{
    private \App\Models\LineOA $lineModel;

    public function __construct()
    {
        parent::__construct();
        $this->lineModel = new \App\Models\LineOA();
    }

    // ====================================================================
    // Admin UI: Agent Bindings page
    // ====================================================================

    public function bindings(): void
    {
        if ((int)($this->user['level'] ?? 0) < 2) {
            http_response_code(403);
            die('Admin access required');
        }
        $companyId = (int)$this->user['com_id'];
        $bindings  = $this->lineModel->getAgentBindings($companyId);
        // #136: two scopes — operator's own team vs registered agent partners.
        // The view renders both as tabs so admins can pick the right pool of
        // iACC users when binding a LINE account.
        $iaccUsers         = $this->lineModel->getEligibleIaccUsers($companyId);
        $agentTenantUsers  = $this->lineModel->getEligibleAgentTenantUsers($companyId);
        $scope             = ($_GET['scope'] ?? 'team') === 'partners' ? 'partners' : 'team';
        $this->render('line-oa/agent-bindings', [
            'bindings'         => $bindings,
            'iaccUsers'        => $iaccUsers,
            'agentTenantUsers' => $agentTenantUsers,
            'scope'            => $scope,
        ]);
    }

    public function bindSave(): void
    {
        if ((int)($this->user['level'] ?? 0) < 2) {
            http_response_code(403);
            die('Admin access required');
        }
        $this->verifyCsrf();
        $companyId    = (int)$this->user['com_id'];
        $adminId      = (int)($this->user['id'] ?? 0);
        $lineUserDbId = (int)($_POST['line_user_id'] ?? 0);
        $action       = $_POST['action'] ?? 'bind';

        if ($lineUserDbId <= 0) {
            $_SESSION['flash_error'] = 'Missing LINE user.';
            $this->redirect('line_agent_bindings');
            return;
        }

        if ($action === 'unbind') {
            $this->lineModel->unbindAgent($companyId, $lineUserDbId);
            $_SESSION['flash_success'] = 'Agent binding removed.';
            $this->redirect('line_agent_bindings');
            return;
        }

        // bind
        $iaccUserId = (int)($_POST['iacc_user_id'] ?? 0);
        if ($iaccUserId <= 0) {
            $_SESSION['flash_error'] = 'Pick an iACC user to bind.';
            $this->redirect('line_agent_bindings');
            return;
        }

        $ok = $this->lineModel->bindAgentToUser($companyId, $lineUserDbId, $iaccUserId, $adminId);
        if ($ok) {
            $_SESSION['flash_success'] = 'Agent bound successfully.';
        } else {
            $_SESSION['flash_error'] = 'Could not bind — verify the LINE user is type=agent and the iACC user belongs to this company.';
        }
        $this->redirect('line_agent_bindings');
    }

    // ====================================================================
    // CSRF helper (mirrors LineOAController)
    // ====================================================================

    protected function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die('CSRF token mismatch');
        }
    }

    // ====================================================================
    // Webhook path: ingestText
    // ====================================================================

    /**
     * Called from line-webhook.php when a text message arrives from a
     * bound agent. Returns:
     *
     *   ['handled' => bool, 'reason' => string, 'booking_id' => int|null,
     *    'reply_messages' => array]   // ready-to-send LINE Messaging API payload
     *
     * Caller is responsible for taking $result['reply_messages'] and
     * pushing them via LineService::replyMessage($replyToken, …).
     *
     * 'handled' is false when the message has no booking trigger — caller
     * should fall through to existing auto-reply matching.
     */
    public static function ingestText(int $companyId, string $messageText, string $lineUserIdStr): array
    {
        $parser = \App\Models\AgentBookingParser::class;
        $parsed = $parser::parse($messageText);

        // No trigger phrase => not a booking message; let auto-reply handle it
        if (!empty($parsed['errors']) && in_array('no_trigger', $parsed['errors'], true)) {
            return ['handled' => false, 'reason' => 'no_trigger', 'booking_id' => null, 'reply_messages' => []];
        }

        $lang = $parsed['lang'];

        // Validation errors => reply with a missing-fields card
        if (!$parsed['ok']) {
            return [
                'handled'        => true,
                'reason'         => 'validation_failed',
                'booking_id'     => null,
                'reply_messages' => [self::buildErrorFlex($parsed['errors'], $lang)],
            ];
        }

        $fields = $parsed['fields'];

        // Resolve the bound iACC user (may be null in v6.6 #134 — customers
        // sending the structured template directly aren't required to be
        // bound as agents; we just attribute the booking differently below).
        $line = new \App\Models\LineOA();
        $iaccUserId   = $line->getBoundIaccUserId($companyId, $lineUserIdStr);
        $isBoundAgent = !empty($iaccUserId);

        // Match the tour name within the tenant's tours
        $tourMatch = self::matchTour($companyId, $fields['tour_name']);
        if ($tourMatch['status'] === 'none') {
            return [
                'handled'        => true,
                'reason'         => 'tour_not_found',
                'booking_id'     => null,
                'reply_messages' => [self::buildPlainText(($lang === 'th'
                    ? 'ไม่พบทัวร์ที่ตรงกับ "%s" ในระบบ'
                    : 'No tour matching "%s" found in your system.'),
                    [$fields['tour_name']])],
            ];
        }
        if ($tourMatch['status'] === 'multiple') {
            $list = '';
            foreach ($tourMatch['candidates'] as $i => $c) {
                $list .= ($i + 1) . ') ' . $c['name'] . "\n";
            }
            return [
                'handled'        => true,
                'reason'         => 'tour_ambiguous',
                'booking_id'     => null,
                'reply_messages' => [self::buildPlainText(($lang === 'th'
                    ? "พบทัวร์หลายรายการที่ตรงกัน:\n%sกรุณาส่งใหม่พร้อมระบุชื่อให้ชัดเจนยิ่งขึ้น"
                    : "Multiple tours matched:\n%sPlease re-send with a more specific name."),
                    [$list])],
            ];
        }

        // Single tour match — build booking row
        $tour = $tourMatch['tour'];

        // Past-date warning prefix for the reply (still write the booking)
        $pastDateWarn = $parser::isDatePast($fields['date']);

        // Compose booking_by — the human-readable identity of who entered
        // the booking. Two paths:
        //
        //   Bound agent (#132): name → email → "User #ID" sentinel from the
        //     bound authorize row. Empty strings fall through too.
        //
        //   Direct customer (#134): the LINE user's display_name from
        //     line_users, suffixed with " [LINE customer]" so the operator
        //     can tell at a glance this booking came in via LINE without an
        //     agent attribution. Falls back to a bare sentinel if display
        //     name is missing.
        $boundUser     = $isBoundAgent ? $line->getBoundUserDetails($companyId, $lineUserIdStr) : null;
        $bookingByName = '';
        if ($isBoundAgent && $boundUser) {
            if (!empty($boundUser['authorize_name'])) {
                $bookingByName = $boundUser['authorize_name'];
            } elseif (!empty($boundUser['email'])) {
                $bookingByName = $boundUser['email'];
            }
        }
        if ($bookingByName === '') {
            if ($isBoundAgent) {
                $bookingByName = 'User #' . $iaccUserId;
            } else {
                $lineUserRow  = $line->getLineUserByLineId($companyId, $lineUserIdStr);
                $displayName  = trim($lineUserRow['display_name'] ?? '');
                $bookingByName = $displayName !== ''
                    ? ($displayName . ' [LINE customer]')
                    : '[LINE customer]';
            }
        }

        // #136: auto-resolve agent_id from the bound user. Only fires when
        // the user is bound AND lives in an agent-partner tenant — direct
        // customers never have an agent attribution.
        $resolvedAgentId = 0;
        if ($isBoundAgent && $boundUser && !empty($boundUser['user_com_id'])) {
            $aid = $line->resolveAgentIdFromBoundUser($companyId, (int)$boundUser['user_com_id']);
            if ($aid) $resolvedAgentId = $aid;
        }

        // Compose remark — captures the tour name + any agent notes +
        // typed agent_code (for audit; auto-resolution to tour_bookings.agent_id
        // is deferred to #136 once the bind UI supports agent-tenant users).
        $remarkParts = [];
        $remarkParts[] = '[from LINE agent text]';
        $remarkParts[] = 'Tour: ' . ($tour['name'] ?? '');
        if (!empty($fields['agent_code'])) $remarkParts[] = 'Agent code (typed): ' . $fields['agent_code'];
        if (!empty($fields['notes']))      $remarkParts[] = 'Notes: ' . $fields['notes'];
        $remark = implode("\n", $remarkParts);

        $statusInfo = ['status' => 'draft', 'reason' => 'unresolved'];
        try {
            $tourBookingModel = new \App\Models\TourBooking();
            $bookingNumber = $tourBookingModel->generateBookingNumber($companyId);

            // Allotment-aware status (auto-confirm if seats available)
            $totalPax = (int)$fields['adults'] + (int)($fields['children'] ?? 0);
            $statusInfo = $tourBookingModel->resolveBookingStatus(
                $companyId, $fields['date'], $totalPax
            );

            $bookingData = [
                'company_id'     => $companyId,
                'booking_number' => $bookingNumber,
                'booking_date'   => date('Y-m-d'),
                'travel_date'    => $fields['date'],
                'agent_id'       => $resolvedAgentId, // #136: auto from binding when partner-tenant
                'sales_rep_id'   => 0,
                'customer_id'    => 0,
                'booking_by'     => $bookingByName,
                'pax_adult'      => (int)$fields['adults'],
                'pax_child'      => (int)($fields['children'] ?? 0),
                'pax_infant'     => 0,
                'pickup_hotel'   => $fields['accommodation'] ?? '',
                'pickup_room'    => $fields['room'] ?? '',
                'status'         => $statusInfo['status'],
                'remark'         => $remark,
                // Bound agent: attribute to their authorize.id.
                // Direct customer: created_by stays NULL (no iACC user).
                'created_by'     => $isBoundAgent
                    ? ($boundUser['authorize_id'] ?? $iaccUserId)
                    : null,
                // Distinguish the two source channels for reporting and
                // future per-channel automation.
                'created_via'    => $isBoundAgent
                    ? 'line_oa_agent_text'
                    : 'line_oa_customer_text',
            ];

            $bookingId = $tourBookingModel->createBooking($bookingData);

            // Per-booking customer contact row (proper home for name +
            // mobile + email + messenger, replacing the "stuff into booking_by"
            // placeholder from the original #120 PR).
            if ($bookingId > 0) {
                $tourBookingModel->saveBookingContact($bookingId, [
                    'contact_name'       => $fields['customer_name']  ?? '',
                    'mobile'             => $fields['customer_mobile'] ?? '',
                    'email'              => $fields['email']          ?? '',
                    'contact_messengers' => $fields['messenger']      ?? '',
                ]);

                // Visibility: log a webhook event when status fell to draft
                // due to allotment so the operator can act on it.
                if ($statusInfo['status'] === 'draft' && $statusInfo['reason'] !== 'unresolved') {
                    try {
                        $line->logWebhookEvent($companyId, 'allotment_blocked', json_encode([
                            'context'        => 'agent_booking_status_resolved',
                            'booking_id'     => $bookingId,
                            'booking_number' => $bookingNumber,
                            'travel_date'    => $fields['date'],
                            'pax'            => $totalPax,
                            'reason'         => $statusInfo['reason'],
                        ]));
                    } catch (\Throwable $_) {}
                }
            }
        } catch (\Throwable $e) {
            error_log('LineAgentController::ingestText createBooking failed: ' . $e->getMessage());
            // Also persist to line_webhook_events so the same error is visible
            // via SQL on cPanel without having to read PHP error logs.
            try {
                $line->logWebhookEvent($companyId, 'error', json_encode([
                    'context' => 'agent_booking_write',
                    'error'   => $e->getMessage(),
                    'fields'  => $fields ?? [],
                ]));
            } catch (\Throwable $_) {
                // logging is best-effort; never let it mask the original error
            }
            $bookingId = 0;
            $bookingNumber = '';
        }

        if ($bookingId <= 0) {
            return [
                'handled'        => true,
                'reason'         => 'write_failed',
                'booking_id'     => null,
                'reply_messages' => [self::buildPlainText($lang === 'th'
                    ? 'เกิดข้อผิดพลาดในการบันทึก กรุณาติดต่อผู้ดูแลระบบ'
                    : 'Could not save the booking. Please contact your admin.')],
            ];
        }

        return [
            'handled'        => true,
            'reason'         => 'booked',
            'booking_id'     => $bookingId,
            'reply_messages' => [self::buildSuccessFlex($bookingNumber, $tour, $fields, $lang, $pastDateWarn, $statusInfo)],
        ];
    }

    // ====================================================================
    // Helpers — tour match, booking insert, reply builders
    // ====================================================================

    /**
     * Fuzzy match a tour name against the company's `model` table
     * (joined with `type` so agents can match by category name too).
     * Returns ['status' => 'none'|'one'|'multiple', 'tour' => array|null, 'candidates' => array]
     */
    private static function matchTour(int $companyId, string $needle): array
    {
        global $db;
        $needle = trim($needle);
        if ($needle === '') return ['status' => 'none', 'tour' => null, 'candidates' => []];

        // CONVERT + COLLATE on each bound parameter pins the LIKE comparison to
        // utf8mb4_unicode_ci regardless of the server's `collation_connection`.
        // Without this, MySQL throws "Illegal mix of collations" when the
        // connection collation (e.g. utf8mb4_bin on staging) differs from the
        // column collation (utf8mb4_unicode_ci).
        $stmt = $db->conn->prepare(
            "SELECT m.id, m.model_name AS name
             FROM model m
             LEFT JOIN type t ON m.type_id = t.id
             WHERE m.company_id = ?
               AND m.deleted_at IS NULL
               AND m.is_active = 1
               AND (m.model_name LIKE CONCAT('%', CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci, '%')
                    OR CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE CONCAT('%', m.model_name, '%')
                    OR t.name LIKE CONCAT('%', CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci, '%'))
             ORDER BY CHAR_LENGTH(m.model_name) ASC
             LIMIT 5"
        );
        $stmt->bind_param('isss', $companyId, $needle, $needle, $needle);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($rows))    return ['status' => 'none',     'tour' => null,    'candidates' => []];
        if (count($rows) === 1) return ['status' => 'one',  'tour' => $rows[0],'candidates' => $rows];
        return ['status' => 'multiple', 'tour' => null, 'candidates' => $rows];
    }

    // ----- Flex / text reply builders -----

    private static function buildPlainText(string $template, array $vars = []): array
    {
        $text = $vars ? vsprintf($template, $vars) : $template;
        return ['type' => 'text', 'text' => $text];
    }

    private static function buildSuccessFlex(string $bookingNumber, array $tour, array $fields, string $lang, bool $pastDateWarn, array $statusInfo = ['status' => 'confirmed', 'reason' => 'available']): array
    {
        $isThai = ($lang === 'th');
        $confirmed = ($statusInfo['status'] ?? 'confirmed') === 'confirmed';

        if ($confirmed) {
            $title = $isThai ? '✅ ยืนยันการจอง' : '✅ Booking Confirmed';
            $titleColor = '#06C755';
        } else {
            $title = $isThai ? '⏳ รอการตรวจสอบ' : '⏳ Booking Pending Review';
            $titleColor = '#e67e22';
        }
        if ($pastDateWarn) $title .= $isThai ? ' ⚠️ (วันที่ผ่านมาแล้ว)' : ' ⚠️ (past date)';

        // Sub-line explaining why a confirmed booking went to draft.
        $subLine = '';
        if (!$confirmed) {
            $reasonMap = [
                'no_allotment' => $isThai ? 'ยังไม่มีการตั้งค่าจำนวนที่นั่งสำหรับวันนี้' : 'No allotment configured for this date',
                'closed'       => $isThai ? 'วันที่นี้ถูกปิดรับการจอง'                  : 'This date is closed for booking',
                'overbook'     => $isThai ? 'จำนวนที่นั่งไม่เพียงพอ'                    : 'Not enough seats available',
            ];
            $subLine = $reasonMap[$statusInfo['reason'] ?? ''] ?? '';
        }

        $paxLine = ($isThai
            ? sprintf('👥 %d ผู้ใหญ่ + %d เด็ก', (int)$fields['adults'], (int)($fields['children'] ?? 0))
            : sprintf('👥 %d adults + %d children', (int)$fields['adults'], (int)($fields['children'] ?? 0)));

        $rows = [
            ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'lg', 'color' => $titleColor, 'wrap' => true],
            ['type' => 'text', 'text' => $bookingNumber, 'size' => 'sm', 'color' => '#888888', 'margin' => 'sm'],
        ];
        if ($subLine !== '') {
            $rows[] = ['type' => 'text', 'text' => $subLine, 'size' => 'xs', 'color' => '#888888', 'margin' => 'sm', 'wrap' => true];
        }
        $rows[] = ['type' => 'separator', 'margin' => 'md'];
        $rows[] = ['type' => 'text', 'text' => $tour['name'] ?? '', 'weight' => 'bold', 'size' => 'md', 'wrap' => true, 'margin' => 'md'];
        $rows[] = ['type' => 'text', 'text' => '📅 ' . $fields['date'], 'size' => 'sm', 'margin' => 'sm'];
        $rows[] = ['type' => 'text', 'text' => $paxLine, 'size' => 'sm', 'margin' => 'sm'];
        $rows[] = ['type' => 'text', 'text' => '👤 ' . ($fields['customer_name'] ?? ''), 'size' => 'sm', 'margin' => 'sm', 'wrap' => true];
        $rows[] = ['type' => 'text', 'text' => '📱 ' . ($fields['customer_mobile'] ?? ''), 'size' => 'sm', 'margin' => 'sm'];
        if (!empty($fields['email'])) {
            $rows[] = ['type' => 'text', 'text' => '✉️ ' . $fields['email'], 'size' => 'sm', 'margin' => 'sm', 'wrap' => true];
        }
        if (!empty($fields['accommodation'])) {
            $rows[] = ['type' => 'text', 'text' => '🏨 ' . $fields['accommodation'], 'size' => 'sm', 'margin' => 'sm', 'wrap' => true];
        }
        if (!empty($fields['room'])) {
            $rows[] = ['type' => 'text', 'text' => ($isThai ? '🚪 ห้อง ' : '🚪 Room ') . $fields['room'], 'size' => 'sm', 'margin' => 'sm'];
        }

        $altPrefix = $confirmed
            ? ($isThai ? 'ยืนยันการจอง '      : 'Booking confirmed ')
            : ($isThai ? 'การจองรอตรวจสอบ ' : 'Booking pending review ');

        return [
            'type' => 'flex',
            'altText' => $altPrefix . $bookingNumber,
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box', 'layout' => 'vertical',
                    'contents' => $rows,
                ],
            ],
        ];
    }

    private static function buildErrorFlex(array $errors, string $lang): array
    {
        $isThai = ($lang === 'th');
        $labelMap = [
            'tour_name_missing' => $isThai ? 'ทัวร์ / tour'           : 'tour / ทัวร์',
            'date_missing'      => $isThai ? 'วันที่ / date'           : 'date / วันที่',
            'date_invalid'      => $isThai ? 'รูปแบบวันที่ (YYYY-MM-DD)' : 'date format (YYYY-MM-DD)',
            'adults_missing'    => $isThai ? 'ผู้ใหญ่ / adults'         : 'adults / ผู้ใหญ่',
            'customer_missing'  => $isThai ? 'ลูกค้า / customer name'   : 'customer / ลูกค้า',
            'mobile_missing'    => $isThai ? 'มือถือ / mobile'         : 'mobile / มือถือ',
            'phone_invalid'     => $isThai ? 'เบอร์โทรไม่ถูกต้อง'        : 'phone number invalid',
            'pax_too_few'       => $isThai ? 'จำนวนผู้เดินทางต้อง ≥ 1'   : 'pax must be ≥ 1',
        ];

        $bullets = [];
        foreach ($errors as $err) {
            $label = $labelMap[$err] ?? $err;
            $bullets[] = ['type' => 'text', 'text' => '• ' . $label, 'size' => 'sm', 'wrap' => true, 'margin' => 'sm'];
        }

        return [
            'type' => 'flex',
            'altText' => ($isThai ? 'การจองไม่ครบถ้วน' : 'Booking incomplete'),
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box', 'layout' => 'vertical',
                    'contents' => array_merge([
                        ['type' => 'text', 'text' => ($isThai ? '⚠️ การจองไม่ครบถ้วน' : '⚠️ Booking Incomplete'), 'weight' => 'bold', 'size' => 'lg', 'color' => '#e67e22'],
                        ['type' => 'separator', 'margin' => 'md'],
                        ['type' => 'text', 'text' => ($isThai ? 'กรุณาเพิ่ม:' : 'Please add:'), 'size' => 'sm', 'margin' => 'md', 'weight' => 'bold'],
                    ], $bullets, [
                        ['type' => 'text', 'text' => ($isThai ? 'แล้วส่งข้อความใหม่อีกครั้ง' : 'Then re-send the full template.'), 'size' => 'xs', 'color' => '#888888', 'margin' => 'lg', 'wrap' => true],
                    ]),
                ],
            ],
        ];
    }
}
