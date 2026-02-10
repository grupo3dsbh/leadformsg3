<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Core\Controller;

/**
 * Entry API Controller
 *
 * RESTful API endpoints for form entry management. All endpoints require
 * API key authentication and are scoped to the authenticated tenant.
 *
 * Routes:
 *   GET    /api/v1/entries/{id}  - Get a single entry
 *   POST   /api/v1/entries       - Create a new entry (form submission)
 *   DELETE /api/v1/entries/{id}  - Delete an entry
 */
class EntryApiController extends Controller
{
    /**
     * GET /api/v1/entries/{id}
     *
     * Retrieve a single entry by ID with all field values.
     */
    public function show(string $id): string
    {
        $entry = $this->findTenantEntry((int) $id);

        if (!$entry) {
            return $this->json(['error' => 'Entry not found.'], 404);
        }

        // Decode the data JSON
        $entry['data'] = json_decode($entry['data'] ?? '{}', true) ?: [];

        // Include form info
        $formStmt = $this->db()->prepare(
            "SELECT id, title, slug FROM forms WHERE id = :fid AND tenant_id = :tid"
        );
        $formStmt->execute([
            'fid' => (int) $entry['form_id'],
            'tid' => (int) tenant()['id'],
        ]);
        $form = $formStmt->fetch(\PDO::FETCH_ASSOC);

        // Include notes if any
        $notesStmt = $this->db()->prepare(
            "SELECT n.id, n.content, n.created_at, u.email AS user_email
               FROM entry_notes n
               LEFT JOIN users u ON u.id = n.user_id
              WHERE n.entry_id = :eid
              ORDER BY n.created_at ASC"
        );
        $notesStmt->execute(['eid' => (int) $id]);
        $notes = $notesStmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json([
            'data' => [
                'id'         => (int) $entry['id'],
                'form_id'    => (int) $entry['form_id'],
                'form'       => $form,
                'data'       => $entry['data'],
                'ip_address' => $entry['ip_address'] ?? null,
                'user_agent' => $entry['user_agent'] ?? null,
                'referrer'   => $entry['referrer'] ?? null,
                'notes'      => $notes,
                'created_at' => $entry['created_at'],
            ],
        ]);
    }

    /**
     * POST /api/v1/entries
     *
     * Create a new entry (form submission) via the API.
     *
     * Required fields:
     *   - form_id: The ID of the form to submit to
     *   - data: An object containing field values
     */
    public function store(): string
    {
        $input = $this->getJsonInput();

        // Validate required fields
        if (empty($input['form_id'])) {
            return $this->json([
                'error'  => 'Validation failed.',
                'errors' => ['form_id' => 'The form_id field is required.'],
            ], 422);
        }

        if (!isset($input['data']) || !is_array($input['data'])) {
            return $this->json([
                'error'  => 'Validation failed.',
                'errors' => ['data' => 'The data field is required and must be an object.'],
            ], 422);
        }

        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        // Verify the form exists and belongs to the tenant
        $formStmt = $db->prepare(
            "SELECT * FROM forms WHERE id = :fid AND tenant_id = :tid"
        );
        $formStmt->execute([
            'fid' => (int) $input['form_id'],
            'tid' => $tenantId,
        ]);
        $form = $formStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            return $this->json(['error' => 'Form not found.'], 404);
        }

        // Check if form is published (unless explicitly allowing draft submissions)
        if (($form['status'] ?? 'draft') !== 'published' && empty($input['allow_draft'])) {
            return $this->json(['error' => 'This form is not currently accepting submissions.'], 403);
        }

        // Check form settings for entry limits
        $formSettings = json_decode($form['settings'] ?? '{}', true) ?: [];
        if (!empty($formSettings['max_entries'])) {
            $entryCountStmt = $db->prepare("SELECT COUNT(*) FROM entries WHERE form_id = :fid");
            $entryCountStmt->execute(['fid' => (int) $form['id']]);
            $currentCount = (int) $entryCountStmt->fetchColumn();

            if ($currentCount >= (int) $formSettings['max_entries']) {
                return $this->json(['error' => 'This form has reached its maximum number of entries.'], 403);
            }
        }

        // Check expiration
        if (!empty($formSettings['expires_at'])) {
            $expiresAt = strtotime($formSettings['expires_at']);
            if ($expiresAt !== false && $expiresAt < time()) {
                return $this->json(['error' => 'This form has expired and is no longer accepting submissions.'], 403);
            }
        }

        // Validate submitted data against form fields
        $formFields   = json_decode($form['fields'] ?? '[]', true) ?: [];
        $validationErrors = $this->validateEntryData($input['data'], $formFields);

        if (!empty($validationErrors)) {
            return $this->json([
                'error'  => 'Validation failed.',
                'errors' => $validationErrors,
            ], 422);
        }

        // Store the entry
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? $input['ip_address'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? $input['user_agent'] ?? null;
        $referrer  = $input['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? null;

        $stmt = $db->prepare(
            "INSERT INTO entries (form_id, data, ip_address, user_agent, referrer, created_at)
             VALUES (:fid, :data, :ip, :ua, :ref, NOW())"
        );
        $stmt->execute([
            'fid'  => (int) $form['id'],
            'data' => json_encode($input['data']),
            'ip'   => $ipAddress,
            'ua'   => $userAgent,
            'ref'  => $referrer,
        ]);

        $entryId = (int) $db->lastInsertId();

        // Fetch the created entry
        $entryStmt = $db->prepare("SELECT * FROM entries WHERE id = :id");
        $entryStmt->execute(['id' => $entryId]);
        $entry = $entryStmt->fetch(\PDO::FETCH_ASSOC);

        $entry['data'] = json_decode($entry['data'] ?? '{}', true) ?: [];

        return $this->json([
            'data'    => $entry,
            'message' => 'Entry created successfully.',
        ], 201);
    }

    /**
     * DELETE /api/v1/entries/{id}
     *
     * Delete an entry by ID.
     */
    public function delete(string $id): string
    {
        $entry = $this->findTenantEntry((int) $id);

        if (!$entry) {
            return $this->json(['error' => 'Entry not found.'], 404);
        }

        $db = $this->db();

        // Delete associated notes
        $db->prepare("DELETE FROM entry_notes WHERE entry_id = :eid")->execute(['eid' => (int) $id]);

        // Delete the entry
        $db->prepare("DELETE FROM entries WHERE id = :id")->execute(['id' => (int) $id]);

        return $this->json(['message' => 'Entry deleted successfully.']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Find an entry scoped to the current tenant (through its form).
     */
    private function findTenantEntry(int $entryId): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT fe.*
               FROM entries fe
               JOIN forms f ON f.id = fe.form_id
              WHERE fe.id = :id AND f.tenant_id = :tid"
        );
        $stmt->execute([
            'id'  => $entryId,
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
     * Validate entry data against form field definitions.
     *
     * Returns an array of field-level errors, or an empty array on success.
     */
    private function validateEntryData(array $data, array $formFields): array
    {
        $errors = [];

        foreach ($formFields as $field) {
            $name     = $field['name'] ?? $field['key'] ?? '';
            $label    = $field['label'] ?? $name;
            $type     = $field['type'] ?? 'text';
            $required = !empty($field['required']);

            if ($name === '') {
                continue;
            }

            $value = $data[$name] ?? null;

            // Required check
            if ($required && ($value === null || $value === '' || $value === [])) {
                $errors[$name] = "{$label} is required.";
                continue;
            }

            // Skip further validation if value is empty and not required
            if ($value === null || $value === '') {
                continue;
            }

            // Type-specific validation
            switch ($type) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$name] = "{$label} must be a valid email address.";
                    }
                    break;

                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[$name] = "{$label} must be a valid URL.";
                    }
                    break;

                case 'number':
                    if (!is_numeric($value)) {
                        $errors[$name] = "{$label} must be a number.";
                    }
                    if (isset($field['min']) && (float) $value < (float) $field['min']) {
                        $errors[$name] = "{$label} must be at least {$field['min']}.";
                    }
                    if (isset($field['max']) && (float) $value > (float) $field['max']) {
                        $errors[$name] = "{$label} must be at most {$field['max']}.";
                    }
                    break;

                case 'tel':
                case 'phone':
                    if (!preg_match('/^[\d\s\-\+\(\)\.]+$/', (string) $value)) {
                        $errors[$name] = "{$label} must be a valid phone number.";
                    }
                    break;

                case 'select':
                case 'radio':
                    if (!empty($field['options'])) {
                        $validOptions = array_column($field['options'], 'value');
                        if (!in_array($value, $validOptions, true)) {
                            $errors[$name] = "{$label} contains an invalid selection.";
                        }
                    }
                    break;

                case 'checkbox':
                    if (!empty($field['options']) && is_array($value)) {
                        $validOptions = array_column($field['options'], 'value');
                        foreach ($value as $v) {
                            if (!in_array($v, $validOptions, true)) {
                                $errors[$name] = "{$label} contains an invalid selection.";
                                break;
                            }
                        }
                    }
                    break;
            }

            // Max length check
            if (isset($field['maxlength']) && is_string($value) && mb_strlen($value) > (int) $field['maxlength']) {
                $errors[$name] = "{$label} must not exceed {$field['maxlength']} characters.";
            }
        }

        return $errors;
    }
}
