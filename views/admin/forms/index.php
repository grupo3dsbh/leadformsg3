<?php
/**
 * Super Admin - Forms List
 *
 * Variables from controller:
 * @var array  $forms
 * @var int    $total
 * @var int    $page
 * @var int    $perPage
 * @var int    $totalPages
 * @var string $search
 * @var string $tenantId
 * @var string $status
 * @var string $sortBy
 * @var string $sortDir
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Formularios</h1>
        <p class="page-subtitle"><?= number_format($total) ?> formularios cadastrados</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="/admin/forms" class="flex items-center gap-4 flex-wrap">
            <div class="flex-1" style="min-width:200px;">
                <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Buscar formularios..." class="form-input">
            </div>
            <select name="status" class="form-select" style="width:auto;">
                <option value="">Todos os Status</option>
                <option value="published" <?= ($status ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                <option value="archived" <?= ($status ?? '') === 'archived' ? 'selected' : '' ?>>Arquivado</option>
            </select>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/admin/forms" class="btn btn-ghost">Limpar</a>
        </form>
    </div>
</div>

<!-- Forms Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (!empty($forms)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titulo</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Respostas</th>
                        <th>Criado em</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td><?= (int) $form['id'] ?></td>
                            <td>
                                <a href="/admin/forms/<?= (int) $form['id'] ?>" class="font-medium"><?= e($form['title'] ?? '') ?></a>
                                <div class="text-xs text-gray-400"><?= e($form['slug'] ?? '') ?></div>
                            </td>
                            <td class="text-sm"><?= e($form['tenant_name'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= ($form['type'] ?? '') === 'conversational' ? 'primary' : 'gray' ?>">
                                    <?= ($form['type'] ?? '') === 'conversational' ? 'Conversacional' : 'Classico' ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $statusClass = match($form['status'] ?? '') {
                                        'published' => 'success',
                                        'draft' => 'warning',
                                        'archived' => 'gray',
                                        default => 'gray',
                                    };
                                    $statusLabel = match($form['status'] ?? '') {
                                        'published' => 'Publicado',
                                        'draft' => 'Rascunho',
                                        'archived' => 'Arquivado',
                                        'expired' => 'Expirado',
                                        default => $form['status'] ?? '-',
                                    };
                                ?>
                                <span class="badge badge-<?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td><?= number_format((int) ($form['submissions_count'] ?? 0)) ?></td>
                            <td class="text-sm text-gray-500"><?= e($form['created_at'] ?? '') ?></td>
                            <td>
                                <a href="/admin/forms/<?= (int) $form['id'] ?>" class="btn btn-sm btn-ghost">Ver</a>
                                <a href="/admin/forms/<?= (int) $form['id'] ?>/entries" class="btn btn-sm btn-outline">Entradas</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state" style="padding:48px 24px;text-align:center;">
                <p class="text-gray-400">Nenhum formulario encontrado.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if (($totalPages ?? 1) > 1): ?>
    <div class="flex items-center justify-center gap-2 mt-6">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&status=<?= urlencode($status ?? '') ?>" class="btn btn-sm btn-ghost">Anterior</a>
        <?php endif; ?>
        <span class="text-sm text-gray-500">Pagina <?= $page ?> de <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&status=<?= urlencode($status ?? '') ?>" class="btn btn-sm btn-ghost">Proxima</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
