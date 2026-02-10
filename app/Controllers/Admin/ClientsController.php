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

        $allowedSorts = ['name', 'created_at', 'status', 'slug'];
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
            $where[] = "t.status = 'active'";
        } elseif ($status === 'suspended') {
            $where[] = "t.status = 'suspended'";
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
                       (SELECT COUNT(*) FROM entries fe
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
        ], 'layouts.admin');
    }

    /**
     * Show the create form for a new tenant.
     */
    public function create(): string
    {
        $plans = $this->db()->query(
            "SELECT id, name FROM plans WHERE is_active = 1 ORDER BY sort_order ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/clients/create', [
            'plans' => $plans,
        ], 'layouts.admin');
    }

    /**
     * Store a new tenant.
     */
    public function store(): string
    {
        $errors = $this->validate($_POST, [
            'name'    => 'required|string|max:255',
            'slug'    => 'required|string|max:100',
            'email'   => 'required|email',
            'plan_id' => 'required|integer',
        ]);

        if (!empty($errors)) {
            $plans = $this->db()->query(
                "SELECT id, name FROM plans WHERE is_active = 1 ORDER BY sort_order ASC"
            )->fetchAll(\PDO::FETCH_ASSOC);

            return $this->view('admin/clients/create', [
                'plans'  => $plans,
                'errors' => $errors,
                'old'    => $_POST,
            ], 'layouts.admin');
        }

        // Check slug uniqueness
        $existing = $this->tenantModel->findBy('slug', $_POST['slug']);
        if ($existing) {
            $plans = $this->db()->query(
                "SELECT id, name FROM plans WHERE is_active = 1 ORDER BY sort_order ASC"
            )->fetchAll(\PDO::FETCH_ASSOC);

            return $this->view('admin/clients/create', [
                'plans'  => $plans,
                'errors' => ['slug' => ['This slug is already in use.']],
                'old'    => $_POST,
            ], 'layouts.admin');
        }

        $db = $this->db();

        $db->prepare(
            "INSERT INTO tenants (name, slug, email, plan_id, status, created_at, updated_at)
             VALUES (:name, :slug, :email, :plan_id, 'active', NOW(), NOW())"
        )->execute([
            'name'    => trim($_POST['name']),
            'slug'    => trim($_POST['slug']),
            'email'   => trim($_POST['email']),
            'plan_id' => (int) $_POST['plan_id'],
        ]);

        $tenantId = $db->lastInsertId();

        // Create a default admin user for this tenant
        $defaultPassword = password_hash('password', PASSWORD_BCRYPT);
        $db->prepare(
            "INSERT INTO users (tenant_id, name, email, password, role, status, locale, timezone, email_verified_at, created_at, updated_at)
             VALUES (:tid, :name, :email, :password, 'admin', 'active', 'pt_BR', 'America/Sao_Paulo', NOW(), NOW(), NOW())"
        )->execute([
            'tid'      => (int) $tenantId,
            'name'     => trim($_POST['name']),
            'email'    => trim($_POST['email']),
            'password' => $defaultPassword,
        ]);

        return $this->redirect('/admin/clients', ['success' => 'Cliente criado com sucesso. Usuario admin criado com email: ' . trim($_POST['email']) . ' / senha: password']);
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

        // Owner info (tenants table has no owner_id column)
        $owner = null;

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
            "SELECT f.*, (SELECT COUNT(*) FROM entries WHERE form_id = f.id) AS entry_count
               FROM forms f WHERE f.tenant_id = :tid ORDER BY f.created_at DESC"
        );
        $forms->execute(['tid' => (int) $id]);
        $forms = $forms->fetchAll(\PDO::FETCH_ASSOC);

        // Recent entries
        $recentEntries = $db->prepare(
            "SELECT fe.*, f.title AS form_title
               FROM entries fe
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
        ], 'layouts.admin');
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
        ], 'layouts.admin');
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
            ], 'layouts.admin');
        }

        // Check slug uniqueness
        $existing = $this->tenantModel->findBySlug($_POST['slug']);
        if ($existing && (int) $existing['id'] !== (int) $id) {
            return $this->view('admin/clients/edit', [
                'tenant' => array_merge($tenant, $_POST),
                'plans'  => $this->db()->query("SELECT id, name FROM plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(\PDO::FETCH_ASSOC),
                'errors' => ['slug' => 'This slug is already in use by another client.'],
            ], 'layouts.admin');
        }

        $this->tenantModel->update((int) $id, [
            'name'               => trim($_POST['name']),
            'slug'               => trim($_POST['slug']),
            'domain'             => trim($_POST['domain'] ?? '') ?: null,
            'plan_id'            => (int) $_POST['plan_id'],
            'max_storage' => isset($_POST['storage_limit_mb'])
                ? (int) $_POST['storage_limit_mb'] * 1048576
                : (int) $tenant['max_storage'],
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
            'status'     => 'suspended',
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
            'status'     => 'active',
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

        // Find the tenant admin/owner user directly (tenants table has no owner_id column)
        $stmt = $this->db()->prepare(
            "SELECT * FROM users WHERE tenant_id = :tid ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute(['tid' => (int) $id]);
        $owner = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$owner) {
            // Auto-create an admin user for this tenant so we can log in
            $db = $this->db();
            $email = $tenant['email'] ?? ($tenant['slug'] . '@leadform.local');
            $defaultPassword = password_hash('password', PASSWORD_BCRYPT);

            $db->prepare(
                "INSERT INTO users (tenant_id, name, email, password, role, status, locale, timezone, email_verified_at, created_at, updated_at)
                 VALUES (:tid, :name, :email, :password, 'admin', 'active', 'pt_BR', 'America/Sao_Paulo', NOW(), NOW(), NOW())"
            )->execute([
                'tid'      => (int) $id,
                'name'     => $tenant['name'],
                'email'    => $email,
                'password' => $defaultPassword,
            ]);

            // Re-fetch the newly created user
            $stmt = $db->prepare("SELECT * FROM users WHERE tenant_id = :tid ORDER BY id DESC LIMIT 1");
            $stmt->execute(['tid' => (int) $id]);
            $owner = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$owner) {
                return $this->redirect("/admin/clients/{$id}", [
                    'error' => 'Nao foi possivel criar usuario para este cliente.',
                ]);
            }
        }

        // Preserve original admin session for returning later
        $_SESSION['impersonating_from'] = [
            'user_id'    => auth()['id'],
            'user_email' => auth()['email'],
            'return_url' => "/admin/clients/{$id}",
        ];

        // Set the session to the target user (both user_id and full user array)
        $_SESSION['user_id'] = $owner['id'];
        $_SESSION['user']    = $owner;
        $_SESSION['tenant_id'] = $tenant['id'];

        $this->logAudit('client_impersonated', 'tenants', (int) $id, [
            'admin_id'       => $_SESSION['impersonating_from']['user_id'],
            'target_user_id' => $owner['id'],
        ]);

        return $this->redirect('/dashboard');
    }

    /**
     * Stop impersonating a client and return to super admin session.
     */
    public function stopImpersonate(): string
    {
        if (!isset($_SESSION['impersonating_from'])) {
            return $this->redirect('/admin');
        }

        $original = $_SESSION['impersonating_from'];
        $returnUrl = $original['return_url'] ?? '/admin/clients';

        // Restore the original admin session
        $_SESSION['user_id'] = $original['user_id'];
        unset($_SESSION['impersonating_from'], $_SESSION['tenant_id']);

        return $this->redirect($returnUrl, ['success' => 'Voce voltou a sua conta de administrador.']);
    }

    /**
     * Delete a tenant and all associated data.
     */
    public function destroy(string $id): string
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
            "DELETE fe FROM entries fe
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
            "INSERT INTO audit_logs (user_id, tenant_id, action, entity_type, entity_id, new_values, ip_address, created_at)
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
