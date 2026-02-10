<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;
use App\Models\Tenant;

/**
 * Client Forms Controller
 *
 * Full lifecycle management for forms: CRUD, publishing, duplication,
 * preview, theme settings, and per-form settings.
 */
class FormsController extends Controller
{
    private Tenant $tenantModel;

    public function __construct()
    {
        parent::__construct();
        $this->tenantModel = new Tenant();
    }

    /**
     * List forms belonging to the current tenant with search and filters.
     */
    public function index(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $search  = trim($_GET['search'] ?? '');
        $status  = $_GET['status'] ?? '';
        $sortBy  = $_GET['sort'] ?? 'created_at';
        $sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset  = ($page - 1) * $perPage;

        $allowedSorts = ['title', 'created_at', 'updated_at', 'is_published'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

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

        // Total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM forms f {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT f.*,
                       (SELECT COUNT(*) FROM form_entries WHERE form_id = f.id) AS entry_count,
                       (SELECT MAX(created_at) FROM form_entries WHERE form_id = f.id) AS last_entry_at
                  FROM forms f
                  {$whereClause}
                  ORDER BY f.{$sortBy} {$sortDir}
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $forms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/forms/index', [
            'forms'      => $forms,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'search'     => $search,
            'status'     => $status,
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
        ]);
    }

    /**
     * Show the form builder page for creating a new form.
     */
    public function create(): string
    {
        $tenantData = tenant();

        // Check plan limits
        if (!$this->tenantModel->canCreateForm($tenantData)) {
            return $this->redirect('/client/forms', [
                'error' => 'You have reached the maximum number of forms allowed by your plan. Please upgrade.',
            ]);
        }

        return $this->view('client/forms/create', [
            'form' => [
                'title'        => '',
                'slug'         => '',
                'description'  => '',
                'fields'       => '[]',
                'settings'     => '{}',
                'theme'        => '{}',
                'is_published' => 0,
            ],
        ]);
    }

    /**
     * Save a newly created form.
     */
    public function store(): string
    {
        $tenantData = tenant();

        if (!$this->tenantModel->canCreateForm($tenantData)) {
            return $this->redirect('/client/forms', [
                'error' => 'Form limit reached for your current plan.',
            ]);
        }

        $errors = $this->validate($_POST, [
            'title'  => 'required|string|max:255',
            'fields' => 'required|string',
        ]);

        if (!empty($errors)) {
            return $this->view('client/forms/create', [
                'form'   => $_POST,
                'errors' => $errors,
            ]);
        }

        // Generate slug
        $slug = $this->generateSlug($_POST['title']);

        // Validate fields JSON
        $fields = json_decode($_POST['fields'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->view('client/forms/create', [
                'form'   => $_POST,
                'errors' => ['fields' => 'Invalid form field configuration.'],
            ]);
        }

        $db = $this->db();

        $stmt = $db->prepare(
            "INSERT INTO forms (tenant_id, title, slug, description, fields, settings, theme, is_published, views, created_by, created_at, updated_at)
             VALUES (:tid, :title, :slug, :desc, :fields, :settings, :theme, 0, 0, :uid, NOW(), NOW())"
        );
        $stmt->execute([
            'tid'      => (int) $tenantData['id'],
            'title'    => trim($_POST['title']),
            'slug'     => $slug,
            'desc'     => trim($_POST['description'] ?? ''),
            'fields'   => json_encode($fields),
            'settings' => $_POST['settings'] ?? '{}',
            'theme'    => $_POST['theme'] ?? '{}',
            'uid'      => auth()['id'],
        ]);

        $formId = $db->lastInsertId();

        return $this->redirect("/client/forms/{$formId}/edit", [
            'success' => 'Form created successfully.',
        ]);
    }

    /**
     * Open a form in the builder for editing.
     */
    public function edit(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $fields = json_decode($form['fields'] ?? '[]', true) ?: [];

        return $this->view('client/forms/edit', [
            'form'   => $form,
            'fields' => $fields,
        ]);
    }

    /**
     * Save form changes from the builder.
     */
    public function update(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $errors = $this->validate($_POST, [
            'title'  => 'required|string|max:255',
            'fields' => 'required|string',
        ]);

        if (!empty($errors)) {
            return $this->view('client/forms/edit', [
                'form'   => array_merge($form, $_POST),
                'fields' => json_decode($_POST['fields'] ?? '[]', true) ?: [],
                'errors' => $errors,
            ]);
        }

        $fields = json_decode($_POST['fields'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->view('client/forms/edit', [
                'form'   => array_merge($form, $_POST),
                'fields' => [],
                'errors' => ['fields' => 'Invalid form field configuration.'],
            ]);
        }

        $db = $this->db();

        $stmt = $db->prepare(
            "UPDATE forms
                SET title       = :title,
                    description = :desc,
                    fields      = :fields,
                    updated_at  = NOW()
              WHERE id = :id AND tenant_id = :tid"
        );
        $stmt->execute([
            'title'  => trim($_POST['title']),
            'desc'   => trim($_POST['description'] ?? ''),
            'fields' => json_encode($fields),
            'id'     => (int) $id,
            'tid'    => (int) tenant()['id'],
        ]);

        return $this->redirect("/client/forms/{$id}/edit", [
            'success' => 'Form saved successfully.',
        ]);
    }

    /**
     * Duplicate/clone a form.
     */
    public function duplicate(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $tenantData = tenant();

        if (!$this->tenantModel->canCreateForm($tenantData)) {
            return $this->redirect('/client/forms', [
                'error' => 'Form limit reached. Cannot duplicate.',
            ]);
        }

        $newTitle = $form['title'] . ' (Copy)';
        $newSlug  = $this->generateSlug($newTitle);

        $db = $this->db();

        $stmt = $db->prepare(
            "INSERT INTO forms (tenant_id, title, slug, description, fields, settings, theme, is_published, views, created_by, created_at, updated_at)
             VALUES (:tid, :title, :slug, :desc, :fields, :settings, :theme, 0, 0, :uid, NOW(), NOW())"
        );
        $stmt->execute([
            'tid'      => (int) $tenantData['id'],
            'title'    => $newTitle,
            'slug'     => $newSlug,
            'desc'     => $form['description'] ?? '',
            'fields'   => $form['fields'] ?? '[]',
            'settings' => $form['settings'] ?? '{}',
            'theme'    => $form['theme'] ?? '{}',
            'uid'      => auth()['id'],
        ]);

        $newFormId = $db->lastInsertId();

        return $this->redirect("/client/forms/{$newFormId}/edit", [
            'success' => 'Form duplicated successfully.',
        ]);
    }

    /**
     * Delete a form and all its entries.
     */
    public function delete(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $db = $this->db();

        // Delete entries first
        $db->prepare("DELETE FROM form_entries WHERE form_id = :fid")->execute(['fid' => (int) $id]);

        // Delete the form
        $db->prepare("DELETE FROM forms WHERE id = :id AND tenant_id = :tid")->execute([
            'id'  => (int) $id,
            'tid' => (int) tenant()['id'],
        ]);

        return $this->redirect('/client/forms', [
            'success' => "Form \"{$form['title']}\" has been deleted.",
        ]);
    }

    /**
     * Publish a form (make it publicly accessible).
     */
    public function publish(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $this->db()->prepare(
            "UPDATE forms SET is_published = 1, published_at = NOW(), updated_at = NOW() WHERE id = :id AND tenant_id = :tid"
        )->execute([
            'id'  => (int) $id,
            'tid' => (int) tenant()['id'],
        ]);

        return $this->redirect("/client/forms/{$id}/edit", [
            'success' => 'Form has been published.',
        ]);
    }

    /**
     * Unpublish a form (take it offline).
     */
    public function unpublish(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $this->db()->prepare(
            "UPDATE forms SET is_published = 0, updated_at = NOW() WHERE id = :id AND tenant_id = :tid"
        )->execute([
            'id'  => (int) $id,
            'tid' => (int) tenant()['id'],
        ]);

        return $this->redirect("/client/forms/{$id}/edit", [
            'success' => 'Form has been unpublished.',
        ]);
    }

    /**
     * Preview a form in its current state (published or draft).
     */
    public function preview(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $fields   = json_decode($form['fields'] ?? '[]', true) ?: [];
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];
        $theme    = json_decode($form['theme'] ?? '{}', true) ?: [];

        return $this->view('client/forms/preview', [
            'form'     => $form,
            'fields'   => $fields,
            'settings' => $settings,
            'theme'    => $theme,
        ]);
    }

    /**
     * Show the settings page for a specific form.
     */
    public function settings(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];

        return $this->view('client/forms/settings', [
            'form'     => $form,
            'settings' => $settings,
        ]);
    }

    /**
     * Save form settings (notification emails, redirect URL, rate limiting, etc.).
     */
    public function updateSettings(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $settings = [
            'notification_emails'  => array_filter(array_map('trim', explode(',', $_POST['notification_emails'] ?? ''))),
            'redirect_url'         => trim($_POST['redirect_url'] ?? ''),
            'success_message'      => trim($_POST['success_message'] ?? 'Thank you for your submission!'),
            'submit_button_text'   => trim($_POST['submit_button_text'] ?? 'Submit'),
            'honeypot_enabled'     => !empty($_POST['honeypot_enabled']),
            'recaptcha_enabled'    => !empty($_POST['recaptcha_enabled']),
            'rate_limit_enabled'   => !empty($_POST['rate_limit_enabled']),
            'rate_limit_max'       => (int) ($_POST['rate_limit_max'] ?? 5),
            'rate_limit_window'    => (int) ($_POST['rate_limit_window'] ?? 60),
            'auto_reply_enabled'   => !empty($_POST['auto_reply_enabled']),
            'auto_reply_subject'   => trim($_POST['auto_reply_subject'] ?? ''),
            'auto_reply_body'      => trim($_POST['auto_reply_body'] ?? ''),
            'closed_message'       => trim($_POST['closed_message'] ?? ''),
            'max_entries'          => (int) ($_POST['max_entries'] ?? 0),
            'expires_at'           => trim($_POST['expires_at'] ?? ''),
            'require_login'        => !empty($_POST['require_login']),
            'allowed_domains'      => trim($_POST['allowed_domains'] ?? ''),
        ];

        $this->db()->prepare(
            "UPDATE forms SET settings = :settings, updated_at = NOW() WHERE id = :id AND tenant_id = :tid"
        )->execute([
            'settings' => json_encode($settings),
            'id'       => (int) $id,
            'tid'      => (int) tenant()['id'],
        ]);

        return $this->redirect("/client/forms/{$id}/settings", [
            'success' => 'Form settings saved.',
        ]);
    }

    /**
     * Show form theme/styling settings.
     */
    public function theme(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $theme = json_decode($form['theme'] ?? '{}', true) ?: [];

        return $this->view('client/forms/theme', [
            'form'  => $form,
            'theme' => $theme,
        ]);
    }

    /**
     * Save form theme/styling settings.
     */
    public function updateTheme(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $theme = [
            'layout'                 => in_array($_POST['layout'] ?? '', ['default', 'card', 'inline', 'conversational'], true)
                ? $_POST['layout']
                : 'default',
            'primary_color'          => trim($_POST['primary_color'] ?? '#3B82F6'),
            'background_color'       => trim($_POST['background_color'] ?? '#FFFFFF'),
            'text_color'             => trim($_POST['text_color'] ?? '#111827'),
            'font_family'            => trim($_POST['font_family'] ?? 'Inter'),
            'font_size'              => trim($_POST['font_size'] ?? '16px'),
            'border_radius'          => trim($_POST['border_radius'] ?? '8px'),
            'button_style'           => in_array($_POST['button_style'] ?? '', ['filled', 'outlined', 'rounded'], true)
                ? $_POST['button_style']
                : 'filled',
            'show_progress_bar'      => !empty($_POST['show_progress_bar']),
            'show_field_labels'      => !empty($_POST['show_field_labels']),
            'show_branding'          => !empty($_POST['show_branding']),
            'custom_css'             => trim($_POST['custom_css'] ?? ''),
            'background_image'       => trim($_POST['background_image'] ?? ''),
            'logo_url'               => trim($_POST['logo_url'] ?? ''),
        ];

        $this->db()->prepare(
            "UPDATE forms SET theme = :theme, updated_at = NOW() WHERE id = :id AND tenant_id = :tid"
        )->execute([
            'theme' => json_encode($theme),
            'id'    => (int) $id,
            'tid'   => (int) tenant()['id'],
        ]);

        return $this->redirect("/client/forms/{$id}/theme", [
            'success' => 'Form theme saved.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Find a form ensuring it belongs to the current tenant.
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
     * Generate a URL-safe slug from a title, ensuring uniqueness within the tenant.
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));

        if ($slug === '') {
            $slug = 'form';
        }

        $db       = $this->db();
        $tenantId = (int) tenant()['id'];
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
