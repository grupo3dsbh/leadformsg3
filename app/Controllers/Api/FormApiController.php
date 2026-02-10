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

        if ($status !== '') {
            $where[]          = 'f.status = :status';
            $params['status'] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM forms f {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT f.id, f.title, f.slug, f.description, f.status,
                       f.views_count, f.submissions_count, f.created_at, f.updated_at
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

        // Load fields from form_fields table
        $fieldsStmt = $this->db()->prepare("SELECT * FROM form_fields WHERE form_id = :fid ORDER BY sort_order ASC");
        $fieldsStmt->execute(['fid' => (int) $id]);
        $form['fields']   = $fieldsStmt->fetchAll(\PDO::FETCH_ASSOC);
        $form['settings'] = json_decode($form['settings'] ?? '{}', true) ?: [];

        // Add entry count
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM entries WHERE form_id = :fid");
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
        if (isset($input['theme']) && is_array($input['theme'])) {
            $settings['theme'] = $input['theme'];
        }
        $status = !empty($input['is_published']) ? 'published' : 'draft';

        $stmt = $db->prepare(
            "INSERT INTO forms (tenant_id, user_id, title, slug, description, settings, status, views_count, submissions_count, created_at, updated_at)
             VALUES (:tid, :uid, :title, :slug, :desc, :settings, :status, 0, 0, NOW(), NOW())"
        );
        $stmt->execute([
            'tid'      => $tenantId,
            'uid'      => auth()['id'] ?? 0,
            'title'    => trim($input['title']),
            'slug'     => $slug,
            'desc'     => trim($input['description'] ?? ''),
            'settings' => json_encode($settings),
            'status'   => $status,
        ]);

        $formId = (int) $db->lastInsertId();

        // Save fields to form_fields table
        if (!empty($input['fields'])) {
            $sortOrder = 0;
            $fieldStmt = $db->prepare(
                "INSERT INTO form_fields (form_id, type, label, placeholder, required, settings, sort_order, created_at, updated_at)
                 VALUES (:fid, :type, :label, :placeholder, :required, :settings, :sort, NOW(), NOW())"
            );
            foreach ($input['fields'] as $field) {
                $fieldStmt->execute([
                    'fid'         => $formId,
                    'type'        => $field['type'] ?? 'text',
                    'label'       => $field['label'] ?? '',
                    'placeholder' => $field['placeholder'] ?? '',
                    'required'    => !empty($field['required']) ? 1 : 0,
                    'settings'    => json_encode($field),
                    'sort'        => $sortOrder++,
                ]);
            }
        }

        // Return the created form
        $form = $this->findTenantForm($formId);
        $fieldsStmt = $db->prepare("SELECT * FROM form_fields WHERE form_id = :fid ORDER BY sort_order ASC");
        $fieldsStmt->execute(['fid' => $formId]);
        $form['fields']   = $fieldsStmt->fetchAll(\PDO::FETCH_ASSOC);
        $form['settings'] = json_decode($form['settings'] ?? '{}', true) ?: [];

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
            // Save fields to form_fields table
            $db = $this->db();
            $db->prepare("DELETE FROM form_fields WHERE form_id = :fid")->execute(['fid' => (int) $id]);
            $sortOrder = 0;
            $fieldStmt = $db->prepare(
                "INSERT INTO form_fields (form_id, type, label, placeholder, required, settings, sort_order, created_at, updated_at)
                 VALUES (:fid, :type, :label, :placeholder, :required, :settings, :sort, NOW(), NOW())"
            );
            foreach ($input['fields'] as $field) {
                $fieldStmt->execute([
                    'fid'         => (int) $id,
                    'type'        => $field['type'] ?? 'text',
                    'label'       => $field['label'] ?? '',
                    'placeholder' => $field['placeholder'] ?? '',
                    'required'    => !empty($field['required']) ? 1 : 0,
                    'settings'    => json_encode($field),
                    'sort'        => $sortOrder++,
                ]);
            }
        }

        if (isset($input['settings'])) {
            $updateFields[]            = 'settings = :settings';
            $updateParams['settings']  = json_encode(is_array($input['settings']) ? $input['settings'] : []);
        }

        if (isset($input['is_published'])) {
            $updateFields[]            = 'status = :status';
            $updateParams['status']    = $input['is_published'] ? 'published' : 'draft';
        }

        if (empty($updateFields)) {
            return $this->json(['error' => 'No fields to update.'], 400);
        }

        $updateFields[] = 'updated_at = NOW()';

        $sql = "UPDATE forms SET " . implode(', ', $updateFields) . " WHERE id = :id AND tenant_id = :tid";
        $this->db()->prepare($sql)->execute($updateParams);

        // Return updated form
        $updatedForm = $this->findTenantForm((int) $id);
        $fieldsStmt = $this->db()->prepare("SELECT * FROM form_fields WHERE form_id = :fid ORDER BY sort_order ASC");
        $fieldsStmt->execute(['fid' => (int) $id]);
        $updatedForm['fields']   = $fieldsStmt->fetchAll(\PDO::FETCH_ASSOC);
        $updatedForm['settings'] = json_decode($updatedForm['settings'] ?? '{}', true) ?: [];

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
        $db->prepare("DELETE FROM entries WHERE form_id = :fid")->execute(['fid' => (int) $id]);

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
        $countStmt = $db->prepare("SELECT COUNT(*) FROM entries fe {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT fe.id, fe.form_id, fe.status, fe.ip_address, fe.user_agent, fe.referrer, fe.created_at
                  FROM entries fe
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

        // Load entry values from entry_values table
        foreach ($entries as &$entry) {
            $evStmt = $db->prepare(
                "SELECT ev.field_id, ev.value, ff.label FROM entry_values ev LEFT JOIN form_fields ff ON ff.id = ev.field_id WHERE ev.entry_id = :eid"
            );
            $evStmt->execute(['eid' => (int) $entry['id']]);
            $entry['values'] = $evStmt->fetchAll(\PDO::FETCH_ASSOC);
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
