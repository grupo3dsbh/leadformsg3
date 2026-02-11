<?php
/**
 * LeadForm SaaS - Form Preview (Client Dashboard)
 *
 * Renders a form preview inside an iframe or embedded view.
 *
 * Variables:
 *   $form     - The form record
 *   $fields   - Array of form fields
 *   $settings - Decoded form settings
 */

$form     = $form ?? [];
$fields   = $fields ?? [];
$settings = $settings ?? [];
$formId   = $form['id'] ?? 0;
$slug     = $form['slug'] ?? '';
$title    = htmlspecialchars($form['title'] ?? 'Preview');
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Preview: <?= $title ?></h1>
        <p class="text-muted">Visualize como o formulario aparece para os respondentes</p>
    </div>
    <div class="page-header-right">
        <a href="/dashboard/forms/<?= (int)$formId ?>/edit" class="btn btn-outline">Voltar ao Editor</a>
        <?php if ($slug): ?>
            <a href="/f/<?= htmlspecialchars($slug) ?>" target="_blank" class="btn btn-primary">Abrir Link Publico</a>
        <?php endif; ?>
    </div>
</div>

<div style="background:#f3f4f6;border-radius:12px;padding:24px;margin-top:16px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <button class="btn btn-sm btn-outline preview-device active" data-width="100%" onclick="setPreviewWidth(this, '100%')">Desktop</button>
        <button class="btn btn-sm btn-outline preview-device" data-width="768px" onclick="setPreviewWidth(this, '768px')">Tablet</button>
        <button class="btn btn-sm btn-outline preview-device" data-width="375px" onclick="setPreviewWidth(this, '375px')">Mobile</button>
    </div>

    <div id="preview-frame-container" style="margin:0 auto;transition:max-width 0.3s ease;max-width:100%;">
        <iframe
            id="preview-iframe"
            src="/f/<?= htmlspecialchars($slug) ?>?preview=1"
            style="width:100%;min-height:600px;border:none;border-radius:8px;background:#fff;"
            sandbox="allow-scripts allow-same-origin allow-forms"
        ></iframe>
    </div>
</div>

<script>
function setPreviewWidth(btn, width) {
    document.querySelectorAll('.preview-device').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('preview-frame-container').style.maxWidth = width;
}
</script>
