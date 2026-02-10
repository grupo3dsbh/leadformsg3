<?php
/**
 * LeadForm SaaS - Auth Pages Layout
 *
 * Split layout for authentication pages (login, register, forgot password, etc.).
 * Left side: gradient branding with feature highlights.
 * Right side: form content area.
 *
 * Variables available via extract():
 *   $content     - The rendered form/page content
 *   $pageTitle   - Page title (optional)
 *   $authHeading - Heading text for the left panel (optional)
 *   $authSubtext - Subtext for the left panel (optional)
 */

$siteName    = 'LeadForm';
$pageTitle   = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . $siteName : $siteName;
$authHeading = $authHeading ?? 'Formularios que convertem visitantes em leads.';
$authSubtext = $authSubtext ?? 'Junte-se a milhares de empresas que usam LeadForm para capturar mais leads com formularios inteligentes.';
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

    <!-- Stylesheet -->
    <link rel="stylesheet" href="/public/css/app.css">

    <style>
        body {
            background: var(--gray-50);
            min-height: 100vh;
        }

        .auth-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Left Panel - Branding */
        .auth-brand {
            flex: 0 0 45%;
            background: var(--gradient-dark);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: var(--space-16) var(--space-12);
            overflow: hidden;
            color: #fff;
        }

        .auth-brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gradient-hero);
            opacity: 0.15;
        }

        .auth-brand::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .auth-brand-content {
            position: relative;
            z-index: 1;
            max-width: 480px;
        }

        .auth-brand-logo {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-12);
            text-decoration: none;
            color: #fff;
        }

        .auth-brand-logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-brand-logo-text {
            font-size: var(--font-size-xl);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .auth-brand-logo-text span {
            color: var(--primary-light);
        }

        .auth-brand h1 {
            font-size: var(--font-size-4xl);
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: var(--space-6);
            letter-spacing: -0.03em;
        }

        .auth-brand p {
            font-size: var(--font-size-lg);
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
            margin-bottom: var(--space-10);
        }

        .auth-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: var(--space-5);
        }

        .auth-features li {
            display: flex;
            align-items: flex-start;
            gap: var(--space-4);
            font-size: var(--font-size-base);
            color: rgba(255, 255, 255, 0.85);
        }

        .auth-feature-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .auth-feature-text strong {
            display: block;
            font-weight: 700;
            margin-bottom: 2px;
            color: #fff;
        }

        .auth-feature-text span {
            font-size: var(--font-size-sm);
            color: rgba(255, 255, 255, 0.5);
        }

        /* Right Panel - Form */
        .auth-form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-8);
        }

        .auth-form-wrapper {
            width: 100%;
            max-width: 440px;
        }

        /* Mobile: hide left panel, show logo on right */
        .auth-mobile-logo {
            display: none;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-8);
            text-decoration: none;
            color: var(--gray-900);
        }

        .auth-mobile-logo-icon {
            width: 32px;
            height: 32px;
        }

        .auth-mobile-logo-text {
            font-size: var(--font-size-lg);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .auth-mobile-logo-text span {
            color: var(--primary);
        }

        @media (max-width: 1024px) {
            .auth-brand {
                flex: 0 0 40%;
                padding: var(--space-10) var(--space-8);
            }

            .auth-brand h1 {
                font-size: var(--font-size-3xl);
            }
        }

        @media (max-width: 768px) {
            .auth-layout {
                flex-direction: column;
            }

            .auth-brand {
                display: none;
            }

            .auth-form-panel {
                padding: var(--space-6);
                min-height: 100vh;
            }

            .auth-mobile-logo {
                display: flex;
            }
        }
    </style>

    <?= $headExtra ?? '' ?>
</head>
<body>

    <div class="auth-layout">

        <!-- ============================================
             LEFT PANEL - BRANDING
             ============================================ -->
        <div class="auth-brand">
            <div class="auth-brand-content">
                <!-- Logo -->
                <a href="/" class="auth-brand-logo">
                    <span class="auth-brand-logo-icon">
                        <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                            <path d="M10 11h12M10 16h8M10 21h10" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="auth-brand-logo-text">Lead<span>Form</span></span>
                </a>

                <!-- Heading -->
                <h1><?= htmlspecialchars($authHeading) ?></h1>
                <p><?= htmlspecialchars($authSubtext) ?></p>

                <!-- Feature Highlights -->
                <ul class="auth-features">
                    <li>
                        <div class="auth-feature-icon">&#9889;</div>
                        <div class="auth-feature-text">
                            <strong>Formularios Conversacionais</strong>
                            <span>Estilo chat ou typeform, altamente personalizaveis.</span>
                        </div>
                    </li>
                    <li>
                        <div class="auth-feature-icon">&#128202;</div>
                        <div class="auth-feature-text">
                            <strong>Analytics em Tempo Real</strong>
                            <span>Acompanhe cada interacao e otimize seus formularios.</span>
                        </div>
                    </li>
                    <li>
                        <div class="auth-feature-icon">&#128268;</div>
                        <div class="auth-feature-text">
                            <strong>Integracoes Poderosas</strong>
                            <span>Conecte com CRM, email marketing, webhooks e mais.</span>
                        </div>
                    </li>
                    <li>
                        <div class="auth-feature-icon">&#129302;</div>
                        <div class="auth-feature-text">
                            <strong>IA Integrada</strong>
                            <span>Gere formularios e analise respostas com inteligencia artificial.</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ============================================
             RIGHT PANEL - FORM CONTENT
             ============================================ -->
        <div class="auth-form-panel">
            <div class="auth-form-wrapper">
                <!-- Mobile-only logo -->
                <a href="/" class="auth-mobile-logo">
                    <svg class="auth-mobile-logo-icon" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="8" fill="url(#logo-grad-auth)"/>
                        <path d="M10 11h12M10 16h8M10 21h10" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="logo-grad-auth" x1="0" y1="0" x2="32" y2="32">
                                <stop stop-color="#667eea"/>
                                <stop offset="1" stop-color="#764ba2"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span class="auth-mobile-logo-text">Lead<span>Form</span></span>
                </a>

                <!-- Page content injected here -->
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

    <!-- ============================================
         DEV MODE ERROR BAR
         ============================================ -->
    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <div class="dev-mode-indicator">DEV</div>
    <div class="dev-error-bar" id="devErrorBar">
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

    <!-- Auto-dismiss toasts -->
    <script>
    (function() {
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

    <?= $footerExtra ?? '' ?>
</body>
</html>
