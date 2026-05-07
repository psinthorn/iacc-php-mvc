<?php
namespace App\Models;

/**
 * AgentBookingParser — v6.3 #120
 *
 * Stateless parser that converts an inbound LINE OA text message from a
 * sales agent into a structured booking record (or a list of validation
 * errors).
 *
 * Locked schema (PM call 2026-05-06, see issue #120):
 *
 *   จองทัวร์    OR    book tour
 *   ทัวร์: <tour_name>           |   tour: <tour_name>
 *   วันที่: <YYYY-MM-DD>         |   date: <YYYY-MM-DD>
 *   ผู้ใหญ่: <int>               |   adults: <int>
 *   เด็ก: <int>                  |   children: <int>
 *   ลูกค้า: <name> <phone>       |   customer: <name> <phone>
 *   ตัวแทน: <agent_code>         |   agent: <agent_code>
 *   หมายเหตุ: <free text>         |   notes: <free text>
 *
 * Required: tour, date, adults, customer, agent.
 * Optional: children, notes.
 *
 * Usage:
 *   $result = AgentBookingParser::parse($messageText);
 *   if ($result['ok']) {
 *       $fields = $result['fields'];   // ['tour_name'=>..., 'date'=>..., 'adults'=>4, ...]
 *       $lang   = $result['lang'];     // 'th' | 'en'
 *   } else {
 *       $errors = $result['errors'];   // ['date_missing', 'phone_invalid', ...]
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
        'agent_code'     => ['ตัวแทน', 'agent'],
        'notes'          => ['หมายเหตุ', 'notes'],
    ];

    private const REQUIRED_FIELDS = ['tour_name', 'date', 'adults', 'customer', 'agent_code'];

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
     * Used to distinguish a structured agent template from a bare legacy
     * command like "book 2026-04-15 14:00".
     */
    private static function hasAnyAnchor(string $message): bool
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

        // Customer field is "<name> <phone>" — split on last whitespace before
        // a phone-like token so names with spaces work.
        if (isset($fields['customer'])) {
            $split = self::splitCustomer($fields['customer']);
            $fields['customer_name']  = $split['name'];
            $fields['customer_phone'] = $split['phone'];
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
                if (empty($f['customer_name']) || empty($f['customer_phone'])) {
                    $errors[] = 'customer_missing';
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

        if (!empty($f['customer_phone']) && !self::isValidPhone($f['customer_phone'])) {
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
