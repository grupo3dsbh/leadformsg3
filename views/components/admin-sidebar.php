<?php
/**
 * LeadForm SaaS - Admin Sidebar Component
 *
 * Reusable sidebar navigation for the super admin panel.
 * Edit this file to add/remove/reorder admin menu items.
 *
 * Variables expected (from parent layout):
 *   $currentPage  - Active sidebar item identifier
 *   $userInitial  - User initial letter for avatar
 *   $userName     - User display name
 */
?>
<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header / Logo -->
    <div class="sidebar-header">
        <div class="sidebar-logo">LF</div>
        <div class="sidebar-brand">Lead<span>Form</span></div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- PRINCIPAL -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Principal</div>

            <a href="/admin" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="icon">&#9634;</span>
                <span>Dashboard</span>
            </a>
            <a href="/admin/clients" class="sidebar-link <?= $currentPage === 'clients' ? 'active' : '' ?>">
                <span class="icon">&#9787;</span>
                <span>Clientes</span>
            </a>
            <a href="/admin/forms" class="sidebar-link <?= $currentPage === 'forms' ? 'active' : '' ?>">
                <span class="icon">&#9776;</span>
                <span>Formularios</span>
            </a>
            <a href="/admin/entries" class="sidebar-link <?= $currentPage === 'entries' ? 'active' : '' ?>">
                <span class="icon">&#9993;</span>
                <span>Entradas</span>
            </a>
        </div>

        <!-- GESTAO -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Gestao</div>

            <a href="/admin/plans" class="sidebar-link <?= $currentPage === 'plans' ? 'active' : '' ?>">
                <span class="icon">&#9733;</span>
                <span>Planos</span>
            </a>
            <a href="/admin/features" class="sidebar-link <?= $currentPage === 'features' ? 'active' : '' ?>">
                <span class="icon">&#9881;</span>
                <span>Funcionalidades</span>
            </a>
            <a href="/admin/features" class="sidebar-link <?= $currentPage === 'integrations' ? 'active' : '' ?>">
                <span class="icon">&#128268;</span>
                <span>Integracoes</span>
            </a>
        </div>

        <!-- SISTEMA -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Sistema</div>

            <a href="/admin/settings" class="sidebar-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <span class="icon">&#9881;</span>
                <span>Configuracoes</span>
            </a>
            <a href="/admin/audit" class="sidebar-link <?= $currentPage === 'audit' ? 'active' : '' ?>">
                <span class="icon">&#128220;</span>
                <span>Auditoria</span>
            </a>
            <a href="/admin/translations" class="sidebar-link <?= $currentPage === 'translations' ? 'active' : '' ?>">
                <span class="icon">&#127760;</span>
                <span>Traducoes</span>
            </a>
            <a href="/admin/settings/seo" class="sidebar-link <?= $currentPage === 'seo' ? 'active' : '' ?>">
                <span class="icon">&#127912;</span>
                <span>SEO &amp; Temas</span>
            </a>
        </div>
    </nav>

    <!-- Sidebar Footer / User -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar-initials avatar-sm"><?= $userInitial ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= $userName ?></div>
                <div class="sidebar-user-role">Super Admin</div>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
