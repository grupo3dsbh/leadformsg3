<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Entries Controller
 *
 * Manages form submissions (entries) for the current tenant: listing,
 * detail viewing, deletion, export (CSV/PDF/Excel), analytics, and notes.
 */
class EntriesController extends Controller
{
    /**
     * List entries for a specific form with pagination and search.
     */
    public function index(string $formId): string
    {
        $db   = $this->db();
        $form = $this->findTenantForm((int) $formId);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        $search  = trim($_GET['search'] ?? '');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';
        $sortDir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = 25;
        $offset   = ($page - 1) * $perPage;

        $where  = ['fe.form_id = :fid'];
        $params = ['fid' => (int) $formId];

        if ($search !== '') {
            $where[]          = 'fe.data LIKE :search';
            $params['search'] = "%{$search}%";
        }

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

        // Fetch entries
        $sql = "SELECT fe.*
                  FROM entries fe
                  {$whereClause}
                  ORDER BY fe.created_at {$sortDir}
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decode entry data
        $formFields = json_decode($form['fields'] ?? '[]', true) ?: [];
        foreach ($entries as &$entry) {
            $entry['decoded_data'] = json_decode($entry['data'] ?? '{}', true) ?: [];
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
            'search'     => $search,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'sortDir'    => $sortDir,
        ]);
    }

    /**
     * View detailed information for a single entry.
     */
    public function show(string $id): string
    {
        $db    = $this->db();
        $entry = $this->findTenantEntry((int) $id);

        if (!$entry) {
            return $this->redirect('/client/forms', ['error' => 'Entry not found.']);
        }

        // Load the parent form
        $form = $this->findTenantForm((int) $entry['form_id']);

        $entryData  = json_decode($entry['data'] ?? '{}', true) ?: [];
        $formFields = json_decode($form['fields'] ?? '[]', true) ?: [];

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

        // Previous and next entry IDs for navigation
        $prevStmt = $db->prepare(
            "SELECT id FROM entries WHERE form_id = :fid AND id < :id ORDER BY id DESC LIMIT 1"
        );
        $prevStmt->execute(['fid' => (int) $entry['form_id'], 'id' => (int) $id]);
        $prevId = $prevStmt->fetchColumn() ?: null;

        $nextStmt = $db->prepare(
            "SELECT id FROM entries WHERE form_id = :fid AND id > :id ORDER BY id ASC LIMIT 1"
        );
        $nextStmt->execute(['fid' => (int) $entry['form_id'], 'id' => (int) $id]);
        $nextId = $nextStmt->fetchColumn() ?: null;

        return $this->view('client/entries/show', [
            'entry'       => $entry,
            'form'        => $form,
            'entryData'   => $entryData,
            'fieldValues' => $fieldValues,
            'notes'       => $notes,
            'prevId'      => $prevId,
            'nextId'      => $nextId,
        ]);
    }

    /**
     * Delete a single entry.
     */
    public function delete(string $id): string
    {
        $entry = $this->findTenantEntry((int) $id);

        if (!$entry) {
            return $this->redirect('/client/forms', ['error' => 'Entry not found.']);
        }

        $formId = (int) $entry['form_id'];

        // Delete associated notes first
        $this->db()->prepare("DELETE FROM entry_notes WHERE entry_id = :eid")->execute(['eid' => (int) $id]);

        // Delete the entry
        $this->db()->prepare("DELETE FROM entries WHERE id = :id")->execute(['id' => (int) $id]);

        return $this->redirect("/client/forms/{$formId}/entries", [
            'success' => 'Entry deleted.',
        ]);
    }

    /**
     * Export entries for a form in the specified format (CSV, PDF, or Excel).
     */
    public function export(string $formId): void
    {
        $db   = $this->db();
        $form = $this->findTenantForm((int) $formId);

        if (!$form) {
            header('HTTP/1.1 404 Not Found');
            echo 'Form not found.';
            exit;
        }

        $format  = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';

        $where  = ['fe.form_id = :fid'];
        $params = ['fid' => (int) $formId];

        if ($dateFrom !== '') {
            $where[]              = 'fe.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[]            = 'fe.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT fe.* FROM entries fe {$whereClause} ORDER BY fe.created_at DESC LIMIT 50000";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $formFields = json_decode($form['fields'] ?? '[]', true) ?: [];

        // Extract field keys in order
        $fieldKeys   = [];
        $fieldLabels = [];
        foreach ($formFields as $field) {
            $name  = $field['name'] ?? $field['key'] ?? '';
            $label = $field['label'] ?? $name;
            if ($name !== '') {
                $fieldKeys[]        = $name;
                $fieldLabels[$name] = $label;
            }
        }

        $slugTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $form['title']);

        if ($format === 'csv') {
            $this->exportCsv($entries, $fieldKeys, $fieldLabels, $slugTitle);
        } elseif ($format === 'excel') {
            $this->exportExcel($entries, $fieldKeys, $fieldLabels, $slugTitle);
        } elseif ($format === 'pdf') {
            $this->exportPdf($entries, $fieldKeys, $fieldLabels, $form, $slugTitle);
        } else {
            $this->exportCsv($entries, $fieldKeys, $fieldLabels, $slugTitle);
        }
    }

    /**
     * Show analytics for a specific form.
     */
    public function analytics(string $formId): string
    {
        $db   = $this->db();
        $form = $this->findTenantForm((int) $formId);

        if (!$form) {
            return $this->redirect('/client/forms', ['error' => 'Form not found.']);
        }

        // Total entries
        $stmt = $db->prepare("SELECT COUNT(*) FROM entries WHERE form_id = :fid");
        $stmt->execute(['fid' => (int) $formId]);
        $totalEntries = (int) $stmt->fetchColumn();

        // Views and conversion rate
        $views          = (int) ($form['views'] ?? 0);
        $conversionRate = $views > 0 ? round(($totalEntries / $views) * 100, 2) : 0;

        // Entries per day (last 30 days)
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS count
               FROM entries
              WHERE form_id = :fid
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY day
              ORDER BY day ASC"
        );
        $stmt->execute(['fid' => (int) $formId]);
        $dailyEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Entries per hour distribution (all time)
        $stmt = $db->prepare(
            "SELECT HOUR(created_at) AS hour, COUNT(*) AS count
               FROM entries
              WHERE form_id = :fid
              GROUP BY hour
              ORDER BY hour ASC"
        );
        $stmt->execute(['fid' => (int) $formId]);
        $hourlyDistribution = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Entries per weekday
        $stmt = $db->prepare(
            "SELECT DAYNAME(created_at) AS day_name, DAYOFWEEK(created_at) AS day_num, COUNT(*) AS count
               FROM entries
              WHERE form_id = :fid
              GROUP BY day_name, day_num
              ORDER BY day_num ASC"
        );
        $stmt->execute(['fid' => (int) $formId]);
        $weekdayDistribution = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Top referrers
        $stmt = $db->prepare(
            "SELECT referrer, COUNT(*) AS count
               FROM entries
              WHERE form_id = :fid AND referrer IS NOT NULL AND referrer != ''
              GROUP BY referrer
              ORDER BY count DESC
              LIMIT 10"
        );
        $stmt->execute(['fid' => (int) $formId]);
        $topReferrers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Browser/device stats (basic parsing from user_agent)
        $stmt = $db->prepare(
            "SELECT user_agent, COUNT(*) AS count
               FROM entries
              WHERE form_id = :fid AND user_agent IS NOT NULL
              GROUP BY user_agent
              ORDER BY count DESC
              LIMIT 20"
        );
        $stmt->execute(['fid' => (int) $formId]);
        $userAgents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Entries per month (last 12 months)
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
               FROM entries
              WHERE form_id = :fid
                AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY month
              ORDER BY month ASC"
        );
        $stmt->execute(['fid' => (int) $formId]);
        $monthlyEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('client/entries/analytics', [
            'form'                 => $form,
            'totalEntries'         => $totalEntries,
            'views'                => $views,
            'conversionRate'       => $conversionRate,
            'dailyEntries'         => $dailyEntries,
            'hourlyDistribution'   => $hourlyDistribution,
            'weekdayDistribution'  => $weekdayDistribution,
            'topReferrers'         => $topReferrers,
            'userAgents'           => $userAgents,
            'monthlyEntries'       => $monthlyEntries,
        ]);
    }

    /**
     * Add a note to a specific entry.
     */
    public function notes(string $id): string
    {
        $entry = $this->findTenantEntry((int) $id);

        if (!$entry) {
            return $this->json(['error' => 'Entry not found.'], 404);
        }

        $errors = $this->validate($_POST, [
            'content' => 'required|string|max:2000',
        ]);

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        $this->db()->prepare(
            "INSERT INTO entry_notes (entry_id, user_id, content, created_at)
             VALUES (:eid, :uid, :content, NOW())"
        )->execute([
            'eid'     => (int) $id,
            'uid'     => auth()['id'],
            'content' => trim($_POST['content']),
        ]);

        // Check if request wants JSON response (AJAX)
        if ($this->isAjax()) {
            return $this->json([
                'success' => true,
                'message' => 'Note added.',
            ]);
        }

        return $this->redirect("/client/entries/{$id}", [
            'success' => 'Note added.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Export helpers
    // -------------------------------------------------------------------------

    /**
     * Export as CSV.
     */
    private function exportCsv(array $entries, array $fieldKeys, array $fieldLabels, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}_" . date('Y-m-d') . ".csv\"");

        $output = fopen('php://output', 'w');

        // Header
        $headers = ['#', 'Submitted At', 'IP Address'];
        foreach ($fieldKeys as $key) {
            $headers[] = $fieldLabels[$key] ?? $key;
        }
        fputcsv($output, $headers);

        // Rows
        $rowNum = 1;
        foreach ($entries as $entry) {
            $decoded = json_decode($entry['data'] ?? '{}', true) ?: [];
            $row     = [$rowNum++, $entry['created_at'], $entry['ip_address'] ?? ''];

            foreach ($fieldKeys as $key) {
                $val  = $decoded[$key] ?? '';
                $row[] = is_array($val) ? implode(', ', $val) : (string) $val;
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Export as Excel-compatible XML spreadsheet.
     */
    private function exportExcel(array $entries, array $fieldKeys, array $fieldLabels, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$filename}_" . date('Y-m-d') . ".xls\"");

        echo '<table border="1">';
        echo '<tr><th>#</th><th>Submitted At</th><th>IP Address</th>';
        foreach ($fieldKeys as $key) {
            echo '<th>' . htmlspecialchars($fieldLabels[$key] ?? $key) . '</th>';
        }
        echo '</tr>';

        $rowNum = 1;
        foreach ($entries as $entry) {
            $decoded = json_decode($entry['data'] ?? '{}', true) ?: [];
            echo '<tr>';
            echo '<td>' . $rowNum++ . '</td>';
            echo '<td>' . htmlspecialchars($entry['created_at'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($entry['ip_address'] ?? '') . '</td>';

            foreach ($fieldKeys as $key) {
                $val = $decoded[$key] ?? '';
                $display = is_array($val) ? implode(', ', $val) : (string) $val;
                echo '<td>' . htmlspecialchars($display) . '</td>';
            }
            echo '</tr>';
        }

        echo '</table>';
        exit;
    }

    /**
     * Export as a simple PDF (HTML-to-PDF using the browser or a library).
     *
     * For a full implementation, integrate a library like DOMPDF or TCPDF.
     * This provides a printer-friendly HTML page with PDF headers.
     */
    private function exportPdf(array $entries, array $fieldKeys, array $fieldLabels, array $form, string $filename): void
    {
        header('Content-Type: text/html; charset=utf-8');

        echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($form['title']) . ' - Entries</title>';
        echo '<style>body{font-family:Arial,sans-serif;font-size:12px;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left;}th{background:#f5f5f5;}</style></head><body>';
        echo '<h1>' . htmlspecialchars($form['title']) . ' - Entries Export</h1>';
        echo '<p>Exported on ' . date('Y-m-d H:i:s') . ' | Total: ' . count($entries) . ' entries</p>';

        echo '<table><tr><th>#</th><th>Submitted At</th>';
        foreach ($fieldKeys as $key) {
            echo '<th>' . htmlspecialchars($fieldLabels[$key] ?? $key) . '</th>';
        }
        echo '</tr>';

        $rowNum = 1;
        foreach ($entries as $entry) {
            $decoded = json_decode($entry['data'] ?? '{}', true) ?: [];
            echo '<tr><td>' . $rowNum++ . '</td>';
            echo '<td>' . htmlspecialchars($entry['created_at'] ?? '') . '</td>';

            foreach ($fieldKeys as $key) {
                $val     = $decoded[$key] ?? '';
                $display = is_array($val) ? implode(', ', $val) : (string) $val;
                echo '<td>' . htmlspecialchars($display) . '</td>';
            }
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
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
     * Check if the current request is an AJAX/XHR request.
     */
    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
