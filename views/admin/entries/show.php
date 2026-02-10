<?php
/**
 * Super Admin - Entry Detail
 *
 * Variables from controller:
 * @var array $entry
 * @var array $entryData
 * @var array $formFields
 * @var array $fieldValues
 * @var array $notes
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Entrada #<?= (int) ($entry['id'] ?? 0) ?></h1>
        <p class="page-subtitle">Formulario: <?= e($entry['form_title'] ?? '-') ?> | Cliente: <?= e($entry['tenant_name'] ?? '-') ?></p>
    </div>
    <div class="page-actions">
        <a href="/admin/entries" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="grid grid-4 gap-6 mb-8">
    <div class="stat-card">
        <div class="stat-value text-sm"><?= e($entry['status'] ?? '-') ?></div>
        <div class="stat-label">Status</div>
    </div>
    <div class="stat-card">
        <div class="stat-value text-sm"><?= e($entry['ip_address'] ?? '-') ?></div>
        <div class="stat-label">IP</div>
    </div>
    <div class="stat-card">
        <div class="stat-value text-sm"><?= e($entry['device_type'] ?? '-') ?></div>
        <div class="stat-label">Dispositivo</div>
    </div>
    <div class="stat-card">
        <div class="stat-value text-sm"><?= e($entry['created_at'] ?? '') ?></div>
        <div class="stat-label">Data</div>
    </div>
</div>

<!-- Field Values -->
<div class="card mb-6">
    <div class="card-header"><h3 class="card-title">Dados da Resposta</h3></div>
    <div class="card-body">
        <?php if (!empty($fieldValues)): ?>
            <?php foreach ($fieldValues as $fv): ?>
                <div class="mb-4">
                    <div class="text-xs text-gray-500 font-medium mb-1"><?= e($fv['label'] ?? $fv['name'] ?? '') ?></div>
                    <div class="text-sm"><?= e(is_array($fv['value'] ?? '') ? json_encode($fv['value']) : ($fv['value'] ?? '-')) ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-400 text-sm">Nenhum dado de resposta encontrado.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Notes -->
<?php if (!empty($notes)): ?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Notas (<?= count($notes) ?>)</h3></div>
    <div class="card-body">
        <?php foreach ($notes as $note): ?>
            <div class="mb-4 pb-4" style="border-bottom:1px solid var(--gray-100);">
                <div class="text-xs text-gray-500"><?= e($note['user_name'] ?? $note['email'] ?? 'Sistema') ?> - <?= e($note['created_at'] ?? '') ?></div>
                <div class="text-sm mt-1"><?= e($note['content'] ?? $note['note'] ?? '') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
