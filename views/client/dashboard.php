<?php
/**
 * Client Dashboard
 *
 * Variables from controller:
 * @var int   $totalForms
 * @var int   $publishedForms
 * @var int   $totalEntries
 * @var int   $monthlyEntries
 * @var int   $todayEntries
 * @var array $dailyEntries
 * @var array $topForms
 * @var array $conversionRates
 * @var array $recentEntries
 * @var array|null $plan
 * @var array $limits
 * @var array $tenant
 */

$overallConversion = 0;
if (!empty($conversionRates)) {
    $totalConversionSum = array_sum(array_column($conversionRates, 'conversion_rate'));
    $overallConversion = round($totalConversionSum / count($conversionRates), 1);
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Bem-vindo de volta, <?= e($tenant['name'] ?? '') ?></p>
    </div>
    <div class="page-actions">
        <a href="/dashboard/forms/create" class="btn btn-gradient">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Criar Formulario
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="grid grid-4 gap-6 mb-8">
    <!-- Meus Formularios -->
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-value"><?= (int)($totalForms ?? 0) ?></div>
        <div class="stat-label">Meus Formularios</div>
        <div class="stat-change up">
            <?= (int)($publishedForms ?? 0) ?> publicados
        </div>
    </div>

    <!-- Total de Entradas -->
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="stat-value"><?= number_format((int)($totalEntries ?? 0)) ?></div>
        <div class="stat-label">Total de Entradas</div>
        <div class="stat-change up">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            +<?= (int)($todayEntries ?? 0) ?> hoje
        </div>
    </div>

    <!-- Entradas Este Mes -->
    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-value"><?= number_format((int)($monthlyEntries ?? 0)) ?></div>
        <div class="stat-label">Entradas Este Mes</div>
        <?php if ($plan && !empty($limits['max_entries_per_month'])): ?>
            <div class="stat-change <?= ($monthlyEntries ?? 0) > ($limits['max_entries_per_month'] * 0.8) ? 'down' : 'up' ?>">
                <?= round((($monthlyEntries ?? 0) / max(1, $limits['max_entries_per_month'])) * 100) ?>% do limite
            </div>
        <?php else: ?>
            <div class="stat-change up">Este periodo</div>
        <?php endif; ?>
    </div>

    <!-- Taxa de Conversao -->
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
        </div>
        <div class="stat-value"><?= $overallConversion ?>%</div>
        <div class="stat-label">Taxa de Conversao</div>
        <div class="stat-change up">Media geral</div>
    </div>
</div>

<!-- Chart + Top Forms -->
<div class="grid grid-2 gap-6 mb-8">
    <!-- Daily Entries Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Respostas por Dia</h3>
            <span class="badge badge-primary">30 dias</span>
        </div>
        <div class="card-body">
            <div id="chart-daily-entries" style="height: 280px; display: flex; align-items: center; justify-content: center; background: var(--gray-50); border-radius: var(--radius-lg);">
                <div class="text-center">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5" style="margin:0 auto 12px"><path d="M3 3v18h18"/><polyline points="18 9 13 13.5 9 10.5 3 16"/></svg>
                    <p class="text-sm text-gray-400">Grafico de respostas diarias</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Forms -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Formularios com Melhor Desempenho</h3>
            <a href="/dashboard/forms" class="btn btn-sm btn-ghost">Ver Todos</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (!empty($topForms)): ?>
                <?php foreach (array_slice($topForms, 0, 6) as $index => $form): ?>
                    <div class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);">
                        <div style="width:28px;height:28px;border-radius:var(--radius-md);background:var(--primary-50);display:flex;align-items:center;justify-content:center;font-size:var(--font-size-xs);font-weight:700;color:var(--primary);flex-shrink:0;">
                            <?= $index + 1 ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="font-medium text-sm truncate"><?= e($form['title'] ?? '') ?></div>
                            <div class="text-xs text-gray-400">
                                <?= (int)($form['entry_count'] ?? 0) ?> respostas
                                <?php if (!empty($form['last_entry_at'])): ?>
                                    &middot; Ultima: <?= format_date($form['last_entry_at'], 'd/m') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (($form['status'] ?? 'draft') === 'published'): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Rascunho</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:var(--space-8)">
                    <p class="text-sm text-gray-400">Crie seu primeiro formulario para comecar.</p>
                    <a href="/dashboard/forms/create" class="btn btn-sm btn-primary mt-4">Criar Formulario</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Conversion Rates + Recent Entries -->
<div class="grid grid-2 gap-6 mb-8">
    <!-- Conversion Rates -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Taxas de Conversao</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($conversionRates)): ?>
                <?php foreach (array_slice($conversionRates, 0, 6) as $cr): ?>
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium truncate" style="max-width:60%;"><?= e($cr['title'] ?? '') ?></span>
                            <span class="text-sm font-bold text-primary"><?= $cr['conversion_rate'] ?>%</span>
                        </div>
                        <div style="width:100%;height:6px;background:var(--gray-200);border-radius:3px;">
                            <div style="width:<?= min(100, (float)$cr['conversion_rate']) ?>%;height:100%;background:var(--gradient-primary);border-radius:3px;transition:width 0.5s ease;"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-400"><?= (int)($cr['views'] ?? 0) ?> visualizacoes</span>
                            <span class="text-xs text-gray-400"><?= (int)($cr['entries'] ?? 0) ?> respostas</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-400">Nenhum dado de conversao disponivel.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Entries -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Entradas Recentes</h3>
            <a href="/dashboard/entries" class="btn btn-sm btn-ghost">Ver Todas</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (!empty($recentEntries)): ?>
                <?php foreach (array_slice($recentEntries, 0, 7) as $entry): ?>
                    <a href="/dashboard/entries/<?= (int)$entry['id'] ?>" class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);text-decoration:none;transition:background 150ms;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background='transparent'">
                        <div style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0;"></div>
                        <div style="flex:1;min-width:0;">
                            <div class="font-medium text-sm truncate" style="color:var(--gray-800);">
                                <?= e($entry['preview'] ?? 'Entrada #' . $entry['id']) ?>
                            </div>
                            <div class="text-xs text-gray-400"><?= e($entry['form_title'] ?? '') ?></div>
                        </div>
                        <span class="text-xs text-gray-400"><?= format_date($entry['created_at'] ?? '', 'd/m H:i') ?></span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:var(--space-8)">
                    <p class="text-sm text-gray-400">Nenhuma entrada recebida ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-8">
    <div class="card-header">
        <h3 class="card-title">Acoes Rapidas</h3>
    </div>
    <div class="card-body">
        <div class="flex items-center gap-4 flex-wrap">
            <a href="/dashboard/forms/create" class="btn btn-gradient">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo Formulario
            </a>
            <a href="/dashboard/entries" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Ver Entradas
            </a>
            <a href="/dashboard/integrations" class="btn btn-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                Configurar Integracoes
            </a>
            <a href="/dashboard/users" class="btn btn-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Convidar Equipe
            </a>
            <a href="/dashboard/entries?export=csv" class="btn btn-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Dados
            </a>
        </div>
    </div>
</div>

<!-- Plan Usage (if applicable) -->
<?php if (!empty($plan)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Uso do Plano: <?= e($plan['name'] ?? '') ?></h3>
        <a href="/dashboard/billing" class="btn btn-sm btn-outline">Gerenciar Assinatura</a>
    </div>
    <div class="card-body">
        <div class="grid grid-3 gap-6">
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-500">Formularios</span>
                    <span class="text-sm font-semibold"><?= (int)($totalForms ?? 0) ?> / <?= (int)($plan['max_forms'] ?? 0) ?></span>
                </div>
                <?php $formPct = ($plan['max_forms'] ?? 0) > 0 ? min(100, (($totalForms ?? 0) / $plan['max_forms']) * 100) : 0; ?>
                <div style="width:100%;height:8px;background:var(--gray-200);border-radius:4px;">
                    <div style="width:<?= $formPct ?>%;height:100%;background:<?= $formPct > 80 ? 'var(--danger)' : 'var(--primary)' ?>;border-radius:4px;transition:width 0.3s ease;"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-500">Entradas/Mes</span>
                    <span class="text-sm font-semibold"><?= number_format((int)($monthlyEntries ?? 0)) ?> / <?= number_format((int)($plan['max_entries_per_month'] ?? 0)) ?></span>
                </div>
                <?php $entryPct = ($plan['max_entries_per_month'] ?? 0) > 0 ? min(100, (($monthlyEntries ?? 0) / $plan['max_entries_per_month']) * 100) : 0; ?>
                <div style="width:100%;height:8px;background:var(--gray-200);border-radius:4px;">
                    <div style="width:<?= $entryPct ?>%;height:100%;background:<?= $entryPct > 80 ? 'var(--danger)' : 'var(--success)' ?>;border-radius:4px;transition:width 0.3s ease;"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-500">Armazenamento</span>
                    <span class="text-sm font-semibold"><?= format_bytes((int)($tenant['storage_used'] ?? 0)) ?> / <?= (int)($plan['max_file_storage'] ?? 0) ?> MB</span>
                </div>
                <?php $storagePct = ($plan['max_file_storage'] ?? 0) > 0 ? min(100, (($tenant['storage_used'] ?? 0) / (($plan['max_file_storage'] ?? 100) * 1048576)) * 100) : 0; ?>
                <div style="width:100%;height:8px;background:var(--gray-200);border-radius:4px;">
                    <div style="width:<?= $storagePct ?>%;height:100%;background:<?= $storagePct > 80 ? 'var(--danger)' : 'var(--info)' ?>;border-radius:4px;transition:width 0.3s ease;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    window.__chartData = {
        dailyEntries: <?= json_encode($dailyEntries ?? []) ?>
    };
</script>
