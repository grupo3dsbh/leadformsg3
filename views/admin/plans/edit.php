<?php
/**
 * Super Admin - Edit Plan
 *
 * Variables from controller:
 * @var array $plan
 * @var array $allFeatures
 * @var array $errors
 * @var int $tenantCount
 */
$errors = $errors ?? [];
$allFeatures = $allFeatures ?? [];
$planFeatures = json_decode($plan['features'] ?? '[]', true) ?: [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar Plano</h1>
        <p class="page-subtitle"><?= e($plan['name'] ?? '') ?></p>
    </div>
    <div class="page-actions">
        <a href="/admin/plans" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/admin/plans/<?= (int) $plan['id'] ?>">
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

            <div class="grid grid-3 gap-6">
                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" class="form-input" value="<?= e($plan['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug *</label>
                    <input type="text" name="slug" class="form-input" value="<?= e($plan['slug'] ?? '') ?>" required placeholder="plano-basico">
                </div>
                <div class="form-group">
                    <label class="form-label">Moeda</label>
                    <select name="currency" class="form-select">
                        <option value="BRL" <?= ($plan['currency'] ?? 'BRL') === 'BRL' ? 'selected' : '' ?>>BRL (Real)</option>
                        <option value="USD" <?= ($plan['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD (Dolar)</option>
                        <option value="EUR" <?= ($plan['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR (Euro)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Preco Mensal *</label>
                    <input type="number" name="price_monthly" class="form-input" value="<?= e($plan['price_monthly'] ?? '0') ?>" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Preco Anual</label>
                    <input type="number" name="price_yearly" class="form-input" value="<?= e($plan['price_yearly'] ?? '0') ?>" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Ordem de Exibicao</label>
                    <input type="number" name="sort_order" class="form-input" value="<?= (int) ($plan['sort_order'] ?? 0) ?>" min="0">
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
                    <p class="form-hint">0 = ilimitado</p>
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

            <?php if (!empty($allFeatures)): ?>
            <h4 class="font-semibold mt-6 mb-4">Funcionalidades Incluidas</h4>
            <div class="grid grid-3 gap-4">
                <?php foreach ($allFeatures as $feat): ?>
                    <label class="flex items-center gap-2" style="cursor:pointer;">
                        <input type="checkbox" name="feature_slugs[]" value="<?= e($feat['slug']) ?>"
                            <?= in_array($feat['slug'], $planFeatures) ? 'checked' : '' ?>>
                        <span class="text-sm"><?= e($feat['name']) ?></span>
                        <span class="text-xs text-gray-400">(<?= e($feat['category'] ?? '') ?>)</span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

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
            <button type="submit" class="btn btn-primary">Salvar Alteracoes</button>
            <a href="/admin/plans" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
