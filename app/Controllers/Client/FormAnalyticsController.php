<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Form Analytics Controller
 */
class FormAnalyticsController extends Controller
{
    public function index(string $id): string
    {
        $db   = $this->db();
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/dashboard/forms', ['error' => 'Formulario nao encontrado.']);
        }

        // Total entries
        $stmt = $db->prepare("SELECT COUNT(*) FROM entries WHERE form_id = :fid");
        $stmt->execute(['fid' => (int) $id]);
        $totalEntries = (int) $stmt->fetchColumn();

        // Views and conversion rate
        $views = (int) ($form['views_count'] ?? 0);
        $conversionRate = $views > 0 ? round(($totalEntries / $views) * 100, 2) : 0;
        $completionRate = (float) ($form['completion_rate'] ?? 0);

        // Daily entries (last 30 days)
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS count
               FROM entries WHERE form_id = :fid
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY day ORDER BY day ASC"
        );
        $stmt->execute(['fid' => (int) $id]);
        $dailyEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Daily views from form_analytics
        $stmt = $db->prepare(
            "SELECT date AS day, views, starts, completions, abandons
               FROM form_analytics WHERE form_id = :fid
                AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              ORDER BY date ASC"
        );
        $stmt->execute(['fid' => (int) $id]);
        $dailyAnalytics = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Device breakdown
        $stmt = $db->prepare(
            "SELECT device_type, COUNT(*) AS count
               FROM entries WHERE form_id = :fid AND device_type IS NOT NULL
              GROUP BY device_type ORDER BY count DESC"
        );
        $stmt->execute(['fid' => (int) $id]);
        $deviceBreakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Status breakdown
        $stmt = $db->prepare(
            "SELECT status, COUNT(*) AS count
               FROM entries WHERE form_id = :fid
              GROUP BY status ORDER BY count DESC"
        );
        $stmt->execute(['fid' => (int) $id]);
        $statusBreakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Average duration
        $stmt = $db->prepare(
            "SELECT AVG(duration_seconds) AS avg_duration
               FROM entries WHERE form_id = :fid AND duration_seconds IS NOT NULL"
        );
        $stmt->execute(['fid' => (int) $id]);
        $avgDuration = (int) ($stmt->fetchColumn() ?: 0);

        // Top referrers
        $stmt = $db->prepare(
            "SELECT referrer, COUNT(*) AS count
               FROM entries WHERE form_id = :fid AND referrer IS NOT NULL AND referrer != ''
              GROUP BY referrer ORDER BY count DESC LIMIT 10"
        );
        $stmt->execute(['fid' => (int) $id]);
        $topReferrers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Top countries
        $stmt = $db->prepare(
            "SELECT country, COUNT(*) AS count
               FROM entries WHERE form_id = :fid AND country IS NOT NULL AND country != ''
              GROUP BY country ORDER BY count DESC LIMIT 10"
        );
        $stmt->execute(['fid' => (int) $id]);
        $topCountries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/forms/analytics', [
            'form'            => $form,
            'totalEntries'    => $totalEntries,
            'views'           => $views,
            'conversionRate'  => $conversionRate,
            'completionRate'  => $completionRate,
            'dailyEntries'    => $dailyEntries,
            'dailyAnalytics'  => $dailyAnalytics,
            'deviceBreakdown' => $deviceBreakdown,
            'statusBreakdown' => $statusBreakdown,
            'avgDuration'     => $avgDuration,
            'topReferrers'    => $topReferrers,
            'topCountries'    => $topCountries,
        ], 'layouts.client');
    }

    private function findTenantForm(int $formId): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM forms WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $formId, 'tid' => (int) tenant()['id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
