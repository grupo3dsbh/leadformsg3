<?php
/**
 * Super Admin - Create Client
 *
 * Variables from controller:
 * @var array $plans
 * @var array $errors
 * @var array $old
 */
$old = $old ?? [];
$errors = $errors ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Novo Cliente</h1>
        <p class="page-subtitle">Cadastrar um novo cliente na plataforma</p>
    </div>
    <div class="page-actions">
        <a href="/admin/clients" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/admin/clients">
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

            <div class="grid grid-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Nome da Empresa *</label>
                    <input type="text" name="name" class="form-input" value="<?= e($old['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug *</label>
                    <input type="text" name="slug" class="form-input" value="<?= e($old['slug'] ?? '') ?>" required placeholder="minha-empresa">
                    <p class="form-hint">Usado na URL: /f/slug/formulario</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" value="<?= e($old['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Plano *</label>
                    <select name="plan_id" class="form-select" required>
                        <option value="">Selecione um plano</option>
                        <?php foreach ($plans ?? [] as $plan): ?>
                            <option value="<?= (int) $plan['id'] ?>" <?= ((int) ($old['plan_id'] ?? 0)) === (int) $plan['id'] ? 'selected' : '' ?>><?= e($plan['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Criar Cliente</button>
            <a href="/admin/clients" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
