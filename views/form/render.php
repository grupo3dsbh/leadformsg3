<?php
/**
 * LeadForm SaaS - Conversational Form Renderer
 *
 * This is the most critical view. It renders the form for end users in a
 * full-screen, minimal page. No header or footer -- the form occupies the
 * entire viewport. Supports two modes: "conversation" (chat) and "typeform"
 * (one question at a time).
 *
 * Variables available via extract():
 *   $form         - The form record (array with id, slug, title, settings, etc.)
 *   $fields       - Array of field definitions
 *   $formSettings - Merged form display settings
 *   $tracking     - Tracking pixels / scripts (optional)
 *   $customCss    - Custom CSS injected by the form owner (optional)
 *   $customJs     - Custom JS injected by the form owner (optional)
 *   $meta         - SEO meta overrides (optional)
 */

$form         = $form ?? [];
$fields       = $fields ?? [];
$formSettings = $formSettings ?? [];
$tracking     = $tracking ?? [];
$customCss    = $customCss ?? '';
$customJs     = $customJs ?? '';
$meta         = $meta ?? [];

// Form identity
$formId    = $form['id'] ?? null;
$formSlug  = $form['slug'] ?? '';
$formTitle = htmlspecialchars($form['title'] ?? 'LeadForm');
$formDesc  = htmlspecialchars($meta['description'] ?? $form['description'] ?? '');
$formMode  = $form['settings']['mode'] ?? 'conversation';
$formLang  = $form['settings']['lang'] ?? 'pt-BR';

// Theme / branding
$brandColor  = htmlspecialchars($formSettings['brand_color'] ?? '#4F46E5');
$bgType      = $formSettings['background_type'] ?? 'gradient';
$bgColor     = htmlspecialchars($formSettings['background_color'] ?? '#1a1a2e');
$bgImage     = htmlspecialchars($formSettings['background_image'] ?? '');
$bgVideo     = htmlspecialchars($formSettings['background_video'] ?? '');
$faviconUrl  = htmlspecialchars($formSettings['favicon'] ?? '/public/images/favicon.png');
$ogImage     = htmlspecialchars($meta['og_image'] ?? '/public/images/og-default.png');

// Powered by
$showBranding = ($formSettings['show_branding'] ?? true) !== false;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($formLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?= $formTitle ?></title>
    <?php if ($formDesc): ?>
    <meta name="description" content="<?= $formDesc ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= $formTitle ?>">
    <?php if ($formDesc): ?>
    <meta property="og:description" content="<?= $formDesc ?>">
    <?php endif; ?>
    <meta property="og:image" content="<?= $ogImage ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $formTitle ?>">

    <!-- Prevent indexing of individual forms unless explicitly allowed -->
    <?php if (empty($meta['allow_indexing'])): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $faviconUrl ?>">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Form Stylesheet -->
    <link rel="stylesheet" href="/public/css/form.css">

    <!-- Dynamic theme variables -->
    <style>
        .leadform {
            --lf-primary: <?= $brandColor ?>;
            <?php
            // Convert hex to RGB for alpha usage
            $hex = ltrim($brandColor, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            ?>
            --lf-primary-rgb: <?= "$r, $g, $b" ?>;
            <?php if ($bgType === 'color'): ?>
            --lf-bg: <?= $bgColor ?>;
            <?php endif; ?>
        }
    </style>

    <?php if ($customCss): ?>
    <!-- Custom CSS -->
    <style><?= $customCss ?></style>
    <?php endif; ?>
</head>
<body style="margin:0; padding:0; overflow:hidden;">

    <!-- ============================================
         FORM APPLICATION ROOT
         ============================================ -->
    <div id="leadform-app"></div>

    <!-- ============================================
         BACKGROUND LAYER
         (rendered separately so form content layers on top)
         ============================================ -->
    <?php if ($bgType === 'video' && $bgVideo): ?>
    <div class="lf-background" id="lfBackground">
        <video autoplay muted loop playsinline>
            <source src="<?= $bgVideo ?>" type="video/mp4">
        </video>
        <div class="lf-background-overlay"></div>
    </div>
    <?php elseif ($bgType === 'image' && $bgImage): ?>
    <div class="lf-background" id="lfBackground">
        <img src="<?= $bgImage ?>" alt="" loading="eager">
        <div class="lf-background-overlay"></div>
    </div>
    <?php elseif ($bgType === 'color'): ?>
    <div class="lf-background" id="lfBackground">
        <div class="lf-background-color" style="background: <?= $bgColor ?>;"></div>
    </div>
    <?php else: ?>
    <!-- Default: gradient background -->
    <div class="lf-background" id="lfBackground">
        <div class="lf-background-gradient"></div>
    </div>
    <?php endif; ?>

    <!-- ============================================
         POWERED BY BADGE
         ============================================ -->
    <?php if ($showBranding): ?>
    <a href="/?ref=form-badge" target="_blank" rel="noopener" class="lf-powered-by" title="Criado com LeadForm">
        Powered by <strong>LeadForm</strong>
    </a>
    <?php endif; ?>

    <!-- ============================================
         FORM RENDERER JS
         ============================================ -->
    <script src="/public/js/form-renderer.js"></script>
    <script>
    (function() {
        var formConfig = <?= json_encode([
            'formId'         => $formId,
            'formSlug'       => $formSlug,
            'mode'           => $formMode,
            'submitUrl'      => '/f/' . $formSlug . '/submit',
            'partialSaveUrl' => '/f/' . $formSlug . '/save-partial',
            'fields'         => $fields,
            'settings'       => $formSettings,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        var renderer = new LeadFormRenderer(formConfig);
    })();
    </script>

    <?php if ($customJs): ?>
    <!-- Custom JS -->
    <script><?= $customJs ?></script>
    <?php endif; ?>

    <!-- ============================================
         TRACKING PIXELS / SCRIPTS
         ============================================ -->
    <?php if (!empty($tracking)): ?>
    <?php foreach ($tracking as $pixel): ?>
    <?php
        $pixelType = $pixel['type'] ?? '';
        $pixelId   = htmlspecialchars($pixel['pixel_id'] ?? '');
    ?>
    <?php if ($pixelType === 'facebook' && $pixelId): ?>
    <!-- Facebook Pixel -->
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
    document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= $pixelId ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= $pixelId ?>&ev=PageView&noscript=1"/></noscript>
    <?php elseif ($pixelType === 'google_analytics' && $pixelId): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $pixelId ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= $pixelId ?>');
    </script>
    <?php elseif ($pixelType === 'gtm' && $pixelId): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= $pixelId ?>');</script>
    <?php elseif ($pixelType === 'custom' && !empty($pixel['script'])): ?>
    <!-- Custom Tracking Script -->
    <?= $pixel['script'] ?>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ============================================
         DEV MODE ERROR BAR
         ============================================ -->
    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <div class="dev-mode-indicator" style="z-index:99999;">DEV</div>
    <div class="dev-error-bar" id="devErrorBar" style="z-index:99999;">
        <div class="dev-error-header">
            <span class="error-type" id="devErrorType">JavaScript Error</span>
            <button class="dev-error-close" id="devErrorClose">&times;</button>
        </div>
        <div class="dev-error-body">
            <div class="dev-error-message" id="devErrorMessage"></div>
            <div class="dev-error-file" id="devErrorFile"></div>
            <pre class="dev-error-trace" id="devErrorTrace"></pre>
        </div>
    </div>
    <script>
    (function() {
        var bar = document.getElementById('devErrorBar');
        var errorType = document.getElementById('devErrorType');
        var errorMsg = document.getElementById('devErrorMessage');
        var errorFile = document.getElementById('devErrorFile');
        var errorTrace = document.getElementById('devErrorTrace');
        var closeBtn = document.getElementById('devErrorClose');
        var errorCount = 0;

        closeBtn.addEventListener('click', function() {
            bar.classList.remove('active');
        });

        window.onerror = function(msg, url, line, col, error) {
            errorCount++;
            errorType.textContent = 'JS Error #' + errorCount;
            errorMsg.textContent = msg;
            errorFile.textContent = (url || 'unknown') + ':' + (line || '?') + ':' + (col || '?');
            errorTrace.textContent = error && error.stack ? error.stack : 'No stack trace available';
            bar.classList.add('active');
            return false;
        };

        window.addEventListener('unhandledrejection', function(event) {
            errorCount++;
            errorType.textContent = 'Unhandled Promise Rejection #' + errorCount;
            errorMsg.textContent = event.reason ? (event.reason.message || String(event.reason)) : 'Unknown rejection';
            errorFile.textContent = '';
            errorTrace.textContent = event.reason && event.reason.stack ? event.reason.stack : '';
            bar.classList.add('active');
        });
    })();
    </script>
    <?php endif; ?>

</body>
</html>
