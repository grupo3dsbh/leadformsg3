<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Leads (Entries) Controller
 */
class LeadsController extends Controller
{
    public function index(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $formId  = $_GET['form_id'] ?? '';
        $status  = $_GET['status'] ?? '';
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = ['f.tenant_id = :tid'];
        $params = ['tid' => $tenantId];

        if ($formId !== '') {
            $where[]       = 'fe.form_id = :fid';
            $params['fid'] = (int) $formId;
        }
        if ($status !== '') {
            $where[]          = 'fe.status = :status';
            $params['status'] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) FROM entries fe JOIN forms f ON f.id = fe.form_id {$whereClause}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT fe.id, fe.form_id, fe.status, fe.ip_address, fe.device_type,
                       fe.created_at, f.title AS form_title
                  FROM entries fe JOIN forms f ON f.id = fe.form_id
                  {$whereClause} ORDER BY fe.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $leads = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($leads as &$lead) {
            $pvStmt = $db->prepare("SELECT value FROM entry_values WHERE entry_id = :eid LIMIT 1");
            $pvStmt->execute(['eid' => (int) $lead['id']]);
            $lead['preview'] = $pvStmt->fetchColumn() ?: '';
        }
        unset($lead);

        $forms = $db->prepare("SELECT id, title FROM forms WHERE tenant_id = :tid ORDER BY title ASC");
        $forms->execute(['tid' => $tenantId]);
        $forms = $forms->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/entries/index', [
            'leads'      => $leads,
            'forms'      => $forms,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'formId'     => $formId,
            'status'     => $status,
        ], 'layouts.client');
    }

    public function show(string $id): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $stmt = $db->prepare(
            "SELECT fe.*, f.title AS form_title
               FROM entries fe JOIN forms f ON f.id = fe.form_id
              WHERE fe.id = :id AND f.tenant_id = :tid"
        );
        $stmt->execute(['id' => (int) $id, 'tid' => $tenantId]);
        $lead = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$lead) {
            return $this->redirect('/dashboard/leads', ['error' => 'Lead nao encontrado.']);
        }

        $valuesStmt = $db->prepare(
            "SELECT ev.*, ff.label, ff.type AS field_type
               FROM entry_values ev
               LEFT JOIN form_fields ff ON ff.id = ev.field_id
              WHERE ev.entry_id = :eid ORDER BY ev.id ASC"
        );
        $valuesStmt->execute(['eid' => (int) $id]);
        $fieldValues = $valuesStmt->fetchAll(\PDO::FETCH_ASSOC);

        $notesStmt = $db->prepare(
            "SELECT n.*, u.name AS user_name FROM entry_notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.entry_id = :eid ORDER BY n.created_at ASC"
        );
        $notesStmt->execute(['eid' => (int) $id]);
        $notes = $notesStmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/entries/show', [
            'lead'        => $lead,
            'fieldValues' => $fieldValues,
            'notes'       => $notes,
        ], 'layouts.client');
    }

    public function destroy(string $id): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $stmt = $db->prepare("SELECT fe.id FROM entries fe JOIN forms f ON f.id = fe.form_id WHERE fe.id = :id AND f.tenant_id = :tid");
        $stmt->execute(['id' => (int) $id, 'tid' => $tenantId]);
        if (!$stmt->fetch()) {
            return $this->redirect('/dashboard/leads', ['error' => 'Lead nao encontrado.']);
        }

        $db->prepare("DELETE FROM entry_values WHERE entry_id = :eid")->execute(['eid' => (int) $id]);
        $db->prepare("DELETE FROM entries WHERE id = :id")->execute(['id' => (int) $id]);

        return $this->redirect('/dashboard/leads', ['success' => 'Lead excluido com sucesso.']);
    }

    public function export(): void
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];
        $formId   = $_POST['form_id'] ?? '';

        $where  = ['f.tenant_id = :tid'];
        $params = ['tid' => $tenantId];
        if ($formId !== '') {
            $where[]       = 'fe.form_id = :fid';
            $params['fid'] = (int) $formId;
        }
        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT fe.id, fe.form_id, fe.status, fe.ip_address, fe.created_at, f.title AS form_title FROM entries fe JOIN forms f ON f.id = fe.form_id {$whereClause} ORDER BY fe.created_at DESC");
        $stmt->execute($params);
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"leads_" . date('Y-m-d_His') . ".csv\"");

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Formulario', 'Status', 'IP', 'Data']);
        foreach ($entries as $entry) {
            fputcsv($output, [$entry['id'], $entry['form_title'], $entry['status'], $entry['ip_address'], $entry['created_at']]);
        }
        fclose($output);
        exit;
    }
}
