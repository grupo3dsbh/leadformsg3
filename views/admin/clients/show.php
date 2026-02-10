<?php
/**
 * Super Admin - Client Detail
 *
 * Variables from controller:
 * @var array      $tenant
 * @var array|null $owner
 * @var array|null $plan
 * @var array      $users
 * @var array      $forms
 * @var array      $recentEntries
 * @var array|null $subscription
 * @var array      $payments
 * @var array      $auditLogs
 */
?>

<div class="page-header">
    <div class="flex items-center gap-4">
        <a href="/admin/clients" class="btn btn-icon btn-ghost">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 class="page-title"><?= e($tenant['name']) ?></h1>
            <p class="page-subtitle"><?= e($tenant['slug']) ?> <?= !empty($tenant['domain']) ? '&middot; ' . e($tenant['domain']) : '' ?></p>
        </div>
    </div>
    <div class="page-actions">
        <a href="/admin/clients/<?= (int)$tenant['id'] ?>/edit" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Editar
        </a>
        <form method="POST" action="/admin/clients/<?= (int)$tenant['id'] ?>/login-as" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Deseja entrar como este cliente?')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Entrar como Cliente
            </button>
        </form>
        <div class="dropdown">
            <button class="btn btn-ghost btn-icon" onclick="this.parentElement.classList.toggle('open')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
            </button>
            <div class="dropdown-menu">
                <?php if (($tenant['status'] ?? 'active') === 'active'): ?>
                    <form method="POST" action="/admin/clients/<?= (int)$tenant['id'] ?>/suspend">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-warning" onclick="return confirm('Suspender este cliente?')" style="width:100%;border:none;background:none;cursor:pointer;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                            Suspender
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="/admin/clients/<?= (int)$tenant['id'] ?>/activate">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-success" style="width:100%;border:none;background:none;cursor:pointer;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Ativar
                        </button>
                    </form>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <form method="POST" action="/admin/clients/<?= (int)$tenant['id'] ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('ATENCAO: Esta acao ira deletar o cliente e TODOS os dados associados. Deseja continuar?')" style="width:100%;border:none;background:none;cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        Excluir Cliente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Client Info + Stats -->
<div class="grid grid-4 gap-6 mb-8">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-value"><?= count($forms) ?></div>
        <div class="stat-label">Formularios</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="stat-value"><?= count($recentEntries) ?>+</div>
        <div class="stat-label">Respostas Recentes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-value"><?= count($users) ?></div>
        <div class="stat-label">Usuarios</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= ($tenant['status'] ?? 'active') === 'active' ? 'success' : 'danger' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-value" style="font-size:var(--font-size-xl);">
            <?php if (($tenant['status'] ?? 'active') === 'active'): ?>
                <span class="badge badge-success" style="font-size:var(--font-size-sm);">Ativo</span>
            <?php elseif (($tenant['status'] ?? '') === 'suspended'): ?>
                <span class="badge badge-danger" style="font-size:var(--font-size-sm);">Suspenso</span>
            <?php else: ?>
                <span class="badge badge-gray" style="font-size:var(--font-size-sm);">Inativo</span>
            <?php endif; ?>
        </div>
        <div class="stat-label">Status</div>
    </div>
</div>

<div class="grid grid-3 gap-6 mb-8">
    <!-- Client Details Card -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3 class="card-title">Informacoes do Cliente</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-2 gap-6">
                <div>
                    <label class="form-label">Nome</label>
                    <p class="text-sm"><?= e($tenant['name']) ?></p>
                </div>
                <div>
                    <label class="form-label">Slug</label>
                    <p class="text-sm font-mono" style="font-family:var(--font-mono);color:var(--primary);"><?= e($tenant['slug']) ?></p>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <p class="text-sm"><?= e($tenant['email'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="form-label">Telefone</label>
                    <p class="text-sm"><?= e($tenant['phone'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="form-label">Dominio</label>
                    <p class="text-sm"><?= e($tenant['domain'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="form-label">Dominio Personalizado</label>
                    <p class="text-sm"><?= e($tenant['custom_domain'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="form-label">Armazenamento</label>
                    <p class="text-sm"><?= format_bytes((int)($tenant['storage_used'] ?? 0)) ?> / <?= format_bytes((int)($tenant['max_storage'] ?? 104857600)) ?></p>
                </div>
                <div>
                    <label class="form-label">Criado em</label>
                    <p class="text-sm"><?= format_date($tenant['created_at'] ?? '') ?></p>
                </div>
            </div>

            <?php if ($owner): ?>
                <div style="margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gray-100);">
                    <label class="form-label">Proprietario</label>
                    <div class="flex items-center gap-3 mt-2">
                        <div class="avatar-initials" style="width:36px;height:36px;font-size:13px;">
                            <?= strtoupper(substr($owner['name'] ?? '', 0, 2)) ?>
                        </div>
                        <div>
                            <div class="font-medium text-sm"><?= e($owner['name'] ?? '') ?></div>
                            <div class="text-xs text-gray-400"><?= e($owner['email'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subscription Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Assinatura</h3>
        </div>
        <div class="card-body">
            <?php if ($plan): ?>
                <div style="text-align:center;padding:var(--space-4) 0;">
                    <span class="badge badge-primary" style="font-size:var(--font-size-base);padding:6px 16px;"><?= e($plan['name']) ?></span>
                    <div class="stat-value mt-4">
                        R$ <?= number_format((float)($plan['price_monthly'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="text-sm text-gray-400">/mes</div>
                </div>
                <div style="margin-top:var(--space-4);padding-top:var(--space-4);border-top:1px solid var(--gray-100);">
                    <div class="flex justify-between mb-2">
                        <span class="text-xs text-gray-500">Formularios</span>
                        <span class="text-xs font-semibold"><?= count($forms) ?> / <?= (int)($plan['max_forms'] ?? 0) ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-xs text-gray-500">Usuarios</span>
                        <span class="text-xs font-semibold"><?= count($users) ?> / <?= (int)($plan['max_users'] ?? 0) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs text-gray-500">Armazenamento</span>
                        <span class="text-xs font-semibold"><?= (int)($plan['max_file_storage'] ?? 0) ?> MB</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:var(--space-6)">
                    <p class="text-sm text-gray-400">Nenhum plano atribuido.</p>
                </div>
            <?php endif; ?>

            <?php if ($subscription): ?>
                <div style="margin-top:var(--space-4);padding-top:var(--space-4);border-top:1px solid var(--gray-100);">
                    <div class="flex justify-between mb-2">
                        <span class="text-xs text-gray-500">Status</span>
                        <span class="badge badge-<?= $subscription['status'] === 'active' ? 'success' : 'warning' ?>"><?= e($subscription['status']) ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-xs text-gray-500">Periodo</span>
                        <span class="text-xs font-semibold"><?= e($subscription['interval'] ?? '-') ?></span>
                    </div>
                    <?php if (!empty($subscription['current_period_end'])): ?>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500">Renovacao</span>
                            <span class="text-xs font-semibold"><?= format_date($subscription['current_period_end'], 'd/m/Y') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Forms and Recent Entries -->
<div class="grid grid-2 gap-6">
    <!-- Forms -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Formularios (<?= count($forms) ?>)</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (!empty($forms)): ?>
                <?php foreach (array_slice($forms, 0, 10) as $form): ?>
                    <div class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);">
                        <div style="width:36px;height:36px;border-radius:var(--radius-lg);background:var(--primary-50);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="font-medium text-sm truncate"><?= e($form['title'] ?? '') ?></div>
                            <div class="text-xs text-gray-400"><?= (int)($form['entry_count'] ?? 0) ?> respostas</div>
                        </div>
                        <?php if (($form['status'] ?? 'draft') === 'published'): ?>
                            <span class="badge badge-success">Publicado</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Rascunho</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:var(--space-8)">
                    <p class="text-sm text-gray-400">Nenhum formulario criado.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Entries -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Respostas Recentes</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (!empty($recentEntries)): ?>
                <?php foreach (array_slice($recentEntries, 0, 10) as $entry): ?>
                    <div class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);">
                        <div style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0;"></div>
                        <div style="flex:1;min-width:0;">
                            <div class="font-medium text-sm truncate"><?= e($entry['form_title'] ?? '') ?></div>
                            <div class="text-xs text-gray-400">ID #<?= (int)$entry['id'] ?></div>
                        </div>
                        <span class="text-xs text-gray-400"><?= format_date($entry['created_at'] ?? '', 'd/m H:i') ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:var(--space-8)">
                    <p class="text-sm text-gray-400">Nenhuma resposta recebida.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment History -->
<?php if (!empty($payments)): ?>
<div class="card mt-8">
    <div class="card-header">
        <h3 class="card-title">Historico de Pagamentos</h3>
    </div>
    <div class="table-container" style="border:none;border-radius:0;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Gateway</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($payments, 0, 10) as $payment): ?>
                    <tr>
                        <td class="font-mono text-sm">#<?= (int)$payment['id'] ?></td>
                        <td class="font-semibold">R$ <?= number_format((float)($payment['amount'] ?? 0), 2, ',', '.') ?></td>
                        <td>
                            <?php
                            $pStatus = $payment['status'] ?? 'pending';
                            $pBadge = match($pStatus) {
                                'succeeded' => 'badge-success',
                                'failed' => 'badge-danger',
                                'refunded' => 'badge-warning',
                                default => 'badge-gray',
                            };
                            ?>
                            <span class="badge <?= $pBadge ?>"><?= e($pStatus) ?></span>
                        </td>
                        <td class="text-sm"><?= e($payment['payment_gateway'] ?? '-') ?></td>
                        <td class="text-sm text-gray-500"><?= format_date($payment['created_at'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
