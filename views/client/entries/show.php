<?php
/**
 * Client - Entry Detail
 *
 * Variables from controller:
 * @var array      $entry
 * @var array      $form
 * @var array      $entryData
 * @var array      $fieldValues
 * @var array      $notes
 * @var int|null   $prevId
 * @var int|null   $nextId
 */

$entryId = (int)($entry['id'] ?? 0);
$formId = (int)($entry['form_id'] ?? 0);
$entryStatus = $entry['status'] ?? 'complete';
$score = $entry['score'] ?? null;
$aiTemp = $entry['ai_temperature'] ?? null;
$aiAnalysis = isset($entry['ai_analysis']) ? (is_string($entry['ai_analysis']) ? json_decode($entry['ai_analysis'], true) : $entry['ai_analysis']) : null;
?>

<div class="page-header">
    <div class="flex items-center gap-4">
        <a href="/dashboard/entries<?= $formId ? '?form_id=' . $formId : '' ?>" class="btn btn-icon btn-ghost">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 class="page-title">Entrada #<?= $entryId ?></h1>
            <p class="page-subtitle"><?= e($form['title'] ?? '') ?> &middot; <?= format_date($entry['created_at'] ?? '', 'd/m/Y H:i:s') ?></p>
        </div>
    </div>
    <div class="page-actions">
        <!-- Navigation -->
        <div class="flex items-center gap-1">
            <?php if ($prevId): ?>
                <a href="/dashboard/entries/<?= $prevId ?>" class="btn btn-icon btn-ghost" title="Anterior">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
            <?php endif; ?>
            <?php if ($nextId): ?>
                <a href="/dashboard/entries/<?= $nextId ?>" class="btn btn-icon btn-ghost" title="Proxima">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            <?php endif; ?>
        </div>
        <form method="POST" action="/dashboard/entries/<?= $entryId ?>" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-ghost text-danger" onclick="return confirm('Excluir esta entrada permanentemente?')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                Excluir
            </button>
        </form>
    </div>
</div>

<div class="grid grid-3 gap-6">
    <!-- Main Content: Entry Data -->
    <div style="grid-column: span 2;">
        <!-- Status + Score Header -->
        <div class="flex items-center gap-3 mb-6">
            <?php
            $statusBadge = match($entryStatus) {
                'complete' => 'badge-success',
                'partial' => 'badge-warning',
                'abandoned' => 'badge-danger',
                default => 'badge-gray',
            };
            $statusLabel = match($entryStatus) {
                'complete' => 'Completa',
                'partial' => 'Parcial',
                'abandoned' => 'Abandonada',
                default => $entryStatus,
            };
            ?>
            <span class="badge <?= $statusBadge ?>" style="font-size:var(--font-size-sm);padding:6px 14px;"><?= $statusLabel ?></span>

            <?php if ($aiTemp): ?>
                <span class="badge <?= $aiTemp === 'hot' ? 'badge-danger' : ($aiTemp === 'warm' ? 'badge-warning' : 'badge-info') ?>" style="font-size:var(--font-size-sm);padding:6px 14px;">
                    <?php if ($aiTemp === 'hot'): ?>
                        Quente
                    <?php elseif ($aiTemp === 'warm'): ?>
                        Morno
                    <?php else: ?>
                        Frio
                    <?php endif; ?>
                </span>
            <?php endif; ?>

            <?php if ($score !== null): ?>
                <div style="display:flex;align-items:center;gap:var(--space-2);padding:4px 12px;background:var(--gray-100);border-radius:var(--radius-full);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gray-500)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span class="text-sm font-bold">Score: <?= number_format((float)$score, 0) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Field Values Card -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Dados da Resposta</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($fieldValues)): ?>
                    <?php foreach ($fieldValues as $fv): ?>
                        <div style="padding:var(--space-4) 0;border-bottom:1px solid var(--gray-100);">
                            <label class="form-label" style="margin-bottom:var(--space-1);color:var(--gray-500);font-size:var(--font-size-xs);text-transform:uppercase;letter-spacing:0.05em;">
                                <?= e($fv['label'] ?? '') ?>
                                <span class="text-gray-300" style="font-weight:400;text-transform:none;">(<?= e($fv['type'] ?? 'text') ?>)</span>
                            </label>
                            <div class="text-sm" style="color:var(--gray-900);word-break:break-word;">
                                <?php
                                $val = $fv['value'] ?? '';
                                if (is_array($val)) {
                                    echo e(implode(', ', $val));
                                } elseif ($fv['type'] === 'file_upload' && !empty($val)) {
                                    echo '<a href="' . e($val) . '" target="_blank" class="text-primary" style="text-decoration:underline;">Ver arquivo</a>';
                                } elseif ($fv['type'] === 'url' && !empty($val)) {
                                    echo '<a href="' . e($val) . '" target="_blank" class="text-primary" style="text-decoration:underline;">' . e($val) . '</a>';
                                } elseif ($fv['type'] === 'email' && !empty($val)) {
                                    echo '<a href="mailto:' . e($val) . '" class="text-primary">' . e($val) . '</a>';
                                } elseif ($fv['type'] === 'rating' && is_numeric($val)) {
                                    for ($s = 1; $s <= 5; $s++) {
                                        $filled = $s <= (int)$val ? 'var(--warning)' : 'var(--gray-200)';
                                        echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="' . $filled . '" stroke="' . $filled . '" stroke-width="1" style="display:inline;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
                                    }
                                } elseif (trim((string)$val) === '') {
                                    echo '<span class="text-gray-300">(vazio)</span>';
                                } else {
                                    echo nl2br(e((string)$val));
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-400">Nenhum dado disponivel para esta entrada.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- AI Analysis -->
        <?php if (!empty($aiAnalysis)): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:6px;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Analise de IA
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($aiAnalysis['summary'])): ?>
                        <div class="mb-4">
                            <label class="form-label" style="margin-bottom:var(--space-1);">Resumo</label>
                            <p class="text-sm"><?= nl2br(e($aiAnalysis['summary'])) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($aiAnalysis['sentiment'])): ?>
                        <div class="mb-4">
                            <label class="form-label" style="margin-bottom:var(--space-1);">Sentimento</label>
                            <span class="badge badge-primary"><?= e($aiAnalysis['sentiment']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($aiAnalysis['tags'])): ?>
                        <div class="mb-4">
                            <label class="form-label" style="margin-bottom:var(--space-1);">Tags</label>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($aiAnalysis['tags'] as $tag): ?>
                                    <span class="badge badge-gray"><?= e($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($aiAnalysis['insights'])): ?>
                        <div>
                            <label class="form-label" style="margin-bottom:var(--space-1);">Insights</label>
                            <p class="text-sm text-gray-600"><?= nl2br(e($aiAnalysis['insights'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Notes Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notas (<?= count($notes) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($notes)): ?>
                    <?php foreach ($notes as $note): ?>
                        <div style="padding:var(--space-4);border:1px solid var(--gray-100);border-radius:var(--radius-lg);margin-bottom:var(--space-3);">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="avatar-initials" style="width:24px;height:24px;font-size:9px;">
                                    <?= strtoupper(substr(($note['first_name'] ?? $note['email'] ?? 'U'), 0, 1)) ?>
                                </div>
                                <span class="text-sm font-medium"><?= e(trim(($note['first_name'] ?? '') . ' ' . ($note['last_name'] ?? '')) ?: e($note['email'] ?? '')) ?></span>
                                <span class="text-xs text-gray-400"><?= format_date($note['created_at'] ?? '', 'd/m/Y H:i') ?></span>
                            </div>
                            <p class="text-sm" style="color:var(--gray-700);"><?= nl2br(e($note['content'] ?? '')) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Add Note Form -->
                <form method="POST" action="/dashboard/entries/<?= $entryId ?>/notes" style="margin-top:var(--space-4);">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <textarea name="content" class="form-textarea" rows="3" placeholder="Adicionar uma nota sobre esta entrada..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Adicionar Nota
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar: Metadata -->
    <div>
        <!-- Entry Metadata Card -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Metadados</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">IP</label>
                    <p class="text-sm font-mono" style="font-family:var(--font-mono);"><?= e($entry['ip_address'] ?? '-') ?></p>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Dispositivo</label>
                    <p class="text-sm"><?= e($entry['device_type'] ?? '-') ?></p>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Navegador</label>
                    <p class="text-sm"><?= e($entry['browser'] ?? '-') ?></p>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Sistema</label>
                    <p class="text-sm"><?= e($entry['os'] ?? '-') ?></p>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Localizacao</label>
                    <p class="text-sm"><?= e(trim(($entry['city'] ?? '') . ($entry['city'] && $entry['country'] ? ', ' : '') . ($entry['country'] ?? '')) ?: '-') ?></p>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Referencia</label>
                    <p class="text-sm truncate" style="max-width:100%;"><?= e($entry['referrer'] ?? '-') ?></p>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Duracao</label>
                    <p class="text-sm">
                        <?php
                        $duration = (int)($entry['duration_seconds'] ?? 0);
                        if ($duration > 0) {
                            $min = floor($duration / 60);
                            $sec = $duration % 60;
                            echo $min > 0 ? "{$min}m {$sec}s" : "{$sec}s";
                        } else {
                            echo '-';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- UTM Data -->
        <?php if (!empty($entry['utm_source']) || !empty($entry['utm_medium']) || !empty($entry['utm_campaign'])): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Dados UTM</h3>
                </div>
                <div class="card-body">
                    <?php foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $utm): ?>
                        <?php if (!empty($entry[$utm])): ?>
                            <div class="mb-3">
                                <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);"><?= strtoupper(str_replace('utm_', '', $utm)) ?></label>
                                <p class="text-sm"><?= e($entry[$utm]) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Linha do Tempo</h3>
            </div>
            <div class="card-body">
                <div style="position:relative;padding-left:var(--space-6);">
                    <?php if (!empty($entry['started_at'])): ?>
                        <div style="position:relative;padding-bottom:var(--space-4);">
                            <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--primary);"></div>
                            <div style="position:absolute;left:-16px;top:14px;width:2px;height:calc(100% - 14px);background:var(--gray-200);"></div>
                            <div class="text-sm font-medium">Inicio</div>
                            <div class="text-xs text-gray-400"><?= format_date($entry['started_at'], 'd/m/Y H:i:s') ?></div>
                        </div>
                    <?php endif; ?>

                    <div style="position:relative;padding-bottom:var(--space-4);">
                        <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--info);"></div>
                        <div style="position:absolute;left:-16px;top:14px;width:2px;height:calc(100% - 14px);background:var(--gray-200);"></div>
                        <div class="text-sm font-medium">Criada</div>
                        <div class="text-xs text-gray-400"><?= format_date($entry['created_at'] ?? '', 'd/m/Y H:i:s') ?></div>
                    </div>

                    <?php if (!empty($entry['completed_at'])): ?>
                        <div style="position:relative;padding-bottom:var(--space-4);">
                            <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--success);"></div>
                            <div style="position:absolute;left:-16px;top:14px;width:2px;height:calc(100% - 14px);background:var(--gray-200);"></div>
                            <div class="text-sm font-medium">Concluida</div>
                            <div class="text-xs text-gray-400"><?= format_date($entry['completed_at'], 'd/m/Y H:i:s') ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($notes)): ?>
                        <?php foreach ($notes as $note): ?>
                            <div style="position:relative;padding-bottom:var(--space-4);">
                                <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--warning);"></div>
                                <div style="position:absolute;left:-16px;top:14px;width:2px;height:calc(100% - 14px);background:var(--gray-200);"></div>
                                <div class="text-sm font-medium">Nota adicionada</div>
                                <div class="text-xs text-gray-400"><?= format_date($note['created_at'] ?? '', 'd/m/Y H:i:s') ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($entry['updated_at']) && $entry['updated_at'] !== $entry['created_at']): ?>
                        <div style="position:relative;">
                            <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--gray-300);"></div>
                            <div class="text-sm font-medium">Ultima atualizacao</div>
                            <div class="text-xs text-gray-400"><?= format_date($entry['updated_at'], 'd/m/Y H:i:s') ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
