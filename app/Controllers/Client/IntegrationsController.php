<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Integrations Controller
 *
 * Manages third-party integrations (Mailchimp, Slack, Zapier, etc.) and
 * webhooks for the current tenant.
 */
class IntegrationsController extends Controller
{
    /**
     * List available integrations and their status for the current tenant.
     */
    public function index(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        // Get all platform features/integrations that are enabled globally
        $availableIntegrations = $db->query(
            "SELECT * FROM features WHERE category = 'integration' AND is_active = 1 ORDER BY sort_order ASC, name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Get this tenant's configured integrations
        $tenantIntegrations = $db->prepare(
            "SELECT * FROM integrations WHERE tenant_id = :tid ORDER BY type ASC"
        );
        $tenantIntegrations->execute(['tid' => $tenantId]);
        $tenantIntegrations = $tenantIntegrations->fetchAll(\PDO::FETCH_ASSOC);

        // Index by type for quick lookup
        $configuredByType = [];
        foreach ($tenantIntegrations as $integration) {
            $configuredByType[$integration['type']] = $integration;
        }

        // Check plan integration limits
        $plan = null;
        $tenantData = tenant();
        if (!empty($tenantData['plan_id'])) {
            $stmt = $db->prepare("SELECT * FROM plans WHERE id = :pid");
            $stmt->execute(['pid' => (int) $tenantData['plan_id']]);
            $plan = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        $allowedIntegrations = $plan
            ? (json_decode($plan['integrations'] ?? '[]', true) ?: [])
            : [];

        return $this->view('client/integrations/index', [
            'available'            => $availableIntegrations,
            'configured'           => $configuredByType,
            'allowedIntegrations'  => $allowedIntegrations,
            'plan'                 => $plan,
        ]);
    }

    /**
     * Show configuration form for a specific integration type.
     */
    public function configure(string $type): string
    {
        $type     = $this->sanitizeType($type);
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        // Check if already configured
        $stmt = $db->prepare(
            "SELECT * FROM integrations WHERE tenant_id = :tid AND type = :type LIMIT 1"
        );
        $stmt->execute(['tid' => $tenantId, 'type' => $type]);
        $integration = $stmt->fetch(\PDO::FETCH_ASSOC);

        $config = $integration
            ? (json_decode($integration['config'] ?? '{}', true) ?: [])
            : [];

        // Get feature info for the integration type
        $featureStmt = $db->prepare("SELECT * FROM features WHERE slug = :slug LIMIT 1");
        $featureStmt->execute(['slug' => $type]);
        $feature = $featureStmt->fetch(\PDO::FETCH_ASSOC);

        return $this->view('client/integrations/configure', [
            'type'        => $type,
            'integration' => $integration,
            'config'      => $config,
            'feature'     => $feature,
        ]);
    }

    /**
     * Save integration configuration.
     */
    public function save(string $type): string
    {
        $type     = $this->sanitizeType($type);
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        // Build config from POST data (exclude internal fields)
        $config = $_POST['config'] ?? [];
        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        // Remove empty values
        $config = array_filter($config, fn($v) => $v !== '' && $v !== null);

        // Check if integration already exists
        $stmt = $db->prepare(
            "SELECT id FROM integrations WHERE tenant_id = :tid AND type = :type LIMIT 1"
        );
        $stmt->execute(['tid' => $tenantId, 'type' => $type]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            $db->prepare(
                "UPDATE integrations SET config = :config, updated_at = NOW() WHERE id = :id"
            )->execute([
                'config' => json_encode($config),
                'id'     => (int) $existing['id'],
            ]);
        } else {
            $db->prepare(
                "INSERT INTO integrations (tenant_id, type, config, is_active, created_at, updated_at)
                 VALUES (:tid, :type, :config, 1, NOW(), NOW())"
            )->execute([
                'tid'    => $tenantId,
                'type'   => $type,
                'config' => json_encode($config),
            ]);
        }

        return $this->redirect('/client/integrations', [
            'success' => ucfirst($type) . ' integration configured successfully.',
        ]);
    }

    /**
     * Toggle an integration's active status.
     */
    public function toggle(string $id): string
    {
        $integration = $this->findTenantIntegration((int) $id);

        if (!$integration) {
            return $this->redirect('/client/integrations', ['error' => 'Integration not found.']);
        }

        $newStatus = $integration['is_active'] ? 0 : 1;

        $this->db()->prepare(
            "UPDATE integrations SET is_active = :status, updated_at = NOW() WHERE id = :id"
        )->execute([
            'status' => $newStatus,
            'id'     => (int) $id,
        ]);

        $label = $newStatus ? 'enabled' : 'disabled';

        return $this->redirect('/client/integrations', [
            'success' => ucfirst($integration['type']) . " integration {$label}.",
        ]);
    }

    /**
     * Remove an integration.
     */
    public function delete(string $id): string
    {
        $integration = $this->findTenantIntegration((int) $id);

        if (!$integration) {
            return $this->redirect('/client/integrations', ['error' => 'Integration not found.']);
        }

        $this->db()->prepare("DELETE FROM integrations WHERE id = :id")->execute(['id' => (int) $id]);

        return $this->redirect('/client/integrations', [
            'success' => ucfirst($integration['type']) . ' integration removed.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Webhooks
    // -------------------------------------------------------------------------

    /**
     * List all webhooks for the current tenant.
     */
    public function webhooks(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $stmt = $db->prepare(
            "SELECT w.*, f.title AS form_title
               FROM webhooks w
               LEFT JOIN forms f ON f.id = w.form_id
              WHERE w.tenant_id = :tid
              ORDER BY w.created_at DESC"
        );
        $stmt->execute(['tid' => $tenantId]);
        $webhooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Available forms for the dropdown
        $forms = $db->prepare("SELECT id, title FROM forms WHERE tenant_id = :tid ORDER BY title ASC");
        $forms->execute(['tid' => $tenantId]);
        $forms = $forms->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/integrations/webhooks', [
            'webhooks' => $webhooks,
            'forms'    => $forms,
        ]);
    }

    /**
     * Show the create webhook form.
     */
    public function createWebhook(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $forms = $db->prepare("SELECT id, title FROM forms WHERE tenant_id = :tid ORDER BY title ASC");
        $forms->execute(['tid' => $tenantId]);
        $forms = $forms->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/integrations/webhook-form', [
            'webhook' => [
                'url'       => '',
                'form_id'   => '',
                'events'    => ['entry.created'],
                'secret'    => '',
                'is_active' => 1,
            ],
            'forms'   => $forms,
            'isEdit'  => false,
        ]);
    }

    /**
     * Store a new webhook.
     */
    public function storeWebhook(): string
    {
        $errors = $this->validate($_POST, [
            'url'    => 'required|url',
            'events' => 'required',
        ]);

        if (!empty($errors)) {
            return $this->view('client/integrations/webhook-form', [
                'webhook' => $_POST,
                'forms'   => $this->getTenantForms(),
                'errors'  => $errors,
                'isEdit'  => false,
            ]);
        }

        $events = is_array($_POST['events'] ?? null) ? $_POST['events'] : [$_POST['events']];
        $secret = trim($_POST['secret'] ?? '') ?: bin2hex(random_bytes(20));

        $this->db()->prepare(
            "INSERT INTO webhooks (tenant_id, form_id, url, events, secret, is_active, created_at, updated_at)
             VALUES (:tid, :fid, :url, :events, :secret, :active, NOW(), NOW())"
        )->execute([
            'tid'    => (int) tenant()['id'],
            'fid'    => !empty($_POST['form_id']) ? (int) $_POST['form_id'] : null,
            'url'    => trim($_POST['url']),
            'events' => json_encode(array_values($events)),
            'secret' => $secret,
            'active' => !empty($_POST['is_active']) ? 1 : 0,
        ]);

        return $this->redirect('/client/integrations/webhooks', [
            'success' => 'Webhook created successfully.',
        ]);
    }

    /**
     * Show the edit form for a webhook.
     */
    public function editWebhook(string $id): string
    {
        $webhook = $this->findTenantWebhook((int) $id);

        if (!$webhook) {
            return $this->redirect('/client/integrations/webhooks', ['error' => 'Webhook not found.']);
        }

        $webhook['events'] = json_decode($webhook['events'] ?? '[]', true) ?: [];

        return $this->view('client/integrations/webhook-form', [
            'webhook' => $webhook,
            'forms'   => $this->getTenantForms(),
            'isEdit'  => true,
        ]);
    }

    /**
     * Update a webhook.
     */
    public function updateWebhook(string $id): string
    {
        $webhook = $this->findTenantWebhook((int) $id);

        if (!$webhook) {
            return $this->redirect('/client/integrations/webhooks', ['error' => 'Webhook not found.']);
        }

        $errors = $this->validate($_POST, [
            'url'    => 'required|url',
            'events' => 'required',
        ]);

        if (!empty($errors)) {
            return $this->view('client/integrations/webhook-form', [
                'webhook' => array_merge($webhook, $_POST),
                'forms'   => $this->getTenantForms(),
                'errors'  => $errors,
                'isEdit'  => true,
            ]);
        }

        $events = is_array($_POST['events'] ?? null) ? $_POST['events'] : [$_POST['events']];

        $this->db()->prepare(
            "UPDATE webhooks
                SET form_id   = :fid,
                    url       = :url,
                    events    = :events,
                    secret    = :secret,
                    is_active = :active,
                    updated_at = NOW()
              WHERE id = :id AND tenant_id = :tid"
        )->execute([
            'fid'    => !empty($_POST['form_id']) ? (int) $_POST['form_id'] : null,
            'url'    => trim($_POST['url']),
            'events' => json_encode(array_values($events)),
            'secret' => trim($_POST['secret'] ?? '') ?: $webhook['secret'],
            'active' => !empty($_POST['is_active']) ? 1 : 0,
            'id'     => (int) $id,
            'tid'    => (int) tenant()['id'],
        ]);

        return $this->redirect('/client/integrations/webhooks', [
            'success' => 'Webhook updated successfully.',
        ]);
    }

    /**
     * Delete a webhook.
     */
    public function deleteWebhook(string $id): string
    {
        $webhook = $this->findTenantWebhook((int) $id);

        if (!$webhook) {
            return $this->redirect('/client/integrations/webhooks', ['error' => 'Webhook not found.']);
        }

        $this->db()->prepare(
            "DELETE FROM webhooks WHERE id = :id AND tenant_id = :tid"
        )->execute([
            'id'  => (int) $id,
            'tid' => (int) tenant()['id'],
        ]);

        return $this->redirect('/client/integrations/webhooks', [
            'success' => 'Webhook deleted.',
        ]);
    }

    /**
     * Send a test payload to a webhook.
     */
    public function testWebhook(string $id): string
    {
        $webhook = $this->findTenantWebhook((int) $id);

        if (!$webhook) {
            return $this->json(['error' => 'Webhook not found.'], 404);
        }

        $testPayload = [
            'event'     => 'test',
            'timestamp' => date('c'),
            'data'      => [
                'form_id'   => 1,
                'entry_id'  => 1,
                'fields'    => [
                    'name'  => 'Test User',
                    'email' => 'test@example.com',
                ],
            ],
        ];

        $signature = hash_hmac('sha256', json_encode($testPayload), $webhook['secret']);

        $ch = curl_init($webhook['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($testPayload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature,
                'X-Webhook-Event: test',
                'User-Agent: LeadForm-Webhook/1.0',
            ],
        ]);

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error      = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return $this->json([
                'success' => false,
                'message' => "Connection error: {$error}",
            ]);
        }

        $success = $statusCode >= 200 && $statusCode < 300;

        // Log the test result
        $this->db()->prepare(
            "UPDATE webhooks SET last_triggered_at = NOW(), last_status = :status WHERE id = :id"
        )->execute([
            'status' => $statusCode,
            'id'     => (int) $id,
        ]);

        return $this->json([
            'success'     => $success,
            'status_code' => $statusCode,
            'response'    => mb_substr((string) $response, 0, 500),
            'message'     => $success
                ? "Webhook responded with status {$statusCode}."
                : "Webhook returned status {$statusCode}.",
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Find an integration belonging to the current tenant.
     */
    private function findTenantIntegration(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM integrations WHERE id = :id AND tenant_id = :tid"
        );
        $stmt->execute([
            'id'  => $id,
            'tid' => (int) tenant()['id'],
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find a webhook belonging to the current tenant.
     */
    private function findTenantWebhook(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM webhooks WHERE id = :id AND tenant_id = :tid"
        );
        $stmt->execute([
            'id'  => $id,
            'tid' => (int) tenant()['id'],
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all forms for the current tenant (for dropdowns).
     */
    private function getTenantForms(): array
    {
        $stmt = $this->db()->prepare(
            "SELECT id, title FROM forms WHERE tenant_id = :tid ORDER BY title ASC"
        );
        $stmt->execute(['tid' => (int) tenant()['id']]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Sanitize an integration type string.
     */
    private function sanitizeType(string $type): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $type);
    }
}
