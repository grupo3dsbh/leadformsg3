<?php
/**
 * Super Admin - Create Plan
 *
 * Variables from controller:
 * @var array $plan
 * @var array $errors
 */
$errors = $errors ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Novo Plano</h1>
        <p class="page-subtitle">Cadastrar um novo plano de assinatura</p>
    </div>
    <div class="page-actions">
        <a href="/admin/plans" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/admin/plans">
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
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" class="form-input" value="<?= e($plan['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug *</label>
                    <input type="text" name="slug" class="form-input" value="<?= e($plan['slug'] ?? '') ?>" required placeholder="plano-basico">
                </div>
                <div class="form-group">
                    <label class="form-label">Preco Mensal *</label>
                    <input type="number" name="price_monthly" class="form-input" value="<?= e($plan['price_monthly'] ?? '') ?>" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Preco Anual</label>
                    <input type="number" name="price_yearly" class="form-input" value="<?= e($plan['price_yearly'] ?? '') ?>" step="0.01" min="0">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descricao</label>
                <textarea name="description" class="form-textarea" rows="3"><?= e($plan['description'] ?? '') ?></textarea>
            </div>

            <h4 class="font-semibold mt-6 mb-4">Limites</h4>
            <div class="grid grid-3 gap-6">
                <div class="form-group">
                    <label class="form-label">Max Formularios</label>
                    <input type="number" name="max_forms" class="form-input" value="<?= (int) ($plan['max_forms'] ?? 5) ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Entradas/Mes</label>
                    <input type="number" name="max_entries_per_month" class="form-input" value="<?= (int) ($plan['max_entries_per_month'] ?? 100) ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Armazenamento (MB)</label>
                    <input type="number" name="max_file_storage" class="form-input" value="<?= (int) ($plan['max_file_storage'] ?? 100) ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Usuarios</label>
                    <input type="number" name="max_users" class="form-input" value="<?= (int) ($plan['max_users'] ?? 1) ?>" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Tamanho Arquivo (MB)</label>
                    <input type="number" name="max_file_size" class="form-input" value="<?= (int) ($plan['max_file_size'] ?? 5) ?>" min="1">
                </div>
            </div>

            <div class="flex items-center gap-6 mt-6">
                <label class="form-toggle">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($plan['is_active']) ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span class="text-sm">Plano Ativo</span>

                <label class="form-toggle" style="margin-left:24px;">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($plan['is_featured']) ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span class="text-sm">Plano Destaque</span>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Criar Plano</button>
            <a href="/admin/plans" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
