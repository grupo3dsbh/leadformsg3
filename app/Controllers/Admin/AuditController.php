<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * Super Admin Audit Controller
 *
 * Provides a comprehensive audit log viewer for platform administrators
 * to review all actions taken across the system.
 */
class AuditController extends Controller
{
    /**
     * List all audit logs with filters and pagination.
     */
    public function index(): string
    {
        $db = $this->db();

        $search     = trim($_GET['search'] ?? '');
        $action     = $_GET['action'] ?? '';
        $userId     = $_GET['user_id'] ?? '';
        $tenantId   = $_GET['tenant_id'] ?? '';
        $entityType = $_GET['entity_type'] ?? '';
        $dateFrom   = $_GET['date_from'] ?? '';
        $dateTo     = $_GET['date_to'] ?? '';
        $sortDir    = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 30;
        $offset     = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]          = '(al.action LIKE :search OR al.metadata LIKE :search OR al.ip_address LIKE :search)';
            $params['search'] = "%{$search}%";
        }

        if ($action !== '') {
            $where[]           = 'al.action = :action';
            $params['action'] = $action;
        }

        if ($userId !== '') {
            $where[]            = 'al.user_id = :user_id';
            $params['user_id'] = (int) $userId;
        }

        if ($tenantId !== '') {
            $where[]              = 'al.tenant_id = :tenant_id';
            $params['tenant_id'] = (int) $tenantId;
        }

        if ($entityType !== '') {
            $where[]                = 'al.entity_type = :entity_type';
            $params['entity_type'] = $entityType;
        }

        if ($dateFrom !== '') {
            $where[]              = 'al.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[]            = 'al.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countSql = "SELECT COUNT(*) FROM audit_logs al {$whereClause}";
        $stmt     = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Fetch logs with user and tenant info
        $sql = "SELECT al.*,
                       u.email      AS user_email,
                       u.first_name AS user_first_name,
                       u.last_name  AS user_last_name,
                       t.name       AS tenant_name
                  FROM audit_logs al
                  LEFT JOIN users u ON u.id = al.user_id
                  LEFT JOIN tenants t ON t.id = al.tenant_id
                  {$whereClause}
                  ORDER BY al.created_at {$sortDir}
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decode metadata
        foreach ($logs as &$log) {
            $log['decoded_metadata'] = json_decode($log['metadata'] ?? '{}', true) ?: [];
        }
        unset($log);

        // Unique actions for filter dropdown
        $actions = $db->query(
            "SELECT DISTINCT action FROM audit_logs ORDER BY action ASC"
        )->fetchAll(\PDO::FETCH_COLUMN);

        // Unique entity types for filter dropdown
        $entityTypes = $db->query(
            "SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type ASC"
        )->fetchAll(\PDO::FETCH_COLUMN);

        // Tenants for filter
        $tenants = $db->query("SELECT id, name FROM tenants ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/audit/index', [
            'logs'        => $logs,
            'actions'     => $actions,
            'entityTypes' => $entityTypes,
            'tenants'     => $tenants,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => (int) ceil($total / $perPage),
            'search'      => $search,
            'action'      => $action,
            'userId'      => $userId,
            'tenantId'    => $tenantId,
            'entityType'  => $entityType,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'sortDir'     => $sortDir,
        ]);
    }

    /**
     * View detailed information for a single audit log entry.
     */
    public function show(string $id): string
    {
        $db = $this->db();

        $stmt = $db->prepare(
            "SELECT al.*,
                    u.email      AS user_email,
                    u.first_name AS user_first_name,
                    u.last_name  AS user_last_name,
                    u.role       AS user_role,
                    t.name       AS tenant_name,
                    t.slug       AS tenant_slug
               FROM audit_logs al
               LEFT JOIN users u ON u.id = al.user_id
               LEFT JOIN tenants t ON t.id = al.tenant_id
              WHERE al.id = :id"
        );
        $stmt->execute(['id' => (int) $id]);
        $log = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$log) {
            return $this->redirect('/admin/audit', ['error' => 'Audit log entry not found.']);
        }

        $log['decoded_metadata'] = json_decode($log['metadata'] ?? '{}', true) ?: [];

        // Try to load the related entity
        $relatedEntity = null;
        if ($log['entity_type'] && $log['entity_id']) {
            $entityTable = $this->resolveEntityTable($log['entity_type']);
            if ($entityTable) {
                $entityStmt = $db->prepare("SELECT * FROM {$entityTable} WHERE id = :eid LIMIT 1");
                $entityStmt->execute(['eid' => (int) $log['entity_id']]);
                $relatedEntity = $entityStmt->fetch(\PDO::FETCH_ASSOC);
            }
        }

        // Nearby audit log entries for context (same entity or user, within +/- 1 hour)
        $contextLogs = $db->prepare(
            "SELECT al.id, al.action, al.entity_type, al.entity_id, al.created_at,
                    u.email AS user_email
               FROM audit_logs al
               LEFT JOIN users u ON u.id = al.user_id
              WHERE al.id != :id
                AND (
                    (al.entity_type = :etype AND al.entity_id = :eid)
                    OR al.user_id = :uid
                )
                AND al.created_at BETWEEN DATE_SUB(:ts, INTERVAL 1 HOUR) AND DATE_ADD(:ts2, INTERVAL 1 HOUR)
              ORDER BY al.created_at DESC
              LIMIT 20"
        );
        $contextLogs->execute([
            'id'    => (int) $id,
            'etype' => $log['entity_type'],
            'eid'   => $log['entity_id'],
            'uid'   => $log['user_id'],
            'ts'    => $log['created_at'],
            'ts2'   => $log['created_at'],
        ]);
        $contextLogs = $contextLogs->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view('admin/audit/show', [
            'log'           => $log,
            'relatedEntity' => $relatedEntity,
            'contextLogs'   => $contextLogs,
        ]);
    }

    /**
     * Map an entity_type string to the corresponding database table name.
     */
    private function resolveEntityTable(string $entityType): ?string
    {
        $map = [
            'tenants'       => 'tenants',
            'users'         => 'users',
            'forms'         => 'forms',
            'entries'  => 'entries',
            'plans'         => 'plans',
            'subscriptions' => 'subscriptions',
            'payments'      => 'payments',
            'integrations'  => 'integrations',
            'webhooks'      => 'webhooks',
        ];

        return $map[$entityType] ?? null;
    }
}
