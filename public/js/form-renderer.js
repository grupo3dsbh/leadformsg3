/**
 * LeadForm SaaS - Conversational Form Renderer
 * Supports 2 modes: "conversation" (chat) and "typeform" (one question at a time)
 * Modular, configurable, and extensible
 */

class LeadFormRenderer {
    constructor(config) {
        // Default configuration
        this.config = {
            containerId: 'leadform-app',
            mode: 'conversation', // 'conversation' or 'typeform'
            formId: null,
            formSlug: null,
            apiUrl: '/api/v1',
            submitUrl: null,
            partialSaveUrl: null,
            fields: [],
            settings: {
                showProgress: true,
                showBranding: true,
                brandLogo: null,
                brandName: 'LeadForm',
                poweredBy: true,
                saveAndContinue: false,
                typingDelay: { min: 800, max: 1500 },
                background: { type: 'gradient', value: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)' },
                completion: {
                    type: 'thankyou', // thankyou, redirect, bonus, subform
                    title: 'Obrigado!',
                    message: 'Suas informações foram enviadas com sucesso.',
                    icon: null,
                    redirectUrl: null,
                    bonusContent: null,
                    subformSlug: null
                },
                theme: {
                    primary: '#4F46E5',
                    bubbleBotBg: 'rgba(255, 255, 255, 0.95)',
                    bubbleBotText: '#333333',
                    bubbleUserBg: 'rgba(79, 70, 229, 0.95)',
                    bubbleUserText: '#ffffff',
                    fontFamily: "'Inter', 'Segoe UI', sans-serif"
                },
                locale: 'pt_BR',
                rtl: false,
                animations: true,
                autoSave: false,
                autoSaveAtField: null, // field id for partial save trigger
                uniqueLink: false,
                expiry: null
            },
            // Hidden fields from URL params
            hiddenFields: {},
            // UTM tracking
            utm: {},
            // Pre-filled data (from continue later or subform)
            prefillData: {},
            // Callbacks
            onStart: null,
            onStepChange: null,
            onFieldAnswer: null,
            onPartialSave: null,
            onComplete: null,
            onError: null,
            ...config
        };

        // State
        this.currentStep = 0;
        this.formData = {};
        this.fieldHistory = []; // For back navigation
        this.isTyping = false;
        this.isSubmitting = false;
        this.entryToken = null;
        this.startTime = Date.now();
        this.fieldStartTimes = {};
        this.analytics = [];

        // DOM
        this.container = null;

        // Parse hidden fields from URL
        this._parseUrlParams();

        // Apply prefill data
        if (this.config.prefillData) {
            Object.assign(this.formData, this.config.prefillData);
        }

        // Initialize
        this._init();
    }

    // ==========================================
    // INITIALIZATION
    // ==========================================

    _init() {
        this.container = document.getElementById(this.config.containerId);
        if (!this.container) {
            console.error('LeadForm: Container not found:', this.config.containerId);
            return;
        }

        // Apply theme CSS variables
        this._applyTheme();

        // Build DOM structure
        this._buildDOM();

        // Setup keyboard navigation
        this._setupKeyboard();

        // Track visit
        this._trackVisit();

        // Check for unique link / expiry
        if (!this._checkAccess()) return;

        // Start form
        this._start();
    }

    _applyTheme() {
        const theme = this.config.settings.theme;
        const root = this.container;

        if (theme.primary) {
            root.style.setProperty('--lf-primary', theme.primary);
            // Extract RGB values
            const hex = theme.primary.replace('#', '');
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            root.style.setProperty('--lf-primary-rgb', `${r}, ${g}, ${b}`);
        }
        if (theme.bubbleBotBg) root.style.setProperty('--lf-bubble-bot', theme.bubbleBotBg);
        if (theme.bubbleBotText) root.style.setProperty('--lf-bubble-bot-text', theme.bubbleBotText);
        if (theme.bubbleUserBg) root.style.setProperty('--lf-bubble-user', theme.bubbleUserBg);
        if (theme.bubbleUserText) root.style.setProperty('--lf-bubble-user-text', theme.bubbleUserText);
        if (theme.fontFamily) root.style.setProperty('--lf-font', theme.fontFamily);
    }

    _buildDOM() {
        this.container.classList.add('leadform');
        this.container.innerHTML = '';

        // Background
        this._buildBackground();

        // Progress bar
        if (this.config.settings.showProgress) {
            this._buildProgress();
        }

        // Branding
        if (this.config.settings.showBranding && this.config.settings.brandLogo) {
            this._buildBranding();
        }

        // Mode-specific content
        if (this.config.mode === 'conversation') {
            this._buildConversationMode();
        } else {
            this._buildTypeformMode();
        }

        // Completion screen
        this._buildCompletion();

        // Save and continue
        if (this.config.settings.saveAndContinue) {
            this._buildSaveAndContinue();
        }

        // Powered by
        if (this.config.settings.poweredBy) {
            this._buildPoweredBy();
        }
    }

    _buildBackground() {
        const bg = this.config.settings.background;
        const bgEl = document.createElement('div');
        bgEl.className = 'lf-background';

        switch (bg.type) {
            case 'video':
                bgEl.innerHTML = `
                    <video autoplay muted loop playsinline>
                        <source src="${this._escapeHtml(bg.value)}" type="video/mp4">
                    </video>
                    <div class="lf-background-overlay"></div>
                `;
                break;
            case 'image':
                bgEl.innerHTML = `
                    <img src="${this._escapeHtml(bg.value)}" alt="">
                    <div class="lf-background-overlay"></div>
                `;
                break;
            case 'gradient':
                bgEl.innerHTML = `<div class="lf-background-gradient" style="background: ${bg.value}"></div>`;
                break;
            case 'color':
                bgEl.innerHTML = `<div class="lf-background-color" style="background: ${this._escapeHtml(bg.value)}"></div>`;
                break;
        }

        this.container.appendChild(bgEl);
    }

    _buildProgress() {
        const progress = document.createElement('div');
        progress.className = 'lf-progress';
        progress.innerHTML = '<div class="lf-progress-fill" id="lf-progress-fill"></div>';
        this.container.appendChild(progress);

        if (this.config.mode === 'typeform') {
            const progressText = document.createElement('div');
            progressText.className = 'lf-progress-text';
            progressText.id = 'lf-progress-text';
            this.container.appendChild(progressText);
        }
    }

    _buildBranding() {
        const branding = document.createElement('div');
        branding.className = 'lf-branding';
        branding.innerHTML = `
            ${this.config.settings.brandLogo ? `<img src="${this._escapeHtml(this.config.settings.brandLogo)}" alt="">` : ''}
            <span class="lf-branding-text">${this._escapeHtml(this.config.settings.brandName || '')}</span>
        `;
        this.container.appendChild(branding);
    }

    // ==========================================
    // CONVERSATION MODE
    // ==========================================

    _buildConversationMode() {
        const wrapper = document.createElement('div');
        wrapper.className = 'lf-mode-conversation';
        wrapper.id = 'lf-conversation';

        wrapper.innerHTML = `
            <div class="lf-messages" id="lf-messages">
                <div class="lf-typing" id="lf-typing">
                    <div class="lf-typing-bubble">
                        <div class="lf-typing-dots">
                            <div class="lf-typing-dot"></div>
                            <div class="lf-typing-dot"></div>
                            <div class="lf-typing-dot"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lf-input-area" id="lf-input-area"></div>
        `;

        this.container.appendChild(wrapper);

        this.messagesEl = wrapper.querySelector('#lf-messages');
        this.typingEl = wrapper.querySelector('#lf-typing');
        this.inputAreaEl = wrapper.querySelector('#lf-input-area');
    }

    // ==========================================
    // TYPEFORM MODE
    // ==========================================

    _buildTypeformMode() {
        const wrapper = document.createElement('div');
        wrapper.className = 'lf-mode-typeform';
        wrapper.id = 'lf-typeform';

        wrapper.innerHTML = `<div class="lf-typeform-question" id="lf-typeform-question"></div>`;

        this.container.appendChild(wrapper);

        this.typeformQuestionEl = wrapper.querySelector('#lf-typeform-question');

        // Navigation arrows
        const nav = document.createElement('div');
        nav.className = 'lf-typeform-nav';
        nav.innerHTML = `
            <button class="lf-typeform-nav-btn" id="lf-nav-prev" title="Anterior">&#8593;</button>
            <button class="lf-typeform-nav-btn" id="lf-nav-next" title="Próximo">&#8595;</button>
        `;
        this.container.appendChild(nav);

        this.navPrevBtn = nav.querySelector('#lf-nav-prev');
        this.navNextBtn = nav.querySelector('#lf-nav-next');

        this.navPrevBtn.addEventListener('click', () => this._goBack());
        this.navNextBtn.addEventListener('click', () => this._submitCurrentStep());
    }

    // ==========================================
    // COMMON BUILDERS
    // ==========================================

    _buildCompletion() {
        const completion = document.createElement('div');
        completion.className = 'lf-completion';
        completion.id = 'lf-completion';

        const s = this.config.settings.completion;
        completion.innerHTML = `
            <div class="lf-completion-card">
                <div class="lf-completion-icon">${s.icon || ''}</div>
                <h2 class="lf-completion-title">${this._escapeHtml(s.title)}</h2>
                <p class="lf-completion-text">${this._escapeHtml(s.message)}</p>
                ${s.type === 'bonus' && s.bonusContent ? `<div class="lf-completion-bonus">${s.bonusContent}</div>` : ''}
                ${s.type === 'redirect' && s.redirectUrl ? `<a href="${this._escapeHtml(s.redirectUrl)}" class="lf-completion-btn">Continuar</a>` : ''}
            </div>
        `;

        this.container.appendChild(completion);
        this.completionEl = completion;
    }

    _buildSaveAndContinue() {
        const save = document.createElement('div');
        save.className = 'lf-save-continue';
        save.innerHTML = `
            <button class="lf-save-btn" id="lf-save-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17,21 17,13 7,13 7,21"/>
                    <polyline points="7,3 7,8 15,8"/>
                </svg>
                Salvar e continuar depois
            </button>
        `;
        this.container.appendChild(save);

        save.querySelector('#lf-save-btn').addEventListener('click', () => this._saveAndContinue());
    }

    _buildPoweredBy() {
        const powered = document.createElement('div');
        powered.className = 'lf-powered-by';
        powered.innerHTML = `Powered by <a href="#">${this._escapeHtml(this.config.settings.brandName || 'LeadForm')}</a>`;
        this.container.appendChild(powered);
    }

    // ==========================================
    // FORM FLOW
    // ==========================================

    _start() {
        if (this.config.onStart) {
            this.config.onStart(this);
        }
        this._showStep(0);
    }

    _showStep(stepIndex) {
        if (stepIndex >= this.config.fields.length) {
            this._finish();
            return;
        }

        this.currentStep = stepIndex;
        const field = this.config.fields[stepIndex];

        // Track field start time
        this.fieldStartTimes[field.id] = Date.now();

        // Evaluate conditional logic
        if (field.settings?.conditional_logic && !this._evaluateCondition(field.settings.conditional_logic)) {
            // Skip this field
            this._showStep(stepIndex + 1);
            return;
        }

        // Update progress
        this._updateProgress();

        // Auto-save check
        if (this.config.settings.autoSave && this.config.settings.autoSaveAtField === field.id) {
            this._autoSavePartial();
        }

        // Render based on mode
        if (this.config.mode === 'conversation') {
            this._showConversationStep(field);
        } else {
            this._showTypeformStep(field);
        }

        // Callback
        if (this.config.onStepChange) {
            this.config.onStepChange(stepIndex, field, this.formData);
        }
    }

    // ==========================================
    // CONVERSATION MODE RENDERING
    // ==========================================

    _showConversationStep(field) {
        // Show typing indicator
        this._showTyping();

        // Get message text
        const message = this._resolveMessage(field);

        // Simulate typing delay
        const delay = this.config.settings.typingDelay;
        const typingTime = delay.min + Math.random() * (delay.max - delay.min);

        setTimeout(() => {
            this._hideTyping();
            this._addChatMessage(message, 'bot');

            setTimeout(() => {
                this._renderConversationInput(field);
            }, 200);
        }, this.config.settings.animations ? typingTime : 100);
    }

    _addChatMessage(text, sender) {
        const msg = document.createElement('div');
        msg.className = `lf-message lf-message-${sender}`;
        msg.innerHTML = `<div class="lf-bubble">${text}</div>`;

        this.messagesEl.insertBefore(msg, this.typingEl);

        // Scroll to message
        setTimeout(() => {
            msg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }

    _renderConversationInput(field) {
        this.inputAreaEl.innerHTML = '';

        switch (field.type) {
            case 'radio':
            case 'select':
                this._renderChatOptions(field);
                break;
            case 'checkbox':
                this._renderChatCheckboxOptions(field);
                break;
            case 'rating':
                this._renderChatRating(field);
                break;
            case 'opinion_scale':
                this._renderChatOpinionScale(field);
                break;
            case 'file_upload':
                this._renderChatFileUpload(field);
                break;
            case 'picture_choice':
                this._renderChatPictureChoice(field);
                break;
            default:
                this._renderChatTextInput(field);
                break;
        }
    }

    _renderChatTextInput(field) {
        const container = document.createElement('div');
        container.className = 'lf-input-container';

        let inputType = 'text';
        let placeholder = field.placeholder || '';
        let extraHtml = '';

        switch (field.type) {
            case 'email':
                inputType = 'email';
                placeholder = placeholder || 'seu@email.com';
                break;
            case 'phone':
                return this._renderChatPhoneInput(field);
            case 'url':
                inputType = 'url';
                placeholder = placeholder || 'https://';
                break;
            case 'password':
                inputType = 'password';
                break;
            case 'cpf_cnpj':
                placeholder = placeholder || 'CPF ou CNPJ';
                break;
            case 'name':
                placeholder = placeholder || 'Seu nome completo';
                break;
        }

        container.innerHTML = `
            ${extraHtml}
            <input type="${inputType}" id="lf-current-input" placeholder="${this._escapeHtml(placeholder)}"
                   ${field.settings?.mask ? `data-mask="${this._escapeHtml(field.settings.mask)}"` : ''}
                   autocomplete="off">
            <button class="lf-send-btn" id="lf-send-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9"/>
                </svg>
            </button>
        `;

        this.inputAreaEl.appendChild(container);

        const input = container.querySelector('#lf-current-input');
        const sendBtn = container.querySelector('#lf-send-btn');

        // Apply mask if needed
        if (field.type === 'cpf_cnpj') {
            this._applyCpfCnpjMask(input);
        } else if (field.type === 'phone') {
            this._applyPhoneMask(input);
        }

        sendBtn.addEventListener('click', () => this._handleChatSubmit(field, input));
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this._handleChatSubmit(field, input);
        });

        setTimeout(() => input.focus(), 150);
    }

    _renderChatPhoneInput(field) {
        const container = document.createElement('div');
        container.className = 'lf-input-container';

        const defaultCountry = field.settings?.country_code || '+55';

        container.innerHTML = `
            <div class="lf-phone-wrapper">
                <div class="lf-country-select" id="lf-country-select">
                    <span class="lf-country-flag" id="lf-country-flag">&#127463;&#127479;</span>
                    <span class="lf-country-code" id="lf-country-code">${this._escapeHtml(defaultCountry)}</span>
                </div>
                <input type="tel" id="lf-current-input" placeholder="${this._escapeHtml(field.placeholder || '(00) 00000-0000')}" autocomplete="off">
            </div>
            <button class="lf-send-btn" id="lf-send-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9"/>
                </svg>
            </button>
        `;

        this.inputAreaEl.appendChild(container);

        const input = container.querySelector('#lf-current-input');
        const sendBtn = container.querySelector('#lf-send-btn');

        this._applyPhoneMask(input);

        sendBtn.addEventListener('click', () => {
            const code = container.querySelector('#lf-country-code').textContent;
            const phone = input.value.trim();
            if (phone) {
                input.value = code + ' ' + phone;
            }
            this._handleChatSubmit(field, input);
        });

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const code = container.querySelector('#lf-country-code').textContent;
                const phone = input.value.trim();
                if (phone) {
                    input.value = code + ' ' + phone;
                }
                this._handleChatSubmit(field, input);
            }
        });

        setTimeout(() => input.focus(), 150);
    }

    _renderChatOptions(field) {
        const container = document.createElement('div');
        container.className = 'lf-options';

        const options = field.options || field.settings?.options || [];

        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'lf-option-btn';
            btn.textContent = typeof opt === 'string' ? opt : opt.label;

            btn.addEventListener('click', () => {
                const value = typeof opt === 'string' ? opt : (opt.value || opt.label);
                this._handleChatOptionSelect(field, value, btn.textContent);
            });

            container.appendChild(btn);
        });

        this.inputAreaEl.appendChild(container);
    }

    _renderChatCheckboxOptions(field) {
        const container = document.createElement('div');
        container.className = 'lf-options';
        container.style.flexDirection = 'column';
        container.style.alignItems = 'center';

        const options = field.options || field.settings?.options || [];
        const selected = new Set();

        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'lf-option-btn';
            const label = typeof opt === 'string' ? opt : opt.label;
            btn.textContent = label;

            btn.addEventListener('click', () => {
                const value = typeof opt === 'string' ? opt : (opt.value || opt.label);
                if (selected.has(value)) {
                    selected.delete(value);
                    btn.classList.remove('selected');
                } else {
                    selected.add(value);
                    btn.classList.add('selected');
                }
            });

            container.appendChild(btn);
        });

        // Confirm button
        const confirmBtn = document.createElement('button');
        confirmBtn.className = 'lf-send-btn';
        confirmBtn.style.marginTop = '12px';
        confirmBtn.innerHTML = '&#10003;';
        confirmBtn.addEventListener('click', () => {
            if (selected.size === 0 && field.required) {
                this._showFieldError('Selecione pelo menos uma opção');
                return;
            }
            const values = Array.from(selected);
            this.formData[field.id || field.field] = values;
            this._addChatMessage(values.join(', '), 'user');
            this.inputAreaEl.innerHTML = '';
            this._recordFieldAnalytics(field);
            this.fieldHistory.push(this.currentStep);
            this.currentStep++;
            setTimeout(() => this._showStep(this.currentStep), 400);
        });

        container.appendChild(confirmBtn);
        this.inputAreaEl.appendChild(container);
    }

    _renderChatRating(field) {
        const container = document.createElement('div');
        container.className = 'lf-rating lf-rating-stars';

        const max = field.settings?.rating_max || 5;
        const icon = field.settings?.rating_icon || 'star';
        const icons = { star: ['&#9734;', '&#9733;'], heart: ['&#9825;', '&#9829;'], thumb: ['&#128077;', '&#128077;'] };
        const [empty, filled] = icons[icon] || icons.star;

        let selectedRating = 0;

        for (let i = 1; i <= max; i++) {
            const item = document.createElement('div');
            item.className = 'lf-rating-item';
            item.innerHTML = empty;
            item.dataset.value = i;

            item.addEventListener('click', () => {
                selectedRating = i;
                container.querySelectorAll('.lf-rating-item').forEach((el, idx) => {
                    if (idx < i) {
                        el.classList.add('active');
                        el.innerHTML = filled;
                    } else {
                        el.classList.remove('active');
                        el.innerHTML = empty;
                    }
                });

                // Auto-submit after a short delay
                setTimeout(() => {
                    this.formData[field.id || field.field] = selectedRating;
                    this._addChatMessage(`${selectedRating}/${max} ${filled}`, 'user');
                    this.inputAreaEl.innerHTML = '';
                    this._recordFieldAnalytics(field);
                    this.fieldHistory.push(this.currentStep);
                    this.currentStep++;
                    setTimeout(() => this._showStep(this.currentStep), 400);
                }, 500);
            });

            container.appendChild(item);
        }

        this.inputAreaEl.appendChild(container);
    }

    _renderChatOpinionScale(field) {
        const container = document.createElement('div');
        container.className = 'lf-rating';

        const min = field.settings?.opinion_min || 1;
        const max = field.settings?.opinion_max || 10;

        for (let i = min; i <= max; i++) {
            const item = document.createElement('div');
            item.className = 'lf-rating-item';
            item.textContent = i;
            item.dataset.value = i;

            item.addEventListener('click', () => {
                container.querySelectorAll('.lf-rating-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');

                setTimeout(() => {
                    this.formData[field.id || field.field] = i;
                    this._addChatMessage(String(i), 'user');
                    this.inputAreaEl.innerHTML = '';
                    this._recordFieldAnalytics(field);
                    this.fieldHistory.push(this.currentStep);
                    this.currentStep++;
                    setTimeout(() => this._showStep(this.currentStep), 400);
                }, 400);
            });

            container.appendChild(item);
        }

        this.inputAreaEl.appendChild(container);
    }

    _renderChatFileUpload(field) {
        const container = document.createElement('div');
        container.className = 'lf-file-upload';

        const maxSize = field.settings?.file_max_size || 10;
        const allowedTypes = field.settings?.file_allowed_types || 'jpg,jpeg,png,pdf,doc,docx';

        container.innerHTML = `
            <div class="lf-file-upload-icon">&#128206;</div>
            <div class="lf-file-upload-text">
                Clique ou arraste para <strong>enviar arquivo</strong><br>
                <small>Max: ${maxSize}MB | Tipos: ${this._escapeHtml(allowedTypes)}</small>
            </div>
            <input type="file" id="lf-file-input" style="display:none"
                   accept="${allowedTypes.split(',').map(t => '.' + t.trim()).join(',')}"
                   ${field.settings?.multiple ? 'multiple' : ''}>
            <div class="lf-file-list" id="lf-file-list"></div>
        `;

        this.inputAreaEl.appendChild(container);

        const fileInput = container.querySelector('#lf-file-input');
        const fileList = container.querySelector('#lf-file-list');

        container.addEventListener('click', (e) => {
            if (e.target.closest('.lf-file-remove')) return;
            fileInput.click();
        });

        container.addEventListener('dragover', (e) => { e.preventDefault(); container.style.borderColor = 'rgba(79, 70, 229, 0.6)'; });
        container.addEventListener('dragleave', () => { container.style.borderColor = ''; });
        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.style.borderColor = '';
            this._handleFileSelect(field, e.dataTransfer.files, fileList);
        });

        fileInput.addEventListener('change', () => {
            this._handleFileSelect(field, fileInput.files, fileList);
        });
    }

    _renderChatPictureChoice(field) {
        const container = document.createElement('div');
        container.className = 'lf-options';
        container.style.gap = '12px';

        const options = field.options || field.settings?.options || [];

        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'lf-option-btn';
            btn.style.cssText = 'display:flex;flex-direction:column;align-items:center;padding:12px;min-width:120px;';

            const imgUrl = typeof opt === 'object' ? opt.image_url : '';
            const label = typeof opt === 'object' ? opt.label : opt;

            btn.innerHTML = `
                ${imgUrl ? `<img src="${this._escapeHtml(imgUrl)}" style="width:80px;height:60px;object-fit:cover;border-radius:8px;margin-bottom:8px">` : ''}
                <span>${this._escapeHtml(label)}</span>
            `;

            btn.addEventListener('click', () => {
                const value = typeof opt === 'object' ? (opt.value || opt.label) : opt;
                this._handleChatOptionSelect(field, value, label);
            });

            container.appendChild(btn);
        });

        this.inputAreaEl.appendChild(container);
    }

    _handleChatSubmit(field, input) {
        const value = input.value.trim();

        if (!value && !field.optional && field.required !== false) {
            this._showFieldError('Este campo é obrigatório');
            input.classList.add('lf-input-error');
            setTimeout(() => input.classList.remove('lf-input-error'), 1500);
            return;
        }

        // Validate
        const error = this._validateField(field, value);
        if (error) {
            this._showFieldError(error);
            input.classList.add('lf-input-error');
            setTimeout(() => input.classList.remove('lf-input-error'), 1500);
            return;
        }

        if (value) {
            const fieldKey = field.id || field.field;
            this.formData[fieldKey] = value;
            this._addChatMessage(field.type === 'password' ? '••••••' : value, 'user');
        }

        this.inputAreaEl.innerHTML = '';
        this._recordFieldAnalytics(field);
        this.fieldHistory.push(this.currentStep);
        this.currentStep++;

        setTimeout(() => this._showStep(this.currentStep), 400);

        // Callback
        if (this.config.onFieldAnswer) {
            this.config.onFieldAnswer(field, value, this.formData);
        }
    }

    _handleChatOptionSelect(field, value, displayText) {
        this.formData[field.id || field.field] = value;
        this._addChatMessage(displayText, 'user');

        this.inputAreaEl.innerHTML = '';
        this._recordFieldAnalytics(field);
        this.fieldHistory.push(this.currentStep);
        this.currentStep++;

        setTimeout(() => this._showStep(this.currentStep), 400);

        if (this.config.onFieldAnswer) {
            this.config.onFieldAnswer(field, value, this.formData);
        }
    }

    _showTyping() {
        if (this.typingEl) {
            this.typingEl.classList.add('active');
            this.isTyping = true;
        }
    }

    _hideTyping() {
        if (this.typingEl) {
            this.typingEl.classList.remove('active');
            this.isTyping = false;
        }
    }

    // ==========================================
    // TYPEFORM MODE RENDERING
    // ==========================================

    _showTypeformStep(field) {
        const el = this.typeformQuestionEl;

        // Exit animation
        el.classList.remove('active');
        el.classList.add('exit-up');

        setTimeout(() => {
            el.classList.remove('exit-up');
            el.innerHTML = '';

            const totalFields = this.config.fields.length;
            const questionNum = this.currentStep + 1;

            const message = this._resolveMessage(field);
            const description = field.description || '';

            el.innerHTML = `
                <div class="lf-typeform-number">
                    <span class="arrow">&#10132;</span> ${questionNum}
                </div>
                <div class="lf-typeform-label">${message}</div>
                ${description ? `<div class="lf-typeform-description">${this._escapeHtml(description)}</div>` : ''}
                <div class="lf-typeform-input-area" id="lf-tf-input-area"></div>
                <div class="lf-typeform-actions" id="lf-tf-actions">
                    <button class="lf-typeform-ok-btn" id="lf-tf-ok-btn">
                        OK <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20,6 9,17 4,12"/></svg>
                    </button>
                    <span class="lf-typeform-hint">pressione <kbd>Enter</kbd></span>
                </div>
            `;

            const inputArea = el.querySelector('#lf-tf-input-area');
            const okBtn = el.querySelector('#lf-tf-ok-btn');

            this._renderTypeformInput(field, inputArea);

            okBtn.addEventListener('click', () => this._submitCurrentStep());

            // Update nav buttons
            if (this.navPrevBtn) {
                this.navPrevBtn.disabled = this.fieldHistory.length === 0;
            }

            // Animate in
            requestAnimationFrame(() => {
                el.classList.add('active');
            });

        }, this.config.settings.animations ? 300 : 0);
    }

    _renderTypeformInput(field, container) {
        switch (field.type) {
            case 'radio':
            case 'select':
                this._renderTypeformOptions(field, container, false);
                break;
            case 'checkbox':
                this._renderTypeformOptions(field, container, true);
                break;
            case 'rating':
                this._renderTypeformRating(field, container);
                break;
            case 'opinion_scale':
                this._renderTypeformOpinionScale(field, container);
                break;
            case 'file_upload':
                this._renderTypeformFileUpload(field, container);
                break;
            default:
                this._renderTypeformTextInput(field, container);
                break;
        }
    }

    _renderTypeformTextInput(field, container) {
        let inputType = 'text';
        let placeholder = field.placeholder || 'Digite sua resposta aqui...';

        switch (field.type) {
            case 'email': inputType = 'email'; placeholder = field.placeholder || 'nome@example.com'; break;
            case 'phone': inputType = 'tel'; placeholder = field.placeholder || '(00) 00000-0000'; break;
            case 'url': inputType = 'url'; placeholder = field.placeholder || 'https://'; break;
            case 'password': inputType = 'password'; break;
            case 'textarea':
                container.innerHTML = `<textarea class="lf-typeform-textarea" id="lf-tf-input" placeholder="${this._escapeHtml(placeholder)}" rows="2"></textarea>`;
                setTimeout(() => container.querySelector('#lf-tf-input').focus(), 400);
                return;
        }

        container.innerHTML = `<input type="${inputType}" class="lf-typeform-input" id="lf-tf-input" placeholder="${this._escapeHtml(placeholder)}" autocomplete="off">`;

        const input = container.querySelector('#lf-tf-input');

        if (field.type === 'cpf_cnpj') this._applyCpfCnpjMask(input);
        if (field.type === 'phone') this._applyPhoneMask(input);

        setTimeout(() => input.focus(), 400);
    }

    _renderTypeformOptions(field, container, isCheckbox) {
        const wrapper = document.createElement('div');
        wrapper.className = 'lf-typeform-options';

        const options = field.options || field.settings?.options || [];
        const selected = new Set();
        const keys = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        options.forEach((opt, idx) => {
            const label = typeof opt === 'string' ? opt : opt.label;
            const value = typeof opt === 'string' ? opt : (opt.value || opt.label);
            const key = keys[idx] || '';

            const optEl = document.createElement('button');
            optEl.className = 'lf-typeform-option';
            optEl.dataset.value = value;

            optEl.innerHTML = `
                ${isCheckbox
                    ? '<div class="check-indicator"></div>'
                    : `<span class="lf-typeform-option-key">${key}</span>`
                }
                <span>${this._escapeHtml(label)}</span>
            `;

            optEl.addEventListener('click', () => {
                if (isCheckbox) {
                    if (selected.has(value)) {
                        selected.delete(value);
                        optEl.classList.remove('selected');
                    } else {
                        selected.add(value);
                        optEl.classList.add('selected');
                    }
                } else {
                    // Radio: single select
                    wrapper.querySelectorAll('.lf-typeform-option').forEach(el => el.classList.remove('selected'));
                    optEl.classList.add('selected');
                    selected.clear();
                    selected.add(value);

                    // Auto-advance for single select
                    setTimeout(() => this._submitCurrentStep(), 400);
                }
            });

            wrapper.appendChild(optEl);
        });

        container.appendChild(wrapper);

        // Store selected reference for retrieval
        container._selectedValues = selected;
        container._isCheckbox = isCheckbox;
    }

    _renderTypeformRating(field, container) {
        const wrapper = document.createElement('div');
        wrapper.className = 'lf-rating lf-rating-stars';
        wrapper.style.justifyContent = 'flex-start';

        const max = field.settings?.rating_max || 5;
        let selectedRating = 0;

        for (let i = 1; i <= max; i++) {
            const item = document.createElement('div');
            item.className = 'lf-rating-item';
            item.innerHTML = '&#9734;';
            item.dataset.value = i;

            item.addEventListener('click', () => {
                selectedRating = i;
                wrapper.querySelectorAll('.lf-rating-item').forEach((el, idx) => {
                    el.classList.toggle('active', idx < i);
                    el.innerHTML = idx < i ? '&#9733;' : '&#9734;';
                });
                container._ratingValue = i;
            });

            wrapper.appendChild(item);
        }

        container.appendChild(wrapper);
        container._ratingValue = 0;
    }

    _renderTypeformOpinionScale(field, container) {
        const wrapper = document.createElement('div');
        wrapper.className = 'lf-rating';

        const min = field.settings?.opinion_min || 1;
        const max = field.settings?.opinion_max || 10;

        for (let i = min; i <= max; i++) {
            const item = document.createElement('div');
            item.className = 'lf-rating-item';
            item.textContent = i;

            item.addEventListener('click', () => {
                wrapper.querySelectorAll('.lf-rating-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                container._opinionValue = i;
            });

            wrapper.appendChild(item);
        }

        container.appendChild(wrapper);
        container._opinionValue = null;
    }

    _renderTypeformFileUpload(field, container) {
        // Reuse chat file upload but adapt styling
        this._renderChatFileUpload(field);
        // Move from inputArea to this container
        const fileEl = this.inputAreaEl?.firstChild;
        if (fileEl) {
            container.appendChild(fileEl);
        }
    }

    _submitCurrentStep() {
        const field = this.config.fields[this.currentStep];
        if (!field) return;

        const fieldKey = field.id || field.field;
        let value = null;
        let displayValue = '';

        if (this.config.mode === 'typeform') {
            const inputArea = document.getElementById('lf-tf-input-area');
            const textInput = document.getElementById('lf-tf-input');

            if (textInput) {
                value = textInput.value.trim();
                displayValue = field.type === 'password' ? '••••••' : value;
            } else if (inputArea._selectedValues) {
                value = inputArea._isCheckbox ? Array.from(inputArea._selectedValues) : inputArea._selectedValues.values().next().value;
                displayValue = Array.isArray(value) ? value.join(', ') : value;
            } else if (inputArea._ratingValue !== undefined) {
                value = inputArea._ratingValue;
                displayValue = String(value);
            } else if (inputArea._opinionValue !== undefined) {
                value = inputArea._opinionValue;
                displayValue = String(value);
            }
        }

        // Validate required
        if ((!value || (Array.isArray(value) && value.length === 0)) && !field.optional && field.required !== false) {
            this._showFieldError('Este campo é obrigatório');
            return;
        }

        // Validate field
        if (value) {
            const error = this._validateField(field, value);
            if (error) {
                this._showFieldError(error);
                return;
            }
        }

        // Store value
        if (value !== null && value !== '') {
            this.formData[fieldKey] = value;
        }

        // Clear error
        this._clearFieldError();

        this._recordFieldAnalytics(field);
        this.fieldHistory.push(this.currentStep);
        this.currentStep++;

        if (this.config.onFieldAnswer) {
            this.config.onFieldAnswer(field, value, this.formData);
        }

        this._showStep(this.currentStep);
    }

    // ==========================================
    // NAVIGATION
    // ==========================================

    _goBack() {
        if (this.fieldHistory.length === 0) return;

        const prevStep = this.fieldHistory.pop();
        this.currentStep = prevStep;

        // Remove the last user message in conversation mode
        if (this.config.mode === 'conversation') {
            const messages = this.messagesEl.querySelectorAll('.lf-message');
            // Remove last 2 messages (bot question + user answer)
            const toRemove = [];
            for (let i = messages.length - 1; i >= 0 && toRemove.length < 2; i--) {
                toRemove.push(messages[i]);
            }
            toRemove.forEach(m => m.remove());
        }

        this._showStep(this.currentStep);
    }

    // ==========================================
    // VALIDATION
    // ==========================================

    _validateField(field, value) {
        if (!value && (field.optional || field.required === false)) return null;

        switch (field.type) {
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    return 'Por favor, insira um e-mail válido';
                }
                break;
            case 'phone':
                if (value.replace(/\D/g, '').length < 10) {
                    return 'Por favor, insira um telefone válido';
                }
                break;
            case 'url':
                try { new URL(value); } catch {
                    return 'Por favor, insira uma URL válida';
                }
                break;
            case 'cpf_cnpj':
                const digits = value.replace(/\D/g, '');
                if (digits.length === 11 && !this._validateCPF(digits)) {
                    return 'CPF inválido';
                }
                if (digits.length === 14 && !this._validateCNPJ(digits)) {
                    return 'CNPJ inválido';
                }
                if (digits.length !== 11 && digits.length !== 14) {
                    return 'Insira um CPF ou CNPJ válido';
                }
                break;
        }

        // Custom validation from settings
        if (field.settings?.validation_rules) {
            const rules = field.settings.validation_rules;
            if (rules.min && value.length < rules.min) {
                return `Mínimo de ${rules.min} caracteres`;
            }
            if (rules.max && value.length > rules.max) {
                return `Máximo de ${rules.max} caracteres`;
            }
            if (rules.pattern && !new RegExp(rules.pattern).test(value)) {
                return rules.patternMessage || 'Formato inválido';
            }
        }

        return null;
    }

    _validateCPF(cpf) {
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        let sum = 0;
        for (let i = 0; i < 9; i++) sum += parseInt(cpf[i]) * (10 - i);
        let check = 11 - (sum % 11);
        if (check >= 10) check = 0;
        if (parseInt(cpf[9]) !== check) return false;
        sum = 0;
        for (let i = 0; i < 10; i++) sum += parseInt(cpf[i]) * (11 - i);
        check = 11 - (sum % 11);
        if (check >= 10) check = 0;
        return parseInt(cpf[10]) === check;
    }

    _validateCNPJ(cnpj) {
        if (/^(\d)\1{13}$/.test(cnpj)) return false;
        const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        let sum = 0;
        for (let i = 0; i < 12; i++) sum += parseInt(cnpj[i]) * weights1[i];
        let check = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        if (parseInt(cnpj[12]) !== check) return false;
        sum = 0;
        for (let i = 0; i < 13; i++) sum += parseInt(cnpj[i]) * weights2[i];
        check = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        return parseInt(cnpj[13]) === check;
    }

    _showFieldError(message) {
        this._clearFieldError();
        const errorEl = document.createElement('div');
        errorEl.className = 'lf-field-error';
        errorEl.id = 'lf-field-error';
        errorEl.textContent = message;

        if (this.config.mode === 'conversation') {
            this.inputAreaEl.appendChild(errorEl);
        } else {
            const actions = document.getElementById('lf-tf-actions');
            if (actions) {
                actions.parentNode.insertBefore(errorEl, actions);
            }
        }
    }

    _clearFieldError() {
        const existing = document.getElementById('lf-field-error');
        if (existing) existing.remove();
    }

    // ==========================================
    // MASKS
    // ==========================================

    _applyPhoneMask(input) {
        input.addEventListener('input', () => {
            let val = input.value.replace(/\D/g, '');
            if (val.length > 11) val = val.substring(0, 11);
            if (val.length > 6) {
                input.value = `(${val.substring(0, 2)}) ${val.substring(2, 7)}-${val.substring(7)}`;
            } else if (val.length > 2) {
                input.value = `(${val.substring(0, 2)}) ${val.substring(2)}`;
            } else if (val.length > 0) {
                input.value = `(${val}`;
            }
        });
    }

    _applyCpfCnpjMask(input) {
        input.addEventListener('input', () => {
            let val = input.value.replace(/\D/g, '');
            if (val.length > 14) val = val.substring(0, 14);

            if (val.length <= 11) {
                // CPF mask: 000.000.000-00
                if (val.length > 9) {
                    input.value = `${val.substring(0, 3)}.${val.substring(3, 6)}.${val.substring(6, 9)}-${val.substring(9)}`;
                } else if (val.length > 6) {
                    input.value = `${val.substring(0, 3)}.${val.substring(3, 6)}.${val.substring(6)}`;
                } else if (val.length > 3) {
                    input.value = `${val.substring(0, 3)}.${val.substring(3)}`;
                } else {
                    input.value = val;
                }
            } else {
                // CNPJ mask: 00.000.000/0000-00
                if (val.length > 12) {
                    input.value = `${val.substring(0, 2)}.${val.substring(2, 5)}.${val.substring(5, 8)}/${val.substring(8, 12)}-${val.substring(12)}`;
                } else if (val.length > 8) {
                    input.value = `${val.substring(0, 2)}.${val.substring(2, 5)}.${val.substring(5, 8)}/${val.substring(8)}`;
                }
            }
        });
    }

    // ==========================================
    // MESSAGE RESOLUTION
    // ==========================================

    _resolveMessage(field) {
        if (typeof field.message === 'function') {
            return field.message(this.formData);
        }
        if (typeof field.message === 'string') {
            // Replace {{field_name}} placeholders with form data
            return field.message.replace(/\{\{(\w+)\}\}/g, (match, key) => {
                return this._escapeHtml(this.formData[key] || match);
            });
        }
        return field.label || '';
    }

    // ==========================================
    // CONDITIONAL LOGIC
    // ==========================================

    _evaluateCondition(logic) {
        if (!logic || !logic.rules) return true;

        const results = logic.rules.map(rule => {
            const fieldValue = this.formData[rule.field];
            switch (rule.operator) {
                case 'equals': return fieldValue == rule.value;
                case 'not_equals': return fieldValue != rule.value;
                case 'contains': return String(fieldValue || '').includes(rule.value);
                case 'not_contains': return !String(fieldValue || '').includes(rule.value);
                case 'gt': return Number(fieldValue) > Number(rule.value);
                case 'lt': return Number(fieldValue) < Number(rule.value);
                case 'is_set': return fieldValue !== undefined && fieldValue !== null && fieldValue !== '';
                case 'is_not_set': return !fieldValue;
                default: return true;
            }
        });

        if (logic.match === 'all') {
            return results.every(r => r);
        }
        return results.some(r => r);
    }

    // ==========================================
    // PROGRESS
    // ==========================================

    _updateProgress() {
        const total = this.config.fields.length;
        const current = this.currentStep + 1;
        const percent = Math.min((current / total) * 100, 100);

        const fill = document.getElementById('lf-progress-fill');
        if (fill) fill.style.width = percent + '%';

        const text = document.getElementById('lf-progress-text');
        if (text) text.textContent = `${current} de ${total}`;
    }

    // ==========================================
    // COMPLETION
    // ==========================================

    async _finish() {
        this.isSubmitting = true;

        // Calculate duration
        const duration = Math.floor((Date.now() - this.startTime) / 1000);

        // Prepare payload
        const payload = {
            form_id: this.config.formId,
            form_slug: this.config.formSlug,
            data: this.formData,
            hidden_fields: this.config.hiddenFields,
            utm: this.config.utm,
            duration: duration,
            analytics: this.analytics,
            entry_token: this.entryToken,
            device: this._getDeviceInfo()
        };

        // Submit
        try {
            const submitUrl = this.config.submitUrl || `/f/${this.config.formSlug}/submit`;

            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Erro ao enviar formulário');
            }

            this.entryToken = result.token;

        } catch (error) {
            console.error('LeadForm submit error:', error);
            if (this.config.onError) {
                this.config.onError(error);
            }
        }

        // Show completion
        const completion = this.config.settings.completion;

        switch (completion.type) {
            case 'redirect':
                if (completion.redirectUrl) {
                    window.location.href = completion.redirectUrl;
                    return;
                }
                break;
            case 'subform':
                if (completion.subformSlug) {
                    window.location.href = `/f/${completion.subformSlug}?prefill=${encodeURIComponent(JSON.stringify(this.formData))}`;
                    return;
                }
                break;
        }

        // Hide form, show completion
        if (this.config.mode === 'conversation') {
            const conv = document.getElementById('lf-conversation');
            if (conv) conv.style.display = 'none';
        } else {
            const tf = document.getElementById('lf-typeform');
            if (tf) tf.style.display = 'none';
            const nav = document.querySelector('.lf-typeform-nav');
            if (nav) nav.style.display = 'none';
        }

        // Update progress to 100%
        const fill = document.getElementById('lf-progress-fill');
        if (fill) fill.style.width = '100%';

        if (this.completionEl) {
            this.completionEl.classList.add('active');
        }

        // Callback
        if (this.config.onComplete) {
            this.config.onComplete(this.formData, this.entryToken);
        }

        this.isSubmitting = false;
    }

    // ==========================================
    // SAVE AND CONTINUE
    // ==========================================

    async _saveAndContinue() {
        const payload = {
            form_id: this.config.formId,
            form_slug: this.config.formSlug,
            data: this.formData,
            current_step: this.currentStep,
            entry_token: this.entryToken,
            status: 'partial'
        };

        try {
            const url = this.config.partialSaveUrl || `/f/${this.config.formSlug}/save-partial`;
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            this.entryToken = result.token;

            // Show save confirmation
            this._showToast('Progresso salvo! Você pode continuar depois usando o link enviado.');

            if (this.config.onPartialSave) {
                this.config.onPartialSave(result);
            }
        } catch (error) {
            console.error('Save error:', error);
            this._showToast('Erro ao salvar. Tente novamente.', 'error');
        }
    }

    async _autoSavePartial() {
        const payload = {
            form_id: this.config.formId,
            form_slug: this.config.formSlug,
            data: this.formData,
            current_step: this.currentStep,
            entry_token: this.entryToken,
            status: 'partial'
        };

        try {
            const url = this.config.partialSaveUrl || `/f/${this.config.formSlug}/save-partial`;
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            this.entryToken = result.token;
        } catch (error) {
            console.error('Auto-save error:', error);
        }
    }

    // ==========================================
    // FILE HANDLING
    // ==========================================

    _handleFileSelect(field, files, listEl) {
        const maxSize = (field.settings?.file_max_size || 10) * 1024 * 1024; // MB to bytes

        Array.from(files).forEach(file => {
            if (file.size > maxSize) {
                this._showToast(`Arquivo "${file.name}" excede o tamanho máximo`, 'error');
                return;
            }

            const item = document.createElement('div');
            item.className = 'lf-file-item';
            item.innerHTML = `
                <span>&#128196; ${this._escapeHtml(file.name)}</span>
                <small>(${this._formatBytes(file.size)})</small>
                <button class="lf-file-remove">&times;</button>
            `;

            item.querySelector('.lf-file-remove').addEventListener('click', (e) => {
                e.stopPropagation();
                item.remove();
            });

            listEl.appendChild(item);
        });

        // Store files in formData
        const fieldKey = field.id || field.field;
        this.formData[fieldKey] = files;
    }

    // ==========================================
    // ANALYTICS
    // ==========================================

    _recordFieldAnalytics(field) {
        const startTime = this.fieldStartTimes[field.id] || Date.now();
        const duration = Math.floor((Date.now() - startTime) / 1000);

        this.analytics.push({
            field_id: field.id,
            field_type: field.type,
            duration_seconds: duration,
            timestamp: new Date().toISOString()
        });
    }

    _trackVisit() {
        // This would normally send to the backend
        // For now, store device info
        this._deviceInfo = this._getDeviceInfo();
    }

    _getDeviceInfo() {
        const ua = navigator.userAgent;
        return {
            user_agent: ua,
            screen_width: screen.width,
            screen_height: screen.height,
            device_type: /Mobile|Android|iPhone/i.test(ua) ? 'mobile' : (/Tablet|iPad/i.test(ua) ? 'tablet' : 'desktop'),
            referrer: document.referrer,
            language: navigator.language
        };
    }

    // ==========================================
    // ACCESS CHECKS
    // ==========================================

    _checkAccess() {
        // Check expiry
        if (this.config.settings.expiry) {
            const expiryDate = new Date(this.config.settings.expiry);
            if (new Date() > expiryDate) {
                this._showAccessDenied('Este formulário expirou.');
                return false;
            }
        }

        // Unique link check would be server-side
        return true;
    }

    _showAccessDenied(message) {
        this.container.innerHTML = `
            <div class="leadform" style="height:100vh;display:flex;justify-content:center;align-items:center;background:var(--lf-bg);">
                <div style="text-align:center;color:#fff;padding:40px;">
                    <div style="font-size:48px;margin-bottom:20px;">&#128274;</div>
                    <h2 style="margin-bottom:10px;">${this._escapeHtml(message)}</h2>
                </div>
            </div>
        `;
    }

    // ==========================================
    // KEYBOARD
    // ==========================================

    _setupKeyboard() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                if (this.config.mode === 'typeform') {
                    const input = document.getElementById('lf-tf-input');
                    if (input && input.tagName === 'TEXTAREA') return; // Allow Enter in textarea
                    e.preventDefault();
                    this._submitCurrentStep();
                }
            }

            // Arrow keys for typeform navigation
            if (this.config.mode === 'typeform') {
                if (e.key === 'ArrowUp' && e.ctrlKey) {
                    e.preventDefault();
                    this._goBack();
                }
            }

            // Keyboard shortcuts for options (A, B, C, D...)
            if (this.config.mode === 'typeform') {
                const key = e.key.toUpperCase();
                if (/^[A-Z]$/.test(key) && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    const activeInput = document.activeElement;
                    if (activeInput && (activeInput.tagName === 'INPUT' || activeInput.tagName === 'TEXTAREA')) return;

                    const options = document.querySelectorAll('.lf-typeform-option');
                    const idx = key.charCodeAt(0) - 65;
                    if (idx < options.length) {
                        options[idx].click();
                    }
                }
            }
        });
    }

    // ==========================================
    // URL PARAMS
    // ==========================================

    _parseUrlParams() {
        const params = new URLSearchParams(window.location.search);

        // UTM params
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(key => {
            if (params.has(key)) {
                this.config.utm[key.replace('utm_', '')] = params.get(key);
            }
        });

        // Hidden fields
        params.forEach((value, key) => {
            if (!key.startsWith('utm_') && key !== 'prefill') {
                this.config.hiddenFields[key] = value;
            }
        });

        // Prefill data from subform redirect
        if (params.has('prefill')) {
            try {
                const prefill = JSON.parse(params.get('prefill'));
                Object.assign(this.config.prefillData, prefill);
            } catch (e) {}
        }

        // Continue token
        if (params.has('token')) {
            this.entryToken = params.get('token');
            // Would load saved data from server
        }
    }

    // ==========================================
    // UTILITIES
    // ==========================================

    _escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    _formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    _showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:10000;
            padding:12px 24px;border-radius:12px;font-size:14px;font-weight:500;
            backdrop-filter:blur(10px);box-shadow:0 8px 32px rgba(0,0,0,0.3);
            animation:lf-fadeIn 0.4s ease;
            background:${type === 'error' ? 'rgba(239,68,68,0.95)' : 'rgba(16,185,129,0.95)'};
            color:#fff;font-family:var(--lf-font);
        `;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ==========================================
    // PUBLIC API
    // ==========================================

    getData() { return { ...this.formData }; }
    getCurrentStep() { return this.currentStep; }
    getTotalSteps() { return this.config.fields.length; }
    getEntryToken() { return this.entryToken; }

    goToStep(index) {
        if (index >= 0 && index < this.config.fields.length) {
            this.currentStep = index;
            this._showStep(index);
        }
    }

    setFieldValue(fieldId, value) {
        this.formData[fieldId] = value;
    }

    destroy() {
        if (this.container) {
            this.container.innerHTML = '';
            this.container.classList.remove('leadform');
        }
    }
}

// ==========================================
// EXPORT
// ==========================================
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LeadFormRenderer;
}

window.LeadFormRenderer = LeadFormRenderer;
