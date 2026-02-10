/**
 * LeadForm SaaS - Admin Panel JavaScript
 * Sidebar, topbar, and admin-specific interactions
 */

const LeadFormAdmin = {

    init() {
        this.initSidebar();
        this.initTopbar();
        this.initTabs();
        this.initToggleSwitches();
        this.initDataTables();
        this.initCharts();
        this.initBulkActions();
    },

    // ==========================================
    // SIDEBAR
    // ==========================================

    initSidebar() {
        // Toggle sidebar on mobile
        const toggle = document.querySelector('.topbar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                overlay?.classList.toggle('active');
            });

            overlay?.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }

        // Submenu toggles
        document.querySelectorAll('.sidebar-link[data-submenu]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const submenu = document.getElementById(link.dataset.submenu);
                if (submenu) {
                    submenu.classList.toggle('open');
                    link.classList.toggle('expanded');
                }
            });
        });

        // Active link highlighting
        const currentPath = window.location.pathname;
        document.querySelectorAll('.sidebar-link').forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath === href) {
                link.classList.add('active');
                // Expand parent submenu if inside one
                const parentSubmenu = link.closest('.sidebar-submenu');
                if (parentSubmenu) {
                    parentSubmenu.classList.add('open');
                }
            }
        });
    },

    // ==========================================
    // TOPBAR
    // ==========================================

    initTopbar() {
        // Search
        const searchInput = document.querySelector('.topbar-search input');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const query = searchInput.value.trim();
                    if (query) {
                        window.location.href = `?search=${encodeURIComponent(query)}`;
                    }
                }
            });
        }

        // Notifications panel
        const notifBtn = document.querySelector('.topbar-btn[data-notifications]');
        if (notifBtn) {
            notifBtn.addEventListener('click', () => {
                // Toggle notifications panel
                const panel = document.getElementById('notifications-panel');
                if (panel) panel.classList.toggle('active');
            });
        }
    },

    // ==========================================
    // TABS
    // ==========================================

    initTabs() {
        document.querySelectorAll('.tabs').forEach(tabGroup => {
            const tabs = tabGroup.querySelectorAll('.tab');
            const panels = tabGroup.parentElement.querySelectorAll('.tab-panel');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.tab;

                    tabs.forEach(t => t.classList.remove('active'));
                    panels.forEach(p => p.classList.remove('active'));

                    tab.classList.add('active');
                    const panel = document.getElementById(target);
                    if (panel) panel.classList.add('active');
                });
            });
        });
    },

    // ==========================================
    // TOGGLE SWITCHES
    // ==========================================

    initToggleSwitches() {
        document.querySelectorAll('.form-toggle input[data-toggle-url]').forEach(toggle => {
            toggle.addEventListener('change', async () => {
                const url = toggle.dataset.toggleUrl;
                const id = toggle.dataset.id;

                try {
                    await LeadFormApp.ajax(url, {
                        method: 'POST',
                        body: { id, enabled: toggle.checked }
                    });
                    LeadFormApp.toast('Atualizado com sucesso!');
                } catch (error) {
                    toggle.checked = !toggle.checked;
                    LeadFormApp.toast('Erro ao atualizar', 'error');
                }
            });
        });
    },

    // ==========================================
    // DATA TABLES
    // ==========================================

    initDataTables() {
        // Select all checkbox
        document.querySelectorAll('.select-all-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const table = checkbox.closest('table') || checkbox.closest('.table-container')?.querySelector('table');
                if (table) {
                    table.querySelectorAll('.row-checkbox').forEach(cb => {
                        cb.checked = checkbox.checked;
                    });
                    this.updateBulkActions();
                }
            });
        });

        // Row checkboxes
        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateBulkActions());
        });

        // Sortable columns
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const column = th.dataset.sort;
                const currentSort = new URLSearchParams(window.location.search).get('sort');
                const currentDir = new URLSearchParams(window.location.search).get('dir') || 'asc';

                let newDir = 'asc';
                if (currentSort === column && currentDir === 'asc') {
                    newDir = 'desc';
                }

                const url = new URL(window.location);
                url.searchParams.set('sort', column);
                url.searchParams.set('dir', newDir);
                window.location = url;
            });
        });
    },

    // ==========================================
    // BULK ACTIONS
    // ==========================================

    initBulkActions() {
        // Already initialized via checkboxes above
    },

    updateBulkActions() {
        const selected = document.querySelectorAll('.row-checkbox:checked');
        const bulkBar = document.getElementById('bulk-actions-bar');

        if (bulkBar) {
            if (selected.length > 0) {
                bulkBar.style.display = 'flex';
                const count = bulkBar.querySelector('.bulk-count');
                if (count) count.textContent = `${selected.length} selecionado(s)`;
            } else {
                bulkBar.style.display = 'none';
            }
        }
    },

    getSelectedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .map(cb => cb.value || cb.dataset.id);
    },

    async bulkAction(action, url) {
        const ids = this.getSelectedIds();
        if (ids.length === 0) {
            LeadFormApp.toast('Selecione pelo menos um item', 'warning');
            return;
        }

        if (!confirm(`Aplicar "${action}" em ${ids.length} item(s)?`)) return;

        try {
            await LeadFormApp.ajax(url, {
                method: 'POST',
                body: { action, ids }
            });
            LeadFormApp.toast('Ação aplicada com sucesso!');
            location.reload();
        } catch (error) {
            LeadFormApp.toast('Erro ao aplicar ação', 'error');
        }
    },

    // ==========================================
    // CHARTS (Placeholder for Chart.js)
    // ==========================================

    initCharts() {
        // Charts will be initialized when Chart.js is loaded
        const chartElements = document.querySelectorAll('[data-chart]');
        if (chartElements.length === 0) return;

        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') return;

        chartElements.forEach(el => {
            const type = el.dataset.chart;
            const dataAttr = el.dataset.chartData;

            try {
                const data = JSON.parse(dataAttr || '{}');
                this.createChart(el, type, data);
            } catch (e) {
                console.warn('Invalid chart data:', e);
            }
        });
    },

    createChart(canvas, type, data) {
        if (typeof Chart === 'undefined') return null;

        const defaultColors = [
            'rgba(79, 70, 229, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(124, 58, 237, 0.8)'
        ];

        return new Chart(canvas, {
            type: type,
            data: {
                labels: data.labels || [],
                datasets: (data.datasets || []).map((ds, idx) => ({
                    ...ds,
                    backgroundColor: ds.backgroundColor || defaultColors[idx % defaultColors.length],
                    borderColor: ds.borderColor || defaultColors[idx % defaultColors.length],
                    borderWidth: ds.borderWidth || 2,
                    tension: 0.4
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: data.showLegend !== false }
                },
                scales: type === 'line' || type === 'bar' ? {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                } : undefined,
                ...data.options
            }
        });
    },

    // ==========================================
    // FORM UTILITIES FOR ADMIN
    // ==========================================

    async deleteItem(url, itemName = 'item') {
        if (!confirm(`Tem certeza que deseja excluir este ${itemName}? Esta ação não pode ser desfeita.`)) return;

        try {
            await LeadFormApp.ajax(url, { method: 'POST' });
            LeadFormApp.toast(`${itemName} excluído com sucesso!`);
            location.reload();
        } catch (error) {
            LeadFormApp.toast(`Erro ao excluir ${itemName}`, 'error');
        }
    },

    async toggleStatus(url, element) {
        try {
            const result = await LeadFormApp.ajax(url, { method: 'POST' });
            LeadFormApp.toast('Status atualizado!');

            // Update badge if present
            const badge = element?.closest('tr')?.querySelector('.badge');
            if (badge && result.status) {
                badge.className = `badge badge-${result.status === 'active' ? 'success' : 'warning'}`;
                badge.textContent = result.status === 'active' ? 'Ativo' : 'Inativo';
            }
        } catch (error) {
            LeadFormApp.toast('Erro ao atualizar status', 'error');
        }
    },

    // ==========================================
    // IMPERSONATION
    // ==========================================

    async loginAsClient(clientId) {
        if (!confirm('Você será redirecionado para o painel do cliente. Deseja continuar?')) return;

        try {
            await LeadFormApp.ajax(`/admin/clients/${clientId}/login-as`, { method: 'POST' });
            window.location.href = '/dashboard';
        } catch (error) {
            LeadFormApp.toast('Erro ao acessar conta do cliente', 'error');
        }
    },

    stopImpersonation() {
        window.location.href = '/admin/stop-impersonate';
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => LeadFormAdmin.init());

// Export globally
window.LeadFormAdmin = LeadFormAdmin;
