<?php
/**
 * Super Admin - Entries List
 *
 * Variables from controller:
 * @var array  $entries
 * @var int    $total
 * @var int    $page
 * @var int    $perPage
 * @var int    $totalPages
 * @var string $search
 * @var string $formId
 * @var string $status
 * @var string $sortBy
 * @var string $sortDir
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Entradas</h1>
        <p class="page-subtitle"><?= number_format($total) ?> respostas recebidas</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="/admin/entries" class="flex items-center gap-4 flex-wrap">
            <div class="flex-1" style="min-width:200px;">
                <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Buscar entradas..." class="form-input">
            </div>
            <select name="status" class="form-select" style="width:auto;">
                <option value="">Todos os Status</option>
                <option value="complete" <?= ($status ?? '') === 'complete' ? 'selected' : '' ?>>Completa</option>
                <option value="partial" <?= ($status ?? '') === 'partial' ? 'selected' : '' ?>>Parcial</option>
                <option value="abandoned" <?= ($status ?? '') === 'abandoned' ? 'selected' : '' ?>>Abandonada</option>
            </select>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/admin/entries" class="btn btn-ghost">Limpar</a>
        </form>
    </div>
</div>

<!-- Entries Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (!empty($entries)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Formulario</th>
                        <th>Cliente</th>
                        <th>Status</th>
                        <th>IP</th>
                        <th>Dispositivo</th>
                        <th>Data</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= (int) $entry['id'] ?></td>
                            <td>
                                <a href="/admin/forms/<?= (int) ($entry['form_id'] ?? 0) ?>" class="font-medium"><?= e($entry['form_title'] ?? '-') ?></a>
                            </td>
                            <td class="text-sm"><?= e($entry['tenant_name'] ?? '-') ?></td>
                            <td>
                                <?php
                                    $statusClass = match($entry['status'] ?? '') {
                                        'complete' => 'success',
                                        'partial' => 'warning',
                                        'abandoned' => 'danger',
                                        default => 'gray',
                                    };
                                    $statusLabel = match($entry['status'] ?? '') {
                                        'complete' => 'Completa',
                                        'partial' => 'Parcial',
                                        'abandoned' => 'Abandonada',
                                        default => $entry['status'] ?? '-',
                                    };
                                ?>
                                <span class="badge badge-<?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="text-sm text-gray-500"><?= e($entry['ip_address'] ?? '-') ?></td>
                            <td class="text-sm text-gray-500"><?= e($entry['device_type'] ?? '-') ?></td>
                            <td class="text-sm text-gray-500"><?= e($entry['created_at'] ?? '') ?></td>
                            <td>
                                <a href="/admin/entries/<?= (int) $entry['id'] ?>" class="btn btn-sm btn-ghost">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state" style="padding:48px 24px;text-align:center;">
                <p class="text-gray-400">Nenhuma entrada encontrada.</p>
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
