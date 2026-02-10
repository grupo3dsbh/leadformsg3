<?php
/**
 * Super Admin - Audit Log
 *
 * Variables from controller:
 * @var array  $logs
 * @var array  $actions
 * @var array  $entityTypes
 * @var array  $tenants
 * @var int    $total
 * @var int    $page
 * @var int    $perPage
 * @var int    $totalPages
 * @var string $search
 * @var string $action
 * @var string $userId
 * @var string $tenantId
 * @var string $entityType
 * @var string $dateFrom
 * @var string $dateTo
 * @var string $sortDir
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Log de Auditoria</h1>
        <p class="page-subtitle"><?= number_format($total) ?> eventos registrados</p>
    </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="/admin/audit" class="filters-bar">
    <div class="filter-search">
        <input type="text" name="search" placeholder="Buscar por acao, metadados, IP..." value="<?= e($search) ?>">
    </div>
    <select name="action" class="filter-select" onchange="this.form.submit()">
        <option value="">Todas as Acoes</option>
        <?php foreach ($actions as $act): ?>
            <option value="<?= e($act) ?>" <?= $action === $act ? 'selected' : '' ?>><?= e($act) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="entity_type" class="filter-select" onchange="this.form.submit()">
        <option value="">Todas as Entidades</option>
        <?php foreach ($entityTypes as $et): ?>
            <option value="<?= e($et) ?>" <?= $entityType === $et ? 'selected' : '' ?>><?= e($et) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="tenant_id" class="filter-select" onchange="this.form.submit()">
        <option value="">Todos os Clientes</option>
        <?php foreach ($tenants as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $tenantId == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" class="filter-select" value="<?= e($dateFrom) ?>" placeholder="De" title="Data inicial" style="min-width:140px;">
    <input type="date" name="date_to" class="filter-select" value="<?= e($dateTo) ?>" placeholder="Ate" title="Data final" style="min-width:140px;">
    <button type="submit" class="btn btn-sm btn-outline">Filtrar</button>
    <?php if ($search || $action || $entityType || $tenantId || $dateFrom || $dateTo): ?>
        <a href="/admin/audit" class="btn btn-sm btn-ghost">Limpar</a>
    <?php endif; ?>
</form>

<!-- Audit Log Table -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Acao</th>
                <th>Entidade</th>
                <th>Detalhes</th>
                <th>IP</th>
                <th>
                    <a href="/admin/audit?dir=<?= $sortDir === 'DESC' ? 'ASC' : 'DESC' ?>&search=<?= e($search) ?>&action=<?= e($action) ?>&entity_type=<?= e($entityType) ?>&tenant_id=<?= e($tenantId) ?>&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" style="text-decoration:none;color:inherit;">
                        Data
                        <span style="font-size:10px;"><?= $sortDir === 'ASC' ? '&#9650;' : '&#9660;' ?></span>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr class="audit-row" style="cursor:pointer;" onclick="toggleDetails(this)">
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="avatar-initials" style="width:28px;height:28px;font-size:10px;">
                                    <?= strtoupper(substr(($log['user_first_name'] ?? $log['user_email'] ?? 'S'), 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="text-sm font-medium">
                                        <?= e(trim(($log['user_first_name'] ?? '') . ' ' . ($log['user_last_name'] ?? '')) ?: 'Sistema') ?>
                                    </div>
                                    <div class="text-xs text-gray-400"><?= e($log['user_email'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $actionBadge = match(true) {
                                str_contains($log['action'] ?? '', 'delete') => 'badge-danger',
                                str_contains($log['action'] ?? '', 'create') => 'badge-success',
                                str_contains($log['action'] ?? '', 'update') => 'badge-info',
                                str_contains($log['action'] ?? '', 'login') => 'badge-primary',
                                str_contains($log['action'] ?? '', 'suspend') => 'badge-warning',
                                default => 'badge-gray',
                            };
                            ?>
                            <span class="badge <?= $actionBadge ?>"><?= e($log['action'] ?? '') ?></span>
                        </td>
                        <td>
                            <?php if (!empty($log['entity_type'])): ?>
                                <span class="text-sm"><?= e($log['entity_type']) ?></span>
                                <?php if (!empty($log['entity_id'])): ?>
                                    <span class="text-xs text-gray-400">#<?= (int)$log['entity_id'] ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-sm text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($log['tenant_name'])): ?>
                                <span class="text-xs text-gray-500"><?= e($log['tenant_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($log['decoded_metadata'])): ?>
                                <span class="text-xs text-gray-400" style="display:inline-block;max-width:200px;" title="<?= e(json_encode($log['decoded_metadata'])) ?>">
                                    <?= e(mb_substr(json_encode($log['decoded_metadata']), 0, 60)) ?>...
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-xs font-mono" style="font-family:var(--font-mono);color:var(--gray-500);"><?= e($log['ip_address'] ?? '') ?></span>
                        </td>
                        <td>
                            <span class="text-sm text-gray-500"><?= format_date($log['created_at'] ?? '', 'd/m/Y H:i:s') ?></span>
                        </td>
                    </tr>
                    <!-- Expandable Details Row -->
                    <tr class="audit-details" style="display:none;">
                        <td colspan="6" style="background:var(--gray-50);padding:var(--space-4) var(--space-6);">
                            <div class="grid grid-3 gap-4">
                                <div>
                                    <label class="form-label" style="margin-bottom:var(--space-1);">ID do Log</label>
                                    <p class="text-sm font-mono" style="font-family:var(--font-mono);">#<?= (int)$log['id'] ?></p>
                                </div>
                                <div>
                                    <label class="form-label" style="margin-bottom:var(--space-1);">User Agent</label>
                                    <p class="text-xs text-gray-500 truncate" style="max-width:300px;"><?= e($log['user_agent'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <label class="form-label" style="margin-bottom:var(--space-1);">Timestamp</label>
                                    <p class="text-sm"><?= e($log['created_at'] ?? '') ?></p>
                                </div>
                            </div>
                            <?php if (!empty($log['decoded_metadata'])): ?>
                                <div style="margin-top:var(--space-4);">
                                    <label class="form-label" style="margin-bottom:var(--space-1);">Metadados</label>
                                    <pre style="background:var(--gray-800);color:var(--gray-100);padding:var(--space-4);border-radius:var(--radius-lg);font-size:var(--font-size-xs);font-family:var(--font-mono);overflow-x:auto;max-height:200px;"><?= e(json_encode($log['decoded_metadata'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            </div>
                            <h3 class="empty-state-title">Nenhum registro encontrado</h3>
                            <p class="empty-state-text">Tente alterar os filtros de busca.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between mt-6">
        <div class="text-sm text-gray-500">
            Mostrando <?= (($page - 1) * $perPage) + 1 ?> a <?= min($page * $perPage, $total) ?> de <?= number_format($total) ?> registros
        </div>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="/admin/audit?page=<?= $page - 1 ?>&search=<?= e($search) ?>&action=<?= e($action) ?>&entity_type=<?= e($entityType) ?>&tenant_id=<?= e($tenantId) ?>&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="/admin/audit?page=<?= $i ?>&search=<?= e($search) ?>&action=<?= e($action) ?>&entity_type=<?= e($entityType) ?>&tenant_id=<?= e($tenantId) ?>&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="/admin/audit?page=<?= $page + 1 ?>&search=<?= e($search) ?>&action=<?= e($action) ?>&entity_type=<?= e($entityType) ?>&tenant_id=<?= e($tenantId) ?>&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleDetails(row) {
    const details = row.nextElementSibling;
    if (details && details.classList.contains('audit-details')) {
        details.style.display = details.style.display === 'none' ? 'table-row' : 'none';
    }
}
</script>
