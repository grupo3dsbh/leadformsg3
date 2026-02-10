<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Tenant model representing an organisation or workspace.
 *
 * Every resource in the application is scoped to a tenant for multi-tenancy.
 */
class Tenant extends Model
{
    protected string $table = 'tenants';

    protected array $fillable = [
        'name',
        'slug',
        'domain',
        'plan_id',
        'owner_id',
        'logo_url',
        'api_key',
        'settings',
        'storage_used_bytes',
        'storage_limit_bytes',
        'is_active',
        'trial_ends_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Find a tenant by its URL-friendly slug.
     *
     * @param string $slug The unique slug.
     * @return array|null The tenant record or null.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Find a tenant by its custom domain.
     *
     * @param string $domain The custom domain (e.g. "forms.acme.com").
     * @return array|null The tenant record or null.
     */
    public function findByDomain(string $domain): ?array
    {
        return $this->findBy('domain', $domain);
    }

    /**
     * Check whether the tenant account is currently active.
     *
     * @param array $tenant The tenant record.
     * @return bool True if the tenant is active.
     */
    public function isActive(array $tenant): bool
    {
        return !empty($tenant['is_active']);
    }

    /**
     * Get the plan associated with this tenant.
     *
     * @param array $tenant The tenant record.
     * @return array|null The plan record or null.
     */
    public function plan(array $tenant): ?array
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Get all users belonging to this tenant.
     *
     * @param int $tenantId The tenant ID.
     * @return array List of user records.
     */
    public function users(int $tenantId): array
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    /**
     * Get all forms belonging to this tenant.
     *
     * @param int $tenantId The tenant ID.
     * @return array List of form records.
     */
    public function forms(int $tenantId): array
    {
        return $this->hasMany(Form::class, 'tenant_id');
    }

    /**
     * Calculate the percentage of storage quota used.
     *
     * @param array $tenant The tenant record.
     * @return float The percentage of storage used (0.0 - 100.0).
     */
    public function storageUsedPercent(array $tenant): float
    {
        $limit = (int) ($tenant['storage_limit_bytes'] ?? 0);

        if ($limit <= 0) {
            return 0.0;
        }

        $used = (int) ($tenant['storage_used_bytes'] ?? 0);

        return round(($used / $limit) * 100, 2);
    }

    /**
     * Determine if the tenant can create another form based on their plan limits.
     *
     * @param array $tenant The tenant record.
     * @return bool True if the tenant has not exceeded the form limit.
     */
    public function canCreateForm(array $tenant): bool
    {
        $plan = $this->plan($tenant);

        if ($plan === null) {
            return false;
        }

        $limits = json_decode($plan['limits'] ?? '{}', true) ?: [];
        $maxForms = $limits['max_forms'] ?? null;

        // Unlimited forms when no cap is defined.
        if ($maxForms === null) {
            return true;
        }

        $formCount = count($this->forms((int) $tenant['id']));

        return $formCount < (int) $maxForms;
    }

    /**
     * Determine if the tenant can add another user based on their plan limits.
     *
     * @param array $tenant The tenant record.
     * @return bool True if the tenant has not exceeded the user limit.
     */
    public function canAddUser(array $tenant): bool
    {
        $plan = $this->plan($tenant);

        if ($plan === null) {
            return false;
        }

        $limits = json_decode($plan['limits'] ?? '{}', true) ?: [];
        $maxUsers = $limits['max_users'] ?? null;

        if ($maxUsers === null) {
            return true;
        }

        $userCount = count($this->users((int) $tenant['id']));

        return $userCount < (int) $maxUsers;
    }

    /**
     * Generate and persist a new API key for the tenant.
     *
     * @param int $tenantId The tenant ID.
     * @return string The newly generated API key.
     */
    public function generateApiKey(int $tenantId): string
    {
        $apiKey = 'lf_' . bin2hex(random_bytes(32));

        $this->update($tenantId, ['api_key' => hash('sha256', $apiKey)]);

        return $apiKey;
    }

    /**
     * Get the active subscription for this tenant.
     *
     * @param int $tenantId The tenant ID.
     * @return array|null The subscription record or null.
     */
    public function subscription(int $tenantId): ?array
    {
        $results = (new Subscription())->where([
            'tenant_id' => $tenantId,
            'status'    => 'active',
        ]);

        return $results[0] ?? null;
    }
}
