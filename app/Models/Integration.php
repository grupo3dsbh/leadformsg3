<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Integration model for third-party service connections.
 *
 * Integrations allow tenants to push form data to external services
 * such as Mailchimp, Slack, Salesforce, Google Sheets, etc.
 */
class Integration extends Model
{
    protected string $table = 'integrations';

    protected array $fillable = [
        'tenant_id',
        'provider',
        'name',
        'config',
        'credentials',
        'events',
        'is_active',
        'last_triggered_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the tenant that owns this integration.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get all forms connected to this integration.
     *
     * @param int $integrationId The integration ID.
     * @return array List of form records.
     */
    public function forms(int $integrationId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT f.* FROM forms f
             INNER JOIN form_integrations fi ON fi.form_id = f.id
             WHERE fi.integration_id = :integration_id'
        );
        $stmt->execute(['integration_id' => $integrationId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Check if the integration is enabled.
     *
     * @param array $integration The integration record.
     * @return bool True if the integration is active.
     */
    public function isActive(array $integration): bool
    {
        return !empty($integration['is_active']);
    }

    /**
     * Trigger the integration for a specific event with provided data.
     *
     * This method dispatches the event payload to the configured provider endpoint.
     * The actual HTTP call or SDK interaction is delegated to the provider adapter.
     *
     * @param array  $integration The integration record.
     * @param string $event       The event name (e.g. "entry.completed", "entry.created").
     * @param array  $data        The payload data to send.
     * @return bool True if the trigger was dispatched successfully.
     */
    public function trigger(array $integration, string $event, array $data): bool
    {
        if (!$this->isActive($integration)) {
            return false;
        }

        $subscribedEvents = json_decode($integration['events'] ?? '[]', true) ?: [];

        if (!empty($subscribedEvents) && !in_array($event, $subscribedEvents, true)) {
            return false;
        }

        $config      = json_decode($integration['config'] ?? '{}', true) ?: [];
        $credentials = json_decode($integration['credentials'] ?? '{}', true) ?: [];

        $payload = [
            'event'          => $event,
            'integration_id' => $integration['id'],
            'provider'       => $integration['provider'],
            'data'           => $data,
            'config'         => $config,
            'timestamp'      => date('Y-m-d H:i:s'),
        ];

        // Update last triggered timestamp.
        $this->update((int) $integration['id'], [
            'last_triggered_at' => date('Y-m-d H:i:s'),
        ]);

        // Dispatch to provider adapter (implementation depends on queue/service layer).
        // For now, log the trigger and return true.
        $logStmt = $this->db()->prepare(
            'INSERT INTO integration_logs (integration_id, event, payload, status, created_at)
             VALUES (:integration_id, :event, :payload, :status, :created_at)'
        );

        $logStmt->execute([
            'integration_id' => $integration['id'],
            'event'          => $event,
            'payload'        => json_encode($payload),
            'status'         => 'dispatched',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}
