<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * Super Admin Dashboard Controller
 *
 * Provides the main overview for platform administrators with key metrics,
 * recent activity, and growth data for the entire SaaS platform.
 */
class DashboardController extends Controller
{
    /**
     * Display the super admin dashboard with platform-wide statistics.
     *
     * Shows total clients, forms, entries, revenue, recent activity,
     * and data for growth charts.
     */
    public function index(): string
    {
        $db = $this->db();

        // --- Aggregate counts ---------------------------------------------------

        $totalClients = (int) $db->query(
            "SELECT COUNT(*) FROM tenants"
        )->fetchColumn();

        $activeClients = (int) $db->query(
            "SELECT COUNT(*) FROM tenants WHERE status = 'active'"
        )->fetchColumn();

        $totalForms = (int) $db->query(
            "SELECT COUNT(*) FROM forms"
        )->fetchColumn();

        $totalEntries = (int) $db->query(
            "SELECT COUNT(*) FROM form_entries"
        )->fetchColumn();

        $totalUsers = (int) $db->query(
            "SELECT COUNT(*) FROM users"
        )->fetchColumn();

        // --- Revenue metrics ----------------------------------------------------

        $monthlyRevenue = (float) $db->query(
            "SELECT COALESCE(SUM(amount), 0)
               FROM payments
              WHERE status = 'completed'
                AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        )->fetchColumn();

        $yearlyRevenue = (float) $db->query(
            "SELECT COALESCE(SUM(amount), 0)
               FROM payments
              WHERE status = 'completed'
                AND created_at >= DATE_FORMAT(NOW(), '%Y-01-01')"
        )->fetchColumn();

        // --- Growth: new clients per month (last 12 months) ---------------------

        $clientGrowth = $db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS count
               FROM tenants
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY month
              ORDER BY month ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // --- Growth: new entries per month (last 12 months) ---------------------

        $entryGrowth = $db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS count
               FROM form_entries
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY month
              ORDER BY month ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // --- Revenue growth per month (last 12 months) --------------------------

        $revenueGrowth = $db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COALESCE(SUM(amount), 0) AS total
               FROM payments
              WHERE status = 'completed'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY month
              ORDER BY month ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // --- Recent activity ----------------------------------------------------

        $recentClients = $db->query(
            "SELECT id, name, slug, status, created_at
               FROM tenants
              ORDER BY created_at DESC
              LIMIT 10"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $recentEntries = $db->query(
            "SELECT fe.id, fe.form_id, fe.created_at,
                    f.title AS form_title,
                    t.name  AS tenant_name
               FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
               JOIN tenants t ON t.id = f.tenant_id
              ORDER BY fe.created_at DESC
              LIMIT 10"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // --- Plan distribution --------------------------------------------------

        $planDistribution = $db->query(
            "SELECT p.name AS plan_name,
                    COUNT(t.id) AS tenant_count
               FROM plans p
               LEFT JOIN tenants t ON t.plan_id = p.id
              GROUP BY p.id, p.name
              ORDER BY tenant_count DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // --- Today's snapshot ---------------------------------------------------

        $todayEntries = (int) $db->query(
            "SELECT COUNT(*) FROM form_entries WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();

        $todaySignups = (int) $db->query(
            "SELECT COUNT(*) FROM tenants WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();

        return $this->view('admin.dashboard', [
            'totalClients'     => $totalClients,
            'activeClients'    => $activeClients,
            'totalForms'       => $totalForms,
            'totalEntries'     => $totalEntries,
            'totalUsers'       => $totalUsers,
            'monthlyRevenue'   => $monthlyRevenue,
            'yearlyRevenue'    => $yearlyRevenue,
            'clientGrowth'     => $clientGrowth,
            'entryGrowth'      => $entryGrowth,
            'revenueGrowth'    => $revenueGrowth,
            'recentClients'    => $recentClients,
            'recentEntries'    => $recentEntries,
            'planDistribution' => $planDistribution,
            'todayEntries'     => $todayEntries,
            'todaySignups'     => $todaySignups,
        ], 'layouts.admin');
    }
}
