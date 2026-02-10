<?php
/**
 * LeadForm SaaS - Client Sidebar Component
 *
 * Reusable sidebar navigation for the client (tenant) admin panel.
 * Edit this file to add/remove/reorder client menu items.
 *
 * Variables expected (from parent layout):
 *   $currentPage    - Active sidebar item identifier
 *   $userInitial    - User initial letter for avatar
 *   $userName       - User display name
 *   $userRole       - User role (owner, admin, member)
 *   $planName       - Current plan name
 *   $formsUsed      - Number of forms used
 *   $formsLimit     - Max forms allowed
 *   $formsPercent   - Forms usage percentage
 *   $storageUsed    - Storage used in MB
 *   $storageLimit   - Storage limit in MB
 *   $storagePercent - Storage usage percentage
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
        <!-- FORMULARIOS -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Formularios</div>

            <a href="/client/dashboard" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="icon">&#9634;</span>
                <span>Dashboard</span>
            </a>
            <a href="/client/forms" class="sidebar-link <?= $currentPage === 'forms' ? 'active' : '' ?>">
                <span class="icon">&#9776;</span>
                <span>Meus Formularios</span>
            </a>
            <a href="/client/entries" class="sidebar-link <?= $currentPage === 'entries' ? 'active' : '' ?>">
                <span class="icon">&#9993;</span>
                <span>Entradas</span>
            </a>
        </div>

        <!-- EQUIPE -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Equipe</div>

            <a href="/client/users" class="sidebar-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                <span class="icon">&#9787;</span>
                <span>Usuarios</span>
            </a>
            <a href="/client/permissions" class="sidebar-link <?= $currentPage === 'permissions' ? 'active' : '' ?>">
                <span class="icon">&#128274;</span>
                <span>Permissoes</span>
            </a>
        </div>

        <!-- INTEGRACOES -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Integracoes</div>

            <a href="/client/integrations" class="sidebar-link <?= $currentPage === 'integrations' ? 'active' : '' ?>">
                <span class="icon">&#128268;</span>
                <span>Integracoes</span>
            </a>
            <a href="/client/webhooks" class="sidebar-link <?= $currentPage === 'webhooks' ? 'active' : '' ?>">
                <span class="icon">&#128279;</span>
                <span>Webhooks</span>
            </a>
            <a href="/client/pixels" class="sidebar-link <?= $currentPage === 'pixels' ? 'active' : '' ?>">
                <span class="icon">&#127919;</span>
                <span>Pixels &amp; Tags</span>
            </a>
        </div>

        <!-- CONFIGURACOES -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Configuracoes</div>

            <a href="/client/profile" class="sidebar-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <span class="icon">&#9787;</span>
                <span>Perfil</span>
            </a>
            <a href="/client/security" class="sidebar-link <?= $currentPage === 'security' ? 'active' : '' ?>">
                <span class="icon">&#128737;</span>
                <span>Seguranca</span>
            </a>
            <a href="/client/api-tokens" class="sidebar-link <?= $currentPage === 'api-tokens' ? 'active' : '' ?>">
                <span class="icon">&#128273;</span>
                <span>API &amp; Tokens</span>
            </a>
            <a href="/client/ai" class="sidebar-link <?= $currentPage === 'ai' ? 'active' : '' ?>">
                <span class="icon">&#129302;</span>
                <span>IA</span>
                <span class="badge badge-primary" style="font-size:9px; padding:1px 6px;">Novo</span>
            </a>
        </div>
    </nav>

    <!-- Plan Usage Indicator -->
    <div class="sidebar-footer">
        <div style="margin-bottom: var(--space-4);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--space-2);">
                <span style="font-size:11px; color:rgba(255,255,255,0.5); font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">Plano <?= $planName ?></span>
                <a href="/client/billing" style="font-size:11px; color:var(--primary-light); text-decoration:none;">Upgrade</a>
            </div>

            <!-- Forms usage -->
            <div style="margin-bottom: var(--space-3);">
                <div style="display:flex; justify-content:space-between; font-size:11px; color:rgba(255,255,255,0.4); margin-bottom:4px;">
                    <span>Formularios</span>
                    <span><?= $formsUsed ?>/<?= $formsLimit ?></span>
                </div>
                <div style="height:4px; background:rgba(255,255,255,0.1); border-radius:2px; overflow:hidden;">
                    <div style="height:100%; width:<?= $formsPercent ?>%; background:<?= $formsPercent >= 90 ? 'var(--danger)' : ($formsPercent >= 70 ? 'var(--warning)' : 'var(--primary-light)') ?>; border-radius:2px; transition:width 0.3s ease;"></div>
                </div>
            </div>

            <!-- Storage usage -->
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
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= $userName ?></div>
                <div class="sidebar-user-role"><?= ucfirst($userRole) ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
