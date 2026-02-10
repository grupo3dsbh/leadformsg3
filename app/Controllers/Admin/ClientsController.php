<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\Tenant;
use App\Models\User;

/**
 * Super Admin Clients (Tenants) Controller
 *
 * Full CRUD management for all tenants on the platform including
 * impersonation, suspension, and activation.
 */
class ClientsController extends Controller
{
    private Tenant $tenantModel;
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->tenantModel = new Tenant();
        $this->userModel   = new User();
    }

    /**
     * List all tenants with search, filter, and pagination.
     */
    public function index(): string
    {
        $db = $this->db();

        $search   = trim($_GET['search'] ?? '');
        $status   = $_GET['status'] ?? '';
        $planId   = $_GET['plan_id'] ?? '';
        $sortBy   = $_GET['sort'] ?? 'created_at';
        $sortDir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = 20;
        $offset   = ($page - 1) * $perPage;

        $allowedSorts = ['name', 'created_at', 'is_active', 'slug'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]  = '(t.name LIKE :search OR t.slug LIKE :search OR t.domain LIKE :search)';
            $params['search'] = "%{$search}%";
        }

        if ($status === 'active') {
            $where[] = 't.is_active = 1';
        } elseif ($status === 'suspended') {
            $where[] = 't.is_active = 0';
        }

        if ($planId !== '') {
            $where[]            = 't.plan_id = :plan_id';
            $params['plan_id'] = (int) $planId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count for pagination
        $countSql = "SELECT COUNT(*) FROM tenants t {$whereClause}";
        $stmt     = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Fetch tenants with plan info
        $sql = "SELECT t.*, p.name AS plan_name,
                       (SELECT COUNT(*) FROM forms WHERE tenant_id = t.id) AS form_count,
                       (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) AS user_count,
                       (SELECT COUNT(*) FROM form_entries fe
                          JOIN forms f ON f.id = fe.form_id
                         WHERE f.tenant_id = t.id) AS entry_count
                  FROM tenants t
                  LEFT JOIN plans p ON p.id = t.plan_id
                  {$whereClause}
                  ORDER BY t.{$sortBy} {$sortDir}
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $clients = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Available plans for filter dropdown
        $plans = $db->query("SELECT id, name FROM plans ORDER BY sort_order ASC")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/clients/index', [
            'clients'    => $clients,
            'plans'      => $plans,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'search'     => $search,
            'status'     => $status,
            'planId'     => $planId,
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
        ]);
    }

    /**
     * Show detailed view of a single tenant.
     */
    public function show(string $id): string
    {
        $db     = $this->db();
        $tenant = $this->tenantModel->find((int) $id);

        if (!$tenant) {
            return $this->redirect('/admin/clients', ['error' => 'Client not found.']);
        }

        // Owner info
        $owner = $tenant['owner_id'] ? $this->userModel->find((int) $tenant['owner_id']) : null;

        // Plan info
        $plan = $tenant['plan_id']
            ? $db->query("SELECT * FROM plans WHERE id = " . (int) $tenant['plan_id'])->fetch(\PDO::FETCH_ASSOC)
            : null;

        // Team members
        $users = $db->prepare("SELECT * FROM users WHERE tenant_id = :tid ORDER BY created_at DESC");
        $users->execute(['tid' => (int) $id]);
        $users = $users->fetchAll(\PDO::FETCH_ASSOC);

        // Forms
        $forms = $db->prepare(
            "SELECT f.*, (SELECT COUNT(*) FROM form_entries WHERE form_id = f.id) AS entry_count
               FROM forms f WHERE f.tenant_id = :tid ORDER BY f.created_at DESC"
        );
        $forms->execute(['tid' => (int) $id]);
        $forms = $forms->fetchAll(\PDO::FETCH_ASSOC);

        // Recent entries
        $recentEntries = $db->prepare(
            "SELECT fe.*, f.title AS form_title
               FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid
              ORDER BY fe.created_at DESC
              LIMIT 20"
        );
        $recentEntries->execute(['tid' => (int) $id]);
        $recentEntries = $recentEntries->fetchAll(\PDO::FETCH_ASSOC);

        // Subscription
        $subscription = $db->prepare(
            "SELECT * FROM subscriptions WHERE tenant_id = :tid AND status = 'active' LIMIT 1"
        );
        $subscription->execute(['tid' => (int) $id]);
        $subscription = $subscription->fetch(\PDO::FETCH_ASSOC) ?: null;

        // Payment history
        $payments = $db->prepare(
            "SELECT * FROM payments WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT 20"
        );
        $payments->execute(['tid' => (int) $id]);
        $payments = $payments->fetchAll(\PDO::FETCH_ASSOC);

        // Audit log
        $auditLogs = $db->prepare(
            "SELECT * FROM audit_logs WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT 20"
        );
        $auditLogs->execute(['tid' => (int) $id]);
        $auditLogs = $auditLogs->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/clients/show', [
            'tenant'        => $tenant,
            'owner'         => $owner,
            'plan'          => $plan,
            'users'         => $users,
            'forms'         => $forms,
            'recentEntries' => $recentEntries,
            'subscription'  => $subscription,
            'payments'      => $payments,
            'auditLogs'     => $auditLogs,
        ]);
    }

    /**
     * Show the edit form for a tenant.
     */
    public function edit(string $id): string
    {
        $tenant = $this->tenantModel->find((int) $id);

        if (!$tenant) {
            return $this->redirect('/admin/clients', ['error' => 'Client not found.']);
        }

        $plans = $this->db()->query(
            "SELECT id, name FROM plans WHERE is_active = 1 ORDER BY sort_order ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/clients/edit', [
            'tenant' => $tenant,
            'plans'  => $plans,
        ]);
    }

    /**
     * Process the tenant update.
     */
    public function update(string $id): string
    {
        $tenant = $this->tenantModel->find((int) $id);

        if (!$tenant) {
            return $this->redirect('/admin/clients', ['error' => 'Client not found.']);
        }

        $errors = $this->validate($_POST, [
            'name'    => 'required|string|max:255',
            'slug'    => 'required|string|max:100',
            'domain'  => 'nullable|string|max:255',
            'plan_id' => 'required|integer',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/clients/edit', [
                'tenant' => array_merge($tenant, $_POST),
                'plans'  => $this->db()->query("SELECT id, name FROM plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(\PDO::FETCH_ASSOC),
                'errors' => $errors,
            ]);
        }

        // Check slug uniqueness
        $existing = $this->tenantModel->findBySlug($_POST['slug']);
        if ($existing && (int) $existing['id'] !== (int) $id) {
            return $this->view('admin/clients/edit', [
                'tenant' => array_merge($tenant, $_POST),
                'plans'  => $this->db()->query("SELECT id, name FROM plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(\PDO::FETCH_ASSOC),
                'errors' => ['slug' => 'This slug is already in use by another client.'],
            ]);
        }

        $this->tenantModel->update((int) $id, [
            'name'               => trim($_POST['name']),
            'slug'               => trim($_POST['slug']),
            'domain'             => trim($_POST['domain'] ?? '') ?: null,
            'plan_id'            => (int) $_POST['plan_id'],
            'storage_limit_bytes' => isset($_POST['storage_limit_mb'])
                ? (int) $_POST['storage_limit_mb'] * 1048576
                : (int) $tenant['storage_limit_bytes'],
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit('client_updated', 'tenants', (int) $id, [
            'admin_id' => auth()['id'],
            'changes'  => $_POST,
        ]);

        return $this->redirect("/admin/clients/{$id}", ['success' => 'Client updated successfully.']);
    }

    /**
     * Suspend a tenant account.
     */
    public function suspend(string $id): string
    {
        $tenant = $this->tenantModel->find((int) $id);

        if (!$tenant) {
            return $this->redirect('/admin/clients', ['error' => 'Client not found.']);
        }

        $this->tenantModel->update((int) $id, [
            'is_active'  => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit('client_suspended', 'tenants', (int) $id, [
            'admin_id' => auth()['id'],
            'reason'   => trim($_POST['reason'] ?? 'No reason provided'),
        ]);

        return $this->redirect("/admin/clients/{$id}", ['success' => 'Client has been suspended.']);
    }

    /**
     * Activate a suspended tenant account.
     */
    public function activate(string $id): string
    {
        $tenant = $this->tenantModel->find((int) $id);

        if (!$tenant) {
            return $this->redirect('/admin/clients', ['error' => 'Client not found.']);
        }

        $this->tenantModel->update((int) $id, [
            'is_active'  => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit('client_activated', 'tenants', (int) $id, [
            'admin_id' => auth()['id'],
        ]);

        return $this->redirect("/admin/clients/{$id}", ['success' => 'Client has been activated.']);
    }

    /**
     * Impersonate a client: log in as their owner user.
     *
     * Stores the original super admin session so it can be restored later.
     */
    public function loginAs(string $id): string
    {
        $tenant = $this->tenantModel->find((int) $id);

        if (!$tenant) {
            return $this->redirect('/admin/clients', ['error' => 'Client not found.']);
        }

        // Find the tenant owner
        $owner = $tenant['owner_id']
            ? $this->userModel->find((int) $tenant['owner_id'])
            : null;

        if (!$owner) {
            // Fallback: find any admin user on this tenant
            $stmt = $this->db()->prepare(
                "SELECT * FROM users WHERE tenant_id = :tid AND role IN ('admin','owner') ORDER BY id ASC LIMIT 1"
            );
            $stmt->execute(['tid' => (int) $id]);
            $owner = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        if (!$owner) {
            return $this->redirect("/admin/clients/{$id}", [
                'error' => 'No owner or admin user found for this client.',
            ]);
        }

        // Preserve original admin session for returning later
        $_SESSION['impersonating_from'] = [
            'user_id'    => auth()['id'],
            'user_email' => auth()['email'],
            'return_url' => "/admin/clients/{$id}",
        ];

        // Set the session to the target user
        $_SESSION['user_id']   = $owner['id'];
        $_SESSION['tenant_id'] = $tenant['id'];

        $this->logAudit('client_impersonated', 'tenants', (int) $id, [
            'admin_id'       => $_SESSION['impersonating_from']['user_id'],
            'target_user_id' => $owner['id'],
        ]);

        return $this->redirect('/client/dashboard');
    }

    /**
     * Delete a tenant and all associated data.
     */
    public function delete(string $id): string
    {
        $tenant = $this->tenantModel->find((int) $id);

        if (!$tenant) {
            return $this->redirect('/admin/clients', ['error' => 'Client not found.']);
        }

        $db = $this->db();

        // Delete in correct order to respect foreign keys
        $tenantId = (int) $id;

        // Delete form entries for all tenant forms
        $db->prepare(
            "DELETE fe FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE f.tenant_id = :tid"
        )->execute(['tid' => $tenantId]);

        // Delete forms
        $db->prepare("DELETE FROM forms WHERE tenant_id = :tid")->execute(['tid' => $tenantId]);

        // Delete integrations
        $db->prepare("DELETE FROM integrations WHERE tenant_id = :tid")->execute(['tid' => $tenantId]);

        // Delete webhooks
        $db->prepare("DELETE FROM webhooks WHERE tenant_id = :tid")->execute(['tid' => $tenantId]);

        // Delete users
        $db->prepare("DELETE FROM users WHERE tenant_id = :tid")->execute(['tid' => $tenantId]);

        // Delete subscriptions
        $db->prepare("DELETE FROM subscriptions WHERE tenant_id = :tid")->execute(['tid' => $tenantId]);

        // Delete payments
        $db->prepare("DELETE FROM payments WHERE tenant_id = :tid")->execute(['tid' => $tenantId]);

        // Delete audit logs
        $db->prepare("DELETE FROM audit_logs WHERE tenant_id = :tid")->execute(['tid' => $tenantId]);

        // Delete the tenant itself
        $this->tenantModel->delete($tenantId);

        $this->logAudit('client_deleted', 'tenants', $tenantId, [
            'admin_id'    => auth()['id'],
            'tenant_name' => $tenant['name'],
        ]);

        return $this->redirect('/admin/clients', ['success' => "Client \"{$tenant['name']}\" has been deleted."]);
    }

    /**
     * Write an entry to the audit log.
     */
    private function logAudit(string $action, string $entity, int $entityId, array $metadata = []): void
    {
        $this->db()->prepare(
            "INSERT INTO audit_logs (user_id, tenant_id, action, entity_type, entity_id, metadata, ip_address, created_at)
             VALUES (:uid, :tid, :action, :entity, :eid, :meta, :ip, NOW())"
        )->execute([
            'uid'    => auth()['id'] ?? null,
            'tid'    => null,
            'action' => $action,
            'entity' => $entity,
            'eid'    => $entityId,
            'meta'   => json_encode($metadata),
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }
}
