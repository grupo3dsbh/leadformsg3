<?php
/**
 * Super Admin - Audit Log Detail
 *
 * Variables from controller:
 * @var array $log
 * @var array $decoded_data
 */
$decoded_data = $decoded_data ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Log de Auditoria #<?= (int) ($log['id'] ?? 0) ?></h1>
        <p class="page-subtitle"><?= e($log['action'] ?? '') ?></p>
    </div>
    <div class="page-actions">
        <a href="/admin/audit" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="card-title">Detalhes</h3></div>
    <div class="card-body">
        <div class="grid grid-2 gap-4">
            <div><span class="text-sm text-gray-500">Acao:</span> <strong><?= e($log['action'] ?? '') ?></strong></div>
            <div><span class="text-sm text-gray-500">Entidade:</span> <strong><?= e($log['entity_type'] ?? '') ?> #<?= (int) ($log['entity_id'] ?? 0) ?></strong></div>
            <div><span class="text-sm text-gray-500">Usuario:</span> <?= e($log['user_name'] ?? $log['user_email'] ?? '-') ?></div>
            <div><span class="text-sm text-gray-500">IP:</span> <?= e($log['ip_address'] ?? '-') ?></div>
            <div><span class="text-sm text-gray-500">User Agent:</span> <?= e($log['user_agent'] ?? '-') ?></div>
            <div><span class="text-sm text-gray-500">Data:</span> <?= e($log['created_at'] ?? '') ?></div>
        </div>
    </div>
</div>

<?php if (!empty($log['old_values'])): ?>
<div class="card mb-6">
    <div class="card-header"><h3 class="card-title">Valores Anteriores</h3></div>
    <div class="card-body">
        <pre style="background:var(--gray-50);padding:var(--space-4);border-radius:var(--radius-md);font-size:var(--font-size-sm);overflow-x:auto;"><?= e(json_encode(json_decode($log['old_values'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: $log['old_values']) ?></pre>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($log['new_values'])): ?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Novos Valores</h3></div>
    <div class="card-body">
        <pre style="background:var(--gray-50);padding:var(--space-4);border-radius:var(--radius-md);font-size:var(--font-size-sm);overflow-x:auto;"><?= e(json_encode(json_decode($log['new_values'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: $log['new_values']) ?></pre>
    </div>
</div>
<?php endif; ?>
