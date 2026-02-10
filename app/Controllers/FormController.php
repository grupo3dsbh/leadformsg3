<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;

/**
 * Public Form Controller
 *
 * Handles all public-facing form operations: rendering a published form,
 * accepting submissions, previewing unpublished forms, displaying the
 * "thank you" page, saving partial (incomplete) entries and resuming
 * previously saved partial entries.
 *
 * This controller is NOT behind the auth middleware -- forms are meant to
 * be filled out by anonymous respondents.
 */
class FormController extends Controller
{
    // ==================================================================
    // RENDER FORM
    // ==================================================================

    /**
     * Load and render a published form by its public slug.
     *
     * Checks: published status, start/end dates, password protection,
     * unique-link restrictions and entry limits before rendering.
     *
     * @param string $slug The form's public slug.
     */
    public function render(string $slug): string
    {
        $form = $this->loadFormBySlug($slug);

        if ($form === null) {
            http_response_code(404);
            return $this->view('errors/404', ['title' => 'Form Not Found']);
        }

        // ---- Status check ----
        if (($form['status'] ?? '') !== 'published') {
            http_response_code(404);
            return $this->view('form/closed', [
                'title'   => 'Form Unavailable',
                'message' => 'This form is not currently accepting responses.',
            ]);
        }

        // ---- Date window check ----
        if (!$this->isWithinDateWindow($form)) {
            return $this->view('form/closed', [
                'title'   => $form['title'] ?? 'Form Closed',
                'message' => $form['closed_message'] ?? 'This form is no longer accepting responses.',
                'form'    => $form,
            ]);
        }

        // ---- Entry limit check ----
        if ($this->hasReachedEntryLimit($form)) {
            return $this->view('form/closed', [
                'title'   => $form['title'] ?? 'Form Closed',
                'message' => $form['limit_message'] ?? 'This form has reached its maximum number of responses.',
                'form'    => $form,
            ]);
        }

        // ---- Password protection ----
        if ($this->isPasswordProtected($form) && !$this->isPasswordVerified($form)) {
            return $this->view('form/password', [
                'title' => $form['title'] ?? 'Protected Form',
                'slug'  => $slug,
                'form'  => $form,
            ]);
        }

        // ---- Unique-link restriction ----
        $uniqueToken = $_GET['token'] ?? null;
        if ($this->requiresUniqueLink($form)) {
            if ($uniqueToken === null || !$this->isValidUniqueToken($form, $uniqueToken)) {
                return $this->view('form/closed', [
                    'title'   => 'Access Denied',
                    'message' => 'A valid invitation link is required to access this form.',
                    'form'    => $form,
                ]);
            }
        }

        // ---- Track visit ----
        $this->trackFormVisit($form);

        // ---- Load form fields ----
        $fields   = $this->loadFormFields((int) $form['id']);
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];

        // ---- Determine template style ----
        $template = match ($settings['display_mode'] ?? 'conversational') {
            'typeform'       => 'form/typeform',
            'classic'        => 'form/classic',
            'conversational' => 'form/conversational',
            default          => 'form/conversational',
        };

        return $this->view($template, [
            'title'       => $form['title'] ?? 'Form',
            'description' => $form['description'] ?? '',
            'form'        => $form,
            'fields'      => $fields,
            'settings'    => $settings,
            'slug'        => $slug,
            'token'       => $uniqueToken,
            'csrf_token'  => $this->getCsrfToken(),
        ]);
    }

    // ==================================================================
    // SUBMIT FORM
    // ==================================================================

    /**
     * Process a form submission.
     *
     * Validates the entry, saves it to the entries and entry_values tables,
     * triggers any configured webhooks/integrations, and redirects to the
     * appropriate completion action (thank-you page, external URL, or message).
     *
     * @param string $slug The form's public slug.
     */
    public function submit(string $slug): string|null
    {
        $form = $this->loadFormBySlug($slug);

        if ($form === null) {
            http_response_code(404);
            return $this->view('errors/404', ['title' => 'Form Not Found']);
        }

        if (($form['status'] ?? '') !== 'published') {
            return $this->json(['error' => true, 'message' => 'Form is not accepting responses.'], 403);
        }

        // ---- CSRF for web submissions ----
        if (!$this->isApiRequest()) {
            $token        = $_POST['_token'] ?? '';
            $sessionToken = $_SESSION['_csrf_token'] ?? '';
            if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
                return $this->redirectBack($slug, 'Invalid security token. Please try again.');
            }
        }

        // ---- Load fields and validate ----
        $formId = (int) $form['id'];
        $fields = $this->loadFormFields($formId);
        $values = $this->extractFieldValues($fields);
        $errors = $this->validateSubmission($fields, $values);

        if (!empty($errors)) {
            if ($this->isApiRequest()) {
                return $this->json(['error' => true, 'errors' => $errors], 422);
            }
            $_SESSION['_flash']['errors']     = $errors;
            $_SESSION['_flash']['old_values'] = $values;
            return $this->redirect("/f/{$slug}");
        }

        // ---- Handle file uploads ----
        $fileValues = $this->processFileUploads($fields, $formId);
        $values     = array_merge($values, $fileValues);

        // ---- Create entry ----
        $entryId = $this->createEntry($form, $values);

        if ($entryId === 0) {
            if ($this->isApiRequest()) {
                return $this->json(['error' => true, 'message' => 'Failed to save submission.'], 500);
            }
            return $this->redirectBack($slug, 'An error occurred. Please try again.');
        }

        // ---- Save entry values ----
        $this->saveEntryValues($entryId, $formId, $fields, $values);

        // ---- Update entry count ----
        $this->incrementEntryCount($formId);

        // ---- Mark unique token as used ----
        $uniqueToken = $_POST['_unique_token'] ?? $_GET['token'] ?? null;
        if ($uniqueToken !== null) {
            $this->markUniqueTokenUsed($formId, $uniqueToken);
        }

        // ---- Trigger webhooks and integrations ----
        $this->triggerWebhooks($form, $entryId, $values);
        $this->triggerIntegrations($form, $entryId, $values);

        // ---- Send notification emails ----
        $this->sendNotificationEmails($form, $entryId, $values);

        // ---- Determine completion action ----
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];

        if ($this->isApiRequest()) {
            return $this->json([
                'success'  => true,
                'entry_id' => $entryId,
                'message'  => $settings['success_message'] ?? 'Thank you for your submission!',
            ]);
        }

        $completionAction = $settings['completion_action'] ?? 'thankyou';

        return match ($completionAction) {
            'redirect' => $this->redirect($settings['redirect_url'] ?? "/f/{$slug}/thankyou"),
            'message'  => $this->view('form/success', [
                'title'   => 'Thank You',
                'message' => $settings['success_message'] ?? 'Thank you for your submission!',
                'form'    => $form,
            ]),
            default    => $this->redirect("/f/{$slug}/thankyou"),
        };
    }

    // ==================================================================
    // PREVIEW
    // ==================================================================

    /**
     * Preview a form (for authenticated form owners only).
     *
     * @param string $formId The form ID.
     */
    public function preview(string $formId): string
    {
        $form = $this->loadFormById((int) $formId);

        if ($form === null) {
            http_response_code(404);
            return $this->view('errors/404', ['title' => 'Form Not Found']);
        }

        // Verify the current user owns this form (via session).
        $sessionTenantId = (int) ($_SESSION['user']['tenant_id'] ?? 0);
        if ($sessionTenantId === 0 || $sessionTenantId !== (int) ($form['tenant_id'] ?? 0)) {
            http_response_code(403);
            return $this->view('errors/403', ['title' => 'Access Denied']);
        }

        $fields   = $this->loadFormFields((int) $form['id']);
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];

        $template = match ($settings['display_mode'] ?? 'conversational') {
            'typeform'       => 'form/typeform',
            'classic'        => 'form/classic',
            'conversational' => 'form/conversational',
            default          => 'form/conversational',
        };

        return $this->view($template, [
            'title'      => '[Preview] ' . ($form['title'] ?? 'Form'),
            'form'       => $form,
            'fields'     => $fields,
            'settings'   => $settings,
            'is_preview' => true,
            'slug'       => $form['slug'] ?? '',
            'csrf_token' => $this->getCsrfToken(),
        ]);
    }

    // ==================================================================
    // THANK YOU
    // ==================================================================

    /**
     * Display the thank-you / confirmation page after a successful submission.
     *
     * @param string $slug The form's public slug.
     */
    public function thankyou(string $slug): string
    {
        $form = $this->loadFormBySlug($slug);

        if ($form === null) {
            http_response_code(404);
            return $this->view('errors/404', ['title' => 'Form Not Found']);
        }

        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];

        return $this->view('form/thankyou', [
            'title'   => $settings['thankyou_title'] ?? 'Thank You!',
            'message' => $settings['thankyou_message'] ?? 'Your response has been recorded successfully.',
            'form'    => $form,
            'settings' => $settings,
        ]);
    }

    // ==================================================================
    // PARTIAL SAVE
    // ==================================================================

    /**
     * Save a partial (incomplete) entry so the respondent can resume later.
     *
     * Returns a JSON response with a continuation token.
     *
     * @param string $slug The form's public slug.
     */
    public function savePartial(string $slug): string
    {
        $form = $this->loadFormBySlug($slug);

        if ($form === null) {
            return $this->json(['error' => true, 'message' => 'Form not found.'], 404);
        }

        $formId = (int) $form['id'];
        $fields = $this->loadFormFields($formId);
        $values = $this->extractFieldValues($fields);

        // Generate a unique continuation token.
        $token = bin2hex(random_bytes(32));

        try {
            $db = \Core\Database::getInstance();

            // Create the partial entry.
            $stmt = $db->prepare(
                'INSERT INTO entries (form_id, tenant_id, status, continuation_token, ip_address, user_agent, created_at, updated_at)
                 VALUES (:form_id, :tenant_id, :status, :token, :ip, :ua, :created_at, :updated_at)'
            );
            $stmt->execute([
                'form_id'    => $formId,
                'tenant_id'  => (int) ($form['tenant_id'] ?? 0),
                'status'     => 'partial',
                'token'      => $token,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $entryId = (int) $db->lastInsertId();

            // Save current field values.
            $this->saveEntryValues($entryId, $formId, $fields, $values);

            // Store the current step/progress.
            $currentStep = (int) ($_POST['_current_step'] ?? 0);
            $db->prepare(
                'UPDATE entries SET metadata = :metadata WHERE id = :id'
            )->execute([
                'metadata' => json_encode(['last_step' => $currentStep, 'saved_at' => date('Y-m-d H:i:s')]),
                'id'       => $entryId,
            ]);

            return $this->json([
                'success' => true,
                'token'   => $token,
                'message' => 'Progress saved. You can resume using the provided link.',
                'resume_url' => ($_ENV['APP_URL'] ?? '') . "/f/{$slug}/continue/{$token}",
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => true, 'message' => 'Failed to save progress.'], 500);
        }
    }

    // ==================================================================
    // CONTINUE FORM
    // ==================================================================

    /**
     * Resume a previously saved partial entry.
     *
     * @param string $token The continuation token.
     */
    public function continueForm(string $token): string
    {
        if ($token === '') {
            http_response_code(400);
            return $this->view('form/closed', [
                'title'   => 'Invalid Link',
                'message' => 'The continuation link is invalid.',
            ]);
        }

        // Load the partial entry.
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT e.*, f.slug as form_slug, f.title as form_title, f.settings as form_settings, f.status as form_status
                 FROM entries e
                 JOIN forms f ON f.id = e.form_id
                 WHERE e.continuation_token = :token AND e.status = :status
                 LIMIT 1'
            );
            $stmt->execute(['token' => $token, 'status' => 'partial']);
            $entry = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $entry = null;
        }

        if ($entry === null) {
            http_response_code(404);
            return $this->view('form/closed', [
                'title'   => 'Entry Not Found',
                'message' => 'This continuation link is invalid or has already been used.',
            ]);
        }

        // Load the form.
        $formId = (int) $entry['form_id'];
        $form   = $this->loadFormById($formId);

        if ($form === null || ($form['status'] ?? '') !== 'published') {
            return $this->view('form/closed', [
                'title'   => 'Form Unavailable',
                'message' => 'This form is no longer accepting responses.',
            ]);
        }

        // Load saved values.
        $fields      = $this->loadFormFields($formId);
        $savedValues = $this->loadEntryValues((int) $entry['id']);
        $metadata    = json_decode($entry['metadata'] ?? '{}', true) ?: [];
        $settings    = json_decode($form['settings'] ?? '{}', true) ?: [];

        $template = match ($settings['display_mode'] ?? 'conversational') {
            'typeform'       => 'form/typeform',
            'classic'        => 'form/classic',
            'conversational' => 'form/conversational',
            default          => 'form/conversational',
        };

        return $this->view($template, [
            'title'        => $form['title'] ?? 'Form',
            'form'         => $form,
            'fields'       => $fields,
            'settings'     => $settings,
            'slug'         => $form['slug'] ?? '',
            'saved_values' => $savedValues,
            'current_step' => $metadata['last_step'] ?? 0,
            'entry_id'     => (int) $entry['id'],
            'continue_token' => $token,
            'csrf_token'   => $this->getCsrfToken(),
        ]);
    }

    // ==================================================================
    // PRIVATE HELPERS: Form Loading
    // ==================================================================

    /**
     * Load a form by its public slug.
     */
    private function loadFormBySlug(string $slug): ?array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT * FROM forms WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $form = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $form ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Load a form by its ID.
     */
    private function loadFormById(int $id): ?array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT * FROM forms WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $form = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $form ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Load all fields for a form, ordered by sort_order.
     */
    private function loadFormFields(int $formId): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT * FROM form_fields WHERE form_id = :form_id ORDER BY sort_order ASC'
            );
            $stmt->execute(['form_id' => $formId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ==================================================================
    // PRIVATE HELPERS: Validation & Access Control
    // ==================================================================

    /**
     * Check if the form is within its active date window.
     */
    private function isWithinDateWindow(array $form): bool
    {
        $now = date('Y-m-d H:i:s');

        if (!empty($form['starts_at']) && $form['starts_at'] > $now) {
            return false;
        }

        if (!empty($form['expires_at']) && $form['expires_at'] < $now) {
            return false;
        }

        return true;
    }

    /**
     * Check if the form has reached its entry limit.
     */
    private function hasReachedEntryLimit(array $form): bool
    {
        $limit = (int) ($form['entry_limit'] ?? 0);

        if ($limit <= 0) {
            return false; // No limit set.
        }

        $currentCount = (int) ($form['entry_count'] ?? 0);

        return $currentCount >= $limit;
    }

    /**
     * Check if the form requires a password.
     */
    private function isPasswordProtected(array $form): bool
    {
        return !empty($form['password']);
    }

    /**
     * Check if the visitor has already verified the form password (stored in session).
     */
    private function isPasswordVerified(array $form): bool
    {
        $formId = (int) ($form['id'] ?? 0);

        // Check POST for password submission.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_password'])) {
            if (hash_equals($form['password'], $_POST['form_password'])) {
                $_SESSION["form_password_{$formId}"] = true;
                return true;
            }
        }

        return !empty($_SESSION["form_password_{$formId}"]);
    }

    /**
     * Check if the form requires a unique invitation link.
     */
    private function requiresUniqueLink(array $form): bool
    {
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];
        return !empty($settings['require_unique_link']);
    }

    /**
     * Validate a unique access token for the form.
     */
    private function isValidUniqueToken(array $form, string $token): bool
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT * FROM form_tokens
                 WHERE form_id = :form_id AND token = :token AND is_used = 0
                   AND (expires_at IS NULL OR expires_at > NOW())
                 LIMIT 1'
            );
            $stmt->execute([
                'form_id' => (int) $form['id'],
                'token'   => $token,
            ]);
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return false;
        }
    }

    // ==================================================================
    // PRIVATE HELPERS: Submission Processing
    // ==================================================================

    /**
     * Extract submitted field values from POST data.
     */
    private function extractFieldValues(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $key = 'field_' . ($field['id'] ?? $field['slug'] ?? '');
            $values[$key] = $_POST[$key] ?? null;
        }

        return $values;
    }

    /**
     * Validate submitted values against field rules.
     */
    private function validateSubmission(array $fields, array $values): array
    {
        $errors = [];

        foreach ($fields as $field) {
            $key   = 'field_' . ($field['id'] ?? $field['slug'] ?? '');
            $value = $values[$key] ?? null;
            $label = $field['label'] ?? $field['title'] ?? "Field #{$field['id']}";
            $rules = json_decode($field['validation_rules'] ?? '{}', true) ?: [];

            // Required check.
            if (!empty($field['is_required']) && ($value === null || $value === '' || $value === [])) {
                $errors[$key] = "{$label} is required.";
                continue;
            }

            if ($value === null || $value === '') {
                continue; // Not required and empty -- skip further validation.
            }

            // Type-specific validation.
            $type = $field['type'] ?? 'text';

            if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$key] = "{$label} must be a valid email address.";
            }

            if ($type === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[$key] = "{$label} must be a valid URL.";
            }

            if ($type === 'number') {
                if (!is_numeric($value)) {
                    $errors[$key] = "{$label} must be a number.";
                } else {
                    if (isset($rules['min']) && (float) $value < (float) $rules['min']) {
                        $errors[$key] = "{$label} must be at least {$rules['min']}.";
                    }
                    if (isset($rules['max']) && (float) $value > (float) $rules['max']) {
                        $errors[$key] = "{$label} must be no more than {$rules['max']}.";
                    }
                }
            }

            if (in_array($type, ['text', 'textarea', 'long_text'], true)) {
                if (isset($rules['min_length']) && mb_strlen($value) < (int) $rules['min_length']) {
                    $errors[$key] = "{$label} must be at least {$rules['min_length']} characters.";
                }
                if (isset($rules['max_length']) && mb_strlen($value) > (int) $rules['max_length']) {
                    $errors[$key] = "{$label} must be no more than {$rules['max_length']} characters.";
                }
            }

            if ($type === 'phone') {
                $cleaned = preg_replace('/[^0-9+\-() ]/', '', $value);
                if (strlen($cleaned) < 8) {
                    $errors[$key] = "{$label} must be a valid phone number.";
                }
            }

            if ($type === 'cpf' && !$this->validateCpf($value)) {
                $errors[$key] = "{$label} must be a valid CPF.";
            }

            if ($type === 'cnpj' && !$this->validateCnpj($value)) {
                $errors[$key] = "{$label} must be a valid CNPJ.";
            }
        }

        return $errors;
    }

    /**
     * Process file upload fields.
     */
    private function processFileUploads(array $fields, int $formId): array
    {
        $values = [];

        foreach ($fields as $field) {
            if (!in_array($field['type'] ?? '', ['file', 'image', 'document'], true)) {
                continue;
            }

            $key = 'field_' . ($field['id'] ?? '');

            if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $file = $_FILES[$key];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Validate file size (default max 10MB).
            $maxSize = (int) ($field['max_file_size'] ?? 10485760);
            if ($file['size'] > $maxSize) {
                continue;
            }

            // Validate file type.
            $allowedTypes = $this->getAllowedFileTypes($field);
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!empty($allowedTypes) && !in_array($ext, $allowedTypes, true)) {
                continue;
            }

            // Move to storage.
            $storagePath = dirname(__DIR__, 2) . '/storage/uploads/forms/' . $formId;
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $fullPath = $storagePath . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                $values[$key] = json_encode([
                    'filename'      => $filename,
                    'original_name' => $file['name'],
                    'size'          => $file['size'],
                    'mime_type'     => $file['type'],
                    'path'          => "uploads/forms/{$formId}/{$filename}",
                ]);
            }
        }

        return $values;
    }

    /**
     * Create an entry record in the entries table.
     */
    private function createEntry(array $form, array $values): int
    {
        try {
            $db = \Core\Database::getInstance();

            $stmt = $db->prepare(
                'INSERT INTO entries (form_id, tenant_id, status, ip_address, user_agent, referrer, created_at, updated_at)
                 VALUES (:form_id, :tenant_id, :status, :ip, :ua, :referrer, :created_at, :updated_at)'
            );
            $stmt->execute([
                'form_id'    => (int) $form['id'],
                'tenant_id'  => (int) ($form['tenant_id'] ?? 0),
                'status'     => 'completed',
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer'   => $_SERVER['HTTP_REFERER'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return (int) $db->lastInsertId();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Save individual field values for an entry.
     */
    private function saveEntryValues(int $entryId, int $formId, array $fields, array $values): void
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO entry_values (entry_id, form_id, field_id, field_slug, value, created_at)
                 VALUES (:entry_id, :form_id, :field_id, :field_slug, :value, :created_at)'
            );

            foreach ($fields as $field) {
                $key   = 'field_' . ($field['id'] ?? $field['slug'] ?? '');
                $value = $values[$key] ?? null;

                if ($value === null) {
                    continue;
                }

                // Serialize arrays (checkboxes, multi-select).
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                $stmt->execute([
                    'entry_id'   => $entryId,
                    'form_id'    => $formId,
                    'field_id'   => (int) ($field['id'] ?? 0),
                    'field_slug' => $field['slug'] ?? '',
                    'value'      => (string) $value,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable) {
            // Log but don't break the flow.
        }
    }

    /**
     * Load saved entry values for a partial entry.
     */
    private function loadEntryValues(int $entryId): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT field_id, field_slug, value FROM entry_values WHERE entry_id = :entry_id'
            );
            $stmt->execute(['entry_id' => $entryId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $values = [];
            foreach ($rows as $row) {
                $key = 'field_' . ($row['field_id'] ?: $row['field_slug']);
                $values[$key] = $row['value'];
            }

            return $values;
        } catch (\Throwable) {
            return [];
        }
    }

    // ==================================================================
    // PRIVATE HELPERS: Tracking & Notifications
    // ==================================================================

    /**
     * Track a form visit for analytics.
     */
    private function trackFormVisit(array $form): void
    {
        try {
            $db = \Core\Database::getInstance();

            // Increment form view counter.
            $db->prepare('UPDATE forms SET view_count = view_count + 1 WHERE id = :id')
               ->execute(['id' => (int) $form['id']]);

            // Log detailed visit.
            $db->prepare(
                'INSERT INTO form_visits (form_id, ip_address, user_agent, referrer, created_at)
                 VALUES (:form_id, :ip, :ua, :referrer, :created_at)'
            )->execute([
                'form_id'    => (int) $form['id'],
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer'   => $_SERVER['HTTP_REFERER'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Non-critical.
        }
    }

    /**
     * Increment the entry counter on the form record.
     */
    private function incrementEntryCount(int $formId): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare('UPDATE forms SET entry_count = entry_count + 1 WHERE id = :id')
               ->execute(['id' => $formId]);
        } catch (\Throwable) {
            // Non-critical.
        }
    }

    /**
     * Mark a unique access token as used.
     */
    private function markUniqueTokenUsed(int $formId, string $token): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare(
                'UPDATE form_tokens SET is_used = 1, used_at = NOW() WHERE form_id = :form_id AND token = :token'
            )->execute(['form_id' => $formId, 'token' => $token]);
        } catch (\Throwable) {
            // Non-critical.
        }
    }

    /**
     * Trigger configured webhooks for the form.
     */
    private function triggerWebhooks(array $form, int $entryId, array $values): void
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT * FROM webhooks WHERE form_id = :form_id AND is_active = 1'
            );
            $stmt->execute(['form_id' => (int) $form['id']]);
            $webhooks = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($webhooks as $webhook) {
                $payload = json_encode([
                    'event'    => 'form.submitted',
                    'form_id'  => (int) $form['id'],
                    'entry_id' => $entryId,
                    'values'   => $values,
                    'submitted_at' => date('Y-m-d\TH:i:sP'),
                ]);

                // Fire-and-forget using a non-blocking approach.
                $this->sendWebhookAsync($webhook['url'], $payload, $webhook['secret'] ?? '');
            }
        } catch (\Throwable) {
            // Non-critical.
        }
    }

    /**
     * Trigger third-party integrations (Google Sheets, Zapier, etc.).
     */
    private function triggerIntegrations(array $form, int $entryId, array $values): void
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT i.* FROM integrations i JOIN form_integrations fi ON fi.integration_id = i.id WHERE fi.form_id = :form_id AND i.status = \'active\''
            );
            $stmt->execute(['form_id' => (int) $form['id']]);
            $integrations = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($integrations as $integration) {
                // Queue integration processing for background execution.
                $db->prepare(
                    'INSERT INTO integration_queue (integration_id, entry_id, payload, status, created_at)
                     VALUES (:integration_id, :entry_id, :payload, :status, :created_at)'
                )->execute([
                    'integration_id' => (int) $integration['id'],
                    'entry_id'       => $entryId,
                    'payload'        => json_encode($values),
                    'status'         => 'pending',
                    'created_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable) {
            // Non-critical.
        }
    }

    /**
     * Send notification emails to form owner(s).
     */
    private function sendNotificationEmails(array $form, int $entryId, array $values): void
    {
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];

        if (empty($settings['notification_emails'])) {
            return;
        }

        // TODO: Integrate with mailer service.
        // Recipients: $settings['notification_emails'] (comma-separated list)
    }

    /**
     * Send a webhook POST request asynchronously (non-blocking).
     */
    private function sendWebhookAsync(string $url, string $payload, string $secret = ''): void
    {
        $headers = [
            'Content-Type: application/json',
            'User-Agent: LeadFormSaaS-Webhook/1.0',
        ];

        if ($secret !== '') {
            $signature = hash_hmac('sha256', $payload, $secret);
            $headers[] = "X-Webhook-Signature: sha256={$signature}";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // ==================================================================
    // PRIVATE HELPERS: Misc
    // ==================================================================

    /**
     * Get allowed file extensions for an upload field.
     */
    private function getAllowedFileTypes(array $field): array
    {
        $rules = json_decode($field['validation_rules'] ?? '{}', true) ?: [];

        if (!empty($rules['allowed_types'])) {
            return array_map('trim', explode(',', $rules['allowed_types']));
        }

        // Default by field type.
        return match ($field['type'] ?? 'file') {
            'image'    => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
            default    => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip'],
        };
    }

    /**
     * Validate a Brazilian CPF number.
     */
    private function validateCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a Brazilian CNPJ number.
     */
    private function validateCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cnpj[13] === $digit2;
    }

    /**
     * Get or generate a CSRF token.
     */
    private function getCsrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Check if the current request is an API/AJAX request.
     */
    private function isApiRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json')
            || strtolower($xhr) === 'xmlhttprequest';
    }

    /**
     * Redirect back to the form with an error message.
     */
    private function redirectBack(string $slug, string $error): never
    {
        $_SESSION['_flash']['error'] = $error;
        header("Location: /f/{$slug}", true, 302);
        exit;
    }
}
