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
            $where[]          = 'fe.data LIKE :search';
            $params['search'] = "%{$search}%";
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
                       FROM form_entries fe
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
                  FROM form_entries fe
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

        // Decode entry data for preview
        foreach ($entries as &$entry) {
            $entry['decoded_data'] = json_decode($entry['data'] ?? '{}', true) ?: [];
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
        ]);
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
                    f.fields AS form_fields,
                    t.name   AS tenant_name,
                    t.id     AS tenant_id
               FROM form_entries fe
               JOIN forms f ON f.id = fe.form_id
               JOIN tenants t ON t.id = f.tenant_id
              WHERE fe.id = :id"
        );
        $stmt->execute(['id' => (int) $id]);
        $entry = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$entry) {
            return $this->redirect('/admin/entries', ['error' => 'Entry not found.']);
        }

        $entryData  = json_decode($entry['data'] ?? '{}', true) ?: [];
        $formFields = json_decode($entry['form_fields'] ?? '[]', true) ?: [];

        // Build field-value pairs for display
        $fieldValues = [];
        foreach ($formFields as $field) {
            $fieldName  = $field['name'] ?? $field['key'] ?? '';
            $fieldLabel = $field['label'] ?? $fieldName;
            $fieldValues[] = [
                'label' => $fieldLabel,
                'name'  => $fieldName,
                'type'  => $field['type'] ?? 'text',
                'value' => $entryData[$fieldName] ?? '',
            ];
        }

        // Notes on this entry
        $notes = $db->prepare(
            "SELECT n.*, u.first_name, u.last_name, u.email
               FROM entry_notes n
               LEFT JOIN users u ON u.id = n.user_id
              WHERE n.entry_id = :eid
              ORDER BY n.created_at ASC"
        );
        $notes->execute(['eid' => (int) $id]);
        $notes = $notes->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/entries/show', [
            'entry'       => $entry,
            'entryData'   => $entryData,
            'formFields'  => $formFields,
            'fieldValues' => $fieldValues,
            'notes'       => $notes,
        ]);
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

        $sql = "SELECT fe.id, fe.form_id, fe.data, fe.ip_address, fe.user_agent, fe.created_at,
                       f.title AS form_title,
                       t.name  AS tenant_name
                  FROM form_entries fe
                  JOIN forms f ON f.id = fe.form_id
                  JOIN tenants t ON t.id = f.tenant_id
                  {$whereClause}
                  ORDER BY fe.created_at DESC
                  LIMIT 10000";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Collect all unique field keys across entries
        $allKeys = [];
        $rows    = [];
        foreach ($entries as $entry) {
            $decoded = json_decode($entry['data'] ?? '{}', true) ?: [];
            foreach (array_keys($decoded) as $key) {
                $allKeys[$key] = true;
            }
            $rows[] = [
                'meta' => [
                    'id'          => $entry['id'],
                    'form_title'  => $entry['form_title'],
                    'tenant_name' => $entry['tenant_name'],
                    'ip_address'  => $entry['ip_address'],
                    'created_at'  => $entry['created_at'],
                ],
                'data' => $decoded,
            ];
        }

        $fieldKeys = array_keys($allKeys);

        // CSV export
        $filename = 'entries_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $output = fopen('php://output', 'w');

        // Header row
        $headers = ['ID', 'Form', 'Tenant', 'IP Address', 'Submitted At'];
        $headers = array_merge($headers, $fieldKeys);
        fputcsv($output, $headers);

        // Data rows
        foreach ($rows as $row) {
            $line = [
                $row['meta']['id'],
                $row['meta']['form_title'],
                $row['meta']['tenant_name'],
                $row['meta']['ip_address'],
                $row['meta']['created_at'],
            ];

            foreach ($fieldKeys as $key) {
                $value = $row['data'][$key] ?? '';
                $line[] = is_array($value) ? json_encode($value) : (string) $value;
            }

            fputcsv($output, $line);
        }

        fclose($output);
        exit;
    }
}
