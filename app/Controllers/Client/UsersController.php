<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;
use App\Models\User;
use App\Models\Tenant;

/**
 * Client Users (Team) Controller
 *
 * Manages team members for the current tenant: inviting, editing, removing,
 * and managing role-based permissions.
 */
class UsersController extends Controller
{
    private User $userModel;
    private Tenant $tenantModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel   = new User();
        $this->tenantModel = new Tenant();
    }

    /**
     * List team users belonging to the current tenant.
     */
    public function index(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $search = trim($_GET['search'] ?? '');
        $role   = $_GET['role'] ?? '';
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = ['u.tenant_id = :tid'];
        $params = ['tid' => $tenantId];

        if ($search !== '') {
            $where[]          = '(u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)';
            $params['search'] = "%{$search}%";
        }

        if ($role !== '') {
            $where[]        = 'u.role = :role';
            $params['role'] = $role;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM users u {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT u.*
                  FROM users u
                  {$whereClause}
                  ORDER BY u.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/users/index', [
            'users'      => $users,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'search'     => $search,
            'role'       => $role,
        ]);
    }

    /**
     * Show the invite/create user form.
     */
    public function create(): string
    {
        $tenantData = tenant();

        if (!$this->tenantModel->canAddUser($tenantData)) {
            return $this->redirect('/client/users', [
                'error' => 'You have reached the maximum number of team members allowed by your plan.',
            ]);
        }

        return $this->view('client/users/create', [
            'user'             => [
                'email'      => '',
                'first_name' => '',
                'last_name'  => '',
                'role'       => 'member',
            ],
            'availableRoles'   => $this->getAvailableRoles(),
            'allPermissions'   => $this->getAllPermissions(),
        ]);
    }

    /**
     * Create a new team user or send an invitation.
     */
    public function store(): string
    {
        $tenantData = tenant();

        if (!$this->tenantModel->canAddUser($tenantData)) {
            return $this->redirect('/client/users', [
                'error' => 'User limit reached for your current plan.',
            ]);
        }

        $errors = $this->validate($_POST, [
            'email'      => 'required|email',
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'role'       => 'required|in:admin,editor,member',
        ]);

        if (!empty($errors)) {
            return $this->view('client/users/create', [
                'user'             => $_POST,
                'errors'           => $errors,
                'availableRoles'   => $this->getAvailableRoles(),
                'allPermissions'   => $this->getAllPermissions(),
            ]);
        }

        // Check if email already exists in this tenant
        $existingStmt = $this->db()->prepare(
            "SELECT id FROM users WHERE email = :email AND tenant_id = :tid"
        );
        $existingStmt->execute([
            'email' => trim($_POST['email']),
            'tid'   => (int) $tenantData['id'],
        ]);

        if ($existingStmt->fetch()) {
            return $this->view('client/users/create', [
                'user'             => $_POST,
                'errors'           => ['email' => 'A user with this email already exists in your team.'],
                'availableRoles'   => $this->getAvailableRoles(),
                'allPermissions'   => $this->getAllPermissions(),
            ]);
        }

        // Build permissions array
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions'])
            ? array_values($_POST['permissions'])
            : [];

        // Generate a random temporary password
        $tempPassword = bin2hex(random_bytes(16));

        $this->db()->prepare(
            "INSERT INTO users (tenant_id, email, password_hash, first_name, last_name, role, permissions, is_active, created_at, updated_at)
             VALUES (:tid, :email, :pass, :fname, :lname, :role, :perms, 1, NOW(), NOW())"
        )->execute([
            'tid'   => (int) $tenantData['id'],
            'email' => trim($_POST['email']),
            'pass'  => password_hash($tempPassword, PASSWORD_ARGON2ID),
            'fname' => trim($_POST['first_name']),
            'lname' => trim($_POST['last_name']),
            'role'  => $_POST['role'],
            'perms' => json_encode($permissions),
        ]);

        // In a production app, send an invitation email with the temp password
        // or a password-reset link. For now, we redirect with a message.

        return $this->redirect('/client/users', [
            'success' => "User \"{$_POST['email']}\" has been created and invited to the team.",
        ]);
    }

    /**
     * Show the edit form for a team user.
     */
    public function edit(string $id): string
    {
        $user = $this->findTenantUser((int) $id);

        if (!$user) {
            return $this->redirect('/client/users', ['error' => 'User not found.']);
        }

        $user['decoded_permissions'] = json_decode($user['permissions'] ?? '[]', true) ?: [];

        return $this->view('client/users/edit', [
            'user'             => $user,
            'availableRoles'   => $this->getAvailableRoles(),
            'allPermissions'   => $this->getAllPermissions(),
        ]);
    }

    /**
     * Update a team user.
     */
    public function update(string $id): string
    {
        $user = $this->findTenantUser((int) $id);

        if (!$user) {
            return $this->redirect('/client/users', ['error' => 'User not found.']);
        }

        $errors = $this->validate($_POST, [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'role'       => 'required|in:admin,editor,member',
        ]);

        if (!empty($errors)) {
            return $this->view('client/users/edit', [
                'user'             => array_merge($user, $_POST),
                'errors'           => $errors,
                'availableRoles'   => $this->getAvailableRoles(),
                'allPermissions'   => $this->getAllPermissions(),
            ]);
        }

        // Prevent demoting yourself
        if ((int) $user['id'] === (int) auth()['id'] && $_POST['role'] !== $user['role']) {
            return $this->view('client/users/edit', [
                'user'             => array_merge($user, $_POST),
                'errors'           => ['role' => 'You cannot change your own role.'],
                'availableRoles'   => $this->getAvailableRoles(),
                'allPermissions'   => $this->getAllPermissions(),
            ]);
        }

        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions'])
            ? array_values($_POST['permissions'])
            : [];

        $updateData = [
            'first_name'  => trim($_POST['first_name']),
            'last_name'   => trim($_POST['last_name']),
            'role'        => $_POST['role'],
            'permissions' => json_encode($permissions),
            'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        // Update password if provided
        if (!empty($_POST['password']) && strlen($_POST['password']) >= 8) {
            $updateData['password_hash'] = password_hash($_POST['password'], PASSWORD_ARGON2ID);
        }

        $this->userModel->update((int) $id, $updateData);

        return $this->redirect('/client/users', [
            'success' => 'User updated successfully.',
        ]);
    }

    /**
     * Remove a user from the team.
     */
    public function delete(string $id): string
    {
        $user = $this->findTenantUser((int) $id);

        if (!$user) {
            return $this->redirect('/client/users', ['error' => 'User not found.']);
        }

        // Prevent self-deletion
        if ((int) $user['id'] === (int) auth()['id']) {
            return $this->redirect('/client/users', [
                'error' => 'You cannot remove yourself from the team.',
            ]);
        }

        // Prevent deleting the tenant owner
        $tenantData = tenant();
        if ((int) $user['id'] === (int) ($tenantData['owner_id'] ?? 0)) {
            return $this->redirect('/client/users', [
                'error' => 'The account owner cannot be removed.',
            ]);
        }

        $this->userModel->delete((int) $id);

        return $this->redirect('/client/users', [
            'success' => "User \"{$user['email']}\" has been removed from the team.",
        ]);
    }

    /**
     * Manage permissions for a specific user.
     */
    public function permissions(string $id): string
    {
        $user = $this->findTenantUser((int) $id);

        if (!$user) {
            return $this->redirect('/client/users', ['error' => 'User not found.']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $permissions = isset($_POST['permissions']) && is_array($_POST['permissions'])
                ? array_values($_POST['permissions'])
                : [];

            $this->userModel->update((int) $id, [
                'permissions' => json_encode($permissions),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            return $this->redirect("/client/users/{$id}/permissions", [
                'success' => 'Permissions updated.',
            ]);
        }

        $currentPermissions = json_decode($user['permissions'] ?? '[]', true) ?: [];

        return $this->view('client/users/permissions', [
            'user'               => $user,
            'currentPermissions' => $currentPermissions,
            'allPermissions'     => $this->getAllPermissions(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Find a user that belongs to the current tenant.
     */
    private function findTenantUser(int $userId): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM users WHERE id = :id AND tenant_id = :tid"
        );
        $stmt->execute([
            'id'  => $userId,
            'tid' => (int) tenant()['id'],
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get available roles for assignment.
     */
    private function getAvailableRoles(): array
    {
        return [
            'admin'  => 'Admin - Full access to all settings and features',
            'editor' => 'Editor - Can create and manage forms and entries',
            'member' => 'Member - View-only access to forms and entries',
        ];
    }

    /**
     * Get all assignable permissions grouped by category.
     */
    private function getAllPermissions(): array
    {
        return [
            'Forms' => [
                'forms.create'  => 'Create forms',
                'forms.edit'    => 'Edit forms',
                'forms.delete'  => 'Delete forms',
                'forms.publish' => 'Publish/unpublish forms',
            ],
            'Entries' => [
                'entries.view'   => 'View entries',
                'entries.delete' => 'Delete entries',
                'entries.export' => 'Export entries',
            ],
            'Users' => [
                'users.view'   => 'View team members',
                'users.manage' => 'Manage team members',
            ],
            'Integrations' => [
                'integrations.view'   => 'View integrations',
                'integrations.manage' => 'Manage integrations',
            ],
            'Settings' => [
                'settings.view'   => 'View settings',
                'settings.manage' => 'Manage settings',
            ],
        ];
    }
}
