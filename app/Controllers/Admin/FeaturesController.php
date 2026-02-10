<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * Super Admin Features Controller
 *
 * Manages platform-wide feature flags and integration settings.
 * Features control what capabilities are available to tenants.
 */
class FeaturesController extends Controller
{
    /**
     * List all features and integrations with their current status.
     */
    public function index(): string
    {
        $db = $this->db();

        $features = $db->query(
            "SELECT f.*,
                    (SELECT COUNT(DISTINCT i.tenant_id)
                       FROM integrations i
                      WHERE i.type = f.slug AND i.status = 'active'
                    ) AS active_tenant_count
               FROM features f
              ORDER BY f.category ASC, f.name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Group by category for display
        $grouped = [];
        foreach ($features as $feature) {
            $category = $feature['category'] ?? 'general';
            $grouped[$category][] = $feature;
        }

        // Available categories summary
        $categories = $db->query(
            "SELECT category, COUNT(*) AS count,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count
               FROM features
              GROUP BY category
              ORDER BY category ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/features/index', [
            'features'   => $features,
            'grouped'    => $grouped,
            'categories' => $categories,
        ], 'layouts.admin');
    }

    /**
     * Toggle a feature's active status.
     */
    public function toggle(string $id): string
    {
        $db = $this->db();

        $stmt = $db->prepare("SELECT * FROM features WHERE id = :id");
        $stmt->execute(['id' => (int) $id]);
        $feature = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$feature) {
            return $this->redirect('/admin/features', ['error' => 'Feature not found.']);
        }

        $newStatus = $feature['is_active'] ? 0 : 1;

        $db->prepare(
            "UPDATE features SET is_active = :status WHERE id = :id"
        )->execute([
            'status' => $newStatus,
            'id'     => (int) $id,
        ]);

        // If disabling, optionally deactivate all tenant integrations of this type
        if ($newStatus === 0 && !empty($_POST['cascade_disable'])) {
            $db->prepare(
                "UPDATE integrations SET status = 'inactive', updated_at = NOW() WHERE type = :slug"
            )->execute(['slug' => $feature['slug']]);
        }

        $this->logAudit('feature_toggled', 'features', (int) $id, [
            'feature_name' => $feature['name'],
            'new_status'   => $newStatus ? 'active' : 'inactive',
            'cascade'      => !empty($_POST['cascade_disable']),
        ]);

        $statusLabel = $newStatus ? 'enabled' : 'disabled';

        return $this->redirect('/admin/features', [
            'success' => "Feature \"{$feature['name']}\" has been {$statusLabel}.",
        ]);
    }

    /**
     * Update configuration settings for a specific feature.
     */
    public function update(string $id): string
    {
        $db = $this->db();

        $stmt = $db->prepare("SELECT * FROM features WHERE id = :id");
        $stmt->execute(['id' => (int) $id]);
        $feature = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$feature) {
            return $this->redirect('/admin/features', ['error' => 'Feature not found.']);
        }

        $errors = $this->validate($_POST, [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if (!empty($errors)) {
            return $this->view('admin/features/index', [
                'feature'  => $feature,
                'errors'   => $errors,
                'editId'   => (int) $id,
            ], 'layouts.admin');
        }

        $db->prepare(
            "UPDATE features
                SET name        = :name,
                    description = :description,
                    is_active   = :is_active
              WHERE id = :id"
        )->execute([
            'name'        => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
            'id'          => (int) $id,
        ]);

        $this->logAudit('feature_updated', 'features', (int) $id, [
            'feature_name' => trim($_POST['name']),
            'changes'      => $_POST,
        ]);

        return $this->redirect('/admin/features', [
            'success' => "Feature \"{$_POST['name']}\" updated successfully.",
        ]);
    }

    /**
     * Write an entry to the audit log.
     */
    private function logAudit(string $action, string $entity, int $entityId, array $metadata = []): void
    {
        $this->db()->prepare(
            "INSERT INTO audit_logs (user_id, tenant_id, action, entity_type, entity_id, new_values, ip_address, created_at)
             VALUES (:uid, NULL, :action, :entity, :eid, :meta, :ip, NOW())"
        )->execute([
            'uid'    => auth()['id'] ?? null,
            'action' => $action,
            'entity' => $entity,
            'eid'    => $entityId,
            'meta'   => json_encode($metadata),
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }
}
