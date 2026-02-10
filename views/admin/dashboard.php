<?php
/**
 * Super Admin Dashboard
 *
 * Variables from controller:
 * @var int    $totalClients
 * @var int    $activeClients
 * @var int    $totalForms
 * @var int    $totalEntries
 * @var int    $totalUsers
 * @var float  $monthlyRevenue
 * @var float  $yearlyRevenue
 * @var array  $clientGrowth
 * @var array  $entryGrowth
 * @var array  $revenueGrowth
 * @var array  $recentClients
 * @var array  $recentEntries
 * @var array  $planDistribution
 * @var int    $todayEntries
 * @var int    $todaySignups
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visao geral da plataforma</p>
    </div>
    <div class="page-actions">
        <a href="/admin/clients/create" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Novo Cliente
        </a>
        <a href="/admin/settings" class="btn btn-ghost">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            Configuracoes
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="grid grid-4 gap-6 mb-8">
    <!-- Total Clients -->
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="stat-value"><?= number_format($totalClients) ?></div>
        <div class="stat-label">Total de Clientes</div>
        <div class="stat-change up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            <?= $activeClients ?> ativos
        </div>
    </div>

    <!-- Total Forms -->
    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div class="stat-value"><?= number_format($totalForms) ?></div>
        <div class="stat-label">Total de Formularios</div>
        <div class="stat-change up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            +<?= $todayEntries ?> hoje
        </div>
    </div>

    <!-- Total Entries -->
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="stat-value"><?= number_format($totalEntries) ?></div>
        <div class="stat-label">Total de Respostas</div>
        <div class="stat-change up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            <?= $todayEntries ?> hoje
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-value">R$ <?= number_format($monthlyRevenue, 2, ',', '.') ?></div>
        <div class="stat-label">Receita Mensal</div>
        <div class="stat-change up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            R$ <?= number_format($yearlyRevenue, 2, ',', '.') ?> anual
        </div>
    </div>
</div>

<!-- Today's Snapshot Bar -->
<div class="card mb-8">
    <div class="card-body">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <span class="text-sm text-gray-500">Cadastros Hoje</span>
                    <div class="font-bold text-xl"><?= $todaySignups ?></div>
                </div>
                <div style="width:1px;height:40px;background:var(--gray-200)"></div>
                <div>
                    <span class="text-sm text-gray-500">Respostas Hoje</span>
                    <div class="font-bold text-xl"><?= $todayEntries ?></div>
                </div>
                <div style="width:1px;height:40px;background:var(--gray-200)"></div>
                <div>
                    <span class="text-sm text-gray-500">Total Usuarios</span>
                    <div class="font-bold text-xl"><?= number_format($totalUsers) ?></div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="/admin/clients" class="btn btn-sm btn-outline">Ver Clientes</a>
                <a href="/admin/audit" class="btn btn-sm btn-ghost">Log de Auditoria</a>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-2 gap-6 mb-8">
    <!-- Client Growth Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Crescimento de Clientes</h3>
            <span class="badge badge-primary">12 meses</span>
        </div>
        <div class="card-body">
            <div id="chart-client-growth" style="height: 280px; display: flex; align-items: center; justify-content: center; background: var(--gray-50); border-radius: var(--radius-lg);">
                <div class="text-center">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5" style="margin:0 auto 12px"><path d="M3 3v18h18"/><polyline points="18 9 13 13.5 9 10.5 3 16"/></svg>
                    <p class="text-sm text-gray-400">Grafico de crescimento</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Growth Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Receita Mensal</h3>
            <span class="badge badge-success">12 meses</span>
        </div>
        <div class="card-body">
            <div id="chart-revenue-growth" style="height: 280px; display: flex; align-items: center; justify-content: center; background: var(--gray-50); border-radius: var(--radius-lg);">
                <div class="text-center">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5" style="margin:0 auto 12px"><rect x="2" y="3" width="20" height="18" rx="2"/><line x1="2" y1="9" x2="22" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    <p class="text-sm text-gray-400">Grafico de receita</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Plan Distribution + Entry Growth -->
<div class="grid grid-2 gap-6 mb-8">
    <!-- Plan Distribution -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Distribuicao por Plano</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($planDistribution)): ?>
                <?php foreach ($planDistribution as $plan): ?>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="avatar-initials" style="width:32px;height:32px;font-size:12px;">
                                <?= strtoupper(substr($plan['plan_name'] ?? 'N', 0, 1)) ?>
                            </div>
                            <span class="font-medium text-sm"><?= e($plan['plan_name'] ?? 'Sem plano') ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div style="width:120px;height:6px;background:var(--gray-200);border-radius:3px;">
                                <?php $pct = $totalClients > 0 ? ($plan['tenant_count'] / $totalClients) * 100 : 0; ?>
                                <div style="width:<?= $pct ?>%;height:100%;background:var(--primary);border-radius:3px;"></div>
                            </div>
                            <span class="text-sm font-semibold" style="min-width:40px;text-align:right;"><?= $plan['tenant_count'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-400">Nenhum plano cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Entries Growth Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Respostas por Mes</h3>
            <span class="badge badge-info">12 meses</span>
        </div>
        <div class="card-body">
            <div id="chart-entries-growth" style="height: 280px; display: flex; align-items: center; justify-content: center; background: var(--gray-50); border-radius: var(--radius-lg);">
                <div class="text-center">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5" style="margin:0 auto 12px"><rect x="3" y="12" width="4" height="8" rx="1"/><rect x="10" y="8" width="4" height="12" rx="1"/><rect x="17" y="4" width="4" height="16" rx="1"/></svg>
                    <p class="text-sm text-gray-400">Grafico de respostas</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-2 gap-6">
    <!-- Recent Clients -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Clientes Recentes</h3>
            <a href="/admin/clients" class="btn btn-sm btn-ghost">Ver Todos</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (!empty($recentClients)): ?>
                <?php foreach (array_slice($recentClients, 0, 7) as $client): ?>
                    <a href="/admin/clients/<?= (int)$client['id'] ?>" class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);text-decoration:none;transition:background 150ms;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background='transparent'">
                        <div class="avatar-initials" style="width:36px;height:36px;font-size:13px;">
                            <?= strtoupper(substr($client['name'] ?? '', 0, 2)) ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="font-medium text-sm truncate" style="color:var(--gray-800)"><?= e($client['name']) ?></div>
                            <div class="text-xs text-gray-400"><?= e($client['slug']) ?></div>
                        </div>
                        <?php if (($client['status'] ?? '') === 'active'): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Suspenso</span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-400"><?= format_date($client['created_at'], 'd/m') ?></span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:var(--space-8)">
                    <p class="text-sm text-gray-400">Nenhum cliente cadastrado.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Entries -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Respostas Recentes</h3>
            <span class="badge badge-gray"><?= $todayEntries ?> hoje</span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (!empty($recentEntries)): ?>
                <?php foreach (array_slice($recentEntries, 0, 7) as $entry): ?>
                    <div class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);">
                        <div style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0;"></div>
                        <div style="flex:1;min-width:0;">
                            <div class="font-medium text-sm truncate"><?= e($entry['form_title'] ?? '') ?></div>
                            <div class="text-xs text-gray-400"><?= e($entry['tenant_name'] ?? '') ?></div>
                        </div>
                        <span class="text-xs text-gray-400"><?= format_date($entry['created_at'], 'd/m H:i') ?></span>
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

<!-- Chart.js data for future integration -->
<script>
    window.__chartData = {
        clientGrowth: <?= json_encode($clientGrowth) ?>,
        entryGrowth: <?= json_encode($entryGrowth) ?>,
        revenueGrowth: <?= json_encode($revenueGrowth) ?>,
        planDistribution: <?= json_encode($planDistribution) ?>
    };
</script>
