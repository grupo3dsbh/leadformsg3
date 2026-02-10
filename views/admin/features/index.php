<?php
/**
 * Super Admin - Features & Integrations
 *
 * Variables from controller:
 * @var array  $features
 * @var array  $integrations
 * @var array  $stats
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Funcionalidades e Integracoes</h1>
        <p class="page-subtitle">Gerencie funcionalidades e integracoes da plataforma</p>
    </div>
</div>

<!-- Stats -->
<?php
    $totalFeatures = count($features ?? []);
    $activeFeatures = count(array_filter($features ?? [], fn($f) => !empty($f['is_active'])));
    $totalIntegrations = array_sum(array_column($features ?? [], 'active_tenant_count'));
?>
<div class="grid grid-3 gap-6 mb-8">
    <div class="stat-card">
        <div class="stat-value"><?= $totalFeatures ?></div>
        <div class="stat-label">Total de Funcionalidades</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $activeFeatures ?></div>
        <div class="stat-label">Funcionalidades Ativas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $totalIntegrations ?></div>
        <div class="stat-label">Integracoes Ativas</div>
    </div>
</div>

<!-- Features Table -->
<div class="card mb-8">
    <div class="card-header">
        <h3 class="card-title">Funcionalidades</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (!empty($features)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Planos</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($features as $feature): ?>
                        <tr>
                            <td class="font-medium"><?= e($feature['name'] ?? '') ?></td>
                            <td class="text-sm text-gray-500"><?= e($feature['slug'] ?? '') ?></td>
                            <td class="text-sm"><?= e($feature['category'] ?? '-') ?></td>
                            <td>
                                <?php if (($feature['is_active'] ?? 0)): ?>
                                    <span class="badge badge-success">Ativa</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm">
                                <?php
                                    $plans = json_decode($feature['plan_required'] ?? '[]', true) ?: [];
                                    echo !empty($plans) ? e(implode(', ', $plans)) : 'Todos';
                                ?>
                            </td>
                            <td>
                                <form method="POST" action="/admin/features/<?= (int) $feature['id'] ?>/toggle" style="display:inline;">
                                    <button type="submit" class="btn btn-sm <?= ($feature['is_active'] ?? 0) ? 'btn-ghost' : 'btn-primary' ?>">
                                        <?= ($feature['is_active'] ?? 0) ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state" style="padding:48px 24px;text-align:center;">
                <p class="text-gray-400">Nenhuma funcionalidade cadastrada.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Integrations Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Integracoes</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (!empty($integrations)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Status</th>
                        <th>Criado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($integrations as $integration): ?>
                        <tr>
                            <td class="font-medium"><?= e($integration['name'] ?? '') ?></td>
                            <td><span class="badge badge-gray"><?= e($integration['type'] ?? '-') ?></span></td>
                            <td class="text-sm"><?= e($integration['tenant_name'] ?? '-') ?></td>
                            <td>
                                <?php if (($integration['status'] ?? '') === 'active'): ?>
                                    <span class="badge badge-success">Ativa</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-gray-500"><?= e($integration['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state" style="padding:48px 24px;text-align:center;">
                <p class="text-gray-400">Nenhuma integracao cadastrada.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
