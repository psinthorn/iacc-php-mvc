<?php
namespace App\Services;

/**
 * LineTemplateRenderer — v6.4 #133
 *
 * Per-tenant template rendering for LINE OA reply text. Reads from the
 * `line_message_templates` table (created by v6.2 LINE OA Rich Messaging,
 * catch-up migration in v6.4); falls back to hardcoded defaults when a
 * template is missing or fails to render.
 *
 * v1 scope (this PR) — plain-text replies only:
 *   - agent.tour_not_found  → "ไม่พบทัวร์ที่ตรงกับ ..."
 *   - agent.tour_ambiguous  → "พบทัวร์หลายรายการที่ตรงกัน: ..."
 *   - agent.write_failed    → "เกิดข้อผิดพลาดในการบันทึก ..."
 *   - legacy.book_redirect  → "กรุณาใช้แบบฟอร์มการจองใหม่ ..."
 *
 * Future scope (Phase 2): the success/error Flex bubbles. They have
 * conditional rows (email line only when email present, etc.) that
 * require a richer template format than plain string substitution.
 *
 * Placeholder syntax: `{{name}}` — Mustache-style, friendly for admin
 * editors. Substitution is a simple str_replace pre-render. Missing
 * placeholders are left as `{{name}}` literally (visible to user, makes
 * misconfiguration loud rather than silent).
 *
 * Usage:
 *
 *   $text = LineTemplateRenderer::renderText(
 *       $companyId,
 *       'agent.tour_not_found',
 *       'th',
 *       ['tour_name' => 'SM-EN-01-AT']
 *   );
 *
 * Lookup order:
 *   1. line_message_templates row (per-tenant, by name)
 *   2. Hardcoded default in self::DEFAULTS (this file)
 *
 * The "lazy seed" pattern (insert the default into the tenant's table on
 * first miss) is intentionally NOT done here — it would create a row that
 * looks like an admin override even though the admin never edited it. The
 * existing template-edit.php page lets admins create/edit per-tenant
 * customizations explicitly when they want to.
 */
class LineTemplateRenderer
{
    /**
     * Hardcoded defaults — single source of truth when no per-tenant
     * customization exists. Each key is the canonical template name; each
     * value carries the bilingual TH+EN content and a sample list of
     * placeholder names (informational, used by the editor's help panel).
     */
    private const DEFAULTS = [
        'agent.tour_not_found' => [
            'message_type' => 'text',
            'th'           => 'ไม่พบทัวร์ที่ตรงกับ "{{tour_name}}" ในระบบ',
            'en'           => 'No tour matching "{{tour_name}}" found in your system.',
            'placeholders' => ['tour_name'],
        ],
        'agent.tour_ambiguous' => [
            'message_type' => 'text',
            'th'           => "พบทัวร์หลายรายการที่ตรงกัน:\n{{candidates}}กรุณาส่งใหม่พร้อมระบุชื่อให้ชัดเจนยิ่งขึ้น",
            'en'           => "Multiple tours matched:\n{{candidates}}Please re-send with a more specific name.",
            'placeholders' => ['candidates'],
        ],
        'agent.write_failed' => [
            'message_type' => 'text',
            'th'           => 'เกิดข้อผิดพลาดในการบันทึก กรุณาติดต่อผู้ดูแลระบบ',
            'en'           => 'Could not save the booking. Please contact your admin.',
            'placeholders' => [],
        ],
        'legacy.book_redirect' => [
            'message_type' => 'text',
            'th'           => "กรุณาใช้แบบฟอร์มการจองใหม่ — พิมพ์ \"จองทัวร์\" พร้อมรายละเอียด เช่น:\n\nจองทัวร์\nทัวร์: <ชื่อทัวร์>\nวันที่: {{sample_date}}\nผู้ใหญ่: <จำนวน>\nลูกค้า: <ชื่อ>\nมือถือ: <เบอร์>",
            'en'           => "Please use the new booking template — start your message with \"book tour\" and include the tour details, e.g.:\n\nbook tour\ntour: <tour name>\ndate: {{sample_date}}\nadults: <count>\ncustomer: <name>\nmobile: <phone>",
            'placeholders' => ['sample_date'],
        ],
    ];

    /**
     * Render a plain-text template for the given tenant + name + language,
     * substituting `{{placeholders}}`.
     *
     * Returns the rendered string. Never throws — failures are logged and
     * the hardcoded default is rendered instead, so the customer-facing
     * reply path is never broken by a misconfigured template.
     */
    public static function renderText(int $companyId, string $name, string $lang, array $vars = []): string
    {
        $lang = ($lang === 'th') ? 'th' : 'en';
        $template = self::lookup($companyId, $name, $lang);

        // Substitute placeholders. Missing keys are intentionally left as
        // literal `{{name}}` so misconfiguration is loud rather than silent.
        $rendered = $template;
        foreach ($vars as $k => $v) {
            $rendered = str_replace('{{' . $k . '}}', (string)$v, $rendered);
        }
        return $rendered;
    }

    /**
     * Look up the per-tenant template content for one language. Falls
     * back to the hardcoded default in self::DEFAULTS when:
     *   - The line_message_templates row is missing (most common case)
     *   - The row's content for the requested language is empty/null
     *   - Any DB error fires (logged, then fallback)
     */
    private static function lookup(int $companyId, string $name, string $lang): string
    {
        $default = self::DEFAULTS[$name] ?? null;
        $defaultContent = $default[$lang] ?? '';

        // No DB connection available (e.g. unit-testing the renderer in
        // isolation) — short-circuit to defaults.
        global $db;
        if (!isset($db) || !is_object($db) || !isset($db->conn)) {
            return $defaultContent;
        }

        try {
            $stmt = $db->conn->prepare(
                "SELECT content_th, content_en FROM line_message_templates
                 WHERE company_id = ? AND name = ?
                   AND is_active = 1
                   AND deleted_at IS NULL
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $stmt->bind_param('is', $companyId, $name);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $col = ($lang === 'th') ? 'content_th' : 'content_en';
                $val = trim((string)($row[$col] ?? ''));
                if ($val !== '') return $val;
            }
        } catch (\Throwable $e) {
            error_log('LineTemplateRenderer::lookup failed for company ' . $companyId
                . ' name ' . $name . ' — falling back to default: ' . $e->getMessage());
        }

        return $defaultContent;
    }

    /**
     * List the canonical template names so the editor UI (in v6.4 #133
     * Phase 2) can show admins what's available to customize. Read by
     * the templates index page if/when we wire it up.
     */
    public static function listKnownTemplates(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $name => $meta) {
            $out[$name] = [
                'message_type' => $meta['message_type'],
                'placeholders' => $meta['placeholders'] ?? [],
            ];
        }
        return $out;
    }
}
