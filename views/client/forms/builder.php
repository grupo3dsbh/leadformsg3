<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($form['title'] ?? 'Novo Formulário') ?> - Form Builder | <?= APP_NAME ?></title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="stylesheet" href="/public/css/formbuilder.css">

    <!-- SortableJS for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body style="overflow:hidden;height:100vh;display:flex;flex-direction:column;">

    <!-- Builder Toolbar -->
    <div class="builder-toolbar">
        <div class="builder-toolbar-left">
            <a href="/dashboard/forms" class="builder-toolbar-back" title="Voltar">&#8592;</a>
            <input type="text" class="builder-toolbar-name" id="form-title"
                   value="<?= e($form['title'] ?? 'Novo Formulário') ?>"
                   placeholder="Nome do formulário">
            <span class="builder-dirty-indicator" id="builder-dirty-indicator" title="Alterações não salvas"></span>
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
        </div>
        <div class="builder-toolbar-right">
            <button class="btn btn-sm btn-ghost" onclick="builder.undo()" title="Ctrl+Z">&#8617; Desfazer</button>
            <button class="btn btn-sm btn-ghost" onclick="builder.redo()" title="Ctrl+Shift+Z">&#8618; Refazer</button>
            <button class="btn btn-sm btn-outline" onclick="builder.preview()" title="Preview">&#128065; Preview</button>
            <button class="btn btn-sm btn-primary" id="builder-save-btn" onclick="builder.save()">&#128190; Salvar</button>
            <button class="btn btn-sm btn-gradient" onclick="builder.publish()">&#128640; Publicar</button>
        </div>
    </div>

    <!-- Builder Layout: 3 panels -->
    <div class="builder-layout">
        <!-- Left: Field Palette -->
        <div class="builder-palette" id="builder-palette">
            <!-- Populated by formbuilder.js -->
        </div>

        <!-- Center: Form Canvas -->
        <div class="builder-canvas" id="builder-canvas">
            <!-- Populated by formbuilder.js -->
        </div>

        <!-- Right: Field Settings -->
        <div class="builder-settings" id="builder-settings">
            <!-- Populated by formbuilder.js -->
        </div>
    </div>

    <!-- Form Settings Modal -->
    <div class="modal-backdrop" id="form-settings-modal">
        <div class="modal" style="max-width:700px;">
            <div class="modal-header">
                <h3>Configurações do Formulário</h3>
                <button data-modal-close style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="tabs" style="margin-bottom:20px;">
                    <button class="tab active" data-tab="tab-appearance">Aparência</button>
                    <button class="tab" data-tab="tab-behavior">Comportamento</button>
                    <button class="tab" data-tab="tab-completion">Conclusão</button>
                    <button class="tab" data-tab="tab-access">Acesso</button>
                    <button class="tab" data-tab="tab-seo">SEO</button>
                </div>

                <!-- Appearance Tab -->
                <div class="tab-panel active" id="tab-appearance">
                    <div class="form-group">
                        <label class="form-label">Cor Primária</label>
                        <input type="color" class="form-input" id="setting-primary-color"
                               value="<?= e($form['settings']['theme']['primary'] ?? '#4F46E5') ?>"
                               style="height:44px;padding:4px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Background</label>
                        <select class="form-select" id="setting-bg-type">
                            <option value="gradient" <?= ($form['settings']['background']['type'] ?? 'gradient') === 'gradient' ? 'selected' : '' ?>>Gradiente</option>
                            <option value="color" <?= ($form['settings']['background']['type'] ?? '') === 'color' ? 'selected' : '' ?>>Cor Sólida</option>
                            <option value="image" <?= ($form['settings']['background']['type'] ?? '') === 'image' ? 'selected' : '' ?>>Imagem</option>
                            <option value="video" <?= ($form['settings']['background']['type'] ?? '') === 'video' ? 'selected' : '' ?>>Vídeo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor do Background</label>
                        <input type="text" class="form-input" id="setting-bg-value"
                               value="<?= e($form['settings']['background']['value'] ?? 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)') ?>"
                               placeholder="URL ou CSS value">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fonte</label>
                        <select class="form-select" id="setting-font">
                            <option value="'Inter', sans-serif">Inter</option>
                            <option value="'Poppins', sans-serif">Poppins</option>
                            <option value="'Roboto', sans-serif">Roboto</option>
                            <option value="'Montserrat', sans-serif">Montserrat</option>
                            <option value="'Open Sans', sans-serif">Open Sans</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" style="margin:0;">Mostrar Logo</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="setting-show-branding" <?= ($form['settings']['showBranding'] ?? true) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" style="margin:0;">Barra de Progresso</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="setting-show-progress" <?= ($form['settings']['showProgress'] ?? true) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CSS Personalizado</label>
                        <textarea class="form-textarea" id="setting-custom-css" rows="4" placeholder=".leadform { ... }"><?= e($form['settings']['custom_css'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Behavior Tab -->
                <div class="tab-panel" id="tab-behavior" style="display:none;">
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" style="margin:0;">Salvar e Continuar Depois</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="setting-save-continue" <?= ($form['settings']['saveAndContinue'] ?? false) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" style="margin:0;">Step Form (Salvar Parcial Automático)</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="setting-step-form" <?= ($form['settings']['step_form'] ?? false) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <span class="form-hint">Salva automaticamente quando o usuário preencher até determinado campo</span>
                    </div>
                    <div class="form-group" id="step-form-field-group" style="<?= ($form['settings']['step_form'] ?? false) ? '' : 'display:none;' ?>">
                        <label class="form-label">Salvar ao chegar no campo</label>
                        <select class="form-select" id="setting-auto-save-field">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" style="margin:0;">Link Único</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="setting-unique-link" <?= ($form['settings']['unique_link'] ?? false) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <span class="form-hint">O link funciona apenas para quem abriu primeiro</span>
                    </div>
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" style="margin:0;">Animações</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="setting-animations" <?= ($form['settings']['animations'] ?? true) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" style="margin:0;">"Powered By" visível</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="setting-powered-by" <?= ($form['settings']['poweredBy'] ?? true) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Completion Tab -->
                <div class="tab-panel" id="tab-completion" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Ação após conclusão</label>
                        <select class="form-select" id="setting-completion-type">
                            <option value="thankyou" <?= ($form['settings']['completion']['type'] ?? 'thankyou') === 'thankyou' ? 'selected' : '' ?>>Página de Obrigado</option>
                            <option value="redirect" <?= ($form['settings']['completion']['type'] ?? '') === 'redirect' ? 'selected' : '' ?>>Redirecionamento</option>
                            <option value="bonus" <?= ($form['settings']['completion']['type'] ?? '') === 'bonus' ? 'selected' : '' ?>>Bônus / Conteúdo</option>
                            <option value="subform" <?= ($form['settings']['completion']['type'] ?? '') === 'subform' ? 'selected' : '' ?>>Sub-formulário</option>
                        </select>
                    </div>
                    <div class="form-group" id="completion-thankyou-group">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-input" id="setting-completion-title"
                               value="<?= e($form['settings']['completion']['title'] ?? 'Obrigado!') ?>">
                    </div>
                    <div class="form-group" id="completion-message-group">
                        <label class="form-label">Mensagem</label>
                        <textarea class="form-textarea" id="setting-completion-message" rows="3"><?= e($form['settings']['completion']['message'] ?? 'Suas informações foram enviadas com sucesso.') ?></textarea>
                    </div>
                    <div class="form-group" id="completion-redirect-group" style="display:none;">
                        <label class="form-label">URL de Redirecionamento</label>
                        <input type="url" class="form-input" id="setting-completion-redirect"
                               value="<?= e($form['settings']['completion']['redirectUrl'] ?? '') ?>"
                               placeholder="https://example.com/obrigado">
                    </div>
                    <div class="form-group" id="completion-subform-group" style="display:none;">
                        <label class="form-label">Slug do Sub-formulário</label>
                        <input type="text" class="form-input" id="setting-completion-subform"
                               value="<?= e($form['settings']['completion']['subformSlug'] ?? '') ?>"
                               placeholder="slug-do-formulario">
                        <span class="form-hint">Os dados já preenchidos serão passados automaticamente</span>
                    </div>
                </div>

                <!-- Access Tab -->
                <div class="tab-panel" id="tab-access" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Link personalizado</label>
                        <div style="display:flex;gap:8px;">
                            <span class="form-input" style="flex:0 0 auto;background:var(--gray-100);width:auto;padding:10px 12px;white-space:nowrap;"><?= APP_URL ?>/f/</span>
                            <input type="text" class="form-input" id="setting-slug"
                                   value="<?= e($form['slug'] ?? '') ?>" placeholder="meu-formulario">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Expiração</label>
                        <input type="datetime-local" class="form-input" id="setting-expiry"
                               value="<?= e($form['settings']['expiry'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Senha de Acesso</label>
                        <input type="text" class="form-input" id="setting-password"
                               value="<?= e($form['settings']['password'] ?? '') ?>" placeholder="Deixe vazio para sem senha">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Máximo de Envios</label>
                        <input type="number" class="form-input" id="setting-max-submissions"
                               value="<?= e($form['settings']['max_submissions'] ?? '') ?>" placeholder="Ilimitado" min="1">
                    </div>
                </div>

                <!-- SEO Tab -->
                <div class="tab-panel" id="tab-seo" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Título SEO</label>
                        <input type="text" class="form-input" id="setting-seo-title"
                               value="<?= e($form['settings']['seo_title'] ?? '') ?>" placeholder="Título para mecanismos de busca">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrição SEO</label>
                        <textarea class="form-textarea" id="setting-seo-description" rows="2"
                                  placeholder="Descrição para mecanismos de busca"><?= e($form['settings']['seo_description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Imagem SEO (URL)</label>
                        <input type="url" class="form-input" id="setting-seo-image"
                               value="<?= e($form['settings']['seo_image'] ?? '') ?>" placeholder="https://...">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" data-modal-close>Cancelar</button>
                <button class="btn btn-primary" onclick="saveFormSettings()">Salvar Configurações</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/public/js/app.js"></script>
    <script src="/public/js/formbuilder.js"></script>

    <script>
        // Form data from PHP
        const formData = <?= json_encode([
            'formId' => $form['id'] ?? null,
            'slug' => $form['slug'] ?? '',
            'title' => $form['title'] ?? 'Novo Formulário',
            'fields' => $fields ?? [],
            'settings' => $form['settings'] ?? [],
        ], JSON_UNESCAPED_UNICODE) ?>;

        // Initialize builder
        const builder = new LeadFormBuilder({
            formId: formData.formId,
            fields: formData.fields || [],
            formSettings: formData.settings || {},
            saveUrl: '/dashboard/forms/ajax-save',
            previewUrl: formData.formId ? `/dashboard/forms/${formData.formId}/preview` : null,
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || ''
        });

        // Form mode switcher
        function setFormMode(mode) {
            builder.options.formSettings.mode = mode;
            document.getElementById('form-mode-label').textContent = mode === 'typeform' ? 'Typeform' : 'Conversa';
            builder._markDirty();
            // Close dropdown
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        }

        // Form settings modal
        function openFormSettings() {
            LeadFormApp.openModal('form-settings-modal');
        }

        function saveFormSettings() {
            const settings = builder.options.formSettings;

            settings.theme = settings.theme || {};
            settings.theme.primary = document.getElementById('setting-primary-color')?.value;
            settings.background = {
                type: document.getElementById('setting-bg-type')?.value,
                value: document.getElementById('setting-bg-value')?.value
            };
            settings.theme.fontFamily = document.getElementById('setting-font')?.value;
            settings.showBranding = document.getElementById('setting-show-branding')?.checked;
            settings.showProgress = document.getElementById('setting-show-progress')?.checked;
            settings.custom_css = document.getElementById('setting-custom-css')?.value;
            settings.saveAndContinue = document.getElementById('setting-save-continue')?.checked;
            settings.step_form = document.getElementById('setting-step-form')?.checked;
            settings.unique_link = document.getElementById('setting-unique-link')?.checked;
            settings.animations = document.getElementById('setting-animations')?.checked;
            settings.poweredBy = document.getElementById('setting-powered-by')?.checked;

            settings.completion = {
                type: document.getElementById('setting-completion-type')?.value,
                title: document.getElementById('setting-completion-title')?.value,
                message: document.getElementById('setting-completion-message')?.value,
                redirectUrl: document.getElementById('setting-completion-redirect')?.value,
                subformSlug: document.getElementById('setting-completion-subform')?.value
            };

            settings.expiry = document.getElementById('setting-expiry')?.value;
            settings.password = document.getElementById('setting-password')?.value;
            settings.max_submissions = document.getElementById('setting-max-submissions')?.value;
            settings.seo_title = document.getElementById('setting-seo-title')?.value;
            settings.seo_description = document.getElementById('setting-seo-description')?.value;
            settings.seo_image = document.getElementById('setting-seo-image')?.value;

            builder._markDirty();
            LeadFormApp.closeModal('form-settings-modal');
            LeadFormApp.toast('Configurações atualizadas!');
        }

        // Tab switching in modal
        document.querySelectorAll('#form-settings-modal .tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('#form-settings-modal .tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('#form-settings-modal .tab-panel').forEach(p => p.style.display = 'none');
                tab.classList.add('active');
                const panel = document.getElementById(tab.dataset.tab);
                if (panel) panel.style.display = '';
            });
        });

        // Completion type switching
        document.getElementById('setting-completion-type')?.addEventListener('change', function() {
            document.getElementById('completion-redirect-group').style.display = this.value === 'redirect' ? '' : 'none';
            document.getElementById('completion-subform-group').style.display = this.value === 'subform' ? '' : 'none';
            document.getElementById('completion-thankyou-group').style.display = ['thankyou', 'bonus'].includes(this.value) ? '' : 'none';
            document.getElementById('completion-message-group').style.display = ['thankyou', 'bonus'].includes(this.value) ? '' : 'none';
        });

        // Step form toggle
        document.getElementById('setting-step-form')?.addEventListener('change', function() {
            document.getElementById('step-form-field-group').style.display = this.checked ? '' : 'none';
        });

        // Settings button in toolbar
        document.querySelector('.builder-toolbar-center')?.insertAdjacentHTML('beforeend',
            `<button class="btn btn-sm btn-ghost" onclick="openFormSettings()" style="margin-left:8px;">&#9881; Configurações</button>`
        );

        // Unsaved changes warning
        window.addEventListener('beforeunload', (e) => {
            if (builder.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>

    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <div class="dev-mode-indicator">DEV</div>
    <?php endif; ?>

</body>
</html>
