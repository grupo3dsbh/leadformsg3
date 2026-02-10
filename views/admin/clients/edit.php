<?php
/**
 * Super Admin - Edit Client
 *
 * Variables from controller:
 * @var array $tenant
 * @var array $plans
 * @var array $errors
 */
$errors = $errors ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar Cliente</h1>
        <p class="page-subtitle"><?= e($tenant['name'] ?? '') ?></p>
    </div>
    <div class="page-actions">
        <a href="/admin/clients/<?= (int) $tenant['id'] ?>" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/admin/clients/<?= (int) $tenant['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
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
                    <input type="text" name="name" class="form-input" value="<?= e($tenant['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug *</label>
                    <input type="text" name="slug" class="form-input" value="<?= e($tenant['slug'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Dominio Personalizado</label>
                    <input type="text" name="domain" class="form-input" value="<?= e($tenant['domain'] ?? '') ?>" placeholder="exemplo.com.br">
                </div>
                <div class="form-group">
                    <label class="form-label">Plano *</label>
                    <select name="plan_id" class="form-select" required>
                        <?php foreach ($plans ?? [] as $plan): ?>
                            <option value="<?= (int) $plan['id'] ?>" <?= ((int) ($tenant['plan_id'] ?? 0)) === (int) $plan['id'] ? 'selected' : '' ?>><?= e($plan['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Limite de Armazenamento (MB)</label>
                    <input type="number" name="storage_limit_mb" class="form-input" value="<?= (int) (($tenant['max_storage'] ?? 0) / 1048576) ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <input type="text" class="form-input" value="<?= e($tenant['status'] ?? '') ?>" disabled>
                    <p class="form-hint">Use os botoes Suspender/Ativar na pagina de detalhes.</p>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Salvar Alteracoes</button>
            <a href="/admin/clients/<?= (int) $tenant['id'] ?>" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
