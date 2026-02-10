<?php
/**
 * Super Admin - Form Detail
 *
 * Variables from controller:
 * @var array $form
 * @var array $fields
 * @var array $integrations
 * @var array $stats
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($form['title'] ?? 'Formulario') ?></h1>
        <p class="page-subtitle">Detalhes do formulario #<?= (int) ($form['id'] ?? 0) ?></p>
    </div>
    <div class="page-actions">
        <a href="/admin/forms" class="btn btn-ghost">Voltar</a>
        <a href="/admin/forms/<?= (int) $form['id'] ?>/entries" class="btn btn-outline">Ver Entradas</a>
    </div>
</div>

<div class="grid grid-3 gap-6 mb-8">
    <div class="stat-card">
        <div class="stat-value"><?= e($form['status'] ?? '-') ?></div>
        <div class="stat-label">Status</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int) ($stats['total_entries'] ?? 0) ?></div>
        <div class="stat-label">Total de Entradas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= e($form['type'] ?? '-') ?></div>
        <div class="stat-label">Tipo</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="card-title">Informacoes</h3></div>
    <div class="card-body">
        <div class="grid grid-2 gap-4">
            <div><span class="text-sm text-gray-500">Slug:</span> <strong><?= e($form['slug'] ?? '') ?></strong></div>
            <div><span class="text-sm text-gray-500">Cliente:</span> <strong><?= e($form['tenant_name'] ?? '-') ?></strong></div>
            <div><span class="text-sm text-gray-500">Criado em:</span> <?= e($form['created_at'] ?? '') ?></div>
            <div><span class="text-sm text-gray-500">Atualizado em:</span> <?= e($form['updated_at'] ?? '') ?></div>
        </div>
        <?php if (!empty($form['description'])): ?>
            <div class="mt-4"><span class="text-sm text-gray-500">Descricao:</span><br><?= e($form['description']) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($fields)): ?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Campos (<?= count($fields) ?>)</h3></div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead>
                <tr><th>Label</th><th>Tipo</th><th>Obrigatorio</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field): ?>
                    <tr>
                        <td><?= e($field['label'] ?? $field['name'] ?? '') ?></td>
                        <td><span class="badge badge-gray"><?= e($field['type'] ?? 'text') ?></span></td>
                        <td><?= !empty($field['is_required']) ? 'Sim' : 'Nao' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
