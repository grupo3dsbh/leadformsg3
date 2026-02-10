<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Webhook model for sending HTTP callbacks on form events.
 *
 * Webhooks allow tenants to receive real-time POST notifications
 * when specific events occur (e.g. new entry, form published).
 */
class Webhook extends Model
{
    protected string $table = 'webhooks';

    protected array $fillable = [
        'tenant_id',
        'form_id',
        'url',
        'secret',
        'events',
        'headers',
        'is_active',
        'retry_count',
        'last_fired_at',
        'last_status_code',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the tenant that owns this webhook.
     *
     * @return array|null The tenant record or null.
     */
    public function tenant(): ?array
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the form this webhook is scoped to (null means all forms).
     *
     * @param array $webhook The webhook record.
     * @return array|null The form record, or null if not form-scoped.
     */
    public function form(array $webhook): ?array
    {
        if (empty($webhook['form_id'])) {
            return null;
        }

        return (new Form())->find((int) $webhook['form_id']);
    }

    /**
     * Get delivery logs for this webhook.
     *
     * @param int $webhookId The webhook ID.
     * @return array List of webhook log records.
     */
    public function logs(int $webhookId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM webhook_logs WHERE webhook_id = :webhook_id ORDER BY created_at DESC'
        );
        $stmt->execute(['webhook_id' => $webhookId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Fire the webhook by sending an HTTP POST request with the payload.
     *
     * Generates an HMAC signature using the webhook secret and logs the attempt.
     *
     * @param array $webhook The webhook record.
     * @param array $payload The data to send as JSON.
     * @return bool True if the webhook was delivered successfully (2xx status).
     */
    public function fire(array $webhook, array $payload): bool
    {
        if (!$this->isActive($webhook)) {
            return false;
        }

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        $headers = [
            'Content-Type: application/json',
            'User-Agent: LeadFormSG/1.0',
            'X-Webhook-Id: ' . $webhook['id'],
            'X-Webhook-Timestamp: ' . time(),
        ];

        // Sign payload with HMAC if a secret is configured.
        if (!empty($webhook['secret'])) {
            $signature = hash_hmac('sha256', $jsonPayload, $webhook['secret']);
            $headers[] = 'X-Webhook-Signature: sha256=' . $signature;
        }

        // Merge custom headers.
        $customHeaders = json_decode($webhook['headers'] ?? '[]', true) ?: [];
        foreach ($customHeaders as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        $ch = curl_init($webhook['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response   = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error      = curl_error($ch);
        curl_close($ch);

        $success = $statusCode >= 200 && $statusCode < 300;

        // Update webhook record.
        $this->update((int) $webhook['id'], [
            'last_fired_at'    => date('Y-m-d H:i:s'),
            'last_status_code' => $statusCode,
        ]);

        // Log the delivery attempt.
        $logStmt = $this->db()->prepare(
            'INSERT INTO webhook_logs (webhook_id, status_code, request_payload, response_body, error, created_at)
             VALUES (:webhook_id, :status_code, :request_payload, :response_body, :error, :created_at)'
        );
        $logStmt->execute([
            'webhook_id'      => $webhook['id'],
            'status_code'     => $statusCode,
            'request_payload' => $jsonPayload,
            'response_body'   => is_string($response) ? mb_substr($response, 0, 5000) : null,
            'error'           => $error ?: null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return $success;
    }

    /**
     * Check if the webhook is enabled.
     *
     * @param array $webhook The webhook record.
     * @return bool True if the webhook is active.
     */
    public function isActive(array $webhook): bool
    {
        return !empty($webhook['is_active']);
    }
}
