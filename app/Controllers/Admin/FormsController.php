<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * Super Admin Forms Controller
 *
 * Read-only cross-tenant view of all forms on the platform with
 * filtering, search, and entry viewing capabilities.
 */
class FormsController extends Controller
{
    /**
     * List all forms across all tenants with search, filters, and pagination.
     */
    public function index(): string
    {
        $db = $this->db();

        $search   = trim($_GET['search'] ?? '');
        $tenantId = $_GET['tenant_id'] ?? '';
        $status   = $_GET['status'] ?? '';
        $sortBy   = $_GET['sort'] ?? 'created_at';
        $sortDir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = 25;
        $offset   = ($page - 1) * $perPage;

        $allowedSorts = ['title', 'created_at', 'updated_at', 'status'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]          = '(f.title LIKE :search OR f.slug LIKE :search)';
            $params['search'] = "%{$search}%";
        }

        if ($tenantId !== '') {
            $where[]              = 'f.tenant_id = :tenant_id';
            $params['tenant_id'] = (int) $tenantId;
        }

        if ($status === 'published') {
            $where[] = "f.status = 'published'";
        } elseif ($status === 'draft') {
            $where[] = "f.status = 'draft'";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countSql = "SELECT COUNT(*) FROM forms f {$whereClause}";
        $stmt     = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Fetch forms with tenant and entry counts
        $sql = "SELECT f.*,
                       t.name AS tenant_name,
                       t.slug AS tenant_slug,
                       (SELECT COUNT(*) FROM entries WHERE form_id = f.id) AS entry_count
                  FROM forms f
                  JOIN tenants t ON t.id = f.tenant_id
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

        // Tenants for filter dropdown
        $tenants = $db->query(
            "SELECT id, name FROM tenants ORDER BY name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/forms/index', [
            'forms'      => $forms,
            'tenants'    => $tenants,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'search'     => $search,
            'tenantId'   => $tenantId,
            'status'     => $status,
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
        ], 'layouts.admin');
    }

    /**
     * View details of a specific form including its configuration and stats.
     */
    public function show(string $id): string
    {
        $db = $this->db();

        $stmt = $db->prepare(
            "SELECT f.*, t.name AS tenant_name, t.slug AS tenant_slug
               FROM forms f
               JOIN tenants t ON t.id = f.tenant_id
              WHERE f.id = :id"
        );
        $stmt->execute(['id' => (int) $id]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            return $this->redirect('/admin/forms', ['error' => 'Form not found.']);
        }

        // Entry statistics
        $entryCount = (int) $db->prepare(
            "SELECT COUNT(*) FROM entries WHERE form_id = :fid"
        )->execute(['fid' => (int) $id]) ? 0 : 0;

        $stmt = $db->prepare("SELECT COUNT(*) FROM entries WHERE form_id = :fid");
        $stmt->execute(['fid' => (int) $id]);
        $entryCount = (int) $stmt->fetchColumn();

        // Entries per day (last 30 days)
        $dailyEntries = $db->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS count
               FROM entries
              WHERE form_id = :fid
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY day
              ORDER BY day ASC"
        );
        $dailyEntries->execute(['fid' => (int) $id]);
        $dailyEntries = $dailyEntries->fetchAll(\PDO::FETCH_ASSOC);

        // Recent entries
        $recentEntries = $db->prepare(
            "SELECT * FROM entries WHERE form_id = :fid ORDER BY created_at DESC LIMIT 10"
        );
        $recentEntries->execute(['fid' => (int) $id]);
        $recentEntries = $recentEntries->fetchAll(\PDO::FETCH_ASSOC);

        // Form fields are stored in form_fields table, not in the form record
        $fields = [];

        return $this->view('admin/forms/show', [
            'form'          => $form,
            'fields'        => $fields,
            'entryCount'    => $entryCount,
            'dailyEntries'  => $dailyEntries,
            'recentEntries' => $recentEntries,
        ], 'layouts.admin');
    }

    /**
     * View all entries for a specific form.
     */
    public function entries(string $id): string
    {
        $db = $this->db();

        // Verify form exists
        $stmt = $db->prepare(
            "SELECT f.*, t.name AS tenant_name
               FROM forms f
               JOIN tenants t ON t.id = f.tenant_id
              WHERE f.id = :id"
        );
        $stmt->execute(['id' => (int) $id]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            return $this->redirect('/admin/forms', ['error' => 'Form not found.']);
        }

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;

        $search = trim($_GET['search'] ?? '');
        $where  = ['fe.form_id = :fid'];
        $params = ['fid' => (int) $id];

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM entries fe {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch entries
        $sql = "SELECT fe.*
                  FROM entries fe
                  {$whereClause}
                  ORDER BY fe.created_at DESC
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

        // Form fields are stored in form_fields table, not in the form record
        $fields = [];

        return $this->view('admin/forms/entries', [
            'form'       => $form,
            'fields'     => $fields,
            'entries'    => $entries,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'search'     => $search,
        ], 'layouts.admin');
    }
}
