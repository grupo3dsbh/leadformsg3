<?php
/**
 * LeadForm SaaS - Super Admin Panel Layout
 *
 * Layout for the super-admin back-office. Includes sidebar navigation,
 * top bar with breadcrumb/search/notifications, and main content area.
 *
 * Variables available via extract():
 *   $content        - The rendered page content
 *   $pageTitle      - Page title (optional)
 *   $breadcrumb     - Array of ['label' => '', 'url' => ''] items (optional)
 *   $currentPage    - Active sidebar item identifier (optional)
 *   $user           - Authenticated admin user array (optional)
 *   $impersonating  - Array with impersonated user info, or null (optional)
 */

$siteName    = 'LeadForm';
$pageTitle   = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Admin' : 'Admin - ' . $siteName;
$currentPage = $currentPage ?? '';
$breadcrumb  = $breadcrumb ?? [];
$user        = $user ?? [];
$userName    = htmlspecialchars($user['name'] ?? 'Admin');
$userEmail   = htmlspecialchars($user['email'] ?? '');
$userInitial = mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1));
$impersonating = $impersonating ?? null;
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

    <!-- Impersonate Banner -->
    <?php if ($impersonating): ?>
    <div class="impersonate-banner">
        <span>&#9888;</span>
        Voce esta visualizando como: <strong><?= htmlspecialchars($impersonating['name'] ?? 'Cliente') ?></strong>
        (<?= htmlspecialchars($impersonating['email'] ?? '') ?>)
        <a href="/admin/impersonate/stop">Voltar ao admin</a>
    </div>
    <?php endif; ?>

    <div class="admin-layout">

        <!-- Sidebar (componente separado - edite em views/components/admin-sidebar.php) -->
        <?php include ROOT_PATH . '/views/components/admin-sidebar.php'; ?>

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
                        <a href="/admin">Admin</a>
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
                        <input type="text" placeholder="Buscar..." aria-label="Buscar" id="adminSearch">
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
                            </div>
                            <a href="/admin/settings" class="dropdown-item">
                                <span>&#9881;</span> Configuracoes
                            </a>
                            <a href="/admin/audit" class="dropdown-item">
                                <span>&#128220;</span> Logs
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
         ADMIN SCRIPTS
         ============================================ -->
    <script>
    (function() {
        // Sidebar toggle (mobile)
        var sidebar = document.getElementById('sidebar');
        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        sidebarToggle.addEventListener('click', openSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        // User dropdown toggle
        var userDropdown = document.getElementById('userDropdown');
        userDropdown.querySelector('button').addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });

        document.addEventListener('click', function() {
            userDropdown.classList.remove('open');
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
                document.getElementById('adminSearch').focus();
            }
        });
    })();
    </script>

    <?= $footerExtra ?? '' ?>
</body>
</html>
