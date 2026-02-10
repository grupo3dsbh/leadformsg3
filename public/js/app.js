/**
 * LeadForm SaaS - Main Application JavaScript
 * Global utilities, components, and interactions
 */

// ==========================================
// GLOBAL APP NAMESPACE
// ==========================================

const LeadFormApp = {
    // ==========================================
    // INITIALIZATION
    // ==========================================

    init() {
        this.initDropdowns();
        this.initModals();
        this.initToasts();
        this.initScrollAnimations();
        this.initMobileMenu();
        this.initStickyHeader();
        this.initDevErrorBar();
        this.initTooltips();
        this.initConfirmDialogs();
    },

    // ==========================================
    // DROPDOWN MENUS
    // ==========================================

    initDropdowns() {
        document.addEventListener('click', (e) => {
            const toggle = e.target.closest('[data-dropdown]');

            // Close all dropdowns
            document.querySelectorAll('.dropdown.open').forEach(d => {
                if (!toggle || !d.contains(toggle)) {
                    d.classList.remove('open');
                }
            });

            if (toggle) {
                e.preventDefault();
                const dropdown = toggle.closest('.dropdown');
                if (dropdown) dropdown.classList.toggle('open');
            }
        });
    },

    // ==========================================
    // MODALS
    // ==========================================

    initModals() {
        document.addEventListener('click', (e) => {
            const openBtn = e.target.closest('[data-modal-open]');
            const closeBtn = e.target.closest('[data-modal-close]');

            if (openBtn) {
                const modalId = openBtn.dataset.modalOpen;
                this.openModal(modalId);
            }

            if (closeBtn) {
                const modal = closeBtn.closest('.modal-backdrop');
                if (modal) this.closeModal(modal.id);
            }

            // Close on backdrop click
            if (e.target.classList.contains('modal-backdrop') && e.target.classList.contains('active')) {
                this.closeModal(e.target.id);
            }
        });

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.active').forEach(m => {
                    this.closeModal(m.id);
                });
            }
        });
    },

    openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // ==========================================
    // TOAST NOTIFICATIONS
    // ==========================================

    initToasts() {
        // Auto-dismiss existing toasts
        document.querySelectorAll('.toast').forEach(toast => {
            setTimeout(() => this.dismissToast(toast), 5000);
        });
    },

    toast(message, type = 'success', duration = 5000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: '&#10003;',
            error: '&#10007;',
            warning: '&#9888;',
            info: '&#8505;'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span style="font-size:18px;">${icons[type] || ''}</span>
            <span>${message}</span>
            <button class="toast-close" onclick="LeadFormApp.dismissToast(this.parentElement)">&times;</button>
        `;

        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => this.dismissToast(toast), duration);
        }

        return toast;
    },

    dismissToast(toast) {
        if (!toast || !toast.parentElement) return;
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    },

    // ==========================================
    // SCROLL ANIMATIONS
    // ==========================================

    initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('[data-animate]').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Add class for animated elements
        const style = document.createElement('style');
        style.textContent = '.animate-in { opacity: 1 !important; transform: translateY(0) !important; }';
        document.head.appendChild(style);
    },

    // ==========================================
    // MOBILE MENU
    // ==========================================

    initMobileMenu() {
        const toggle = document.getElementById('mobile-menu-toggle');
        const menu = document.getElementById('mobile-menu');

        if (toggle && menu) {
            toggle.addEventListener('click', () => {
                menu.classList.toggle('active');
                toggle.classList.toggle('active');
            });
        }
    },

    // ==========================================
    // STICKY HEADER
    // ==========================================

    initStickyHeader() {
        const header = document.querySelector('.site-header');
        if (!header) return;

        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        });
    },

    // ==========================================
    // DEV ERROR BAR
    // ==========================================

    initDevErrorBar() {
        const errorBar = document.getElementById('devErrorBar');
        if (!errorBar) return;

        // Capture JavaScript errors
        window.onerror = (msg, url, line, col, error) => {
            this.showDevError('JavaScript Error', msg, `${url}:${line}:${col}`, error?.stack);
            return false; // Don't suppress the error
        };

        // Capture unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.showDevError('Unhandled Promise', event.reason?.message || String(event.reason), '', event.reason?.stack);
        });

        // Capture fetch errors
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch(...args);
                if (!response.ok && response.status >= 500) {
                    const text = await response.clone().text();
                    this.showDevError(`HTTP ${response.status}`, text.substring(0, 500), args[0]);
                }
                return response;
            } catch (error) {
                this.showDevError('Network Error', error.message, args[0]);
                throw error;
            }
        };
    },

    showDevError(type, message, file, stack) {
        const bar = document.getElementById('devErrorBar');
        if (!bar) return;

        bar.classList.add('active');

        const body = bar.querySelector('.dev-error-body') || bar;
        const errorEntry = document.createElement('div');
        errorEntry.style.cssText = 'border-bottom:1px solid rgba(255,255,255,0.1);padding:12px 0;';
        errorEntry.innerHTML = `
            <div class="dev-error-message">[${this._escapeHtml(type)}] ${this._escapeHtml(message)}</div>
            ${file ? `<div class="dev-error-file">${this._escapeHtml(file)}</div>` : ''}
            ${stack ? `<div class="dev-error-trace">${this._escapeHtml(stack)}</div>` : ''}
        `;

        body.appendChild(errorEntry);
    },

    closeDevErrorBar() {
        const bar = document.getElementById('devErrorBar');
        if (bar) {
            bar.classList.remove('active');
            const body = bar.querySelector('.dev-error-body');
            if (body) body.innerHTML = '';
        }
    },

    // ==========================================
    // TOOLTIPS
    // ==========================================

    initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', () => {
                const tip = document.createElement('div');
                tip.className = 'tooltip';
                tip.textContent = el.dataset.tooltip;
                tip.style.cssText = `
                    position:absolute;background:var(--gray-900,#111);color:#fff;
                    padding:6px 12px;border-radius:6px;font-size:12px;white-space:nowrap;
                    z-index:9999;pointer-events:none;
                `;
                document.body.appendChild(tip);

                const rect = el.getBoundingClientRect();
                tip.style.left = (rect.left + rect.width / 2 - tip.offsetWidth / 2) + 'px';
                tip.style.top = (rect.top - tip.offsetHeight - 8 + window.scrollY) + 'px';

                el._tooltip = tip;
            });

            el.addEventListener('mouseleave', () => {
                if (el._tooltip) {
                    el._tooltip.remove();
                    el._tooltip = null;
                }
            });
        });
    },

    // ==========================================
    // CONFIRM DIALOGS
    // ==========================================

    initConfirmDialogs() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-confirm]');
            if (btn) {
                const message = btn.dataset.confirm || 'Tem certeza?';
                if (!confirm(message)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }
        });
    },

    // ==========================================
    // AJAX HELPERS
    // ==========================================

    async ajax(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        };

        // Add CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            defaultOptions.headers['X-CSRF-TOKEN'] = csrfMeta.content;
        }

        if (options.body && !(options.body instanceof FormData)) {
            defaultOptions.headers['Content-Type'] = 'application/json';
            if (typeof options.body === 'object') {
                options.body = JSON.stringify(options.body);
            }
        }

        const config = { ...defaultOptions, ...options, headers: { ...defaultOptions.headers, ...options.headers } };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw { response, data };
            }

            return data;
        } catch (error) {
            if (error.data?.message) {
                this.toast(error.data.message, 'error');
            }
            throw error;
        }
    },

    // ==========================================
    // FORM UTILITIES
    // ==========================================

    serializeForm(form) {
        const data = {};
        new FormData(form).forEach((value, key) => {
            if (data[key]) {
                if (!Array.isArray(data[key])) data[key] = [data[key]];
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        return data;
    },

    // ==========================================
    // UTILITIES
    // ==========================================

    _escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    },

    debounce(fn, delay = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    },

    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.toast('Copiado!', 'success', 2000);
        }).catch(() => {
            // Fallback
            const el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            this.toast('Copiado!', 'success', 2000);
        });
    },

    formatNumber(num) {
        return new Intl.NumberFormat('pt-BR').format(num);
    },

    formatCurrency(amount, currency = 'BRL') {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(amount);
    },

    formatDate(dateStr, options = {}) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            ...options
        });
    },

    timeAgo(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'agora';
        if (diff < 3600) return `${Math.floor(diff / 60)}min atrás`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d atrás`;
        return this.formatDate(dateStr);
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => LeadFormApp.init());

// Export globally
window.LeadFormApp = LeadFormApp;
