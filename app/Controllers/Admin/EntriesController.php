<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * Super Admin Entries Controller
 *
 * Cross-tenant view and export of all form entries on the platform.
 */
class EntriesController extends Controller
{
    /**
     * List all entries across all tenants with filters and pagination.
     */
    public function index(): string
    {
        $db = $this->db();

        $search   = trim($_GET['search'] ?? '');
        $tenantId = $_GET['tenant_id'] ?? '';
        $formId   = $_GET['form_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';
        $sortBy   = $_GET['sort'] ?? 'created_at';
        $sortDir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = 30;
        $offset   = ($page - 1) * $perPage;

        $allowedSorts = ['created_at', 'form_id'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]          = '(f.title LIKE :search OR t.name LIKE :search2)';
            $params['search']  = "%{$search}%";
            $params['search2'] = "%{$search}%";
        }

        if ($tenantId !== '') {
            $where[]              = 'f.tenant_id = :tenant_id';
            $params['tenant_id'] = (int) $tenantId;
        }

        if ($formId !== '') {
            $where[]           = 'fe.form_id = :form_id';
            $params['form_id'] = (int) $formId;
        }

        if ($dateFrom !== '') {
            $where[]              = 'fe.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[]            = 'fe.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countSql = "SELECT COUNT(*)
                       FROM entries fe
                       JOIN forms f ON f.id = fe.form_id
                       {$whereClause}";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Fetch entries with form and tenant info
        $sql = "SELECT fe.*,
                       f.title AS form_title,
                       f.slug  AS form_slug,
                       t.name  AS tenant_name,
                       t.id    AS tenant_id
                  FROM entries fe
                  JOIN forms f ON f.id = fe.form_id
                  JOIN tenants t ON t.id = f.tenant_id
                  {$whereClause}
                  ORDER BY fe.{$sortBy} {$sortDir}
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Entry data is stored in entry_values table, not in entries.data
        foreach ($entries as &$entry) {
            $entry['decoded_data'] = [];
        }
        unset($entry);

        // Dropdowns for filters
        $tenants = $db->query("SELECT id, name FROM tenants ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $forms   = $db->query("SELECT id, title FROM forms ORDER BY title ASC")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/entries/index', [
            'entries'    => $entries,
            'tenants'    => $tenants,
            'forms'      => $forms,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'search'     => $search,
            'tenantId'   => $tenantId,
            'formId'     => $formId,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
        ], 'layouts.admin');
    }

    /**
     * View detailed information for a single entry.
     */
    public function show(string $id): string
    {
        $db = $this->db();

        $stmt = $db->prepare(
            "SELECT fe.*,
                    f.title  AS form_title,
                    t.name   AS tenant_name,
                    t.id     AS tenant_id
               FROM entries fe
               JOIN forms f ON f.id = fe.form_id
               JOIN tenants t ON t.id = f.tenant_id
              WHERE fe.id = :id"
        );
        $stmt->execute(['id' => (int) $id]);
        $entry = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$entry) {
            return $this->redirect('/admin/entries', ['error' => 'Entry not found.']);
        }

        // Entry data is stored in entry_values table
        $valuesStmt = $db->prepare(
            "SELECT ev.field_id, ev.field_type, ev.value, ff.label
               FROM entry_values ev
               LEFT JOIN form_fields ff ON ff.id = ev.field_id
              WHERE ev.entry_id = :eid
              ORDER BY ff.sort_order ASC"
        );
        $valuesStmt->execute(['eid' => (int) $id]);
        $entryValues = $valuesStmt->fetchAll(\PDO::FETCH_ASSOC);

        $fieldValues = [];
        foreach ($entryValues as $ev) {
            $fieldValues[] = [
                'label' => $ev['label'] ?? 'Campo ' . $ev['field_id'],
                'name'  => 'field_' . $ev['field_id'],
                'type'  => $ev['field_type'] ?? 'text',
                'value' => $ev['value'] ?? '',
            ];
        }

        // Notes on this entry
        $notes = $db->prepare(
            "SELECT n.*, u.name AS user_name, u.email
               FROM entry_notes n
               LEFT JOIN users u ON u.id = n.user_id
              WHERE n.entry_id = :eid
              ORDER BY n.created_at ASC"
        );
        $notes->execute(['eid' => (int) $id]);
        $notes = $notes->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/entries/show', [
            'entry'       => $entry,
            'entryData'   => [],
            'formFields'  => [],
            'fieldValues' => $fieldValues,
            'notes'       => $notes,
        ], 'layouts.admin');
    }

    /**
     * Export entries matching the current filters to CSV.
     */
    public function export(): void
    {
        $db = $this->db();

        $tenantId = $_GET['tenant_id'] ?? '';
        $formId   = $_GET['form_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';
        $format   = $_GET['format'] ?? 'csv';

        $where  = [];
        $params = [];

        if ($tenantId !== '') {
            $where[]              = 'f.tenant_id = :tenant_id';
            $params['tenant_id'] = (int) $tenantId;
        }

        if ($formId !== '') {
            $where[]           = 'fe.form_id = :form_id';
            $params['form_id'] = (int) $formId;
        }

        if ($dateFrom !== '') {
            $where[]              = 'fe.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[]            = 'fe.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT fe.id, fe.form_id, fe.status, fe.ip_address, fe.created_at,
                       f.title AS form_title,
                       t.name  AS tenant_name
                  FROM entries fe
                  JOIN forms f ON f.id = fe.form_id
                  JOIN tenants t ON t.id = f.tenant_id
                  {$whereClause}
                  ORDER BY fe.created_at DESC
                  LIMIT 10000";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // CSV export
        $filename = 'entries_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, ['ID', 'Form', 'Tenant', 'Status', 'IP Address', 'Submitted At']);

        // Data rows
        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry['id'],
                $entry['form_title'],
                $entry['tenant_name'],
                $entry['status'],
                $entry['ip_address'],
                $entry['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }
}
