<?php
namespace App\Models;

/**
 * AgentBookingParser — v6.3 #120 / #132
 *
 * Stateless parser that converts an inbound LINE OA text message from an
 * agent (or, after v6.6, a direct customer) into a structured booking
 * record — or a list of validation errors.
 *
 * Schema (current — updated in #132):
 *
 *   จองทัวร์    OR    book tour                          (strong trigger)
 *   จอง        OR    book                              (weak trigger — only when paired with field anchors)
 *
 *   ทัวร์: <model_name>             |   tour: <model_name>             (required)
 *   วันที่ (Travel Date): <YYYY-MM-DD>|   date (Travel Date): <YYYY-MM-DD> (required)
 *   ผู้ใหญ่: <int>                  |   adults: <int>                  (required)
 *   เด็ก: <int>                     |   children: <int>                (optional)
 *   ลูกค้า: <name>                  |   customer: <name>               (required)
 *   มือถือ: <phone>                 |   mobile: <phone>                (required if ลูกค้า: has no trailing digits)
 *   อีเมล: <email>                  |   email: <email>                 (optional)
 *   เมสเซนเจอร์: <id>               |   messenger: <id>                (optional, e.g. line:foo or @bar)
 *   ตัวแทน: <code>                  |   agent: <code>                  (optional — captured into remark for audit; auto-resolution to agent_id is tracked in #136)
 *   ที่พัก: <name>                   |   accommodation: <name>          (optional → tour_bookings.pickup_hotel)
 *   หมายเลขห้อง: <id>                |   room: <id>                     (optional → tour_bookings.pickup_room)
 *   หมายเหตุ: <free text>            |   notes: <free text>             (optional)
 *
 * Backward compatibility: if `ลูกค้า:` includes a trailing phone-like token
 * and `มือถือ:` is NOT provided, the trailing digits are split off as the
 * mobile number. This preserves the original v6.3 #120 single-line format.
 *
 * Customer contact (name, mobile, email, messenger) is persisted to
 * `tour_booking_contacts` (not `tour_bookings.booking_by`) — the latter is
 * reserved for the iACC user who entered the booking.
 *
 * Usage:
 *   $result = AgentBookingParser::parse($messageText);
 *   if ($result['ok']) {
 *       $fields = $result['fields'];   // ['tour_name'=>..., 'customer_mobile'=>..., 'accommodation'=>..., ...]
 *       $lang   = $result['lang'];     // 'th' | 'en'
 *   } else {
 *       $errors = $result['errors'];   // ['date_missing', 'phone_invalid', 'mobile_missing', ...]
 *       $lang   = $result['lang'];
 *   }
 */
class AgentBookingParser
{
    // Trigger phrases (case-insensitive substring match anywhere in the message).
    //
    // Strong triggers always activate the agent intercept — useful so an empty
    // template still gets the validation Flex listing the missing fields.
    //
    // Weak triggers only activate when the message also contains at least one
    // structured field anchor (ทัวร์:, date:, etc.). Otherwise we let the
    // legacy "book/จอง <date> <time>" customer handler in line-webhook.php
    // keep handling them — that flow is for non-agent customers and is
    // unrelated to agent text-template booking.
    private const TRIGGERS_STRONG = [
        'th' => ['จองทัวร์'],
        'en' => ['book tour'],
    ];
    private const TRIGGERS_WEAK = [
        'th' => ['จอง'],
        'en' => ['book'],
    ];

    // Field-keyword aliases (TH and EN); first match wins per language
    private const FIELD_KEYWORDS = [
        'tour_name'      => ['ทัวร์', 'tour'],
        'date'           => ['วันที่', 'date'],
        'adults'         => ['ผู้ใหญ่', 'adults'],
        'children'       => ['เด็ก', 'children'],
        'customer'       => ['ลูกค้า', 'customer'],
        'mobile'         => ['มือถือ', 'mobile'],
        'email'          => ['อีเมล', 'email'],
        'messenger'      => ['เมสเซนเจอร์', 'messenger'],
        'agent_code'     => ['ตัวแทน', 'agent'],
        'nationality'    => ['สัญชาติ', 'nationality'],
        'accommodation'  => ['ที่พัก', 'accommodation'],
        'room'           => ['หมายเลขห้อง', 'room'],
        'notes'          => ['หมายเหตุ', 'notes'],
    ];

    // agent_code is no longer required — auto-resolution from the LINE binding
    // is tracked in #136. Customer phone comes from explicit `มือถือ:` /
    // `mobile:` keyword (preferred) or trailing digits in `ลูกค้า:` (fallback).
    private const REQUIRED_FIELDS = ['tour_name', 'date', 'adults', 'customer'];

    /**
     * Returns:
     *   ['ok' => bool, 'fields' => array, 'errors' => string[], 'lang' => 'th'|'en', 'matched_trigger' => string|null]
     *
     * 'ok' is true only when no validation errors are present.
     */
    public static function parse(string $message): array
    {
        $message = self::convertThaiNumerals($message);
        $lang    = self::detectLang($message);
        $trigger = self::detectTrigger($message);

        // No trigger at all, OR a weak trigger with no field anchor — let
        // the legacy customer-date handler keep handling the message.
        if ($trigger === null
            || (!$trigger['strong'] && !self::hasAnyAnchor($message))) {
            return [
                'ok'              => false,
                'fields'          => [],
                'errors'          => ['no_trigger'],
                'lang'            => $lang,
                'matched_trigger' => null,
            ];
        }

        $fields = self::extractFields($message);
        $errors = self::validate($fields);

        return [
            'ok'              => empty($errors),
            'fields'          => $fields,
            'errors'          => $errors,
            'lang'            => $lang,
            'matched_trigger' => $trigger['phrase'],
        ];
    }

    // ============================================================
    // Lang + trigger detection
    // ============================================================

    private static function detectLang(string $message): string
    {
        // Heuristic: any Thai char => TH; else EN
        return preg_match('/[\x{0E00}-\x{0E7F}]/u', $message) ? 'th' : 'en';
    }

    /**
     * Returns ['phrase' => string, 'strong' => bool] or null if no trigger
     * is found. Strong triggers are unconditional; weak triggers must be
     * paired with a field anchor (see hasAnyAnchor) to count.
     */
    private static function detectTrigger(string $message): ?array
    {
        $haystack = mb_strtolower($message);
        foreach (self::TRIGGERS_STRONG as $lang => $phrases) {
            foreach ($phrases as $p) {
                if (mb_stripos($haystack, $p) !== false) {
                    return ['phrase' => $p, 'strong' => true];
                }
            }
        }
        foreach (self::TRIGGERS_WEAK as $lang => $phrases) {
            foreach ($phrases as $p) {
                if (mb_stripos($haystack, $p) !== false) {
                    return ['phrase' => $p, 'strong' => false];
                }
            }
        }
        return null;
    }

    /**
     * True if the message contains at least one "<field-keyword>:" anchor.
     * Used internally to gate weak triggers, and externally by
     * LineAgentController to distinguish "browse intent" (`จองทัวร์` alone)
     * from "incomplete booking attempt" (anchors present but missing
     * required values).
     */
    public static function hasAnyAnchor(string $message): bool
    {
        foreach (self::FIELD_KEYWORDS as $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/' . preg_quote($kw, '/') . '\s*[:：]/ui', $message)) {
                    return true;
                }
            }
        }
        return false;
    }

    // ============================================================
    // Field extraction
    // ============================================================

    /**
     * Extract structured fields by anchoring on keyword + ":" and reading until
     * the next keyword anchor or end-of-string. Order-independent.
     */
    private static function extractFields(string $message): array
    {
        // Build a sorted regex of all keyword anchors so we can split on them.
        $anchors = [];
        foreach (self::FIELD_KEYWORDS as $field => $keywords) {
            foreach ($keywords as $kw) {
                $anchors[] = preg_quote($kw, '/');
            }
        }
        // Sort longest-first so e.g. "agent" doesn't accidentally match inside "agentcode"
        usort($anchors, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
        $anchorRe = '(?:' . implode('|', $anchors) . ')';

        // Find every "<keyword>:<value>" segment, value runs until next keyword or EOL/EOS
        $fields = [];
        $pattern = '/(' . $anchorRe . ')\s*[:：]\s*(.*?)(?=(?:\s+(?:' . $anchorRe . ')\s*[:：])|$)/sui';
        if (preg_match_all($pattern, $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $kw  = trim($m[1]);
                $val = trim($m[2]);
                $field = self::keywordToField($kw);
                if ($field !== null && !isset($fields[$field])) {
                    $fields[$field] = $val;
                }
            }
        }

        // Normalize field types
        if (isset($fields['adults']))   $fields['adults']   = self::toIntOrNull($fields['adults']);
        if (isset($fields['children'])) $fields['children'] = self::toIntOrNull($fields['children']);
        // Default children=0 if not provided (optional field)
        $fields['children'] = $fields['children'] ?? 0;

        // Customer field handling:
        // - If `มือถือ:` / `mobile:` is provided, treat `ลูกค้า:` as the name only.
        // - Otherwise fall back to the legacy split: `ลูกค้า: <name> <phone>`.
        // Either way, `customer_mobile` becomes the canonical phone for downstream
        // validation and persistence.
        if (isset($fields['customer'])) {
            $explicitMobile = trim($fields['mobile'] ?? '');
            if ($explicitMobile !== '') {
                $fields['customer_name']   = trim($fields['customer']);
                $fields['customer_phone']  = '';
                $fields['customer_mobile'] = $explicitMobile;
            } else {
                $split = self::splitCustomer($fields['customer']);
                $fields['customer_name']   = $split['name'];
                $fields['customer_phone']  = $split['phone'];
                $fields['customer_mobile'] = $split['phone'];
            }
        }

        return $fields;
    }

    private static function keywordToField(string $keyword): ?string
    {
        $kw = mb_strtolower(trim($keyword));
        foreach (self::FIELD_KEYWORDS as $field => $keywords) {
            foreach ($keywords as $k) {
                if (mb_strtolower($k) === $kw) return $field;
            }
        }
        return null;
    }

    private static function splitCustomer(string $raw): array
    {
        // Find a phone-like token (8+ digits with optional dashes / leading +)
        if (preg_match('/(\+?\d[\d\-\s]{7,})\s*$/', $raw, $m)) {
            $phone = preg_replace('/[\s\-]/', '', $m[1]);
            $name  = trim(str_replace($m[1], '', $raw));
            return ['name' => $name, 'phone' => $phone];
        }
        // No phone detected — whole string is the name
        return ['name' => trim($raw), 'phone' => ''];
    }

    private static function toIntOrNull(string $v): ?int
    {
        $v = trim($v);
        if ($v === '' || !preg_match('/^\d+$/', $v)) return null;
        return (int)$v;
    }

    /**
     * Convert Thai numerals (๐-๙) to Arabic numerals.
     */
    private static function convertThaiNumerals(string $s): string
    {
        return strtr($s, [
            '๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4',
            '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9',
        ]);
    }

    // ============================================================
    // Validation
    // ============================================================

    private static function validate(array $f): array
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $req) {
            if ($req === 'customer') {
                if (empty($f['customer_name'])) {
                    $errors[] = 'customer_missing';
                }
                if (empty($f['customer_mobile'])) {
                    $errors[] = 'mobile_missing';
                }
                continue;
            }
            if (!isset($f[$req]) || $f[$req] === '' || $f[$req] === null) {
                $errors[] = $req . '_missing';
            }
        }

        if (isset($f['date']) && $f['date'] !== '') {
            if (!self::isValidDate($f['date'])) {
                $errors[] = 'date_invalid';
            }
        }

        if (isset($f['adults']) && $f['adults'] !== null) {
            $totalPax = (int)$f['adults'] + (int)($f['children'] ?? 0);
            if ($totalPax < 1) $errors[] = 'pax_too_few';
        }

        if (!empty($f['customer_mobile']) && !self::isValidPhone($f['customer_mobile'])) {
            $errors[] = 'phone_invalid';
        }

        return $errors;
    }

    private static function isValidDate(string $date): bool
    {
        // Accept YYYY-MM-DD only (locked schema)
        if (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $date)) return false;
        $ts = strtotime($date);
        return $ts !== false;
    }

    private static function isValidPhone(string $phone): bool
    {
        // Strip and check digit count: 9–13 digits (covers TH mobile + intl)
        $digits = preg_replace('/\D/', '', $phone);
        return strlen($digits) >= 9 && strlen($digits) <= 13;
    }

    /**
     * Helper for callers: was this date in the past relative to today?
     * (Not a validation error — just used to add a warning to the reply.)
     */
    public static function isDatePast(string $date): bool
    {
        $ts = strtotime($date);
        if ($ts === false) return false;
        return $ts < strtotime('today');
    }
}
