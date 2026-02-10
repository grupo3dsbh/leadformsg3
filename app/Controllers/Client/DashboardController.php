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

        $totalForms = (int) $db->prepare(
            "SELECT COUNT(*) FROM forms WHERE tenant_id = :tid"
        )->execute(['tid' => $tenantId]) ? 0 : 0;

        $stmt = $db->prepare("SELECT COUNT(*) FROM forms WHERE tenant_id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $totalForms = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM forms WHERE tenant_id = :tid AND is_published = 1"
        );
        $stmt->execute(['tid' => $tenantId]);
        $publishedForms = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*)
               FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid"
        );
        $stmt->execute(['tid' => $tenantId]);
        $totalEntries = (int) $stmt->fetchColumn();

        // Entries this month
        $stmt = $db->prepare(
            "SELECT COUNT(*)
               FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
                AND fe.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $stmt->execute(['tid' => $tenantId]);
        $monthlyEntries = (int) $stmt->fetchColumn();

        // Entries today
        $stmt = $db->prepare(
            "SELECT COUNT(*)
               FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
                AND DATE(fe.created_at) = CURDATE()"
        );
        $stmt->execute(['tid' => $tenantId]);
        $todayEntries = (int) $stmt->fetchColumn();

        // --- Entries per day (last 30 days) for chart ----------------------------

        $stmt = $db->prepare(
            "SELECT DATE(fe.created_at) AS day, COUNT(*) AS count
               FROM form_entries fe
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
            "SELECT f.id, f.title, f.slug, f.is_published,
                    COUNT(fe.id) AS entry_count,
                    MAX(fe.created_at) AS last_entry_at
               FROM forms f
               LEFT JOIN form_entries fe ON fe.form_id = f.id
              WHERE f.tenant_id = :tid
              GROUP BY f.id, f.title, f.slug, f.is_published
              ORDER BY entry_count DESC
              LIMIT 10"
        );
        $stmt->execute(['tid' => $tenantId]);
        $topForms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // --- Conversion rates (views vs. entries) per form ----------------------

        $stmt = $db->prepare(
            "SELECT f.id, f.title, f.views, COUNT(fe.id) AS entries,
                    CASE WHEN f.views > 0 THEN ROUND((COUNT(fe.id) / f.views) * 100, 2) ELSE 0 END AS conversion_rate
               FROM forms f
               LEFT JOIN form_entries fe ON fe.form_id = f.id
              WHERE f.tenant_id = :tid AND f.views > 0
              GROUP BY f.id, f.title, f.views
              ORDER BY conversion_rate DESC
              LIMIT 10"
        );
        $stmt->execute(['tid' => $tenantId]);
        $conversionRates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // --- Recent activity (latest entries) -----------------------------------

        $stmt = $db->prepare(
            "SELECT fe.id, fe.data, fe.created_at, fe.ip_address,
                    f.title AS form_title, f.id AS form_id
               FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
              ORDER BY fe.created_at DESC
              LIMIT 15"
        );
        $stmt->execute(['tid' => $tenantId]);
        $recentEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decode data for preview
        foreach ($recentEntries as &$entry) {
            $decoded = json_decode($entry['data'] ?? '{}', true) ?: [];
            // Show first meaningful field value as preview
            $entry['preview'] = '';
            foreach ($decoded as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $entry['preview'] = mb_substr(trim($value), 0, 80);
                    break;
                }
            }
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

        $limits = $plan ? (json_decode($plan['limits'] ?? '{}', true) ?: []) : [];

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
        ]);
    }
}
