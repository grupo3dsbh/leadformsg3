<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($form['title'] ?? 'Novo Formulario') ?> - Builder | <?= APP_NAME ?></title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="stylesheet" href="/public/css/formbuilder.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body class="builder-body">

<?php
    $formStatus = $form['status'] ?? 'draft';
    $isPublished = ($formStatus === 'published');
?>

<!-- Builder Toolbar -->
<div class="builder-toolbar">
    <div class="builder-toolbar-left">
        <a href="/dashboard/forms" class="builder-toolbar-back" title="Voltar">&#8592;</a>
        <input type="text" class="builder-toolbar-name" id="form-title"
               value="<?= e($form['title'] ?? 'Novo Formulario') ?>"
               placeholder="Nome do formulario">
        <span class="builder-dirty-indicator" id="builder-dirty-indicator" title="Alteracoes nao salvas"></span>
    </div>
    <div class="builder-toolbar-center">
        <span class="builder-field-count" id="builder-field-count">0 campos</span>
        <div class="dropdown">
            <button class="btn btn-sm btn-ghost" data-dropdown>
                Modo: <strong id="form-mode-label"><?= ($form['settings']['mode'] ?? 'conversation') === 'typeform' ? 'Typeform' : 'Conversa' ?></strong> &#9660;
            </button>
            <div class="dropdown-menu">
                <div class="dropdown-item" onclick="setFormMode('conversation')">&#128172; Modo Conversa</div>
                <div class="dropdown-item" onclick="setFormMode('typeform')">&#128196; Modo Typeform</div>
            </div>
        </div>
        <button class="btn btn-sm btn-ghost" onclick="openFormSettings()">&#9881; Configuracoes</button>
    </div>
    <div class="builder-toolbar-right">
        <button class="btn btn-sm btn-ghost" onclick="builder.undo()" title="Ctrl+Z">&#8617;</button>
        <button class="btn btn-sm btn-ghost" onclick="builder.redo()" title="Ctrl+Shift+Z">&#8618;</button>
        <button class="btn btn-sm btn-outline" onclick="builder.preview()">&#128065; Preview</button>
        <button class="btn btn-sm btn-primary" id="builder-save-btn" onclick="builder.save()">&#128190; Salvar</button>
        <button class="btn btn-sm <?= $isPublished ? 'btn-warning' : 'btn-gradient' ?>" id="builder-publish-btn" onclick="togglePublish()">
            <?= $isPublished ? '&#128584; Despublicar' : '&#128640; Publicar' ?>
        </button>
    </div>
</div>

<!-- Builder Layout: field list + canvas + settings -->
<div class="builder-layout">
    <!-- Left: Field Palette (add blocks) -->
    <div class="builder-palette" id="builder-palette"></div>

    <!-- Center: Canvas (field list - conversational cards) -->
    <div class="builder-canvas" id="builder-canvas"></div>

    <!-- Right: Field Settings -->
    <div class="builder-settings" id="builder-settings"></div>
</div>

<!-- Form Settings Modal -->
<div class="modal-backdrop" id="form-settings-modal">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <h3>Configuracoes do Formulario</h3>
            <button data-modal-close style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tabs" style="margin-bottom:20px;">
                <button class="tab active" data-tab="tab-appearance">Aparencia</button>
                <button class="tab" data-tab="tab-behavior">Comportamento</button>
                <button class="tab" data-tab="tab-completion">Conclusao</button>
                <button class="tab" data-tab="tab-access">Acesso</button>
                <button class="tab" data-tab="tab-seo">SEO</button>
            </div>

            <div class="tab-panel active" id="tab-appearance">
                <div class="form-group">
                    <label class="form-label">Cor Primaria</label>
                    <input type="color" class="form-input" id="setting-primary-color"
                           value="<?= e($form['settings']['theme']['primary'] ?? '#4F46E5') ?>"
                           style="height:44px;padding:4px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Background</label>
                    <select class="form-select" id="setting-bg-type">
                        <option value="gradient" <?= ($form['settings']['background']['type'] ?? 'gradient') === 'gradient' ? 'selected' : '' ?>>Gradiente</option>
                        <option value="color" <?= ($form['settings']['background']['type'] ?? '') === 'color' ? 'selected' : '' ?>>Cor Solida</option>
                        <option value="image" <?= ($form['settings']['background']['type'] ?? '') === 'image' ? 'selected' : '' ?>>Imagem</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Valor do Background</label>
                    <input type="text" class="form-input" id="setting-bg-value"
                           value="<?= e($form['settings']['background']['value'] ?? 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Fonte</label>
                    <select class="form-select" id="setting-font">
                        <option value="'Inter', sans-serif">Inter</option>
                        <option value="'Poppins', sans-serif">Poppins</option>
                        <option value="'Roboto', sans-serif">Roboto</option>
                    </select>
                </div>
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <label class="form-label" style="margin:0;">Barra de Progresso</label>
                        <label class="form-toggle"><input type="checkbox" id="setting-show-progress" <?= ($form['settings']['showProgress'] ?? true) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">CSS Personalizado</label>
                    <textarea class="form-textarea" id="setting-custom-css" rows="3"><?= e($form['settings']['custom_css'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="tab-panel" id="tab-behavior" style="display:none;">
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <label class="form-label" style="margin:0;">Salvar e Continuar Depois</label>
                        <label class="form-toggle"><input type="checkbox" id="setting-save-continue" <?= ($form['settings']['saveAndContinue'] ?? false) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <label class="form-label" style="margin:0;">Animacoes</label>
                        <label class="form-toggle"><input type="checkbox" id="setting-animations" <?= ($form['settings']['animations'] ?? true) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    </div>
                </div>
            </div>

            <div class="tab-panel" id="tab-completion" style="display:none;">
                <div class="form-group">
                    <label class="form-label">Acao apos conclusao</label>
                    <select class="form-select" id="setting-completion-type">
                        <option value="thankyou" <?= ($form['settings']['completion']['type'] ?? 'thankyou') === 'thankyou' ? 'selected' : '' ?>>Pagina de Obrigado</option>
                        <option value="redirect" <?= ($form['settings']['completion']['type'] ?? '') === 'redirect' ? 'selected' : '' ?>>Redirecionamento</option>
                    </select>
                </div>
                <div class="form-group" id="completion-thankyou-group">
                    <label class="form-label">Titulo</label>
                    <input type="text" class="form-input" id="setting-completion-title" value="<?= e($form['settings']['completion']['title'] ?? 'Obrigado!') ?>">
                </div>
                <div class="form-group" id="completion-message-group">
                    <label class="form-label">Mensagem</label>
                    <textarea class="form-textarea" id="setting-completion-message" rows="3"><?= e($form['settings']['completion']['message'] ?? 'Suas informacoes foram enviadas com sucesso.') ?></textarea>
                </div>
                <div class="form-group" id="completion-redirect-group" style="display:none;">
                    <label class="form-label">URL de Redirecionamento</label>
                    <input type="url" class="form-input" id="setting-completion-redirect" value="<?= e($form['settings']['completion']['redirectUrl'] ?? '') ?>">
                </div>
            </div>

            <div class="tab-panel" id="tab-access" style="display:none;">
                <div class="form-group">
                    <label class="form-label">Link personalizado</label>
                    <div style="display:flex;gap:8px;">
                        <span class="form-input" style="flex:0 0 auto;background:var(--gray-100);width:auto;padding:10px 12px;white-space:nowrap;"><?= APP_URL ?>/f/</span>
                        <input type="text" class="form-input" id="setting-slug" value="<?= e($form['slug'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Data de Expiracao</label>
                    <input type="datetime-local" class="form-input" id="setting-expiry" value="<?= e($form['settings']['expiry'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Senha de Acesso</label>
                    <input type="text" class="form-input" id="setting-password" value="<?= e($form['settings']['password'] ?? '') ?>" placeholder="Sem senha">
                </div>
                <div class="form-group">
                    <label class="form-label">Maximo de Envios</label>
                    <input type="number" class="form-input" id="setting-max-submissions" value="<?= e($form['settings']['max_submissions'] ?? '') ?>" placeholder="Ilimitado" min="1">
                </div>
            </div>

            <div class="tab-panel" id="tab-seo" style="display:none;">
                <div class="form-group">
                    <label class="form-label">Titulo SEO</label>
                    <input type="text" class="form-input" id="setting-seo-title" value="<?= e($form['settings']['seo_title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Descricao SEO</label>
                    <textarea class="form-textarea" id="setting-seo-description" rows="2"><?= e($form['settings']['seo_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" data-modal-close>Cancelar</button>
            <button class="btn btn-primary" onclick="saveFormSettings()">Salvar</button>
        </div>
    </div>
</div>

<script src="/public/js/app.js"></script>
<script src="/public/js/formbuilder.js"></script>

<script>
    const formData = <?= json_encode([
        'formId' => $form['id'] ?? null,
        'slug' => $form['slug'] ?? '',
        'title' => $form['title'] ?? 'Novo Formulario',
        'status' => $form['status'] ?? 'draft',
        'fields' => $fields ?? [],
        'settings' => $form['settings'] ?? [],
    ], JSON_UNESCAPED_UNICODE) ?>;

    const builder = new LeadFormBuilder({
        formId: formData.formId,
        fields: formData.fields || [],
        formSettings: formData.settings || {},
        saveUrl: '/dashboard/forms/ajax-save',
        previewUrl: formData.formId ? `/dashboard/forms/${formData.formId}/preview` : null,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || ''
    });

    // Track current publish state
    let isPublished = formData.status === 'published';

    function setFormMode(mode) {
        builder.options.formSettings.mode = mode;
        document.getElementById('form-mode-label').textContent = mode === 'typeform' ? 'Typeform' : 'Conversa';
        builder._markDirty();
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
    }

    async function togglePublish() {
        const btn = document.getElementById('builder-publish-btn');
        btn.disabled = true;

        // Save first
        await builder.save();

        try {
            const newPublish = !isPublished;
            const response = await fetch(builder.options.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': builder.options.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    form_id: builder.options.formId,
                    set_status: newPublish ? 'published' : 'draft',
                    _token: builder.options.csrfToken
                })
            });

            const result = await response.json();
            if (response.ok && result.success) {
                isPublished = newPublish;
                btn.className = 'btn btn-sm ' + (isPublished ? 'btn-warning' : 'btn-gradient');
                btn.innerHTML = isPublished ? '&#128584; Despublicar' : '&#128640; Publicar';
                builder._showToast(isPublished ? 'Formulario publicado!' : 'Formulario despublicado.');
            } else {
                throw new Error(result.error || 'Erro');
            }
        } catch (error) {
            builder._showToast(error.message || 'Erro ao alterar status', 'error');
        }

        btn.disabled = false;
    }

    function openFormSettings() {
        LeadFormApp.openModal('form-settings-modal');
    }

    function saveFormSettings() {
        const s = builder.options.formSettings;
        s.theme = s.theme || {};
        s.theme.primary = document.getElementById('setting-primary-color')?.value;
        s.background = {
            type: document.getElementById('setting-bg-type')?.value,
            value: document.getElementById('setting-bg-value')?.value
        };
        s.theme.fontFamily = document.getElementById('setting-font')?.value;
        s.showProgress = document.getElementById('setting-show-progress')?.checked;
        s.custom_css = document.getElementById('setting-custom-css')?.value;
        s.saveAndContinue = document.getElementById('setting-save-continue')?.checked;
        s.animations = document.getElementById('setting-animations')?.checked;
        s.completion = {
            type: document.getElementById('setting-completion-type')?.value,
            title: document.getElementById('setting-completion-title')?.value,
            message: document.getElementById('setting-completion-message')?.value,
            redirectUrl: document.getElementById('setting-completion-redirect')?.value,
        };
        s.expiry = document.getElementById('setting-expiry')?.value;
        s.password = document.getElementById('setting-password')?.value;
        s.max_submissions = document.getElementById('setting-max-submissions')?.value;
        s.seo_title = document.getElementById('setting-seo-title')?.value;
        s.seo_description = document.getElementById('setting-seo-description')?.value;
        builder._markDirty();
        LeadFormApp.closeModal('form-settings-modal');
        LeadFormApp.toast('Configuracoes salvas!');
    }

    // Tab switching
    document.querySelectorAll('#form-settings-modal .tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('#form-settings-modal .tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('#form-settings-modal .tab-panel').forEach(p => p.style.display = 'none');
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).style.display = '';
        });
    });

    document.getElementById('setting-completion-type')?.addEventListener('change', function() {
        document.getElementById('completion-redirect-group').style.display = this.value === 'redirect' ? '' : 'none';
        document.getElementById('completion-thankyou-group').style.display = this.value !== 'redirect' ? '' : 'none';
        document.getElementById('completion-message-group').style.display = this.value !== 'redirect' ? '' : 'none';
    });

    window.addEventListener('beforeunload', (e) => {
        if (builder.isDirty) { e.preventDefault(); e.returnValue = ''; }
    });
</script>

<?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
<div class="dev-mode-indicator">DEV</div>
<?php endif; ?>

</body>
</html>
