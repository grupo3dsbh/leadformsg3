<?php
/**
 * Super Admin - Clients List
 *
 * Variables from controller:
 * @var array  $clients
 * @var array  $plans
 * @var int    $total
 * @var int    $page
 * @var int    $perPage
 * @var int    $totalPages
 * @var string $search
 * @var string $status
 * @var string $planId
 * @var string $sortBy
 * @var string $sortDir
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Clientes</h1>
        <p class="page-subtitle"><?= number_format($total) ?> clientes cadastrados</p>
    </div>
    <div class="page-actions">
        <a href="/admin/clients/create" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Cliente
        </a>
    </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="/admin/clients" class="filters-bar">
    <div class="filter-search">
        <input type="text" name="search" placeholder="Buscar por nome, slug ou dominio..." value="<?= e($search) ?>">
    </div>
    <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">Todos os Status</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativos</option>
        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspensos</option>
    </select>
    <select name="plan_id" class="filter-select" onchange="this.form.submit()">
        <option value="">Todos os Planos</option>
        <?php foreach ($plans as $plan): ?>
            <option value="<?= (int)$plan['id'] ?>" <?= $planId == $plan['id'] ? 'selected' : '' ?>><?= e($plan['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm btn-outline">Filtrar</button>
    <?php if ($search || $status || $planId): ?>
        <a href="/admin/clients" class="btn btn-sm btn-ghost">Limpar</a>
    <?php endif; ?>
</form>

<!-- Clients Table -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>
                    <a href="/admin/clients?sort=name&dir=<?= ($sortBy === 'name' && $sortDir === 'ASC') ? 'DESC' : 'ASC' ?>&search=<?= e($search) ?>&status=<?= e($status) ?>&plan_id=<?= e($planId) ?>" style="text-decoration:none;color:inherit;">
                        Cliente
                        <?php if ($sortBy === 'name'): ?>
                            <span style="font-size:10px"><?= $sortDir === 'ASC' ? '&#9650;' : '&#9660;' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>Email</th>
                <th>Plano</th>
                <th class="text-center">Formularios</th>
                <th class="text-center">Respostas</th>
                <th>Status</th>
                <th>
                    <a href="/admin/clients?sort=created_at&dir=<?= ($sortBy === 'created_at' && $sortDir === 'DESC') ? 'ASC' : 'DESC' ?>&search=<?= e($search) ?>&status=<?= e($status) ?>&plan_id=<?= e($planId) ?>" style="text-decoration:none;color:inherit;">
                        Criado
                        <?php if ($sortBy === 'created_at'): ?>
                            <span style="font-size:10px"><?= $sortDir === 'ASC' ? '&#9650;' : '&#9660;' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="text-right">Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($clients)): ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <?php if (!empty($client['logo'])): ?>
                                    <img src="<?= e($client['logo']) ?>" class="avatar avatar-sm" alt="">
                                <?php else: ?>
                                    <div class="avatar-initials" style="width:32px;height:32px;font-size:11px;">
                                        <?= strtoupper(substr($client['name'] ?? '', 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-medium"><?= e($client['name']) ?></div>
                                    <div class="text-xs text-gray-400"><?= e($client['slug']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-sm"><?= e($client['email'] ?? '-') ?></span>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?= e($client['plan_name'] ?? 'Sem plano') ?></span>
                        </td>
                        <td class="text-center">
                            <span class="font-semibold"><?= (int)($client['form_count'] ?? 0) ?></span>
                        </td>
                        <td class="text-center">
                            <span class="font-semibold"><?= number_format((int)($client['entry_count'] ?? 0)) ?></span>
                        </td>
                        <td>
                            <?php if (($client['status'] ?? 'active') === 'active'): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php elseif (($client['status'] ?? '') === 'suspended'): ?>
                                <span class="badge badge-danger">Suspenso</span>
                            <?php else: ?>
                                <span class="badge badge-gray">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-sm text-gray-500"><?= format_date($client['created_at'] ?? '', 'd/m/Y') ?></span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="/admin/clients/<?= (int)$client['id'] ?>" class="btn btn-icon btn-sm btn-ghost" title="Ver detalhes">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="/admin/clients/<?= (int)$client['id'] ?>/edit" class="btn btn-icon btn-sm btn-ghost" title="Editar">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="POST" action="/admin/clients/<?= (int)$client['id'] ?>/login-as" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-icon btn-sm btn-ghost" title="Entrar como cliente" onclick="return confirm('Deseja entrar como este cliente?')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                    </button>
                                </form>
                                <?php if (($client['status'] ?? 'active') === 'active'): ?>
                                    <form method="POST" action="/admin/clients/<?= (int)$client['id'] ?>/suspend" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-icon btn-sm btn-ghost text-warning" title="Suspender" onclick="return confirm('Deseja suspender este cliente?')">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/admin/clients/<?= (int)$client['id'] ?>/activate" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-icon btn-sm btn-ghost text-success" title="Ativar">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                            </div>
                            <h3 class="empty-state-title">Nenhum cliente encontrado</h3>
                            <p class="empty-state-text">Tente alterar os filtros de busca ou adicione um novo cliente.</p>
                            <a href="/admin/clients/create" class="btn btn-primary">Adicionar Cliente</a>
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
            Mostrando <?= (($page - 1) * $perPage) + 1 ?> a <?= min($page * $perPage, $total) ?> de <?= number_format($total) ?> resultados
        </div>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="/admin/clients?page=<?= $page - 1 ?>&search=<?= e($search) ?>&status=<?= e($status) ?>&plan_id=<?= e($planId) ?>&sort=<?= e($sortBy) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            ?>

            <?php if ($start > 1): ?>
                <a href="/admin/clients?page=1&search=<?= e($search) ?>&status=<?= e($status) ?>&plan_id=<?= e($planId) ?>&sort=<?= e($sortBy) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn">1</a>
                <?php if ($start > 2): ?>
                    <span class="pagination-btn" style="border:none;cursor:default;">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="/admin/clients?page=<?= $i ?>&search=<?= e($search) ?>&status=<?= e($status) ?>&plan_id=<?= e($planId) ?>&sort=<?= e($sortBy) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                    <span class="pagination-btn" style="border:none;cursor:default;">...</span>
                <?php endif; ?>
                <a href="/admin/clients?page=<?= $totalPages ?>&search=<?= e($search) ?>&status=<?= e($status) ?>&plan_id=<?= e($planId) ?>&sort=<?= e($sortBy) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="/admin/clients?page=<?= $page + 1 ?>&search=<?= e($search) ?>&status=<?= e($status) ?>&plan_id=<?= e($planId) ?>&sort=<?= e($sortBy) ?>&dir=<?= e($sortDir) ?>" class="pagination-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
