<?php
/**
 * LeadForm SaaS - Public Pages Layout
 *
 * Base layout for public-facing pages: home, pricing, features, docs, etc.
 * Variables available via extract():
 *   $content     - The rendered page content
 *   $pageTitle   - Page title (optional)
 *   $pageDesc    - Meta description (optional)
 *   $ogImage     - Open Graph image URL (optional)
 *   $bodyClass   - Extra body class (optional)
 *   $canonical   - Canonical URL (optional)
 */

$siteName   = 'LeadForm';
$pageTitle  = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . $siteName : $siteName . ' - Formularios Conversacionais Inteligentes';
$pageDesc   = htmlspecialchars($pageDesc ?? 'Crie formularios conversacionais que convertem. Aumente suas taxas de conversao com formularios inteligentes e bonitos.');
$ogImage    = $ogImage ?? '/public/images/og-default.png';
$bodyClass  = $bodyClass ?? '';
$canonical  = $canonical ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $pageDesc ?>">
    <meta name="robots" content="index, follow">

    <?php if ($canonical): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $pageDesc ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:site_name" content="<?= $siteName ?>">
    <?php if ($canonical): ?>
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $pageTitle ?>">
    <meta name="twitter:description" content="<?= $pageDesc ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/public/images/favicon.png">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="/public/css/app.css">

    <!-- Additional head content -->
    <?= $headExtra ?? '' ?>
</head>
<body class="public-page <?= htmlspecialchars($bodyClass) ?>">

    <!-- Header (componente separado - edite em views/components/header.php) -->
    <?php include ROOT_PATH . '/views/components/header.php'; ?>

    <!-- ============================================
         MAIN CONTENT
         ============================================ -->
    <main class="site-main">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer (componente separado - edite em views/components/footer.php) -->
    <?php include ROOT_PATH . '/views/components/footer.php'; ?>

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

        <?php if (!empty($_SESSION['_flash']['warning'])): ?>
        <div class="toast toast-warning" role="alert">
            <span>&#9888;</span>
            <span><?= htmlspecialchars($_SESSION['_flash']['warning']) ?></span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php unset($_SESSION['_flash']['warning']); endif; ?>

        <?php if (!empty($_SESSION['_flash']['info'])): ?>
        <div class="toast toast-info" role="alert">
            <span>&#8505;</span>
            <span><?= htmlspecialchars($_SESSION['_flash']['info']) ?></span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php unset($_SESSION['_flash']['info']); endif; ?>
    </div>

    <!-- Dev Error Bar (componente separado - edite em views/components/dev-error-bar.php) -->
    <?php include ROOT_PATH . '/views/components/dev-error-bar.php'; ?>

    <!-- ============================================
         HEADER SCROLL + MOBILE MENU SCRIPTS
         ============================================ -->
    <script>
    (function() {
        // Sticky header - becomes solid on scroll
        var header = document.getElementById('siteHeader');
        var scrollThreshold = 20;

        function handleScroll() {
            if (window.scrollY > scrollThreshold) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        // Mobile menu toggle
        var navToggle = document.getElementById('navToggle');
        var mobileMenu = document.getElementById('mobileMenu');
        var mobileOverlay = document.getElementById('mobileOverlay');
        var mobileClose = document.getElementById('mobileClose');

        function openMobile() {
            mobileMenu.classList.add('open');
            mobileOverlay.classList.add('active');
            navToggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function closeMobile() {
            mobileMenu.classList.remove('open');
            mobileOverlay.classList.remove('active');
            navToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        navToggle.addEventListener('click', function() {
            var isOpen = mobileMenu.classList.contains('open');
            isOpen ? closeMobile() : openMobile();
        });

        mobileClose.addEventListener('click', closeMobile);
        mobileOverlay.addEventListener('click', closeMobile);

        // Auto-dismiss toasts after 5 seconds
        var toasts = document.querySelectorAll('.toast');
        toasts.forEach(function(toast) {
            setTimeout(function() {
                toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(function() { toast.remove(); }, 400);
            }, 5000);
        });
    })();
    </script>

    <!-- Additional footer scripts -->
    <?= $footerExtra ?? '' ?>
</body>
</html>
