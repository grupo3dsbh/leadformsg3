<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * User model for authentication and tenant membership.
 */
class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'two_factor_secret',
        'two_factor_enabled',
        'email_verified_at',
        'last_login_at',
        'status',
        'locale',
        'timezone',
        'created_at',
        'updated_at',
    ];

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Authenticate a user with email and password.
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!password_verify($password, $user['password'] ?? '')) {
            return null;
        }

        if (($user['status'] ?? '') !== 'active') {
            return null;
        }

        $this->updateLastLogin((int) $user['id']);

        return $user;
    }

    /**
     * Verify a plaintext password against a stored hash.
     */
    public function verifyPassword(string $password, string $passwordHash): bool
    {
        return password_verify($password, $passwordHash);
    }

    /**
     * Update the last login timestamp.
     */
    public function updateLastLogin(int $userId): bool
    {
        return (bool) $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check whether the user has two-factor authentication enabled.
     */
    public function hasTwoFactor(array $user): bool
    {
        return !empty($user['two_factor_enabled']) && !empty($user['two_factor_secret']);
    }

    /**
     * Determine if a user has the admin role.
     */
    public function isAdmin(array $user): bool
    {
        return in_array($user['role'] ?? '', ['admin', 'super_admin'], true);
    }

    /**
     * Determine if a user has the super_admin role.
     */
    public function isSuperAdmin(array $user): bool
    {
        return ($user['role'] ?? '') === 'super_admin';
    }

    /**
     * Get the tenant this user belongs to.
     */
    public function tenant(array $user): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope query results to a specific tenant.
     */
    public function scopeTenant(int $tenantId): array
    {
        $db = \Core\Database::getInstance();
        return $db->table($this->table)->where('tenant_id', $tenantId)->get();
    }
}
