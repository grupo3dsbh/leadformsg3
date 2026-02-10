<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\Plan;

/**
 * Super Admin Plans Controller
 *
 * CRUD management for subscription plans including pricing, limits,
 * features, and activation toggling.
 */
class PlansController extends Controller
{
    private Plan $planModel;

    public function __construct()
    {
        parent::__construct();
        $this->planModel = new Plan();
    }

    /**
     * List all subscription plans.
     */
    public function index(): string
    {
        $db = $this->db();

        $plans = $db->query(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM tenants WHERE plan_id = p.id) AS tenant_count
               FROM plans p
              ORDER BY p.sort_order ASC, p.created_at ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/plans/index', [
            'plans' => $plans,
        ], 'layouts.admin');
    }

    /**
     * Show the create plan form.
     */
    public function create(): string
    {
        return $this->view('admin/plans/create', [
            'plan' => [
                'name'                 => '',
                'slug'                 => '',
                'description'          => '',
                'price_monthly'        => '',
                'price_yearly'         => '',
                'currency'             => 'USD',
                'max_forms'            => 5,
                'max_entries_per_month' => 100,
                'max_file_storage'     => 100,
                'max_users'            => 1,
                'max_file_size'        => 5,
                'features'             => '[]',
                'integrations'         => '[]',
                'is_featured'          => 0,
                'is_active'            => 1,
                'sort_order'           => 0,
            ],
        ], 'layouts.admin');
    }

    /**
     * Store a newly created plan.
     */
    public function store(): string
    {
        $errors = $this->validate($_POST, [
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'required|numeric|min:0',
            'currency'      => 'required|string|max:3',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/plans/create', [
                'plan'   => $_POST,
                'errors' => $errors,
            ], 'layouts.admin');
        }

        // Check slug uniqueness
        $existing = $this->planModel->findBySlug(trim($_POST['slug']));
        if ($existing) {
            return $this->view('admin/plans/create', [
                'plan'   => $_POST,
                'errors' => ['slug' => 'This slug is already in use.'],
            ], 'layouts.admin');
        }

        $features = $this->parseJsonField($_POST['features'] ?? '[]');
        $integrations = $this->parseJsonField($_POST['integrations'] ?? '[]');

        $this->planModel->create([
            'name'                 => trim($_POST['name']),
            'slug'                 => trim($_POST['slug']),
            'description'          => trim($_POST['description'] ?? ''),
            'price_monthly'        => (float) $_POST['price_monthly'],
            'price_yearly'         => (float) $_POST['price_yearly'],
            'currency'             => strtoupper(trim($_POST['currency'])),
            'max_forms'            => (int) ($_POST['max_forms'] ?? 5),
            'max_entries_per_month' => (int) ($_POST['max_entries_per_month'] ?? 100),
            'max_file_storage'     => (int) ($_POST['max_file_storage'] ?? 100),
            'max_users'            => (int) ($_POST['max_users'] ?? 1),
            'max_file_size'        => (int) ($_POST['max_file_size'] ?? 5),
            'features'             => json_encode($features),
            'integrations'         => json_encode($integrations),
            'is_featured'          => !empty($_POST['is_featured']) ? 1 : 0,
            'is_active'            => !empty($_POST['is_active']) ? 1 : 0,
            'sort_order'           => (int) ($_POST['sort_order'] ?? 0),
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        return $this->redirect('/admin/plans', ['success' => 'Plan created successfully.']);
    }

    /**
     * Show the edit form for a plan.
     */
    public function edit(string $id): string
    {
        $plan = $this->planModel->find((int) $id);

        if (!$plan) {
            return $this->redirect('/admin/plans', ['error' => 'Plan not found.']);
        }

        $tenantCount = (int) $this->db()->prepare(
            "SELECT COUNT(*) FROM tenants WHERE plan_id = :pid"
        )->execute(['pid' => (int) $id]) ? $this->db()->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;

        // Re-query properly
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM tenants WHERE plan_id = :pid");
        $stmt->execute(['pid' => (int) $id]);
        $tenantCount = (int) $stmt->fetchColumn();

        return $this->view('admin/plans/edit', [
            'plan'        => $plan,
            'tenantCount' => $tenantCount,
        ], 'layouts.admin');
    }

    /**
     * Update an existing plan.
     */
    public function update(string $id): string
    {
        $plan = $this->planModel->find((int) $id);

        if (!$plan) {
            return $this->redirect('/admin/plans', ['error' => 'Plan not found.']);
        }

        $errors = $this->validate($_POST, [
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'required|numeric|min:0',
            'currency'      => 'required|string|max:3',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/plans/edit', [
                'plan'   => array_merge($plan, $_POST),
                'errors' => $errors,
            ], 'layouts.admin');
        }

        // Check slug uniqueness excluding current
        $existing = $this->planModel->findBySlug(trim($_POST['slug']));
        if ($existing && (int) $existing['id'] !== (int) $id) {
            return $this->view('admin/plans/edit', [
                'plan'   => array_merge($plan, $_POST),
                'errors' => ['slug' => 'This slug is already in use by another plan.'],
            ], 'layouts.admin');
        }

        $features     = $this->parseJsonField($_POST['features'] ?? $plan['features']);
        $integrations = $this->parseJsonField($_POST['integrations'] ?? $plan['integrations']);

        $this->planModel->update((int) $id, [
            'name'                 => trim($_POST['name']),
            'slug'                 => trim($_POST['slug']),
            'description'          => trim($_POST['description'] ?? ''),
            'price_monthly'        => (float) $_POST['price_monthly'],
            'price_yearly'         => (float) $_POST['price_yearly'],
            'currency'             => strtoupper(trim($_POST['currency'])),
            'max_forms'            => (int) ($_POST['max_forms'] ?? $plan['max_forms']),
            'max_entries_per_month' => (int) ($_POST['max_entries_per_month'] ?? $plan['max_entries_per_month']),
            'max_file_storage'     => (int) ($_POST['max_file_storage'] ?? $plan['max_file_storage']),
            'max_users'            => (int) ($_POST['max_users'] ?? $plan['max_users']),
            'max_file_size'        => (int) ($_POST['max_file_size'] ?? $plan['max_file_size']),
            'features'             => json_encode($features),
            'integrations'         => json_encode($integrations),
            'is_featured'          => !empty($_POST['is_featured']) ? 1 : 0,
            'is_active'            => isset($_POST['is_active']) ? (int) $_POST['is_active'] : (int) $plan['is_active'],
            'sort_order'           => (int) ($_POST['sort_order'] ?? $plan['sort_order']),
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        return $this->redirect('/admin/plans', ['success' => 'Plan updated successfully.']);
    }

    /**
     * Toggle a plan's active status.
     */
    public function toggle(string $id): string
    {
        $plan = $this->planModel->find((int) $id);

        if (!$plan) {
            return $this->redirect('/admin/plans', ['error' => 'Plan not found.']);
        }

        $newStatus = $plan['is_active'] ? 0 : 1;

        $this->planModel->update((int) $id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $statusLabel = $newStatus ? 'activated' : 'deactivated';

        return $this->redirect('/admin/plans', [
            'success' => "Plan \"{$plan['name']}\" has been {$statusLabel}.",
        ]);
    }

    /**
     * Delete a plan.
     *
     * Will not allow deletion if tenants are still subscribed to it.
     */
    public function delete(string $id): string
    {
        $plan = $this->planModel->find((int) $id);

        if (!$plan) {
            return $this->redirect('/admin/plans', ['error' => 'Plan not found.']);
        }

        // Prevent deletion if tenants are using this plan
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM tenants WHERE plan_id = :pid");
        $stmt->execute(['pid' => (int) $id]);
        $tenantCount = (int) $stmt->fetchColumn();

        if ($tenantCount > 0) {
            return $this->redirect('/admin/plans', [
                'error' => "Cannot delete plan \"{$plan['name']}\": {$tenantCount} client(s) are currently subscribed.",
            ]);
        }

        $this->planModel->delete((int) $id);

        return $this->redirect('/admin/plans', [
            'success' => "Plan \"{$plan['name']}\" has been deleted.",
        ]);
    }

    /**
     * Safely parse a JSON field, returning the decoded value or a default.
     */
    private function parseJsonField(string $value): mixed
    {
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    }
}
