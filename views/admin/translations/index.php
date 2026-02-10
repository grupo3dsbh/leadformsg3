<?php
/**
 * Super Admin - Translations
 *
 * Variables from controller:
 * @var array  $locales
 * @var array  $groups
 * @var string $currentLocale
 * @var int    $totalKeys
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Traducoes</h1>
        <p class="page-subtitle">Gerencie as traducoes da plataforma</p>
    </div>
</div>

<!-- Locale selector -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex items-center gap-4 flex-wrap">
            <span class="text-sm font-medium">Idioma:</span>
            <?php foreach ($locales ?? [] as $loc): ?>
                <a href="/admin/translations?locale=<?= urlencode($loc) ?>"
                   class="btn btn-sm <?= ($currentLocale ?? '') === $loc ? 'btn-primary' : 'btn-ghost' ?>">
                    <?= strtoupper(e($loc)) ?>
                </a>
            <?php endforeach; ?>
            <?php if (empty($locales)): ?>
                <span class="text-sm text-gray-400">Nenhum idioma cadastrado.</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Translation Groups -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Grupos de Traducao</h3>
        <span class="badge badge-gray"><?= (int) ($totalKeys ?? 0) ?> chaves</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (!empty($groups)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Chaves</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td class="font-medium"><?= e($group['group'] ?? '') ?></td>
                            <td class="text-sm text-gray-500"><?= (int) ($group['key_count'] ?? 0) ?></td>
                            <td>
                                <a href="/admin/translations/<?= urlencode($group['group'] ?? '') ?>?locale=<?= urlencode($currentLocale ?? 'pt-BR') ?>" class="btn btn-sm btn-ghost">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state" style="padding:48px 24px;text-align:center;">
                <p class="text-gray-400">Nenhuma traducao encontrada para este idioma.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
