<?php
/**
 * Super Admin - Edit Translation Group
 *
 * Variables from controller:
 * @var string $group
 * @var string $locale
 * @var array  $translations
 */
$translations = $translations ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar Traducoes</h1>
        <p class="page-subtitle">Grupo: <?= e($group ?? '') ?> | Idioma: <?= strtoupper(e($locale ?? '')) ?></p>
    </div>
    <div class="page-actions">
        <a href="/admin/translations" class="btn btn-ghost">Voltar</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/admin/translations">
        <?= csrf_field() ?>
        <input type="hidden" name="group" value="<?= e($group ?? '') ?>">
        <input type="hidden" name="locale" value="<?= e($locale ?? '') ?>">
        <div class="card-body">
            <?php if (!empty($translations)): ?>
                <?php foreach ($translations as $key => $value): ?>
                    <div class="form-group">
                        <label class="form-label" style="font-family:var(--font-mono);font-size:var(--font-size-xs);"><?= e($key) ?></label>
                        <input type="text" name="translations[<?= e($key) ?>]" class="form-input" value="<?= e($value) ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-400">Nenhuma chave de traducao encontrada neste grupo.</p>
            <?php endif; ?>
        </div>
        <?php if (!empty($translations)): ?>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Salvar Traducoes</button>
        </div>
        <?php endif; ?>
    </form>
</div>
