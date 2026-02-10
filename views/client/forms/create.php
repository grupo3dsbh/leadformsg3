<?php
/**
 * Client - Create Form
 *
 * @var array $form
 * @var array $errors
 */
$errors = $errors ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Novo Formulario</h1>
        <p class="page-subtitle">Criar um novo formulario</p>
    </div>
    <div class="page-actions">
        <a href="/dashboard/forms" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/dashboard/forms">
        <?= csrf_field() ?>
        <div class="card-body">
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

            <div class="form-group">
                <label class="form-label">Titulo do Formulario *</label>
                <input type="text" name="title" class="form-input" value="<?= e($form['title'] ?? '') ?>" required placeholder="Ex: Formulario de Contato">
            </div>

            <div class="form-group">
                <label class="form-label">Descricao</label>
                <textarea name="description" class="form-textarea" rows="3" placeholder="Descricao opcional do formulario"><?= e($form['description'] ?? '') ?></textarea>
            </div>

            <input type="hidden" name="fields" value="<?= e($form['fields'] ?? '[]') ?>">
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Criar Formulario</button>
            <a href="/dashboard/forms" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
