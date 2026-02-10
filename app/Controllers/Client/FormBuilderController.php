<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Form Builder Controller
 */
class FormBuilderController extends Controller
{
    public function index(string $id): string
    {
        $form = $this->findTenantForm((int) $id);
        if (!$form) {
            return $this->redirect('/dashboard/forms', ['error' => 'Formulario nao encontrado.']);
        }

        $fields = $this->getFormFields((int) $id);
        $settings = json_decode($form['settings'] ?? '{}', true) ?: [];

        return $this->view('client/forms/builder', [
            'form'     => $form,
            'fields'   => $fields,
            'settings' => $settings,
        ], 'layouts.client');
    }

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

        // Update fields in form_fields table
        if (isset($_POST['fields'])) {
            $decoded = json_decode($_POST['fields'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($this->isAjax()) {
                    return $this->json(['success' => false, 'error' => 'Invalid fields JSON.'], 400);
                }
                return $this->redirect("/dashboard/forms/{$id}/builder", ['error' => 'Dados de campos invalidos.']);
            }

            $db->prepare("DELETE FROM form_fields WHERE form_id = :fid")->execute(['fid' => (int) $id]);
            $sortOrder = 0;
            $fieldStmt = $db->prepare(
                "INSERT INTO form_fields (form_id, type, label, placeholder, required, settings, sort_order, created_at, updated_at)
                 VALUES (:fid, :type, :label, :placeholder, :required, :settings, :sort, NOW(), NOW())"
            );
            foreach ($decoded as $field) {
                $fieldStmt->execute([
                    'fid'         => (int) $id,
                    'type'        => $field['type'] ?? 'text',
                    'label'       => $field['label'] ?? $field['name'] ?? '',
                    'placeholder' => $field['placeholder'] ?? '',
                    'required'    => !empty($field['required']) ? 1 : 0,
                    'settings'    => json_encode($field['settings'] ?? $field),
                    'sort'        => $sortOrder++,
                ]);
            }
        }

        if (isset($_POST['title']) && trim($_POST['title']) !== '') {
            $updates[] = 'title = :title';
            $params['title'] = trim($_POST['title']);
        }
        if (isset($_POST['description'])) {
            $updates[] = 'description = :description';
            $params['description'] = trim($_POST['description']);
        }
        if (isset($_POST['settings'])) {
            $updates[] = 'settings = :settings';
            $params['settings'] = $_POST['settings'];
        }

        $sql = "UPDATE forms SET " . implode(', ', $updates) . " WHERE id = :id AND tenant_id = :tid";
        $db->prepare($sql)->execute($params);

        if ($this->isAjax()) {
            return $this->json(['success' => true, 'message' => 'Formulario salvo com sucesso.']);
        }
        return $this->redirect("/dashboard/forms/{$id}/builder", ['success' => 'Formulario salvo com sucesso.']);
    }

    private function findTenantForm(int $formId): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM forms WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $formId, 'tid' => (int) tenant()['id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function getFormFields(int $formId): array
    {
        $stmt = $this->db()->prepare("SELECT * FROM form_fields WHERE form_id = :fid ORDER BY sort_order ASC");
        $stmt->execute(['fid' => $formId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
