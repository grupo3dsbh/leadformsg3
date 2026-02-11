<?php
/**
 * LeadForm SaaS - Client Sidebar Component (Collapsible)
 *
 * Collapsible sidebar: collapsed by default showing only icons.
 * On hover, expands to show labels. Pin button to keep expanded.
 * Preference saved per user via localStorage.
 */
?>
<aside class="sidebar sidebar-collapsed" id="sidebar">
    <!-- Sidebar Header / Logo -->
    <div class="sidebar-header">
        <div class="sidebar-logo">LF</div>
        <div class="sidebar-text sidebar-brand">Lead<span>Form</span></div>
        <button class="sidebar-pin-btn sidebar-text" id="sidebarPinBtn" title="Fixar menu">
            <span class="pin-icon">&#128204;</span>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- FORMULARIOS -->
        <div class="sidebar-section">
            <div class="sidebar-section-title sidebar-text">Formularios</div>

            <a href="/dashboard" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" data-tooltip="Dashboard">
                <span class="icon">&#9634;</span>
                <span class="sidebar-text">Dashboard</span>
            </a>
            <a href="/dashboard/forms" class="sidebar-link <?= $currentPage === 'forms' ? 'active' : '' ?>" data-tooltip="Meus Formularios">
                <span class="icon">&#9776;</span>
                <span class="sidebar-text">Meus Formularios</span>
            </a>
            <a href="/dashboard/leads" class="sidebar-link <?= $currentPage === 'leads' ? 'active' : '' ?>" data-tooltip="Leads">
                <span class="icon">&#9993;</span>
                <span class="sidebar-text">Leads</span>
            </a>
        </div>

        <!-- INTEGRACOES -->
        <div class="sidebar-section">
            <div class="sidebar-section-title sidebar-text">Integracoes</div>

            <a href="/dashboard/integrations" class="sidebar-link <?= $currentPage === 'integrations' ? 'active' : '' ?>" data-tooltip="Integracoes">
                <span class="icon">&#128268;</span>
                <span class="sidebar-text">Integracoes</span>
            </a>
            <a href="/dashboard/webhooks" class="sidebar-link <?= $currentPage === 'webhooks' ? 'active' : '' ?>" data-tooltip="Webhooks">
                <span class="icon">&#128279;</span>
                <span class="sidebar-text">Webhooks</span>
            </a>
            <a href="/dashboard/pixels" class="sidebar-link <?= $currentPage === 'pixels' ? 'active' : '' ?>" data-tooltip="Pixels & Tags">
                <span class="icon">&#127919;</span>
                <span class="sidebar-text">Pixels &amp; Tags</span>
            </a>
        </div>

        <!-- CONFIGURACOES -->
        <div class="sidebar-section">
            <div class="sidebar-section-title sidebar-text">Configuracoes</div>

            <a href="/dashboard/profile" class="sidebar-link <?= $currentPage === 'profile' ? 'active' : '' ?>" data-tooltip="Perfil">
                <span class="icon">&#9787;</span>
                <span class="sidebar-text">Perfil</span>
            </a>
            <a href="/dashboard/security" class="sidebar-link <?= $currentPage === 'security' ? 'active' : '' ?>" data-tooltip="Seguranca">
                <span class="icon">&#128737;</span>
                <span class="sidebar-text">Seguranca</span>
            </a>
            <a href="/dashboard/api-keys" class="sidebar-link <?= $currentPage === 'api-keys' ? 'active' : '' ?>" data-tooltip="API & Tokens">
                <span class="icon">&#128273;</span>
                <span class="sidebar-text">API &amp; Tokens</span>
            </a>
            <a href="/dashboard/ai-settings" class="sidebar-link <?= $currentPage === 'ai-settings' ? 'active' : '' ?>" data-tooltip="IA">
                <span class="icon">&#129302;</span>
                <span class="sidebar-text">IA</span>
                <span class="badge badge-primary sidebar-text" style="font-size:9px; padding:1px 6px;">Novo</span>
            </a>
        </div>
    </nav>

    <!-- Plan Usage Indicator (only when expanded) -->
    <div class="sidebar-footer">
        <div class="sidebar-text" style="margin-bottom: var(--space-4);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--space-2);">
                <span style="font-size:11px; color:rgba(255,255,255,0.5); font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">Plano <?= $planName ?></span>
                <a href="/dashboard/billing" style="font-size:11px; color:var(--primary-light); text-decoration:none;">Upgrade</a>
            </div>
            <div style="margin-bottom: var(--space-3);">
                <div style="display:flex; justify-content:space-between; font-size:11px; color:rgba(255,255,255,0.4); margin-bottom:4px;">
                    <span>Formularios</span>
                    <span><?= $formsUsed ?>/<?= $formsLimit ?></span>
                </div>
                <div style="height:4px; background:rgba(255,255,255,0.1); border-radius:2px; overflow:hidden;">
                    <div style="height:100%; width:<?= $formsPercent ?>%; background:<?= $formsPercent >= 90 ? 'var(--danger)' : ($formsPercent >= 70 ? 'var(--warning)' : 'var(--primary-light)') ?>; border-radius:2px; transition:width 0.3s ease;"></div>
                </div>
            </div>
            <div>
                <div style="display:flex; justify-content:space-between; font-size:11px; color:rgba(255,255,255,0.4); margin-bottom:4px;">
                    <span>Armazenamento</span>
                    <span><?= number_format($storageUsed, 1) ?>/<?= number_format($storageLimit, 0) ?> MB</span>
                </div>
                <div style="height:4px; background:rgba(255,255,255,0.1); border-radius:2px; overflow:hidden;">
                    <div style="height:100%; width:<?= $storagePercent ?>%; background:<?= $storagePercent >= 90 ? 'var(--danger)' : ($storagePercent >= 70 ? 'var(--warning)' : 'var(--primary-light)') ?>; border-radius:2px; transition:width 0.3s ease;"></div>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="sidebar-user" style="border-top:1px solid rgba(255,255,255,0.08); padding-top: var(--space-4);">
            <div class="avatar-initials avatar-sm"><?= $userInitial ?></div>
            <div class="sidebar-user-info sidebar-text">
                <div class="sidebar-user-name"><?= $userName ?></div>
                <div class="sidebar-user-role"><?= ucfirst($userRole) ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
