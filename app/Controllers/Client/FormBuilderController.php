<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Form Builder Controller
 *
 * Renders the drag-and-drop form builder interface and handles
 * saving form field configurations.
 */
class FormBuilderController extends Controller
{
    /**
     * Show the form builder for a specific form.
     */
    public function index(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            return $this->redirect('/dashboard/forms', ['error' => 'Formulario nao encontrado.']);
        }

        $fields = json_decode($form['fields'] ?? '[]', true) ?: [];
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];
        $theme = json_decode($form['theme'] ?? '{}', true) ?: [];

        return $this->view('client/forms/builder', [
            'form'     => $form,
            'fields'   => $fields,
            'settings' => $settings,
            'theme'    => $theme,
        ], 'layouts.client');
    }

    /**
     * Save form builder data (fields, settings, theme).
     */
    public function save(string $id): string
    {
        $form = $this->findTenantForm((int) $id);

        if (!$form) {
            if ($this->isAjax()) {
                return $this->json(['success' => false, 'error' => 'Form not found.'], 404);
            }
            return $this->redirect('/dashboard/forms', ['error' => 'Formulario nao encontrado.']);
        }

        $db = $this->db();
        $updates = ['updated_at = NOW()'];
        $params = ['id' => (int) $id, 'tid' => (int) tenant()['id']];

        // Update fields
        if (isset($_POST['fields'])) {
            $decoded = json_decode($_POST['fields'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($this->isAjax()) {
                    return $this->json(['success' => false, 'error' => 'Invalid fields JSON.'], 400);
                }
                return $this->redirect("/dashboard/forms/{$id}/builder", ['error' => 'Dados de campos invalidos.']);
            }
            $updates[] = 'fields = :fields';
            $params['fields'] = json_encode($decoded);
        }

        // Update title
        if (isset($_POST['title']) && trim($_POST['title']) !== '') {
            $updates[] = 'title = :title';
            $params['title'] = trim($_POST['title']);
        }

        // Update description
        if (isset($_POST['description'])) {
            $updates[] = 'description = :description';
            $params['description'] = trim($_POST['description']);
        }

        // Update settings
        if (isset($_POST['settings'])) {
            $updates[] = 'settings = :settings';
            $params['settings'] = $_POST['settings'];
        }

        // Update theme
        if (isset($_POST['theme'])) {
            $updates[] = 'theme = :theme';
            $params['theme'] = $_POST['theme'];
        }

        $sql = "UPDATE forms SET " . implode(', ', $updates) . " WHERE id = :id AND tenant_id = :tid";
        $db->prepare($sql)->execute($params);

        if ($this->isAjax()) {
            return $this->json(['success' => true, 'message' => 'Formulario salvo com sucesso.']);
        }

        return $this->redirect("/dashboard/forms/{$id}/builder", ['success' => 'Formulario salvo com sucesso.']);
    }

    /**
     * Find a form belonging to the current tenant.
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
     * Check if the current request is an AJAX request.
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
