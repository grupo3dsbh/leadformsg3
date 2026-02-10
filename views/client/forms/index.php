<?php
/**
 * Client - Forms List
 *
 * Variables from controller:
 * @var array  $forms
 * @var int    $total
 * @var int    $page
 * @var int    $perPage
 * @var int    $totalPages
 * @var string $search
 * @var string $status
 * @var string $sortBy
 * @var string $sortDir
 */

$viewMode = $_GET['view'] ?? 'grid';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Meus Formularios</h1>
        <p class="page-subtitle"><?= $total ?> formularios criados</p>
    </div>
    <div class="page-actions">
        <a href="/dashboard/forms/create" class="btn btn-gradient btn-lg">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Criar Formulario
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filters-bar">
    <form method="GET" action="/dashboard/forms" class="flex items-center gap-3 w-full flex-wrap" style="margin:0;">
        <div class="filter-search">
            <input type="text" name="search" placeholder="Buscar formularios..." value="<?= e($search) ?>">
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Todos os Status</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicados</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Rascunhos</option>
        </select>
        <input type="hidden" name="view" value="<?= e($viewMode) ?>">
        <button type="submit" class="btn btn-sm btn-outline">Buscar</button>
        <?php if ($search || $status): ?>
            <a href="/dashboard/forms?view=<?= e($viewMode) ?>" class="btn btn-sm btn-ghost">Limpar</a>
        <?php endif; ?>

        <!-- View Toggle -->
        <div class="flex items-center gap-1" style="margin-left:auto;">
            <a href="/dashboard/forms?view=grid&search=<?= e($search) ?>&status=<?= e($status) ?>" class="btn btn-icon btn-sm <?= $viewMode === 'grid' ? 'btn-primary' : 'btn-ghost' ?>" title="Visualizacao em Grade">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </a>
            <a href="/dashboard/forms?view=list&search=<?= e($search) ?>&status=<?= e($status) ?>" class="btn btn-icon btn-sm <?= $viewMode === 'list' ? 'btn-primary' : 'btn-ghost' ?>" title="Visualizacao em Lista">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </a>
        </div>
    </form>
</div>

<?php if (!empty($forms)): ?>
    <?php if ($viewMode === 'grid'): ?>
        <!-- Grid View -->
        <div class="grid grid-3 gap-6">
            <?php foreach ($forms as $form): ?>
                <?php
                $isPublished = !empty($form['is_published']) || ($form['status'] ?? 'draft') === 'published';
                $entryCount = (int)($form['entry_count'] ?? 0);
                $views = (int)($form['views_count'] ?? $form['views'] ?? 0);
                $convRate = $views > 0 ? round(($entryCount / $views) * 100, 1) : 0;
                ?>
                <div class="card" style="display:flex;flex-direction:column;">
                    <div class="card-body" style="flex:1;">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div style="flex:1;min-width:0;">
                                <h4 class="font-semibold truncate" style="font-size:var(--font-size-base);"><?= e($form['title'] ?? '') ?></h4>
                                <div class="text-xs text-gray-400 mt-1">
                                    /f/<?= e($form['slug'] ?? '') ?>
                                </div>
                            </div>
                            <?php if ($isPublished): ?>
                                <span class="badge badge-success">Publicado</span>
                            <?php else: ?>
                                <span class="badge badge-gray">Rascunho</span>
                            <?php endif; ?>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-3 gap-3 mb-4">
                            <div style="text-align:center;padding:var(--space-2);background:var(--gray-50);border-radius:var(--radius-md);">
                                <div class="font-bold text-sm"><?= $entryCount ?></div>
                                <div class="text-xs text-gray-400">Respostas</div>
                            </div>
                            <div style="text-align:center;padding:var(--space-2);background:var(--gray-50);border-radius:var(--radius-md);">
                                <div class="font-bold text-sm"><?= $views ?></div>
                                <div class="text-xs text-gray-400">Visualizacoes</div>
                            </div>
                            <div style="text-align:center;padding:var(--space-2);background:var(--gray-50);border-radius:var(--radius-md);">
                                <div class="font-bold text-sm"><?= $convRate ?>%</div>
                                <div class="text-xs text-gray-400">Conversao</div>
                            </div>
                        </div>

                        <div class="text-xs text-gray-400">
                            Criado em <?= format_date($form['created_at'] ?? '', 'd/m/Y') ?>
                            <?php if (!empty($form['last_entry_at'])): ?>
                                &middot; Ultima resposta: <?= format_date($form['last_entry_at'], 'd/m') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <a href="/dashboard/forms/<?= (int)$form['id'] ?>/builder" class="btn btn-sm btn-primary">Editar</a>
                                <a href="/f/<?= e($form['slug'] ?? '') ?>" target="_blank" class="btn btn-sm btn-ghost" title="Visualizar">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-ghost" onclick="this.parentElement.classList.toggle('open')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="/dashboard/forms/<?= (int)$form['id'] ?>/builder" class="dropdown-item">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </a>
                                    <a href="/f/<?= e($form['slug'] ?? '') ?>" target="_blank" class="dropdown-item">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Visualizar
                                    </a>
                                    <form method="POST" action="/dashboard/forms/<?= (int)$form['id'] ?>/duplicate" style="display:contents;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dropdown-item" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                            Duplicar
                                        </button>
                                    </form>
                                    <div class="dropdown-divider"></div>
                                    <form method="POST" action="/dashboard/forms/<?= (int)$form['id'] ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Excluir este formulario e todas as respostas?')" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- List View -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Formulario</th>
                        <th>Status</th>
                        <th class="text-center">Respostas</th>
                        <th class="text-center">Visualizacoes</th>
                        <th class="text-center">Conversao</th>
                        <th>Criado</th>
                        <th class="text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <?php
                        $isPublished = !empty($form['is_published']) || ($form['status'] ?? 'draft') === 'published';
                        $entryCount = (int)($form['entry_count'] ?? 0);
                        $views = (int)($form['views_count'] ?? $form['views'] ?? 0);
                        $convRate = $views > 0 ? round(($entryCount / $views) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="font-medium"><?= e($form['title'] ?? '') ?></div>
                                    <div class="text-xs text-gray-400">/f/<?= e($form['slug'] ?? '') ?></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($isPublished): ?>
                                    <span class="badge badge-success">Publicado</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Rascunho</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center font-semibold"><?= $entryCount ?></td>
                            <td class="text-center"><?= $views ?></td>
                            <td class="text-center"><?= $convRate ?>%</td>
                            <td class="text-sm text-gray-500"><?= format_date($form['created_at'] ?? '', 'd/m/Y') ?></td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="/dashboard/forms/<?= (int)$form['id'] ?>/builder" class="btn btn-sm btn-primary">Editar</a>
                                    <a href="/f/<?= e($form['slug'] ?? '') ?>" target="_blank" class="btn btn-icon btn-sm btn-ghost" title="Visualizar">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-gray-500">
                Mostrando <?= (($page - 1) * $perPage) + 1 ?> a <?= min($page * $perPage, $total) ?> de <?= $total ?>
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/dashboard/forms?page=<?= $page - 1 ?>&view=<?= e($viewMode) ?>&search=<?= e($search) ?>&status=<?= e($status) ?>" class="pagination-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="/dashboard/forms?page=<?= $i ?>&view=<?= e($viewMode) ?>&search=<?= e($search) ?>&status=<?= e($status) ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="/dashboard/forms?page=<?= $page + 1 ?>&view=<?= e($viewMode) ?>&search=<?= e($search) ?>&status=<?= e($status) ?>" class="pagination-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="12" x2="12" y2="18"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </div>
            <h3 class="empty-state-title">Crie seu primeiro formulario</h3>
            <p class="empty-state-text">Comece a capturar leads criando um formulario conversacional inteligente.</p>
            <a href="/dashboard/forms/create" class="btn btn-gradient btn-lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Criar Formulario
            </a>
        </div>
    </div>
<?php endif; ?>
