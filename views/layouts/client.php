<?php
/**
 * LeadForm SaaS - Client Admin Panel Layout
 *
 * Layout for the client (tenant) back-office. Includes sidebar with
 * form/team/integration navigation, plan usage indicators, and top bar.
 *
 * Variables available via extract():
 *   $content        - The rendered page content
 *   $pageTitle      - Page title (optional)
 *   $breadcrumb     - Array of ['label' => '', 'url' => ''] items (optional)
 *   $currentPage    - Active sidebar item identifier (optional)
 *   $user           - Authenticated client user array (optional)
 *   $plan           - Current plan info array (optional)
 *   $usage          - Usage stats array (optional)
 */

$siteName    = 'LeadForm';
$pageTitle   = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . $siteName : $siteName;
$currentPage = $currentPage ?? '';
$breadcrumb  = $breadcrumb ?? [];

// Get user from passed data or auth() helper
$user        = $user ?? (function_exists('auth') ? auth() : null) ?? [];
$userName    = htmlspecialchars($user['name'] ?? 'Usuario');
$userEmail   = htmlspecialchars($user['email'] ?? '');
$userRole    = htmlspecialchars($user['role'] ?? 'admin');
$userInitial = mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1));

// Get tenant data
$tenantData  = $tenant ?? (function_exists('tenant') ? tenant() : null) ?? [];

// Plan and usage info - try from passed data or query directly
if (empty($plan) && !empty($tenantData['plan_id'])) {
    try {
        $db = \Core\Database::getInstance();
        $pStmt = $db->prepare("SELECT * FROM plans WHERE id = :pid");
        $pStmt->execute(['pid' => (int) $tenantData['plan_id']]);
        $plan = $pStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
        $plan = [];
    }
}
$plan = $plan ?? [];
$planName    = htmlspecialchars($plan['name'] ?? 'Free');

// Calculate usage
$formsUsed   = 0;
$formsLimit  = (int) ($plan['max_forms'] ?? 5);
$storageUsed = (float) (($tenantData['storage_used'] ?? 0) / 1048576); // bytes to MB
$storageLimit = (float) (($tenantData['max_storage'] ?? 104857600) / 1048576);

if (!empty($tenantData['id'])) {
    try {
        $db = \Core\Database::getInstance();
        $fcStmt = $db->prepare("SELECT COUNT(*) FROM forms WHERE tenant_id = :tid");
        $fcStmt->execute(['tid' => (int) $tenantData['id']]);
        $formsUsed = (int) $fcStmt->fetchColumn();
    } catch (\Throwable $e) {
        // ignore
    }
}

// Usage percentages
$formsPercent   = $formsLimit > 0 ? min(100, round(($formsUsed / $formsLimit) * 100)) : 0;
$storagePercent = $storageLimit > 0 ? min(100, round(($storageUsed / $storageLimit) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $pageTitle ?></title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/public/images/favicon.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="stylesheet" href="/public/css/admin.css">

    <?= $headExtra ?? '' ?>
</head>
<body>

    <div class="admin-layout">

        <!-- Sidebar (componente separado - edite em views/components/client-sidebar.php) -->
        <?php include ROOT_PATH . '/views/components/client-sidebar.php'; ?>

        <!-- ============================================
             MAIN CONTENT
             ============================================ -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <!-- Mobile sidebar toggle -->
                    <button class="topbar-toggle" id="sidebarToggle" aria-label="Abrir menu lateral">&#9776;</button>

                    <!-- Breadcrumb -->
                    <div class="topbar-breadcrumb">
                        <a href="/client/dashboard">Painel</a>
                        <?php foreach ($breadcrumb as $crumb): ?>
                            <span class="separator">/</span>
                            <?php if (!empty($crumb['url'])): ?>
                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
                            <?php else: ?>
                                <span class="current"><?= htmlspecialchars($crumb['label']) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="topbar-right">
                    <!-- Search -->
                    <div class="topbar-search">
                        <span class="search-icon">&#128269;</span>
                        <input type="text" placeholder="Buscar formularios..." aria-label="Buscar" id="clientSearch">
                    </div>

                    <!-- Notifications -->
                    <button class="topbar-btn" id="notifBtn" aria-label="Notificacoes" title="Notificacoes">
                        &#128276;
                        <span class="notification-dot" id="notifDot" style="display:none;"></span>
                    </button>

                    <!-- User Dropdown -->
                    <div class="dropdown" id="userDropdown">
                        <button class="topbar-btn" aria-label="Menu do usuario">
                            <div class="avatar-initials avatar-sm" style="font-size: 12px;"><?= $userInitial ?></div>
                        </button>
                        <div class="dropdown-menu">
                            <div style="padding: 8px 12px; border-bottom: 1px solid var(--gray-100); margin-bottom: 4px;">
                                <div class="font-semibold text-sm"><?= $userName ?></div>
                                <div class="text-xs text-gray-400"><?= $userEmail ?></div>
                                <div class="text-xs text-primary mt-1">Plano <?= $planName ?></div>
                            </div>
                            <a href="/client/profile" class="dropdown-item">
                                <span>&#9787;</span> Perfil
                            </a>
                            <a href="/client/security" class="dropdown-item">
                                <span>&#128737;</span> Seguranca
                            </a>
                            <a href="/client/billing" class="dropdown-item">
                                <span>&#9733;</span> Assinatura
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/logout" class="dropdown-item" style="color: var(--danger);">
                                <span>&#9211;</span> Sair
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>

    <!-- ============================================
         TOAST NOTIFICATIONS
         ============================================ -->
    <div class="toast-container" id="toastContainer">
        <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="toast toast-success" role="alert">
            <span>&#10003;</span>
            <span><?= htmlspecialchars($_SESSION['_flash']['success']) ?></span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php unset($_SESSION['_flash']['success']); endif; ?>

        <?php if (!empty($_SESSION['_flash']['error'])): ?>
        <div class="toast toast-error" role="alert">
            <span>&#10007;</span>
            <span><?= htmlspecialchars($_SESSION['_flash']['error']) ?></span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php unset($_SESSION['_flash']['error']); endif; ?>
    </div>

    <!-- Dev Error Bar (componente separado - edite em views/components/dev-error-bar.php) -->
    <?php include ROOT_PATH . '/views/components/dev-error-bar.php'; ?>

    <!-- ============================================
         CLIENT PANEL SCRIPTS
         ============================================ -->
    <script>
    (function() {
        var sidebar = document.getElementById('sidebar');
        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebarOverlay = document.getElementById('sidebarOverlay');
        var sidebarPinBtn = document.getElementById('sidebarPinBtn');
        var adminLayout = document.querySelector('.admin-layout');

        // Restore sidebar pin state from localStorage
        var sidebarPinned = localStorage.getItem('leadform_sidebar_pinned') === 'true';
        if (sidebarPinned && sidebar) {
            sidebar.classList.add('sidebar-pinned');
            sidebar.classList.remove('sidebar-collapsed');
            if (adminLayout) adminLayout.classList.add('sidebar-pinned');
        }

        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (sidebarOverlay) sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // Pin/unpin sidebar
        if (sidebarPinBtn) {
            sidebarPinBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var isPinned = sidebar.classList.contains('sidebar-pinned');
                if (isPinned) {
                    sidebar.classList.remove('sidebar-pinned');
                    sidebar.classList.add('sidebar-collapsed');
                    if (adminLayout) adminLayout.classList.remove('sidebar-pinned');
                    localStorage.setItem('leadform_sidebar_pinned', 'false');
                } else {
                    sidebar.classList.add('sidebar-pinned');
                    sidebar.classList.remove('sidebar-collapsed');
                    if (adminLayout) adminLayout.classList.add('sidebar-pinned');
                    localStorage.setItem('leadform_sidebar_pinned', 'true');
                }
            });
        }

        // User dropdown toggle
        var userDropdown = document.getElementById('userDropdown');
        if (userDropdown) {
            userDropdown.querySelector('button').addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('open');
            });
        }

        document.addEventListener('click', function() {
            if (userDropdown) userDropdown.classList.remove('open');
        });

        // Auto-dismiss toasts
        var toasts = document.querySelectorAll('.toast');
        toasts.forEach(function(toast) {
            setTimeout(function() {
                toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(function() { toast.remove(); }, 400);
            }, 5000);
        });

        // Global search shortcut (Ctrl/Cmd + K)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                var s = document.getElementById('clientSearch');
                if (s) s.focus();
            }
        });
    })();
    </script>

    <?= $footerExtra ?? '' ?>
</body>
</html>
