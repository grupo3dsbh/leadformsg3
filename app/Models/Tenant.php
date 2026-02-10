<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Tenant model representing an organisation or workspace.
 */
class Tenant extends Model
{
    protected string $table = 'tenants';

    protected array $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'logo',
        'domain',
        'custom_domain',
        'plan_id',
        'subscription_status',
        'subscription_ends_at',
        'settings',
        'api_key',
        'api_secret',
        'storage_used',
        'max_storage',
        'status',
        'created_at',
        'updated_at',
    ];

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function findByDomain(string $domain): ?array
    {
        return $this->findBy('domain', $domain);
    }

    public function isActive(array $tenant): bool
    {
        return ($tenant['status'] ?? '') === 'active';
    }

    public function plan(array $tenant): ?array
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function users(int $tenantId): array
    {
        $db = \Core\Database::getInstance();
        return $db->table('users')->where('tenant_id', $tenantId)->get();
    }

    public function forms(int $tenantId): array
    {
        $db = \Core\Database::getInstance();
        return $db->table('forms')->where('tenant_id', $tenantId)->get();
    }

    public function storageUsedPercent(array $tenant): float
    {
        $limit = (int) ($tenant['max_storage'] ?? 0);

        if ($limit <= 0) {
            return 0.0;
        }

        $used = (int) ($tenant['storage_used'] ?? 0);

        return round(($used / $limit) * 100, 2);
    }

    public function canCreateForm(array $tenant): bool
    {
        $plan = $this->plan($tenant);

        if ($plan === null) {
            return true; // No plan = allow
        }

        $maxForms = (int) ($plan['max_forms'] ?? 0);

        if ($maxForms === 0) {
            return true; // 0 = unlimited
        }

        $formCount = count($this->forms((int) $tenant['id']));

        return $formCount < $maxForms;
    }

    public function canAddUser(array $tenant): bool
    {
        $plan = $this->plan($tenant);

        if ($plan === null) {
            return true;
        }

        $maxUsers = (int) ($plan['max_users'] ?? 0);

        if ($maxUsers === 0) {
            return true;
        }

        $userCount = count($this->users((int) $tenant['id']));

        return $userCount < $maxUsers;
    }

    public function generateApiKey(int $tenantId): string
    {
        $apiKey = 'lf_' . bin2hex(random_bytes(32));

        $this->update($tenantId, ['api_key' => hash('sha256', $apiKey)]);

        return $apiKey;
    }

    public function subscription(int $tenantId): ?array
    {
        try {
            $db = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT * FROM subscriptions WHERE tenant_id = :tenant_id AND status = :status LIMIT 1'
            );
            $stmt->execute(['tenant_id' => $tenantId, 'status' => 'active']);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result !== false ? $result : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
