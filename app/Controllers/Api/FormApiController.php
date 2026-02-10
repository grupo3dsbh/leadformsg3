<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Core\Controller;

/**
 * Form API Controller
 *
 * RESTful API endpoints for form management. All endpoints require API key
 * authentication and are scoped to the authenticated tenant.
 *
 * Routes:
 *   GET    /api/v1/forms            - List forms
 *   GET    /api/v1/forms/{id}       - Get a single form
 *   POST   /api/v1/forms            - Create a form
 *   PUT    /api/v1/forms/{id}       - Update a form
 *   DELETE /api/v1/forms/{id}       - Delete a form
 *   GET    /api/v1/forms/{id}/entries - Get form entries
 */
class FormApiController extends Controller
{
    /**
     * GET /api/v1/forms
     *
     * List forms for the authenticated tenant with optional pagination and search.
     */
    public function index(): string
    {
        $tenantId = (int) tenant()['id'];
        $db       = $this->db();

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;
        $search  = trim($_GET['search'] ?? '');
        $status  = $_GET['status'] ?? '';

        $where  = ['f.tenant_id = :tid'];
        $params = ['tid' => $tenantId];

        if ($search !== '') {
            $where[]          = '(f.title LIKE :search OR f.slug LIKE :search)';
            $params['search'] = "%{$search}%";
        }

        if ($status === 'published') {
            $where[] = 'f.is_published = 1';
        } elseif ($status === 'draft') {
            $where[] = 'f.is_published = 0';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM forms f {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT f.id, f.title, f.slug, f.description, f.is_published,
                       f.views, f.created_at, f.updated_at,
                       (SELECT COUNT(*) FROM form_entries WHERE form_id = f.id) AS entry_count
                  FROM forms f
                  {$whereClause}
                  ORDER BY f.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $forms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json([
            'data' => $forms,
            'meta' => [
                'total'        => $total,
                'page'         => $page,
                'per_page'     => $perPage,
                'total_pages'  => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/v1/forms/{id}
     *
     * Get a single form by ID with full field configuration.
     */
    public function show(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->json(['error' => 'Form not found.'], 404);
        }

        // Decode JSON fields for the response
        $form['fields']   = json_decode($form['fields'] ?? '[]', true) ?: [];
        $form['settings'] = json_decode($form['settings'] ?? '{}', true) ?: [];
        $form['theme']    = json_decode($form['theme'] ?? '{}', true) ?: [];

        // Add entry count
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM form_entries WHERE form_id = :fid");
        $stmt->execute(['fid' => (int) $id]);
        $form['entry_count'] = (int) $stmt->fetchColumn();

        return $this->json(['data' => $form]);
    }

    /**
     * POST /api/v1/forms
     *
     * Create a new form.
     */
    public function store(): string
    {
        $input = $this->getJsonInput();

        if (!isset($input['title']) || trim($input['title']) === '') {
            return $this->json([
                'error'  => 'Validation failed.',
                'errors' => ['title' => 'The title field is required.'],
            ], 422);
        }

        if (!isset($input['fields']) || !is_array($input['fields'])) {
            return $this->json([
                'error'  => 'Validation failed.',
                'errors' => ['fields' => 'The fields array is required.'],
            ], 422);
        }

        $tenantId = (int) tenant()['id'];
        $db       = $this->db();

        // Generate unique slug
        $slug     = $this->generateSlug($input['title'], $tenantId);
        $settings = isset($input['settings']) && is_array($input['settings']) ? $input['settings'] : [];
        $theme    = isset($input['theme']) && is_array($input['theme']) ? $input['theme'] : [];

        $stmt = $db->prepare(
            "INSERT INTO forms (tenant_id, title, slug, description, fields, settings, theme, is_published, views, created_at, updated_at)
             VALUES (:tid, :title, :slug, :desc, :fields, :settings, :theme, :published, 0, NOW(), NOW())"
        );
        $stmt->execute([
            'tid'       => $tenantId,
            'title'     => trim($input['title']),
            'slug'      => $slug,
            'desc'      => trim($input['description'] ?? ''),
            'fields'    => json_encode($input['fields']),
            'settings'  => json_encode($settings),
            'theme'     => json_encode($theme),
            'published' => !empty($input['is_published']) ? 1 : 0,
        ]);

        $formId = (int) $db->lastInsertId();

        // Return the created form
        $form = $this->findTenantForm($formId);
        $form['fields']   = json_decode($form['fields'] ?? '[]', true) ?: [];
        $form['settings'] = json_decode($form['settings'] ?? '{}', true) ?: [];
        $form['theme']    = json_decode($form['theme'] ?? '{}', true) ?: [];

        return $this->json(['data' => $form], 201);
    }

    /**
     * PUT /api/v1/forms/{id}
     *
     * Update an existing form.
     */
    public function update(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->json(['error' => 'Form not found.'], 404);
        }

        $input = $this->getJsonInput();

        $updateFields = [];
        $updateParams = ['id' => (int) $id, 'tid' => (int) tenant()['id']];

        if (isset($input['title'])) {
            if (trim($input['title']) === '') {
                return $this->json([
                    'error'  => 'Validation failed.',
                    'errors' => ['title' => 'The title cannot be empty.'],
                ], 422);
            }
            $updateFields[]          = 'title = :title';
            $updateParams['title']   = trim($input['title']);
        }

        if (isset($input['description'])) {
            $updateFields[]        = 'description = :desc';
            $updateParams['desc']  = trim($input['description']);
        }

        if (isset($input['fields'])) {
            if (!is_array($input['fields'])) {
                return $this->json([
                    'error'  => 'Validation failed.',
                    'errors' => ['fields' => 'Fields must be an array.'],
                ], 422);
            }
            $updateFields[]          = 'fields = :fields';
            $updateParams['fields']  = json_encode($input['fields']);
        }

        if (isset($input['settings'])) {
            $updateFields[]            = 'settings = :settings';
            $updateParams['settings']  = json_encode(is_array($input['settings']) ? $input['settings'] : []);
        }

        if (isset($input['theme'])) {
            $updateFields[]         = 'theme = :theme';
            $updateParams['theme']  = json_encode(is_array($input['theme']) ? $input['theme'] : []);
        }

        if (isset($input['is_published'])) {
            $updateFields[]              = 'is_published = :published';
            $updateParams['published']   = $input['is_published'] ? 1 : 0;
        }

        if (empty($updateFields)) {
            return $this->json(['error' => 'No fields to update.'], 400);
        }

        $updateFields[] = 'updated_at = NOW()';

        $sql = "UPDATE forms SET " . implode(', ', $updateFields) . " WHERE id = :id AND tenant_id = :tid";
        $this->db()->prepare($sql)->execute($updateParams);

        // Return updated form
        $updatedForm = $this->findTenantForm((int) $id);
        $updatedForm['fields']   = json_decode($updatedForm['fields'] ?? '[]', true) ?: [];
        $updatedForm['settings'] = json_decode($updatedForm['settings'] ?? '{}', true) ?: [];
        $updatedForm['theme']    = json_decode($updatedForm['theme'] ?? '{}', true) ?: [];

        return $this->json(['data' => $updatedForm]);
    }

    /**
     * DELETE /api/v1/forms/{id}
     *
     * Delete a form and all its entries.
     */
    public function delete(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->json(['error' => 'Form not found.'], 404);
        }

        $db = $this->db();

        // Delete entries
        $db->prepare("DELETE FROM form_entries WHERE form_id = :fid")->execute(['fid' => (int) $id]);

        // Delete the form
        $db->prepare("DELETE FROM forms WHERE id = :id AND tenant_id = :tid")->execute([
            'id'  => (int) $id,
            'tid' => (int) tenant()['id'],
        ]);

        return $this->json(['message' => 'Form deleted successfully.']);
    }

    /**
     * GET /api/v1/forms/{id}/entries
     *
     * Get paginated entries for a specific form.
     */
    public function entries(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->json(['error' => 'Form not found.'], 404);
        }

        $db      = $this->db();
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $where  = ['fe.form_id = :fid'];
        $params = ['fid' => (int) $id];

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';

        if ($dateFrom !== '') {
            $where[]              = 'fe.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[]            = 'fe.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM form_entries fe {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT fe.id, fe.form_id, fe.data, fe.ip_address, fe.user_agent, fe.referrer, fe.created_at
                  FROM form_entries fe
                  {$whereClause}
                  ORDER BY fe.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decode data JSON
        foreach ($entries as &$entry) {
            $entry['data'] = json_decode($entry['data'] ?? '{}', true) ?: [];
        }
        unset($entry);

        return $this->json([
            'data' => $entries,
            'meta' => [
                'form_id'     => (int) $id,
                'form_title'  => $form['title'],
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Find a form scoped to the current tenant.
     */
    private function findTenantForm(int $formId): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM forms WHERE id = :id AND tenant_id = :tid"
        );
        $stmt->execute([
            'id'  => $formId,
            'tid' => (int) tenant()['id'],
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Parse JSON input from the request body.
     */
    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === '' || $raw === false) {
            return $_POST ?: [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Generate a unique slug for a form within a tenant.
     */
    private function generateSlug(string $title, int $tenantId): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));

        if ($slug === '') {
            $slug = 'form';
        }

        $db       = $this->db();
        $original = $slug;
        $counter  = 1;

        while (true) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM forms WHERE slug = :slug AND tenant_id = :tid"
            );
            $stmt->execute(['slug' => $slug, 'tid' => $tenantId]);

            if ((int) $stmt->fetchColumn() === 0) {
                break;
            }

            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
