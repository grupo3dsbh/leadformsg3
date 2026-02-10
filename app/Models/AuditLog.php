<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * AuditLog model for tracking changes to entities.
 *
 * Provides an immutable record of who changed what and when,
 * supporting compliance, debugging, and accountability requirements.
 */
class AuditLog extends Model
{
    protected string $table = 'audit_logs';

    protected array $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Record an audit log entry.
     *
     * @param string     $action    The action performed (e.g. "created", "updated", "deleted").
     * @param string     $entity    The entity type and ID in "Type:ID" format (e.g. "Form:42").
     * @param array|null $oldValues The previous state of the entity (null for create actions).
     * @param array|null $newValues The new state of the entity (null for delete actions).
     * @param int|null   $userId    The ID of the user who performed the action.
     * @param int|null   $tenantId  The tenant scope for the action.
     * @return int|false The new audit log ID or false on failure.
     */
    public static function log(
        string $action,
        string $entity,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?int $tenantId = null,
    ): int|false {
        [$entityType, $entityId] = array_pad(explode(':', $entity, 2), 2, null);

        $instance = new static();

        return $instance->create([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues !== null ? json_encode($oldValues) : null,
            'new_values'  => $newValues !== null ? json_encode($newValues) : null,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get the user who performed the audited action.
     *
     * @return array|null The user record or null.
     */
    public function user(): ?array
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant this audit log entry belongs to.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
