<?php
/**
 * Super Admin - Plans Management
 *
 * Variables from controller:
 * @var array $plans
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Planos</h1>
        <p class="page-subtitle">Gerencie os planos de assinatura da plataforma</p>
    </div>
    <div class="page-actions">
        <a href="/admin/plans/create" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Plano
        </a>
    </div>
</div>

<?php if (!empty($plans)): ?>
<div class="grid grid-3 gap-6" id="plans-grid">
    <?php foreach ($plans as $plan): ?>
        <?php
        $features = json_decode($plan['features'] ?? '[]', true) ?: [];
        $isActive = !empty($plan['is_active']);
        $isFeatured = !empty($plan['is_featured']);
        ?>
        <div class="card" style="<?= $isFeatured ? 'border-color:var(--primary);box-shadow:var(--shadow-glow);' : '' ?><?= !$isActive ? 'opacity:0.6;' : '' ?>" data-plan-id="<?= (int)$plan['id'] ?>">
            <?php if ($isFeatured): ?>
                <div style="background:var(--gradient-primary);color:#fff;text-align:center;padding:6px;font-size:var(--font-size-xs);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;">
                    Mais Popular
                </div>
            <?php endif; ?>
            <div class="card-body" style="text-align:center;">
                <!-- Plan Status -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2" style="cursor:grab;" title="Arraste para reordenar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                        <span class="text-xs text-gray-400">#<?= (int)($plan['sort_order'] ?? 0) ?></span>
                    </div>
                    <?php if ($isActive): ?>
                        <span class="badge badge-success">Ativo</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Inativo</span>
                    <?php endif; ?>
                </div>

                <!-- Plan Name -->
                <h3 style="font-size:var(--font-size-xl);margin-bottom:var(--space-2);"><?= e($plan['name']) ?></h3>
                <p class="text-sm text-gray-400 mb-4"><?= e($plan['description'] ?? '') ?></p>

                <!-- Pricing -->
                <div style="margin-bottom:var(--space-6);">
                    <div class="flex items-end justify-center gap-1">
                        <span style="font-size:var(--font-size-sm);color:var(--gray-500);">R$</span>
                        <span style="font-size:var(--font-size-4xl);font-weight:800;line-height:1;color:var(--gray-900);">
                            <?= number_format((float)($plan['price_monthly'] ?? 0), 0, ',', '.') ?>
                        </span>
                        <span style="font-size:var(--font-size-sm);color:var(--gray-400);">/mes</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        R$ <?= number_format((float)($plan['price_yearly'] ?? 0), 2, ',', '.') ?>/ano
                    </div>
                </div>

                <!-- Limits -->
                <div style="text-align:left;margin-bottom:var(--space-6);">
                    <div class="flex items-center gap-2 mb-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span class="text-sm"><?= (int)($plan['max_forms'] ?? 0) ?> formularios</span>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span class="text-sm"><?= number_format((int)($plan['max_entries_per_month'] ?? 0)) ?> respostas/mes</span>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span class="text-sm"><?= (int)($plan['max_users'] ?? 0) ?> usuarios</span>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span class="text-sm"><?= (int)($plan['max_file_storage'] ?? 0) ?> MB armazenamento</span>
                    </div>
                    <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                        <div class="flex items-center gap-2 mb-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <span class="text-sm"><?= e(is_string($feature) ? $feature : ($feature['name'] ?? '')) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Subscriber Count -->
                <div style="padding:var(--space-3);background:var(--gray-50);border-radius:var(--radius-lg);margin-bottom:var(--space-4);">
                    <div class="flex items-center justify-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <span class="text-sm font-semibold"><?= (int)($plan['tenant_count'] ?? 0) ?></span>
                        <span class="text-xs text-gray-400">assinantes</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <a href="/admin/plans/<?= (int)$plan['id'] ?>/edit" class="btn btn-sm btn-outline" style="flex:1;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Editar
                    </a>
                    <form method="POST" action="/admin/plans/<?= (int)$plan['id'] ?>/toggle" style="flex:1;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-ghost' : 'btn-success' ?> w-full">
                            <?= $isActive ? 'Desativar' : 'Ativar' ?>
                        </button>
                    </form>
                    <?php if ((int)($plan['tenant_count'] ?? 0) === 0): ?>
                        <form method="POST" action="/admin/plans/<?= (int)$plan['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm btn-icon btn-ghost text-danger" title="Excluir" onclick="return confirm('Excluir este plano?')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
            </div>
            <h3 class="empty-state-title">Nenhum plano cadastrado</h3>
            <p class="empty-state-text">Crie planos de assinatura para oferecer aos seus clientes.</p>
            <a href="/admin/plans/create" class="btn btn-primary">Criar Primeiro Plano</a>
        </div>
    </div>
<?php endif; ?>
