<?php
namespace App\Services;

/**
 * LineTourCatalog — v6.6 #135
 *
 * Builds the Flex carousel of available tours and the pre-filled
 * booking template that gets sent back when a user taps a "Book this"
 * button. The browse → tap → reply flow is the discoverability layer
 * on top of the structured booking template (#120, #132, #134) so
 * customers and agents don't have to memorize tour codes like
 * "SM-EN-01-AT".
 *
 * Flow:
 *   User sends   "ดูทัวร์" / "show tours"  (any of the configured triggers)
 *   Bot replies  Flex carousel of up to N tours, each with a Book button
 *   User taps    Book this → LINE postback action=prefill_booking&tour_id=X
 *   Bot replies  pre-filled text template with the chosen tour name
 *   User edits   completes the rest (date, pax, contact) and sends
 *   Existing     #134 flow handles the booking write
 *
 * Defers (out of scope for v1):
 *   - model.is_customer_bookable flag (filter customer-facing list)
 *   - model.thumbnail_url (use default placeholder until populated)
 *   - Pagination via "See more" CTA bubble (cap at 10 for now)
 *   - Per-tenant trigger phrases (use a fixed bilingual list)
 */
class LineTourCatalog
{
    /**
     * Bilingual triggers that activate the carousel browse. Case-insensitive.
     * If a message starts with any of these, fire the carousel before the
     * agent booking intercept tries to parse it as a booking.
     */
    private const TRIGGERS = [
        'ดูทัวร์', 'รายการทัวร์', 'ทัวร์ทั้งหมด',
        'show tours', 'tour list', 'tours', 'list tours', 'catalog',
    ];

    /**
     * Maximum bubbles per carousel. LINE Flex caps at 12; we use 10 to
     * leave room for a future "See more" CTA bubble.
     */
    private const MAX_BUBBLES = 10;

    /**
     * Returns true if the inbound message text matches any browse trigger.
     */
    public static function isTriggered(string $message): bool
    {
        $haystack = mb_strtolower(trim($message));
        foreach (self::TRIGGERS as $trigger) {
            if (mb_stripos($haystack, mb_strtolower($trigger)) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a Flex carousel of active tours for the operator. Returns the
     * complete LINE Messaging API message envelope (type=flex, altText,
     * contents=carousel).
     *
     * If the operator has no active tours, returns a plain-text "no tours
     * available" message instead (also bilingual).
     */
    public static function buildCarousel(int $companyId, string $lang = 'en'): array
    {
        $isThai = ($lang === 'th');
        $tours  = self::fetchActiveTours($companyId, self::MAX_BUBBLES);

        if (empty($tours)) {
            return [
                'type' => 'text',
                'text' => $isThai
                    ? 'ขณะนี้ยังไม่มีทัวร์ที่เปิดให้จอง กรุณาตรวจสอบอีกครั้งภายหลัง'
                    : 'No tours available right now — please check back later.',
            ];
        }

        $bubbles = [];
        foreach ($tours as $t) {
            $bubbles[] = self::buildBubble($t, $isThai, $lang);
        }

        return [
            'type'    => 'flex',
            'altText' => $isThai
                ? 'รายการทัวร์ที่เปิดให้จอง'
                : 'Available tours — tap a card to start booking',
            'contents' => [
                'type'     => 'carousel',
                'contents' => $bubbles,
            ],
        ];
    }

    /**
     * Build a pre-filled booking template for the chosen tour. Sent in
     * response to the postback action=prefill_booking&tour_id=X.
     *
     * The user will receive this text and need to fill in the remaining
     * fields (date, pax, customer info) before sending it back. Existing
     * #134 booking flow handles the write.
     *
     * Returns plain-text reply (one LINE message). Returns null if the
     * tour was not found or doesn't belong to the operator (defensive
     * tenancy check).
     */
    public static function buildPrefillReply(int $companyId, int $tourId, string $lang = 'en'): ?array
    {
        $tour = self::fetchTour($companyId, $tourId);
        if (!$tour) return null;

        $isThai = ($lang === 'th');
        $sampleDate = date('Y-m-d', strtotime('+7 days'));
        $tourName   = $tour['model_name'];

        // Expanded in the v6.6 #135 follow-up to include all parser-supported
        // optional fields (hotel, room, messenger, notes) so users discover
        // them rather than missing pickup-relevant info on first send.
        $text = $isThai
            ? "เริ่มการจองทัวร์ \"{$tourName}\"\nกรุณาส่งข้อความตามรูปแบบด้านล่าง พร้อมกรอกข้อมูลให้ครบ (ฟิลด์ที่มี * เป็นข้อมูลที่จำเป็น):\n\n"
              . "จองทัวร์\n"
              . "ทัวร์: {$tourName}\n"
              . "วันที่: {$sampleDate} *\n"
              . "ผู้ใหญ่: <จำนวน> *\n"
              . "เด็ก: 0\n"
              . "ลูกค้า: <ชื่อ> *\n"
              . "มือถือ: <เบอร์> *\n"
              . "อีเมล: <อีเมล (ถ้ามี)>\n"
              . "เมสเซนเจอร์: <line:id หรือ @page (ถ้ามี)>\n"
              . "ที่พัก: <ชื่อโรงแรม/ที่พัก (ถ้ามี)>\n"
              . "หมายเลขห้อง: <เลขห้อง (ถ้ามี)>\n"
              . "หมายเหตุ: <รายละเอียดเพิ่มเติม (ถ้ามี)>"
            : "Starting booking for \"{$tourName}\"\nPlease send back the template below with the remaining details filled in (* = required):\n\n"
              . "book tour\n"
              . "tour: {$tourName}\n"
              . "date: {$sampleDate} *\n"
              . "adults: <count> *\n"
              . "children: 0\n"
              . "customer: <name> *\n"
              . "mobile: <phone> *\n"
              . "email: <email (optional)>\n"
              . "messenger: <line:id or @page (optional)>\n"
              . "accommodation: <hotel/villa name (optional)>\n"
              . "room: <room number (optional)>\n"
              . "notes: <extra details (optional)>";

        return ['type' => 'text', 'text' => $text];
    }

    // ============================================================
    // Internals
    // ============================================================

    /**
     * Fetch active, customer-bookable tours for the operator. Joins type
     * for the category label. Tenancy enforced via company_id.
     *
     * The is_customer_bookable filter (added in the v6.6 #135 follow-up)
     * lets operators hide non-tour rows like entrance fees from the
     * customer-facing catalog while keeping them otherwise active for
     * pricing/reporting. Default for new and existing rows is 1
     * (visible) — operators flip specific rows to 0 to hide them.
     */
    private static function fetchActiveTours(int $companyId, int $limit): array
    {
        global $db;
        // The `parent_model_id IS NULL` filter (added in v6.6 sub-model
        // support) ensures only top-level tours appear in the carousel —
        // sub-items like entrance fees are children of their parent tour
        // and get auto-seeded as line items at booking time, never browsed
        // standalone by customers.
        $stmt = $db->conn->prepare(
            "SELECT m.id, m.model_name, m.des, m.price,
                    t.name AS type_name
             FROM model m
             LEFT JOIN type t ON m.type_id = t.id
             WHERE m.company_id = ?
               AND m.deleted_at IS NULL
               AND m.is_active = 1
               AND m.is_customer_bookable = 1
               AND m.parent_model_id IS NULL
             ORDER BY t.name ASC, m.model_name ASC
             LIMIT ?"
        );
        $stmt->bind_param('ii', $companyId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Fetch a single tour by id, scoped to the operator. Defensive
     * against postback tampering (a malicious user editing the
     * data= URL to point at another tenant's tour id).
     */
    private static function fetchTour(int $companyId, int $tourId): ?array
    {
        global $db;
        $stmt = $db->conn->prepare(
            "SELECT m.id, m.model_name, m.des, m.price
             FROM model m
             WHERE m.company_id = ?
               AND m.id = ?
               AND m.deleted_at IS NULL
               AND m.is_active = 1
             LIMIT 1"
        );
        $stmt->bind_param('ii', $companyId, $tourId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Build a single Flex bubble for one tour.
     *
     * Layout:
     *   - Header: type/category name (small, muted)
     *   - Body: model_name (bold, larger), description preview, price line
     *   - Footer: "Book this" postback button
     */
    private static function buildBubble(array $tour, bool $isThai, string $lang = 'en'): array
    {
        $title       = (string)($tour['model_name'] ?? '');
        $category    = (string)($tour['type_name']  ?? '');
        $description = trim(strip_tags((string)($tour['des'] ?? '')));
        if (mb_strlen($description) > 80) {
            $description = mb_substr($description, 0, 80) . '…';
        }
        $price = floatval($tour['price'] ?? 0);
        $priceLabel = $price > 0
            ? '฿' . number_format($price, 0) . ($isThai ? ' / ท่าน' : ' / pax')
            : ($isThai ? 'สอบถามราคา' : 'Contact for price');

        $bookLabel = $isThai ? '📝 จองทัวร์นี้' : '📝 Book this';

        $bodyContents = [];
        if ($category !== '') {
            $bodyContents[] = ['type' => 'text', 'text' => $category, 'size' => 'xs', 'color' => '#888888'];
        }
        $bodyContents[] = ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'lg', 'wrap' => true, 'margin' => 'sm'];
        if ($description !== '') {
            $bodyContents[] = ['type' => 'text', 'text' => $description, 'size' => 'sm', 'color' => '#666666', 'wrap' => true, 'margin' => 'md'];
        }
        $bodyContents[] = ['type' => 'separator', 'margin' => 'md'];
        $bodyContents[] = ['type' => 'text', 'text' => $priceLabel, 'weight' => 'bold', 'size' => 'md', 'color' => '#06C755', 'margin' => 'md'];

        return [
            'type' => 'bubble',
            'size' => 'kilo',
            'body' => [
                'type'     => 'box',
                'layout'   => 'vertical',
                'contents' => $bodyContents,
            ],
            'footer' => [
                'type'   => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type'   => 'button',
                        'style'  => 'primary',
                        'color'  => '#06C755',
                        'action' => [
                            'type'  => 'postback',
                            'label' => $bookLabel,
                            // displayText shows in the chat as if the user typed
                            // it — gives them a record of which tour they picked.
                            'displayText' => ($isThai ? 'จองทัวร์: ' : 'Book: ') . $title,
                            // Carry the carousel's language back through
                            // the postback so the prefill reply matches —
                            // detecting from display_name is unreliable
                            // (Thai users with English-spelled names
                            // would land on the wrong template).
                            'data'  => 'action=prefill_booking&tour_id=' . intval($tour['id']) . '&lang=' . ($lang === 'th' ? 'th' : 'en'),
                        ],
                    ],
                ],
            ],
        ];
    }
}
