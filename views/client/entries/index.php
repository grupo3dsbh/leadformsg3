<?php
/**
 * Client - Entries List
 *
 * Variables from controller:
 * @var array  $entries         - Array of entry records with field data
 * @var array  $forms           - Array of forms for the form selector
 * @var int    $selectedFormId  - Currently selected form ID (0 = all)
 * @var array  $stats           - Entries stats ['total', 'complete', 'partial', 'abandoned']
 * @var string $search          - Current search term
 * @var string $statusFilter    - Current status filter
 * @var string $dateFrom        - Date range start
 * @var string $dateTo          - Date range end
 * @var array  $columns         - Form field columns to display
 * @var array  $pagination      - ['page', 'per_page', 'total', 'total_pages']
 */

$entries        = $entries ?? [];
$forms          = $forms ?? [];
$selectedFormId = (int)($selectedFormId ?? 0);
$stats          = $stats ?? ['total' => 0, 'complete' => 0, 'partial' => 0, 'abandoned' => 0];
$search         = $search ?? '';
$statusFilter   = $statusFilter ?? '';
$dateFrom       = $dateFrom ?? '';
$dateTo         = $dateTo ?? '';
$columns        = $columns ?? [];
$pagination     = $pagination ?? ['page' => 1, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];
$page           = (int)($pagination['page'] ?? 1);
$totalPages     = (int)($pagination['total_pages'] ?? 1);

function entryStatusBadge(string $status): string {
    switch ($status) {
        case 'complete':
            return '<span class="badge badge-success">Completa</span>';
        case 'partial':
            return '<span class="badge badge-warning">Parcial</span>';
        case 'abandoned':
            return '<span class="badge badge-danger">Abandonada</span>';
        default:
            return '<span class="badge badge-gray">' . e($status) . '</span>';
    }
}

function temperatureBadge($score): string {
    $score = (int)$score;
    if ($score >= 80) return '<span class="badge badge-danger">Quente</span>';
    if ($score >= 50) return '<span class="badge badge-warning">Morno</span>';
    if ($score >= 20) return '<span class="badge badge-info">Frio</span>';
    return '<span class="badge badge-gray">--</span>';
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Entradas</h1>
        <p class="page-subtitle"><?= number_format((int)$stats['total']) ?> entradas no total</p>
    </div>
    <div class="page-actions">
        <div class="dropdown" id="exportDropdown">
            <button class="btn btn-outline" onclick="document.getElementById('exportDropdown').classList.toggle('open')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar
            </button>
            <div class="dropdown-menu">
                <a href="/dashboard/entries/export?format=csv&form_id=<?= $selectedFormId ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="dropdown-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Exportar CSV
                </a>
                <a href="/dashboard/entries/export?format=pdf&form_id=<?= $selectedFormId ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="dropdown-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Exportar PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Form Selector -->
<div class="card mb-6">
    <div class="card-body" style="padding: var(--space-4) var(--space-6);">
        <div class="flex items-center gap-4">
            <label class="text-sm font-semibold text-gray-600" style="white-space:nowrap;">Formulario:</label>
            <select class="form-select" id="formSelector" onchange="selectForm(this.value)" style="max-width:400px;">
                <option value="0" <?= $selectedFormId === 0 ? 'selected' : '' ?>>Todos os Formularios</option>
                <?php foreach ($forms as $f): ?>
                    <option value="<?= (int)$f['id'] ?>" <?= $selectedFormId === (int)$f['id'] ? 'selected' : '' ?>>
                        <?= e($f['title'] ?? '') ?> (<?= (int)($f['entry_count'] ?? 0) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Stats Bar -->
<div class="grid grid-4 gap-4 mb-6">
    <div class="stat-card" style="padding:var(--space-4) var(--space-5);">
        <div class="flex items-center gap-3">
            <div class="stat-icon primary" style="width:40px;height:40px;margin-bottom:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            <div>
                <div class="font-bold text-xl"><?= number_format((int)$stats['total']) ?></div>
                <div class="text-xs text-gray-400">Total</div>
            </div>
        </div>
    </div>
    <div class="stat-card" style="padding:var(--space-4) var(--space-5);">
        <div class="flex items-center gap-3">
            <div class="stat-icon success" style="width:40px;height:40px;margin-bottom:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <div class="font-bold text-xl"><?= number_format((int)$stats['complete']) ?></div>
                <div class="text-xs text-gray-400">Completas</div>
            </div>
        </div>
    </div>
    <div class="stat-card" style="padding:var(--space-4) var(--space-5);">
        <div class="flex items-center gap-3">
            <div class="stat-icon warning" style="width:40px;height:40px;margin-bottom:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="font-bold text-xl"><?= number_format((int)$stats['partial']) ?></div>
                <div class="text-xs text-gray-400">Parciais</div>
            </div>
        </div>
    </div>
    <div class="stat-card" style="padding:var(--space-4) var(--space-5);">
        <div class="flex items-center gap-3">
            <div class="stat-icon danger" style="width:40px;height:40px;margin-bottom:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div>
                <div class="font-bold text-xl"><?= number_format((int)$stats['abandoned']) ?></div>
                <div class="text-xs text-gray-400">Abandonadas</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="filters-bar">
    <div class="filter-search">
        <input type="text"
               placeholder="Buscar nas entradas..."
               value="<?= e($search) ?>"
               id="entrySearch"
               onkeyup="if(event.key==='Enter') applyEntryFilters()">
    </div>
    <select class="filter-select" id="entryStatusFilter">
        <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Todos os Status</option>
        <option value="complete" <?= $statusFilter === 'complete' ? 'selected' : '' ?>>Completas</option>
        <option value="partial" <?= $statusFilter === 'partial' ? 'selected' : '' ?>>Parciais</option>
        <option value="abandoned" <?= $statusFilter === 'abandoned' ? 'selected' : '' ?>>Abandonadas</option>
    </select>
    <input type="date" class="filter-select" id="entryDateFrom" value="<?= e($dateFrom) ?>" title="Data inicial">
    <input type="date" class="filter-select" id="entryDateTo" value="<?= e($dateTo) ?>" title="Data final">
    <button class="btn btn-sm btn-outline" onclick="applyEntryFilters()">Filtrar</button>
    <?php if ($search || $statusFilter || $dateFrom || $dateTo): ?>
        <a href="/dashboard/entries<?= $selectedFormId ? '?form_id=' . $selectedFormId : '' ?>" class="btn btn-sm btn-ghost">Limpar</a>
    <?php endif; ?>
</div>

<!-- Entries Table -->
<?php if (!empty($entries)): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th style="width:100px;">Status</th>
                    <?php if (!empty($columns)): ?>
                        <?php foreach (array_slice($columns, 0, 4) as $col): ?>
                            <th><?= e($col['label'] ?? $col['name'] ?? '') ?></th>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <th>Formulario</th>
                        <th>Preview</th>
                    <?php endif; ?>
                    <th style="width:100px;">Score</th>
                    <th style="width:140px;">Data</th>
                    <th style="width:100px;" class="text-right">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <?php
                        $entryId     = (int)($entry['id'] ?? 0);
                        $entryStatus = $entry['status'] ?? 'complete';
                        $entryScore  = (int)($entry['score'] ?? 0);
                        $entryData   = $entry['data'] ?? [];
                        $formTitle   = $entry['form_title'] ?? '';
                        $createdAt   = $entry['created_at'] ?? '';
                    ?>
                    <tr>
                        <td>
                            <span class="text-xs text-gray-400"><?= $entryId ?></span>
                        </td>
                        <td><?= entryStatusBadge($entryStatus) ?></td>
                        <?php if (!empty($columns)): ?>
                            <?php foreach (array_slice($columns, 0, 4) as $col): ?>
                                <td>
                                    <span class="truncate" style="max-width:200px;display:inline-block;">
                                        <?= e($entryData[$col['name'] ?? ''] ?? '--') ?>
                                    </span>
                                </td>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <td>
                                <div class="font-medium text-sm"><?= e($formTitle) ?></div>
                            </td>
                            <td>
                                <span class="text-sm truncate" style="max-width:250px;display:inline-block;">
                                    <?= e($entry['preview'] ?? '--') ?>
                                </span>
                            </td>
                        <?php endif; ?>
                        <td>
                            <?= temperatureBadge($entryScore) ?>
                        </td>
                        <td>
                            <div class="text-sm"><?= !empty($createdAt) ? format_date($createdAt, 'd/m/Y') : '--' ?></div>
                            <div class="text-xs text-gray-400"><?= !empty($createdAt) ? format_date($createdAt, 'H:i') : '' ?></div>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="/dashboard/entries/<?= $entryId ?>" class="btn btn-icon btn-sm btn-ghost" title="Ver detalhes">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <form method="POST" action="/dashboard/entries/<?= $entryId ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-icon btn-sm btn-ghost" title="Excluir" onclick="return confirm('Excluir esta entrada?')" style="color:var(--danger);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-gray-500">
                Mostrando <?= (($page - 1) * ($pagination['per_page'] ?? 25)) + 1 ?> a <?= min($page * ($pagination['per_page'] ?? 25), (int)$pagination['total']) ?> de <?= number_format((int)$pagination['total']) ?>
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= buildEntryUrl(['page' => $page - 1]) ?>" class="pagination-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="<?= buildEntryUrl(['page' => $i]) ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= buildEntryUrl(['page' => $page + 1]) ?>" class="pagination-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Empty State -->
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            <h3 class="empty-state-title">Nenhuma entrada encontrada</h3>
            <?php if ($search || $statusFilter || $dateFrom || $dateTo): ?>
                <p class="empty-state-text">Tente ajustar seus filtros de busca.</p>
                <a href="/dashboard/entries<?= $selectedFormId ? '?form_id=' . $selectedFormId : '' ?>" class="btn btn-outline">Limpar Filtros</a>
            <?php else: ?>
                <p class="empty-state-text">As entradas dos seus formularios aparecerao aqui quando comecarem a chegar.</p>
                <a href="/dashboard/forms" class="btn btn-primary">Ver Meus Formularios</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
// Helper to build filter URLs for pagination
function buildEntryUrl(array $overrides = []): string {
    global $selectedFormId, $search, $statusFilter, $dateFrom, $dateTo, $page;
    $params = [
        'form_id'   => $overrides['form_id'] ?? $selectedFormId,
        'search'    => $overrides['search'] ?? $search,
        'status'    => $overrides['status'] ?? $statusFilter,
        'date_from' => $overrides['date_from'] ?? $dateFrom,
        'date_to'   => $overrides['date_to'] ?? $dateTo,
        'page'      => $overrides['page'] ?? $page,
    ];
    $params = array_filter($params, fn($v) => $v !== '' && $v !== 0 && $v !== null);
    return '/dashboard/entries' . ($params ? '?' . http_build_query($params) : '');
}
?>

<script>
function selectForm(formId) {
    var params = new URLSearchParams(window.location.search);
    if (formId && formId !== '0') {
        params.set('form_id', formId);
    } else {
        params.delete('form_id');
    }
    params.delete('page');
    window.location.href = '/dashboard/entries' + (params.toString() ? '?' + params.toString() : '');
}

function applyEntryFilters() {
    var params = new URLSearchParams();
    var formId = document.getElementById('formSelector').value;
    var search = document.getElementById('entrySearch').value;
    var status = document.getElementById('entryStatusFilter').value;
    var dateFrom = document.getElementById('entryDateFrom').value;
    var dateTo = document.getElementById('entryDateTo').value;

    if (formId && formId !== '0') params.set('form_id', formId);
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    window.location.href = '/dashboard/entries' + (params.toString() ? '?' + params.toString() : '');
}

// Close export dropdown on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#exportDropdown')) {
        document.getElementById('exportDropdown').classList.remove('open');
    }
});
</script>
