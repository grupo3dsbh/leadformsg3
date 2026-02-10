<?php
/**
 * Client - Edit Form
 *
 * @var array $form
 * @var array $fields
 * @var array $errors
 */
$errors = $errors ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar Formulario</h1>
        <p class="page-subtitle"><?= e($form['title'] ?? '') ?></p>
    </div>
    <div class="page-actions">
        <a href="/dashboard/forms" class="btn btn-ghost">Voltar</a>
        <a href="/dashboard/forms/<?= (int) $form['id'] ?>/builder" class="btn btn-primary">Abrir Builder</a>
        <?php if (!empty($form['slug'])): ?>
            <a href="/f/<?= e($form['slug']) ?>" target="_blank" class="btn btn-outline">Visualizar</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-6">
        <ul style="margin:0;padding-left:1rem;">
            <?php foreach ($errors as $field => $messages): ?>
                <?php foreach ((array) $messages as $msg): ?>
                    <li><?= e($msg) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid grid-3 gap-6 mb-6">
    <div class="stat-card">
        <div class="stat-value"><?= count($fields) ?></div>
        <div class="stat-label">Campos</div>
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

<div class="card">
    <form method="POST" action="/dashboard/forms/<?= (int) $form['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="card-body">
            <div class="grid grid-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Titulo *</label>
                    <input type="text" name="title" class="form-input" value="<?= e($form['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-input" value="<?= e($form['slug'] ?? '') ?>" disabled>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descricao</label>
                <textarea name="description" class="form-textarea" rows="3"><?= e($form['description'] ?? '') ?></textarea>
            </div>

            <!-- Fields are managed via form_fields table, not a JSON column -->
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="/dashboard/forms" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
