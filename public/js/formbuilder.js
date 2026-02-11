/**
 * LeadForm SaaS - Form Builder
 * Drag & Drop visual form builder with flow connections
 * Uses SortableJS for drag and drop
 */

class LeadFormBuilder {
    constructor(options = {}) {
        this.options = {
            canvasId: 'builder-canvas',
            paletteId: 'builder-palette',
            settingsId: 'builder-settings',
            formId: options.formId || null,
            saveUrl: options.saveUrl || '/dashboard/forms/save',
            previewUrl: options.previewUrl || '/dashboard/forms/preview',
            csrfToken: options.csrfToken || '',
            fields: options.fields || [],
            formSettings: options.formSettings || {},
            ...options
        };

        // State
        this.fields = [...this.options.fields];
        this.selectedFieldIndex = -1;
        this.isDirty = false;
        this.undoStack = [];
        this.redoStack = [];
        this.fieldCounter = this.fields.length;

        // Field type definitions
        this.fieldTypes = {
            // Padrão
            name: { label: 'Nome', icon: '&#128100;', category: 'basic', defaultSettings: { name_format: 'first_last', required: true } },
            email: { label: 'Email', icon: '&#9993;', category: 'basic', defaultSettings: { required: true } },
            phone: { label: 'WhatsApp/Telefone', icon: '&#128222;', category: 'basic', defaultSettings: { country_code: '+55', required: true } },
            company: { label: 'Empresa', icon: '&#127970;', category: 'basic', defaultSettings: {} },
            address: { label: 'Endereço', icon: '&#128205;', category: 'basic', defaultSettings: { address_autocomplete: true } },
            cpf_cnpj: { label: 'CPF/CNPJ', icon: '&#128196;', category: 'basic', defaultSettings: { required: true } },

            // Campos
            text: { label: 'Texto', icon: '&#9999;', category: 'fields', defaultSettings: {} },
            textarea: { label: 'Texto Longo', icon: '&#128221;', category: 'fields', defaultSettings: {} },
            radio: { label: 'Escolha Única', icon: '&#9899;', category: 'fields', defaultSettings: { options: ['Opção 1', 'Opção 2', 'Opção 3'] } },
            select: { label: 'Seleção', icon: '&#128203;', category: 'fields', defaultSettings: { options: ['Opção 1', 'Opção 2', 'Opção 3'] } },
            checkbox: { label: 'Múltipla Escolha', icon: '&#9745;', category: 'fields', defaultSettings: { options: ['Opção 1', 'Opção 2', 'Opção 3'] } },
            password: { label: 'Senha', icon: '&#128274;', category: 'fields', defaultSettings: {} },
            url: { label: 'URL', icon: '&#128279;', category: 'fields', defaultSettings: {} },

            // Avançado
            social_media: { label: 'Redes Sociais', icon: '&#127760;', category: 'advanced', defaultSettings: { social_type: 'instagram', options: ['facebook', 'instagram', 'whatsapp', 'linkedin', 'youtube', 'tiktok'] } },
            username: { label: 'Username', icon: '&#128101;', category: 'advanced', defaultSettings: {} },
            signature: { label: 'Assinatura', icon: '&#9997;', category: 'advanced', defaultSettings: {} },
            datepicker: { label: 'Data', icon: '&#128197;', category: 'advanced', defaultSettings: {} },
            file_upload: { label: 'Upload', icon: '&#128206;', category: 'advanced', defaultSettings: { file_max_size: 10, file_allowed_types: 'jpg,jpeg,png,pdf,doc,docx' } },
            opinion_scale: { label: 'Escala de Opinião', icon: '&#128200;', category: 'advanced', defaultSettings: { opinion_min: 1, opinion_max: 10 } },
            rating: { label: 'Avaliação', icon: '&#11088;', category: 'advanced', defaultSettings: { rating_max: 5, rating_icon: 'star' } },
            picture_choice: { label: 'Escolha com Imagem', icon: '&#128247;', category: 'advanced', defaultSettings: { options: [] } },
            hidden: { label: 'Campo Oculto', icon: '&#128065;', category: 'advanced', defaultSettings: {} },

            // Layout
            paragraph: { label: 'Parágrafo', icon: '&#182;', category: 'layout', defaultSettings: { content: 'Texto informativo...' }, isLayout: true },
            divider: { label: 'Divisor', icon: '&#8213;', category: 'layout', defaultSettings: {}, isLayout: true },
            section_break: { label: 'Quebra de Seção', icon: '&#128195;', category: 'layout', defaultSettings: { section_title: 'Nova Seção' }, isLayout: true }
        };

        this.categories = {
            basic: { label: 'Padrão', icon: '&#11088;' },
            fields: { label: 'Campos', icon: '&#9999;' },
            advanced: { label: 'Avançado', icon: '&#9881;' },
            layout: { label: 'Layout', icon: '&#128200;' }
        };

        this._init();
    }

    // ==========================================
    // INITIALIZATION
    // ==========================================

    _init() {
        this._renderPalette();
        this._setupCanvasDropZone();
        this._renderCanvas();
        this._renderSettings();
        this._setupAutoSave();
        this._setupKeyboardShortcuts();
        this._updateFieldCount();

        // Track title changes
        const titleEl = document.getElementById('form-title');
        if (titleEl) {
            titleEl.addEventListener('input', () => this._markDirty());
        }
    }

    // ==========================================
    // PALETTE (Left Panel)
    // ==========================================

    _renderPalette() {
        const palette = document.getElementById(this.options.paletteId);
        if (!palette) return;

        let html = '<div class="palette-search"><input type="text" id="palette-search" placeholder="Buscar bloco..." class="form-input"></div>';
        html += '<div class="palette-scroll">';

        Object.entries(this.categories).forEach(([catKey, cat]) => {
            const fields = Object.entries(this.fieldTypes).filter(([, f]) => f.category === catKey);

            html += `
                <div class="palette-category" data-category="${catKey}">
                    <div class="palette-category-header" onclick="builder.toggleCategory('${catKey}')">
                        <span>${cat.icon} ${cat.label}</span>
                        <span class="palette-toggle">&#9660;</span>
                    </div>
                    <div class="palette-items" id="palette-${catKey}">
                        ${fields.map(([type, field]) => `
                            <div class="palette-item" data-type="${type}" draggable="true">
                                <span class="palette-item-icon">${field.icon}</span>
                                <span class="palette-item-label">${field.label}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        });

        html += '</div>'; // close palette-scroll

        palette.innerHTML = html;

        // Search filter
        document.getElementById('palette-search')?.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            palette.querySelectorAll('.palette-item').forEach(item => {
                const label = item.querySelector('.palette-item-label').textContent.toLowerCase();
                item.style.display = label.includes(query) ? '' : 'none';
            });
        });

        // Make palette items draggable
        palette.querySelectorAll('.palette-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', item.dataset.type);
                e.dataTransfer.effectAllowed = 'copy';
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });

            // Double click to add
            item.addEventListener('dblclick', () => {
                this.addField(item.dataset.type);
            });
        });
    }

    toggleCategory(catKey) {
        const items = document.getElementById(`palette-${catKey}`);
        if (items) {
            items.classList.toggle('collapsed');
            const header = items.previousElementSibling;
            const toggle = header?.querySelector('.palette-toggle');
            if (toggle) {
                toggle.innerHTML = items.classList.contains('collapsed') ? '&#9654;' : '&#9660;';
            }
        }
    }

    // ==========================================
    // CANVAS (Center)
    // ==========================================

    _setupCanvasDropZone() {
        const canvas = document.getElementById(this.options.canvasId);
        if (!canvas) return;

        canvas.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            canvas.classList.add('canvas-dragover');
        });

        canvas.addEventListener('dragleave', () => {
            canvas.classList.remove('canvas-dragover');
        });

        canvas.addEventListener('drop', (e) => {
            e.preventDefault();
            canvas.classList.remove('canvas-dragover');
            const type = e.dataTransfer.getData('text/plain');
            if (type && this.fieldTypes[type]) {
                this.addField(type);
            }
        });
    }

    _renderCanvas() {
        const canvas = document.getElementById(this.options.canvasId);
        if (!canvas) return;

        if (this.fields.length === 0) {
            canvas.innerHTML = `
                <div class="canvas-empty" id="canvas-empty">
                    <div class="canvas-empty-icon">&#128466;</div>
                    <h3>Arraste os blocos aqui para começar</h3>
                    <p>Escolha os campos na barra lateral e arraste para esta área</p>
                </div>
            `;
        } else {
            canvas.innerHTML = `<div class="canvas-fields" id="canvas-fields"></div>`;
            this._renderCanvasFields();
        }
    }

    _renderCanvasFields() {
        const container = document.getElementById('canvas-fields');
        if (!container) return;

        // Conversational card style: drag handle | icon | label + required star | actions
        container.innerHTML = this.fields.map((field, index) => {
            const typeDef = this.fieldTypes[field.type] || {};
            const isSelected = index === this.selectedFieldIndex;

            return `
                <div class="canvas-field ${isSelected ? 'selected' : ''}"
                     data-index="${index}" data-field-id="${field.id}"
                     onclick="builder.selectField(${index})">
                    <div class="canvas-field-drag" title="Arrastar para reordenar">&#9776;</div>
                    <span class="canvas-field-icon">${typeDef.icon || '&#9881;'}</span>
                    <div class="canvas-field-info">
                        <span class="canvas-field-label">${this._escapeHtml(field.label || 'Sem titulo')}</span>
                        ${field.required ? '<span class="canvas-field-required">*</span>' : ''}
                        ${field.settings?.conditional_logic ? '<span class="canvas-field-logic-badge" title="Tem logica condicional">&#9889;</span>' : ''}
                    </div>
                    <div class="canvas-field-actions">
                        <button class="btn-icon btn-sm btn-ghost" onclick="event.stopPropagation();builder.openJumpLogic(${index})" title="Logica de salto">&#9889;</button>
                        <button class="btn-icon btn-sm btn-ghost" onclick="event.stopPropagation();builder.duplicateField(${index})" title="Duplicar">&#128203;</button>
                        <button class="btn-icon btn-sm btn-ghost" onclick="event.stopPropagation();builder.removeField(${index})" title="Remover">&#128465;</button>
                    </div>
                </div>
            `;
        }).join('');

        // Setup sortable for reordering
        if (window.Sortable) {
            new Sortable(container, {
                handle: '.canvas-field-drag',
                animation: 250,
                ghostClass: 'canvas-field-ghost',
                chosenClass: 'canvas-field-chosen',
                onEnd: (evt) => {
                    const field = this.fields.splice(evt.oldIndex, 1)[0];
                    this.fields.splice(evt.newIndex, 0, field);
                    this._markDirty();
                    if (this.selectedFieldIndex === evt.oldIndex) {
                        this.selectedFieldIndex = evt.newIndex;
                    }
                    this._renderCanvasFields();
                }
            });
        }
    }

    _getFieldPreview(field) {
        switch (field.type) {
            case 'text':
            case 'email':
            case 'url':
            case 'password':
            case 'name':
            case 'company':
            case 'username':
            case 'cpf_cnpj':
                return `<div class="preview-input">${this._escapeHtml(field.placeholder || 'Digite aqui...')}</div>`;
            case 'phone':
                return `<div class="preview-input"><span class="preview-flag">&#127463;&#127479; +55</span> ${this._escapeHtml(field.placeholder || '(00) 00000-0000')}</div>`;
            case 'textarea':
                return `<div class="preview-textarea">${this._escapeHtml(field.placeholder || 'Digite aqui...')}</div>`;
            case 'radio':
            case 'select':
                const opts = field.options || field.settings?.options || [];
                return `<div class="preview-options">${opts.slice(0, 3).map(o =>
                    `<div class="preview-option"><span class="preview-radio"></span> ${this._escapeHtml(typeof o === 'string' ? o : o.label)}</div>`
                ).join('')}${opts.length > 3 ? `<div class="preview-option-more">+${opts.length - 3} mais</div>` : ''}</div>`;
            case 'checkbox':
                const copts = field.options || field.settings?.options || [];
                return `<div class="preview-options">${copts.slice(0, 3).map(o =>
                    `<div class="preview-option"><span class="preview-checkbox"></span> ${this._escapeHtml(typeof o === 'string' ? o : o.label)}</div>`
                ).join('')}</div>`;
            case 'rating':
                const max = field.settings?.rating_max || 5;
                return `<div class="preview-rating">${'&#9734;'.repeat(max)}</div>`;
            case 'opinion_scale':
                const omin = field.settings?.opinion_min || 1;
                const omax = field.settings?.opinion_max || 10;
                return `<div class="preview-scale">${Array.from({ length: omax - omin + 1 }, (_, i) => `<span>${omin + i}</span>`).join('')}</div>`;
            case 'file_upload':
                return `<div class="preview-upload">&#128206; Clique para upload</div>`;
            case 'datepicker':
                return `<div class="preview-input">&#128197; Selecione uma data</div>`;
            case 'signature':
                return `<div class="preview-signature">&#9997; Assine aqui</div>`;
            case 'address':
                return `<div class="preview-input">&#128205; Endereço com autocomplete</div>`;
            case 'paragraph':
                return `<div class="preview-paragraph">${this._escapeHtml(field.settings?.content || 'Texto informativo')}</div>`;
            case 'divider':
                return `<hr class="preview-divider">`;
            case 'section_break':
                return `<div class="preview-section-break">${this._escapeHtml(field.settings?.section_title || 'Nova Seção')}</div>`;
            case 'hidden':
                return `<div class="preview-hidden">Campo oculto: ${this._escapeHtml(field.settings?.default_value || '')}</div>`;
            default:
                return `<div class="preview-input">...</div>`;
        }
    }

    // ==========================================
    // SETTINGS PANEL (Right)
    // ==========================================

    _renderSettings() {
        const panel = document.getElementById(this.options.settingsId);
        if (!panel) return;

        if (this.selectedFieldIndex < 0 || this.selectedFieldIndex >= this.fields.length) {
            panel.innerHTML = `
                <div class="settings-empty">
                    <div style="font-size:48px;opacity:0.3;margin-bottom:16px;">&#9881;</div>
                    <p>Selecione um campo para editar suas propriedades</p>
                </div>
            `;
            return;
        }

        const field = this.fields[this.selectedFieldIndex];
        const typeDef = this.fieldTypes[field.type] || {};

        panel.innerHTML = `
            <div class="settings-header">
                <span class="settings-icon">${typeDef.icon || ''}</span>
                <span class="settings-title">${typeDef.label || field.type}</span>
                <button class="btn btn-sm btn-ghost" onclick="builder.deselectField()">&#10005;</button>
            </div>

            <div class="settings-body">
                <!-- General Tab -->
                <div class="settings-section">
                    <h4 class="settings-section-title">Geral</h4>

                    <div class="form-group">
                        <label class="form-label">Mensagem da Conversa</label>
                        <textarea class="form-textarea" id="field-message" rows="2" placeholder="Mensagem que aparece antes do campo (ex: Qual é o seu nome?)">${this._escapeHtml(field.message || '')}</textarea>
                        <span class="form-hint">Suporta {{campo_id}} para dados já preenchidos</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-input" id="field-label" value="${this._escapeHtml(field.label || '')}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-input" id="field-description" value="${this._escapeHtml(field.description || '')}" placeholder="Texto de ajuda opcional">
                    </div>

                    ${!typeDef.isLayout ? `
                        <div class="form-group">
                            <label class="form-label">Placeholder</label>
                            <input type="text" class="form-input" id="field-placeholder" value="${this._escapeHtml(field.placeholder || '')}">
                        </div>

                        <div class="form-group">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <label class="form-label" style="margin:0;">Obrigatório</label>
                                <label class="form-toggle">
                                    <input type="checkbox" id="field-required" ${field.required ? 'checked' : ''}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">ID do Campo</label>
                            <input type="text" class="form-input" id="field-id" value="${this._escapeHtml(field.id || field.field || '')}" placeholder="campo_unico">
                            <span class="form-hint">Usado na API e referências</span>
                        </div>
                    ` : ''}
                </div>

                ${this._renderTypeSpecificSettings(field)}

                ${!typeDef.isLayout ? `
                    <!-- Validation -->
                    <div class="settings-section">
                        <h4 class="settings-section-title">Validação</h4>
                        <div class="form-group">
                            <label class="form-label">Mínimo de caracteres</label>
                            <input type="number" class="form-input" id="field-val-min" value="${field.settings?.validation_rules?.min || ''}" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Máximo de caracteres</label>
                            <input type="number" class="form-input" id="field-val-max" value="${field.settings?.validation_rules?.max || ''}" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Regex personalizado</label>
                            <input type="text" class="form-input" id="field-val-pattern" value="${this._escapeHtml(field.settings?.validation_rules?.pattern || '')}" placeholder="Ex: ^[A-Z].*">
                        </div>
                    </div>

                    <!-- Conditional Logic -->
                    <div class="settings-section">
                        <h4 class="settings-section-title">Lógica Condicional</h4>
                        <div class="form-group">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <label class="form-label" style="margin:0;">Ativar lógica</label>
                                <label class="form-toggle">
                                    <input type="checkbox" id="field-logic-enabled" ${field.settings?.conditional_logic ? 'checked' : ''}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div id="logic-rules-container" style="${field.settings?.conditional_logic ? '' : 'display:none;'}">
                            <div class="form-group">
                                <label class="form-label">Mostrar este campo quando</label>
                                <select class="form-select" id="field-logic-match">
                                    <option value="all" ${field.settings?.conditional_logic?.match === 'all' ? 'selected' : ''}>Todas as condições</option>
                                    <option value="any" ${field.settings?.conditional_logic?.match === 'any' ? 'selected' : ''}>Qualquer condição</option>
                                </select>
                            </div>
                            <div id="logic-rules">
                                ${this._renderLogicRules(field)}
                            </div>
                            <button class="btn btn-sm btn-outline" onclick="builder.addLogicRule()">+ Adicionar regra</button>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        // Bind input events
        this._bindSettingsEvents();
    }

    _renderTypeSpecificSettings(field) {
        let html = '<div class="settings-section"><h4 class="settings-section-title">Configurações do Campo</h4>';

        switch (field.type) {
            case 'name':
                html += `
                    <div class="form-group">
                        <label class="form-label">Formato</label>
                        <select class="form-select" id="field-name-format">
                            <option value="first_last" ${field.settings?.name_format === 'first_last' ? 'selected' : ''}>Nome e Sobrenome (2 linhas)</option>
                            <option value="full" ${field.settings?.name_format === 'full' ? 'selected' : ''}>Nome Completo (1 linha)</option>
                            <option value="first_only" ${field.settings?.name_format === 'first_only' ? 'selected' : ''}>Apenas Nome</option>
                            <option value="last_only" ${field.settings?.name_format === 'last_only' ? 'selected' : ''}>Apenas Sobrenome</option>
                        </select>
                    </div>
                `;
                break;

            case 'phone':
                html += `
                    <div class="form-group">
                        <label class="form-label">Código do País Padrão</label>
                        <select class="form-select" id="field-country-code">
                            <option value="+55" ${field.settings?.country_code === '+55' ? 'selected' : ''}>&#127463;&#127479; Brasil (+55)</option>
                            <option value="+1" ${field.settings?.country_code === '+1' ? 'selected' : ''}>&#127482;&#127480; EUA (+1)</option>
                            <option value="+351" ${field.settings?.country_code === '+351' ? 'selected' : ''}>&#127477;&#127481; Portugal (+351)</option>
                            <option value="+34" ${field.settings?.country_code === '+34' ? 'selected' : ''}>&#127466;&#127480; Espanha (+34)</option>
                        </select>
                    </div>
                `;
                break;

            case 'radio':
            case 'select':
            case 'checkbox':
                html += this._renderOptionsEditor(field);
                break;

            case 'rating':
                html += `
                    <div class="form-group">
                        <label class="form-label">Ícone</label>
                        <select class="form-select" id="field-rating-icon">
                            <option value="star" ${field.settings?.rating_icon === 'star' ? 'selected' : ''}>&#11088; Estrelas</option>
                            <option value="heart" ${field.settings?.rating_icon === 'heart' ? 'selected' : ''}>&#10084; Corações</option>
                            <option value="thumb" ${field.settings?.rating_icon === 'thumb' ? 'selected' : ''}>&#128077; Joinha</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Máximo</label>
                        <input type="number" class="form-input" id="field-rating-max" value="${field.settings?.rating_max || 5}" min="2" max="10">
                    </div>
                `;
                break;

            case 'opinion_scale':
                html += `
                    <div class="form-group">
                        <label class="form-label">Valor Mínimo</label>
                        <input type="number" class="form-input" id="field-opinion-min" value="${field.settings?.opinion_min || 1}" min="0" max="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor Máximo</label>
                        <input type="number" class="form-input" id="field-opinion-max" value="${field.settings?.opinion_max || 10}" min="5" max="20">
                    </div>
                `;
                break;

            case 'file_upload':
                html += `
                    <div class="form-group">
                        <label class="form-label">Tamanho Máximo (MB)</label>
                        <input type="number" class="form-input" id="field-file-maxsize" value="${field.settings?.file_max_size || 10}" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Extensões Permitidas</label>
                        <input type="text" class="form-input" id="field-file-types" value="${this._escapeHtml(field.settings?.file_allowed_types || 'jpg,jpeg,png,pdf')}" placeholder="jpg,png,pdf">
                    </div>
                    <div class="form-group">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <label class="form-label" style="margin:0;">Múltiplos Arquivos</label>
                            <label class="form-toggle">
                                <input type="checkbox" id="field-file-multiple" ${field.settings?.multiple ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                `;
                break;

            case 'social_media':
                html += `
                    <div class="form-group">
                        <label class="form-label">Tipo de Rede Social</label>
                        <select class="form-select" id="field-social-type">
                            <option value="facebook" ${field.settings?.social_type === 'facebook' ? 'selected' : ''}>Facebook</option>
                            <option value="instagram" ${field.settings?.social_type === 'instagram' ? 'selected' : ''}>Instagram</option>
                            <option value="whatsapp" ${field.settings?.social_type === 'whatsapp' ? 'selected' : ''}>WhatsApp</option>
                            <option value="linkedin" ${field.settings?.social_type === 'linkedin' ? 'selected' : ''}>LinkedIn</option>
                            <option value="youtube" ${field.settings?.social_type === 'youtube' ? 'selected' : ''}>YouTube</option>
                            <option value="tiktok" ${field.settings?.social_type === 'tiktok' ? 'selected' : ''}>TikTok</option>
                            <option value="custom" ${field.settings?.social_type === 'custom' ? 'selected' : ''}>Personalizado</option>
                        </select>
                    </div>
                `;
                break;

            case 'datepicker':
                html += `
                    <div class="form-group">
                        <label class="form-label">Data Mínima</label>
                        <input type="date" class="form-input" id="field-date-min" value="${field.settings?.min_date || ''}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Máxima</label>
                        <input type="date" class="form-input" id="field-date-max" value="${field.settings?.max_date || ''}">
                    </div>
                `;
                break;

            case 'paragraph':
                html += `
                    <div class="form-group">
                        <label class="form-label">Conteúdo</label>
                        <textarea class="form-textarea" id="field-paragraph-content" rows="4">${this._escapeHtml(field.settings?.content || '')}</textarea>
                    </div>
                `;
                break;

            case 'section_break':
                html += `
                    <div class="form-group">
                        <label class="form-label">Título da Seção</label>
                        <input type="text" class="form-input" id="field-section-title" value="${this._escapeHtml(field.settings?.section_title || '')}">
                    </div>
                `;
                break;
        }

        html += '</div>';
        return html;
    }

    _renderOptionsEditor(field) {
        const options = field.options || field.settings?.options || [];

        let html = `
            <div class="form-group">
                <label class="form-label">Opções</label>
                <div id="options-list" class="options-editor">
                    ${options.map((opt, idx) => {
                        const label = typeof opt === 'string' ? opt : opt.label;
                        const value = typeof opt === 'string' ? opt : (opt.value || opt.label);
                        return `
                            <div class="option-item" data-index="${idx}">
                                <span class="option-drag">&#9776;</span>
                                <input type="text" class="form-input option-label-input" value="${this._escapeHtml(label)}" placeholder="Opção ${idx + 1}" data-idx="${idx}">
                                <button class="btn btn-sm btn-ghost option-remove" onclick="builder.removeOption(${idx})">&#10005;</button>
                            </div>
                        `;
                    }).join('')}
                </div>
                <button class="btn btn-sm btn-outline mt-2" onclick="builder.addOption()">+ Adicionar opção</button>
            </div>
        `;

        return html;
    }

    _renderLogicRules(field) {
        const logic = field.settings?.conditional_logic;
        if (!logic?.rules) return '';

        return logic.rules.map((rule, idx) => `
            <div class="logic-rule" data-index="${idx}">
                <select class="form-select logic-field-select" data-idx="${idx}">
                    <option value="">Selecione campo</option>
                    ${this.fields.filter((f, i) => i !== this.selectedFieldIndex).map(f => `
                        <option value="${f.id || f.field}" ${rule.field === (f.id || f.field) ? 'selected' : ''}>${this._escapeHtml(f.label || f.id || f.field)}</option>
                    `).join('')}
                </select>
                <select class="form-select logic-operator-select" data-idx="${idx}">
                    <option value="equals" ${rule.operator === 'equals' ? 'selected' : ''}>Igual a</option>
                    <option value="not_equals" ${rule.operator === 'not_equals' ? 'selected' : ''}>Diferente de</option>
                    <option value="contains" ${rule.operator === 'contains' ? 'selected' : ''}>Contém</option>
                    <option value="gt" ${rule.operator === 'gt' ? 'selected' : ''}>Maior que</option>
                    <option value="lt" ${rule.operator === 'lt' ? 'selected' : ''}>Menor que</option>
                    <option value="is_set" ${rule.operator === 'is_set' ? 'selected' : ''}>Preenchido</option>
                </select>
                <input type="text" class="form-input logic-value-input" value="${this._escapeHtml(rule.value || '')}" placeholder="Valor" data-idx="${idx}">
                <button class="btn btn-sm btn-ghost" onclick="builder.removeLogicRule(${idx})">&#10005;</button>
            </div>
        `).join('');
    }

    // ==========================================
    // FIELD OPERATIONS
    // ==========================================

    addField(type, index = -1) {
        const typeDef = this.fieldTypes[type];
        if (!typeDef) return;

        this.fieldCounter++;
        const fieldId = `field_${type}_${this.fieldCounter}`;

        const field = {
            id: fieldId,
            field: fieldId,
            type: type,
            label: typeDef.label,
            message: '',
            description: '',
            placeholder: '',
            required: typeDef.defaultSettings?.required || false,
            options: typeDef.defaultSettings?.options ? [...typeDef.defaultSettings.options] : undefined,
            settings: { ...typeDef.defaultSettings }
        };

        this._saveUndo();

        if (index >= 0) {
            this.fields.splice(index, 0, field);
        } else {
            this.fields.push(field);
        }

        this.selectedFieldIndex = index >= 0 ? index : this.fields.length - 1;
        this._markDirty();
        this._renderCanvas();
        this._renderCanvasFields();
        this._renderSettings();
        this._updateFieldCount();

        // Scroll to new field
        setTimeout(() => {
            const el = document.querySelector(`[data-index="${this.selectedFieldIndex}"]`);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    removeField(index) {
        if (index < 0 || index >= this.fields.length) return;

        if (!confirm('Remover este campo?')) return;

        this._saveUndo();
        this.fields.splice(index, 1);

        if (this.selectedFieldIndex === index) {
            this.selectedFieldIndex = -1;
        } else if (this.selectedFieldIndex > index) {
            this.selectedFieldIndex--;
        }

        this._markDirty();
        this._renderCanvas();
        this._renderCanvasFields();
        this._renderSettings();
        this._updateFieldCount();
    }

    duplicateField(index) {
        if (index < 0 || index >= this.fields.length) return;

        this._saveUndo();
        this.fieldCounter++;

        const original = this.fields[index];
        const copy = JSON.parse(JSON.stringify(original));
        copy.id = `${original.type}_${this.fieldCounter}`;
        copy.field = copy.id;
        copy.label = copy.label + ' (cópia)';

        this.fields.splice(index + 1, 0, copy);
        this.selectedFieldIndex = index + 1;

        this._markDirty();
        this._renderCanvas();
        this._renderCanvasFields();
        this._renderSettings();
        this._updateFieldCount();
    }

    selectField(index) {
        this.selectedFieldIndex = index;
        this._renderCanvasFields();
        this._renderSettings();
    }

    deselectField() {
        this.selectedFieldIndex = -1;
        this._renderCanvasFields();
        this._renderSettings();
    }

    // ==========================================
    // OPTIONS MANAGEMENT
    // ==========================================

    addOption() {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];

        if (!field.options) field.options = [];
        if (!field.settings) field.settings = {};
        if (!field.settings.options) field.settings.options = [];

        const newOpt = `Opção ${(field.options || field.settings.options).length + 1}`;
        if (field.options) field.options.push(newOpt);
        if (field.settings.options) field.settings.options.push(newOpt);

        this._markDirty();
        this._renderSettings();
        this._renderCanvasFields();
    }

    removeOption(index) {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];

        if (field.options) field.options.splice(index, 1);
        if (field.settings?.options) field.settings.options.splice(index, 1);

        this._markDirty();
        this._renderSettings();
        this._renderCanvasFields();
    }

    // ==========================================
    // LOGIC RULES
    // ==========================================

    addLogicRule() {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];

        if (!field.settings) field.settings = {};
        if (!field.settings.conditional_logic) {
            field.settings.conditional_logic = { match: 'all', rules: [] };
        }

        field.settings.conditional_logic.rules.push({
            field: '',
            operator: 'equals',
            value: ''
        });

        this._markDirty();
        this._renderSettings();
    }

    removeLogicRule(index) {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];

        if (field.settings?.conditional_logic?.rules) {
            field.settings.conditional_logic.rules.splice(index, 1);
            if (field.settings.conditional_logic.rules.length === 0) {
                delete field.settings.conditional_logic;
            }
        }

        this._markDirty();
        this._renderSettings();
        this._renderCanvasFields();
    }

    // ==========================================
    // JUMP LOGIC / FLOW BUILDER
    // ==========================================

    openJumpLogic(fieldIndex) {
        if (fieldIndex < 0 || fieldIndex >= this.fields.length) return;

        const field = this.fields[fieldIndex];
        this.selectedFieldIndex = fieldIndex;

        // Create or open the flow panel
        let panel = document.getElementById('flow-panel');
        if (!panel) {
            panel = document.createElement('div');
            panel.id = 'flow-panel';
            panel.className = 'flow-panel';
            document.body.appendChild(panel);
        }

        const jumpRules = field.settings?.jump_logic || [];

        panel.innerHTML = `
            <div class="flow-panel-header">
                <h3>&#9889; Logica de Salto</h3>
                <button class="btn btn-sm btn-ghost" onclick="builder.closeJumpLogic()">&times;</button>
            </div>
            <div class="flow-panel-body">
                <div style="margin-bottom:12px;padding:8px 12px;background:var(--primary-50);border-radius:8px;font-size:13px;color:var(--primary);">
                    Campo: <strong>${this._escapeHtml(field.label || field.id)}</strong>
                </div>
                <p style="font-size:12px;color:var(--gray-500);margin-bottom:16px;">
                    Configure para qual campo o formulario deve pular, baseado na resposta do usuario.
                </p>

                <div id="jump-rules-list">
                    ${jumpRules.map((rule, idx) => this._renderJumpRule(rule, idx)).join('')}
                </div>

                <button class="btn btn-sm btn-outline" style="width:100%;margin-top:8px;" onclick="builder.addJumpRule()">
                    + Adicionar regra de salto
                </button>

                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--gray-200);">
                    <div class="form-group">
                        <label class="form-label">Salto padrao (sem condicao)</label>
                        <select class="form-select" id="jump-default-target" onchange="builder.updateJumpDefault(this.value)">
                            <option value="">Proximo campo (padrao)</option>
                            ${this.fields.filter((f, i) => i > fieldIndex).map((f, i) => `
                                <option value="${f.id}" ${field.settings?.jump_logic_default === f.id ? 'selected' : ''}>
                                    ${fieldIndex + i + 2}. ${this._escapeHtml(f.label || f.id)}
                                </option>
                            `).join('')}
                            <option value="_end" ${field.settings?.jump_logic_default === '_end' ? 'selected' : ''}>Finalizar formulario</option>
                        </select>
                    </div>
                </div>
            </div>
        `;

        panel.classList.add('open');
        this._renderCanvasFields();
        this._renderSettings();
    }

    closeJumpLogic() {
        const panel = document.getElementById('flow-panel');
        if (panel) panel.classList.remove('open');
    }

    _renderJumpRule(rule, idx) {
        const currentFieldIndex = this.selectedFieldIndex;
        const field = this.fields[currentFieldIndex];
        const hasOptions = ['radio', 'select', 'checkbox'].includes(field.type);
        const options = field.options || field.settings?.options || [];

        return `
            <div class="jump-rule" data-rule-index="${idx}">
                <div class="jump-rule-header">
                    <span>Regra ${idx + 1}</span>
                    <button class="btn btn-sm btn-ghost" onclick="builder.removeJumpRule(${idx})" style="color:var(--danger);">&#10005;</button>
                </div>

                <div class="jump-condition">
                    <div class="form-group">
                        <label class="form-label">Se a resposta</label>
                        <select class="form-select" onchange="builder.updateJumpRuleOperator(${idx}, this.value)">
                            <option value="equals" ${rule.operator === 'equals' ? 'selected' : ''}>E igual a</option>
                            <option value="not_equals" ${rule.operator === 'not_equals' ? 'selected' : ''}>E diferente de</option>
                            <option value="contains" ${rule.operator === 'contains' ? 'selected' : ''}>Contem</option>
                            <option value="is_set" ${rule.operator === 'is_set' ? 'selected' : ''}>Foi preenchida</option>
                            <option value="not_set" ${rule.operator === 'not_set' ? 'selected' : ''}>Nao foi preenchida</option>
                        </select>
                    </div>

                    ${rule.operator !== 'is_set' && rule.operator !== 'not_set' ? `
                        <div class="form-group">
                            <label class="form-label">Valor</label>
                            ${hasOptions ? `
                                <select class="form-select" onchange="builder.updateJumpRuleValue(${idx}, this.value)">
                                    <option value="">Selecione...</option>
                                    ${options.map(opt => {
                                        const val = typeof opt === 'string' ? opt : opt.label;
                                        return `<option value="${this._escapeHtml(val)}" ${rule.value === val ? 'selected' : ''}>${this._escapeHtml(val)}</option>`;
                                    }).join('')}
                                </select>
                            ` : `
                                <input type="text" class="form-input" value="${this._escapeHtml(rule.value || '')}"
                                       onchange="builder.updateJumpRuleValue(${idx}, this.value)" placeholder="Valor esperado">
                            `}
                        </div>
                    ` : ''}
                </div>

                <div class="form-group" style="margin-top:8px;">
                    <label class="form-label">Entao pular para</label>
                    <select class="form-select" onchange="builder.updateJumpRuleTarget(${idx}, this.value)">
                        <option value="">Selecione...</option>
                        ${this.fields.filter((f, i) => i !== currentFieldIndex).map((f, i) => `
                            <option value="${f.id}" ${rule.target === f.id ? 'selected' : ''}>
                                ${this._escapeHtml(f.label || f.id)}
                            </option>
                        `).join('')}
                        <option value="_end" ${rule.target === '_end' ? 'selected' : ''}>Finalizar formulario</option>
                    </select>
                </div>
            </div>
        `;
    }

    addJumpRule() {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];
        if (!field.settings) field.settings = {};
        if (!field.settings.jump_logic) field.settings.jump_logic = [];

        field.settings.jump_logic.push({
            operator: 'equals',
            value: '',
            target: ''
        });

        this._markDirty();
        this.openJumpLogic(this.selectedFieldIndex);
    }

    removeJumpRule(idx) {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];
        if (field.settings?.jump_logic) {
            field.settings.jump_logic.splice(idx, 1);
        }
        this._markDirty();
        this.openJumpLogic(this.selectedFieldIndex);
    }

    updateJumpRuleOperator(idx, value) {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];
        if (field.settings?.jump_logic?.[idx]) {
            field.settings.jump_logic[idx].operator = value;
            this._markDirty();
            this.openJumpLogic(this.selectedFieldIndex);
        }
    }

    updateJumpRuleValue(idx, value) {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];
        if (field.settings?.jump_logic?.[idx]) {
            field.settings.jump_logic[idx].value = value;
            this._markDirty();
        }
    }

    updateJumpRuleTarget(idx, value) {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];
        if (field.settings?.jump_logic?.[idx]) {
            field.settings.jump_logic[idx].target = value;
            this._markDirty();
        }
    }

    updateJumpDefault(value) {
        if (this.selectedFieldIndex < 0) return;
        const field = this.fields[this.selectedFieldIndex];
        if (!field.settings) field.settings = {};
        field.settings.jump_logic_default = value || null;
        this._markDirty();
    }

    // ==========================================
    // SETTINGS BINDING
    // ==========================================

    _bindSettingsEvents() {
        const bindInput = (id, prop, transform = null) => {
            const el = document.getElementById(id);
            if (!el) return;

            const handler = () => {
                if (this.selectedFieldIndex < 0) return;
                const field = this.fields[this.selectedFieldIndex];
                let value = el.type === 'checkbox' ? el.checked : el.value;
                if (transform) value = transform(value);

                // Set nested property
                const parts = prop.split('.');
                let obj = field;
                for (let i = 0; i < parts.length - 1; i++) {
                    if (!obj[parts[i]]) obj[parts[i]] = {};
                    obj = obj[parts[i]];
                }
                obj[parts[parts.length - 1]] = value;

                this._markDirty();
                this._renderCanvasFields();
            };

            el.addEventListener('input', handler);
            el.addEventListener('change', handler);
        };

        // General
        bindInput('field-message', 'message');
        bindInput('field-label', 'label');
        bindInput('field-description', 'description');
        bindInput('field-placeholder', 'placeholder');
        bindInput('field-required', 'required');
        bindInput('field-id', 'id');

        // Validation
        bindInput('field-val-min', 'settings.validation_rules.min', v => v ? parseInt(v) : null);
        bindInput('field-val-max', 'settings.validation_rules.max', v => v ? parseInt(v) : null);
        bindInput('field-val-pattern', 'settings.validation_rules.pattern');

        // Type-specific
        bindInput('field-name-format', 'settings.name_format');
        bindInput('field-country-code', 'settings.country_code');
        bindInput('field-rating-icon', 'settings.rating_icon');
        bindInput('field-rating-max', 'settings.rating_max', v => parseInt(v));
        bindInput('field-opinion-min', 'settings.opinion_min', v => parseInt(v));
        bindInput('field-opinion-max', 'settings.opinion_max', v => parseInt(v));
        bindInput('field-file-maxsize', 'settings.file_max_size', v => parseInt(v));
        bindInput('field-file-types', 'settings.file_allowed_types');
        bindInput('field-file-multiple', 'settings.multiple');
        bindInput('field-social-type', 'settings.social_type');
        bindInput('field-date-min', 'settings.min_date');
        bindInput('field-date-max', 'settings.max_date');
        bindInput('field-paragraph-content', 'settings.content');
        bindInput('field-section-title', 'settings.section_title');

        // Logic toggle
        const logicToggle = document.getElementById('field-logic-enabled');
        if (logicToggle) {
            logicToggle.addEventListener('change', () => {
                const container = document.getElementById('logic-rules-container');
                if (container) container.style.display = logicToggle.checked ? '' : 'none';

                if (this.selectedFieldIndex >= 0) {
                    const field = this.fields[this.selectedFieldIndex];
                    if (!logicToggle.checked) {
                        delete field.settings?.conditional_logic;
                    } else if (!field.settings?.conditional_logic) {
                        if (!field.settings) field.settings = {};
                        field.settings.conditional_logic = { match: 'all', rules: [] };
                    }
                }
            });
        }

        // Option inputs
        document.querySelectorAll('.option-label-input').forEach(input => {
            input.addEventListener('input', () => {
                const idx = parseInt(input.dataset.idx);
                if (this.selectedFieldIndex >= 0) {
                    const field = this.fields[this.selectedFieldIndex];
                    if (field.options) field.options[idx] = input.value;
                    if (field.settings?.options) field.settings.options[idx] = input.value;
                    this._markDirty();
                }
            });
        });
    }

    // ==========================================
    // SAVE / LOAD
    // ==========================================

    async save() {
        const saveBtn = document.getElementById('builder-save-btn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Salvando...';
        }

        const titleEl = document.getElementById('form-title');
        const payload = {
            form_id: this.options.formId,
            title: titleEl ? titleEl.value : '',
            fields: this.fields,
            settings: this.options.formSettings,
            _token: this.options.csrfToken
        };

        try {
            const response = await fetch(this.options.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (response.ok) {
                this.isDirty = false;
                this._showToast('Formulário salvo com sucesso!');
                if (result.form_id) this.options.formId = result.form_id;
            } else {
                throw new Error(result.message || 'Erro ao salvar');
            }
        } catch (error) {
            this._showToast(error.message, 'error');
        }

        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '&#128190; Salvar';
        }
    }

    preview() {
        // Save first, then open preview
        this.save().then(() => {
            const url = this.options.previewUrl || `/dashboard/forms/${this.options.formId}/preview`;
            window.open(url, '_blank');
        });
    }

    async publish() {
        await this.save();

        try {
            const response = await fetch(this.options.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    form_id: this.options.formId,
                    publish: true,
                    _token: this.options.csrfToken
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                this._showToast('Formulario publicado com sucesso!');
            } else {
                throw new Error(result.error || 'Erro ao publicar');
            }
        } catch (error) {
            this._showToast(error.message || 'Erro ao publicar', 'error');
        }
    }

    getFormData() {
        return {
            fields: this.fields,
            settings: this.options.formSettings
        };
    }

    // ==========================================
    // UNDO / REDO
    // ==========================================

    _saveUndo() {
        this.undoStack.push(JSON.stringify(this.fields));
        this.redoStack = [];
        if (this.undoStack.length > 50) this.undoStack.shift();
    }

    undo() {
        if (this.undoStack.length === 0) return;
        this.redoStack.push(JSON.stringify(this.fields));
        this.fields = JSON.parse(this.undoStack.pop());
        this.selectedFieldIndex = -1;
        this._renderCanvas();
        this._renderCanvasFields();
        this._renderSettings();
        this._markDirty();
    }

    redo() {
        if (this.redoStack.length === 0) return;
        this.undoStack.push(JSON.stringify(this.fields));
        this.fields = JSON.parse(this.redoStack.pop());
        this.selectedFieldIndex = -1;
        this._renderCanvas();
        this._renderCanvasFields();
        this._renderSettings();
        this._markDirty();
    }

    // ==========================================
    // UTILITIES
    // ==========================================

    _setupAutoSave() {
        setInterval(() => {
            if (this.isDirty) {
                // Auto-save to localStorage as backup
                try {
                    localStorage.setItem(`leadform_builder_${this.options.formId || 'new'}`, JSON.stringify({
                        fields: this.fields,
                        settings: this.options.formSettings,
                        timestamp: Date.now()
                    }));
                } catch (e) {}
            }
        }, 30000); // Every 30 seconds
    }

    _setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.save();
            }

            // Ctrl+Z to undo
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                this.undo();
            }

            // Ctrl+Shift+Z to redo
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && e.shiftKey) {
                e.preventDefault();
                this.redo();
            }

            // Delete key to remove selected field
            if (e.key === 'Delete' && this.selectedFieldIndex >= 0) {
                const active = document.activeElement;
                if (!active || (active.tagName !== 'INPUT' && active.tagName !== 'TEXTAREA' && active.tagName !== 'SELECT')) {
                    this.removeField(this.selectedFieldIndex);
                }
            }

            // Escape to deselect
            if (e.key === 'Escape') {
                this.deselectField();
            }
        });
    }

    _markDirty() {
        this.isDirty = true;
        const indicator = document.getElementById('builder-dirty-indicator');
        if (indicator) indicator.style.display = 'inline-block';
    }

    _updateFieldCount() {
        const counter = document.getElementById('builder-field-count');
        if (counter) counter.textContent = `${this.fields.length} campos`;
    }

    _escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    _showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type === 'error' ? 'error' : 'success'}`;
        toast.innerHTML = `
            <span>${this._escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
}

// Export
window.LeadFormBuilder = LeadFormBuilder;
