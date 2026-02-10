<?php
/**
 * Client - Form Details
 *
 * @var array $form
 * @var array $fields
 * @var array $settings
 * @var int $entryCount
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($form['title'] ?? 'Formulario') ?></h1>
        <p class="page-subtitle">Detalhes do formulario</p>
    </div>
    <div class="page-actions">
        <a href="/dashboard/forms" class="btn btn-ghost">Voltar</a>
        <a href="/dashboard/forms/<?= (int) $form['id'] ?>/edit" class="btn btn-outline">Editar</a>
        <a href="/dashboard/forms/<?= (int) $form['id'] ?>/builder" class="btn btn-primary">Builder</a>
    </div>
</div>

<div class="grid grid-4 gap-6 mb-6">
    <div class="stat-card">
        <div class="stat-value"><?= count($fields) ?></div>
        <div class="stat-label">Campos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $entryCount ?></div>
        <div class="stat-label">Respostas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int) ($form['views_count'] ?? 0) ?></div>
        <div class="stat-label">Visualizacoes</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">
            <?php if (($form['status'] ?? 'draft') === 'published'): ?>
                <span class="badge badge-success">Publicado</span>
            <?php else: ?>
                <span class="badge badge-gray"><?= ucfirst(e($form['status'] ?? 'draft')) ?></span>
            <?php endif; ?>
        </div>
        <div class="stat-label">Status</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="card-title">Informacoes</h3></div>
    <div class="card-body">
        <div class="grid grid-2 gap-4">
            <div><span class="text-sm text-gray-500">Slug:</span> <strong class="font-mono"><?= e($form['slug'] ?? '') ?></strong></div>
            <div><span class="text-sm text-gray-500">Link Publico:</span> <a href="/f/<?= e($form['slug'] ?? '') ?>" target="_blank" class="text-primary">/f/<?= e($form['slug'] ?? '') ?></a></div>
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
                        <td><?= !empty($field['required']) ? 'Sim' : 'Nao' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
