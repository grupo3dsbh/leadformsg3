<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Entries Controller
 *
 * Manages form submissions (entries) for the current tenant.
 */
class EntriesController extends Controller
{
    public function index(string $formId): string
    {
        $db   = $this->db();
        $form = $this->findTenantForm((int) $formId);

        if (!$form) {
            return $this->redirect('/dashboard/forms', ['error' => 'Form not found.']);
        }

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';
        $sortDir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = 25;
        $offset   = ($page - 1) * $perPage;

        $where  = ['fe.form_id = :fid'];
        $params = ['fid' => (int) $formId];

        if ($dateFrom !== '') {
            $where[]             = 'fe.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $where[]           = 'fe.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM entries fe {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT fe.* FROM entries fe {$whereClause} ORDER BY fe.created_at {$sortDir} LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $formFields = $this->getFormFields((int) $formId);

        foreach ($entries as &$entry) {
            $pvStmt = $db->prepare(
                "SELECT ev.field_id, ev.value, ff.label
                   FROM entry_values ev
                   LEFT JOIN form_fields ff ON ff.id = ev.field_id
                  WHERE ev.entry_id = :eid ORDER BY ev.id ASC"
            );
            $pvStmt->execute(['eid' => (int) $entry['id']]);
            $entry['field_values'] = $pvStmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        unset($entry);

        return $this->view('client/entries/index', [
            'form'       => $form,
            'fields'     => $formFields,
            'entries'    => $entries,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'sortDir'    => $sortDir,
        ], 'layouts.client');
    }

    public function show(string $id): string
    {
        $db    = $this->db();
        $entry = $this->findTenantEntry((int) $id);

        if (!$entry) {
            return $this->redirect('/dashboard/forms', ['error' => 'Entry not found.']);
        }

        $form = $this->findTenantForm((int) $entry['form_id']);

        $valuesStmt = $db->prepare(
            "SELECT ev.*, ff.label, ff.type AS field_type
               FROM entry_values ev
               LEFT JOIN form_fields ff ON ff.id = ev.field_id
              WHERE ev.entry_id = :eid ORDER BY ev.id ASC"
        );
        $valuesStmt->execute(['eid' => (int) $id]);
        $fieldValues = $valuesStmt->fetchAll(\PDO::FETCH_ASSOC);

        $notes = $db->prepare(
            "SELECT n.*, u.name, u.email
               FROM entry_notes n
               LEFT JOIN users u ON u.id = n.user_id
              WHERE n.entry_id = :eid ORDER BY n.created_at ASC"
        );
        $notes->execute(['eid' => (int) $id]);
        $notes = $notes->fetchAll(\PDO::FETCH_ASSOC);

        $prevStmt = $db->prepare("SELECT id FROM entries WHERE form_id = :fid AND id < :id ORDER BY id DESC LIMIT 1");
        $prevStmt->execute(['fid' => (int) $entry['form_id'], 'id' => (int) $id]);
        $prevId = $prevStmt->fetchColumn() ?: null;

        $nextStmt = $db->prepare("SELECT id FROM entries WHERE form_id = :fid AND id > :id ORDER BY id ASC LIMIT 1");
        $nextStmt->execute(['fid' => (int) $entry['form_id'], 'id' => (int) $id]);
        $nextId = $nextStmt->fetchColumn() ?: null;

        return $this->view('client/entries/show', [
            'entry'       => $entry,
            'form'        => $form,
            'fieldValues' => $fieldValues,
            'notes'       => $notes,
            'prevId'      => $prevId,
            'nextId'      => $nextId,
        ], 'layouts.client');
    }

    public function delete(string $id): string
    {
        $entry = $this->findTenantEntry((int) $id);
        if (!$entry) {
            return $this->redirect('/dashboard/forms', ['error' => 'Entry not found.']);
        }

        $formId = (int) $entry['form_id'];
        $this->db()->prepare("DELETE FROM entry_notes WHERE entry_id = :eid")->execute(['eid' => (int) $id]);
        $this->db()->prepare("DELETE FROM entry_values WHERE entry_id = :eid")->execute(['eid' => (int) $id]);
        $this->db()->prepare("DELETE FROM entries WHERE id = :id")->execute(['id' => (int) $id]);

        return $this->redirect("/dashboard/forms/{$formId}/entries", ['success' => 'Entry deleted.']);
    }

    public function export(string $formId): void
    {
        $db   = $this->db();
        $form = $this->findTenantForm((int) $formId);
        if (!$form) {
            header('HTTP/1.1 404 Not Found');
            echo 'Form not found.';
            exit;
        }

        $where  = ['fe.form_id = :fid'];
        $params = ['fid' => (int) $formId];
        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("SELECT fe.* FROM entries fe {$whereClause} ORDER BY fe.created_at DESC LIMIT 50000");
        $stmt->execute($params);
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $formFields = $this->getFormFields((int) $formId);

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"entries_" . date('Y-m-d') . ".csv\"");

        $output = fopen('php://output', 'w');
        $headers = ['#', 'Status', 'IP', 'Created At'];
        foreach ($formFields as $ff) {
            $headers[] = $ff['label'] ?? ('Field ' . $ff['id']);
        }
        fputcsv($output, $headers);

        $rowNum = 1;
        foreach ($entries as $entry) {
            $evStmt = $db->prepare("SELECT field_id, value FROM entry_values WHERE entry_id = :eid");
            $evStmt->execute(['eid' => (int) $entry['id']]);
            $valMap = [];
            foreach ($evStmt->fetchAll(\PDO::FETCH_ASSOC) as $ev) {
                $valMap[$ev['field_id']] = $ev['value'];
            }

            $row = [$rowNum++, $entry['status'], $entry['ip_address'] ?? '', $entry['created_at']];
            foreach ($formFields as $ff) {
                $row[] = $valMap[$ff['id']] ?? '';
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function analytics(string $formId): string
    {
        $db   = $this->db();
        $form = $this->findTenantForm((int) $formId);
        if (!$form) {
            return $this->redirect('/dashboard/forms', ['error' => 'Form not found.']);
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM entries WHERE form_id = :fid");
        $stmt->execute(['fid' => (int) $formId]);
        $totalEntries = (int) $stmt->fetchColumn();

        $views          = (int) ($form['views_count'] ?? 0);
        $conversionRate = $views > 0 ? round(($totalEntries / $views) * 100, 2) : 0;

        $stmt = $db->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS count FROM entries WHERE form_id = :fid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY day ORDER BY day ASC");
        $stmt->execute(['fid' => (int) $formId]);
        $dailyEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count FROM entries WHERE form_id = :fid AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
        $stmt->execute(['fid' => (int) $formId]);
        $monthlyEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/entries/analytics', [
            'form'           => $form,
            'totalEntries'   => $totalEntries,
            'views'          => $views,
            'conversionRate' => $conversionRate,
            'dailyEntries'   => $dailyEntries,
            'monthlyEntries' => $monthlyEntries,
        ], 'layouts.client');
    }

    public function notes(string $id): string
    {
        $entry = $this->findTenantEntry((int) $id);
        if (!$entry) {
            return $this->json(['error' => 'Entry not found.'], 404);
        }

        $errors = $this->validate($_POST, ['content' => 'required|string|max:2000']);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        $this->db()->prepare(
            "INSERT INTO entry_notes (entry_id, user_id, note, created_at) VALUES (:eid, :uid, :note, NOW())"
        )->execute([
            'eid'  => (int) $id,
            'uid'  => auth()['id'],
            'note' => trim($_POST['content']),
        ]);

        return $this->json(['success' => true, 'message' => 'Note added.']);
    }

    private function getFormFields(int $formId): array
    {
        $stmt = $this->db()->prepare("SELECT * FROM form_fields WHERE form_id = :fid ORDER BY sort_order ASC");
        $stmt->execute(['fid' => $formId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function findTenantForm(int $formId): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM forms WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $formId, 'tid' => (int) tenant()['id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function findTenantEntry(int $entryId): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT fe.* FROM entries fe JOIN forms f ON f.id = fe.form_id WHERE fe.id = :id AND f.tenant_id = :tid"
        );
        $stmt->execute(['id' => $entryId, 'tid' => (int) tenant()['id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
