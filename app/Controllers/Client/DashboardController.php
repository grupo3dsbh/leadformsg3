<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Dashboard Controller
 *
 * Shows tenant-scoped metrics: form counts, entry counts, recent activity,
 * conversion rates, and top-performing forms.
 */
class DashboardController extends Controller
{
    /**
     * Display the client dashboard with tenant-specific statistics.
     */
    public function index(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        // --- Aggregate counts ---------------------------------------------------

        $stmt = $db->prepare("SELECT COUNT(*) FROM forms WHERE tenant_id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $totalForms = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM forms WHERE tenant_id = :tid AND status = 'published'"
        );
        $stmt->execute(['tid' => $tenantId]);
        $publishedForms = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*)
               FROM entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid"
        );
        $stmt->execute(['tid' => $tenantId]);
        $totalEntries = (int) $stmt->fetchColumn();

        // Entries this month
        $stmt = $db->prepare(
            "SELECT COUNT(*)
               FROM entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
                AND fe.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $stmt->execute(['tid' => $tenantId]);
        $monthlyEntries = (int) $stmt->fetchColumn();

        // Entries today
        $stmt = $db->prepare(
            "SELECT COUNT(*)
               FROM entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
                AND DATE(fe.created_at) = CURDATE()"
        );
        $stmt->execute(['tid' => $tenantId]);
        $todayEntries = (int) $stmt->fetchColumn();

        // --- Entries per day (last 30 days) for chart ----------------------------

        $stmt = $db->prepare(
            "SELECT DATE(fe.created_at) AS day, COUNT(*) AS count
               FROM entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
                AND fe.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY day
              ORDER BY day ASC"
        );
        $stmt->execute(['tid' => $tenantId]);
        $dailyEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // --- Top forms by entry count (all time) --------------------------------

        $stmt = $db->prepare(
            "SELECT f.id, f.title, f.slug, f.status,
                    COUNT(fe.id) AS entry_count,
                    MAX(fe.created_at) AS last_entry_at
               FROM forms f
               LEFT JOIN entries fe ON fe.form_id = f.id
              WHERE f.tenant_id = :tid
              GROUP BY f.id, f.title, f.slug, f.status
              ORDER BY entry_count DESC
              LIMIT 10"
        );
        $stmt->execute(['tid' => $tenantId]);
        $topForms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // --- Conversion rates (views vs. entries) per form ----------------------

        $stmt = $db->prepare(
            "SELECT f.id, f.title, f.views_count, COUNT(fe.id) AS entries,
                    CASE WHEN f.views_count > 0 THEN ROUND((COUNT(fe.id) / f.views_count) * 100, 2) ELSE 0 END AS conversion_rate
               FROM forms f
               LEFT JOIN entries fe ON fe.form_id = f.id
              WHERE f.tenant_id = :tid AND f.views_count > 0
              GROUP BY f.id, f.title, f.views_count
              ORDER BY conversion_rate DESC
              LIMIT 10"
        );
        $stmt->execute(['tid' => $tenantId]);
        $conversionRates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // --- Recent activity (latest entries) -----------------------------------

        $stmt = $db->prepare(
            "SELECT fe.id, fe.status, fe.created_at, fe.ip_address,
                    f.title AS form_title, f.id AS form_id
               FROM entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
              ORDER BY fe.created_at DESC
              LIMIT 15"
        );
        $stmt->execute(['tid' => $tenantId]);
        $recentEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get preview from entry_values for each entry
        foreach ($recentEntries as &$entry) {
            $pvStmt = $db->prepare(
                "SELECT value FROM entry_values WHERE entry_id = :eid LIMIT 1"
            );
            $pvStmt->execute(['eid' => (int) $entry['id']]);
            $firstValue = $pvStmt->fetchColumn();
            $entry['preview'] = $firstValue ? mb_substr(trim((string)$firstValue), 0, 80) : '';
        }
        unset($entry);

        // --- Tenant plan info ---------------------------------------------------

        $tenantData = tenant();
        $plan = null;
        if (!empty($tenantData['plan_id'])) {
            $stmt = $db->prepare("SELECT * FROM plans WHERE id = :pid");
            $stmt->execute(['pid' => (int) $tenantData['plan_id']]);
            $plan = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        $limits = $plan ? [
            'max_forms' => (int) ($plan['max_forms'] ?? 0),
            'max_entries_per_month' => (int) ($plan['max_entries_per_month'] ?? 0),
            'max_users' => (int) ($plan['max_users'] ?? 0),
            'max_file_storage' => (int) ($plan['max_file_storage'] ?? 0),
        ] : [];

        return $this->view('client/dashboard', [
            'totalForms'      => $totalForms,
            'publishedForms'  => $publishedForms,
            'totalEntries'    => $totalEntries,
            'monthlyEntries'  => $monthlyEntries,
            'todayEntries'    => $todayEntries,
            'dailyEntries'    => $dailyEntries,
            'topForms'        => $topForms,
            'conversionRates' => $conversionRates,
            'recentEntries'   => $recentEntries,
            'plan'            => $plan,
            'limits'          => $limits,
            'tenant'          => $tenantData,
        ], 'layouts.client');
    }
}
