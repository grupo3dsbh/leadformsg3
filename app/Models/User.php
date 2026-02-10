<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * User model for authentication and tenant membership.
 *
 * Represents application users who belong to tenants and interact with forms.
 */
class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'tenant_id',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'role',
        'permissions',
        'avatar_url',
        'two_factor_secret',
        'two_factor_enabled',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
        'locale',
        'timezone',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Find a user by their email address.
     *
     * @param string $email The email to search for.
     * @return array|null The user record or null if not found.
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Authenticate a user with email and password.
     *
     * Returns the user record on success or null on failure.
     * Also updates the last login timestamp on success.
     *
     * @param string $email    The user's email address.
     * @param string $password The plaintext password to verify.
     * @return array|null The authenticated user record or null.
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!$this->verifyPassword($password, $user['password_hash'])) {
            return null;
        }

        if (empty($user['is_active'])) {
            return null;
        }

        $this->updateLastLogin((int) $user['id']);

        return $user;
    }

    /**
     * Verify a plaintext password against a stored hash.
     *
     * @param string $password     The plaintext password.
     * @param string $passwordHash The stored bcrypt/argon2 hash.
     * @return bool True if the password matches.
     */
    public function verifyPassword(string $password, string $passwordHash): bool
    {
        return password_verify($password, $passwordHash);
    }

    /**
     * Update the last login timestamp and IP for a user.
     *
     * @param int         $userId The user ID.
     * @param string|null $ip     Optional IP address of the login request.
     * @return bool True on success.
     */
    public function updateLastLogin(int $userId, ?string $ip = null): bool
    {
        $data = ['last_login_at' => date('Y-m-d H:i:s')];

        if ($ip !== null) {
            $data['last_login_ip'] = $ip;
        }

        return (bool) $this->update($userId, $data);
    }

    /**
     * Check whether the user has two-factor authentication enabled.
     *
     * @param array $user The user record.
     * @return bool True if 2FA is enabled.
     */
    public function hasTwoFactor(array $user): bool
    {
        return !empty($user['two_factor_enabled']) && !empty($user['two_factor_secret']);
    }

    /**
     * Determine if a user has the admin role.
     *
     * @param array $user The user record.
     * @return bool True if the user is an admin.
     */
    public function isAdmin(array $user): bool
    {
        return in_array($user['role'] ?? '', ['admin', 'super_admin'], true);
    }

    /**
     * Determine if a user has the super_admin role.
     *
     * @param array $user The user record.
     * @return bool True if the user is a super admin.
     */
    public function isSuperAdmin(array $user): bool
    {
        return ($user['role'] ?? '') === 'super_admin';
    }

    /**
     * Check if a user possesses a specific permission.
     *
     * Super admins implicitly have all permissions.
     *
     * @param array  $user       The user record.
     * @param string $permission The permission key to check (e.g. "forms.create").
     * @return bool True if the user has the permission.
     */
    public function hasPermission(array $user, string $permission): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $permissions = json_decode($user['permissions'] ?? '[]', true) ?: [];

        return in_array($permission, $permissions, true);
    }

    /**
     * Get the tenant this user belongs to.
     *
     * @param array $user The user record.
     * @return array|null The tenant record or null.
     */
    public function tenant(array $user): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope query results to a specific tenant.
     *
     * @param int $tenantId The tenant ID to filter by.
     * @return array List of users belonging to the tenant.
     */
    public function scopeTenant(int $tenantId): array
    {
        $db = \Core\Database::getInstance();
        return $db->table($this->table)->where('tenant_id', $tenantId)->get();
    }
}
