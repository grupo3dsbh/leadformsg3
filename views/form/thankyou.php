<?php
/**
 * LeadForm SaaS - Thank You Page
 *
 * Full-screen, standalone page displayed after a successful form submission.
 * Supports custom messages, icons, redirect buttons, and theme matching.
 *
 * Variables available via extract():
 *   $form         - The form record array
 *   $formSettings - Merged form display settings
 *   $thankyou     - Thank-you page configuration array (optional)
 *   $entry        - The submission entry (optional)
 */

$form         = $form ?? [];
$formSettings = $formSettings ?? [];
$thankyou     = $thankyou ?? [];
$entry        = $entry ?? [];

$formTitle = htmlspecialchars($form['title'] ?? 'LeadForm');

// Thank you page configuration
$heading     = htmlspecialchars($thankyou['heading'] ?? 'Obrigado!');
$message     = htmlspecialchars($thankyou['message'] ?? 'Sua resposta foi enviada com sucesso. Entraremos em contato em breve.');
$icon        = $thankyou['icon'] ?? 'checkmark'; // checkmark, heart, star, rocket, custom
$customIcon  = htmlspecialchars($thankyou['custom_icon_url'] ?? '');
$showButton  = ($thankyou['show_button'] ?? true) !== false;
$buttonText  = htmlspecialchars($thankyou['button_text'] ?? 'Voltar ao site');
$buttonUrl   = htmlspecialchars($thankyou['button_url'] ?? '/');
$autoRedirect     = !empty($thankyou['auto_redirect']);
$autoRedirectUrl  = htmlspecialchars($thankyou['auto_redirect_url'] ?? $buttonUrl);
$autoRedirectSecs = max(1, (int) ($thankyou['auto_redirect_seconds'] ?? 5));

// Theme
$brandColor = htmlspecialchars($formSettings['brand_color'] ?? '#4F46E5');
$bgType     = $formSettings['background_type'] ?? 'gradient';
$bgColor    = htmlspecialchars($formSettings['background_color'] ?? '#1a1a2e');

// Icon map (using HTML entities/SVG for each preset)
$iconMap = [
    'checkmark' => '<svg width="64" height="64" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="30" stroke="currentColor" stroke-width="3" opacity="0.2"/><circle cx="32" cy="32" r="30" stroke="currentColor" stroke-width="3" stroke-dasharray="188.5" stroke-dashoffset="0" style="animation: tyCircle 0.6s ease forwards;"/><path d="M20 33l8 8 16-16" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" style="animation: tyCheck 0.4s 0.5s ease forwards; stroke-dasharray: 40; stroke-dashoffset: 40;"/></svg>',
    'heart'     => '<svg width="64" height="64" viewBox="0 0 64 64" fill="none"><path d="M32 56s-22-12.5-22-27a12 12 0 0122-6.5A12 12 0 0154 29c0 14.5-22 27-22 27z" fill="currentColor" opacity="0.15" stroke="currentColor" stroke-width="2.5"/></svg>',
    'star'      => '<svg width="64" height="64" viewBox="0 0 64 64" fill="none"><path d="M32 6l7.9 16 17.6 2.6-12.8 12.4 3 17.5L32 46.3 16.3 54.5l3-17.5L6.5 24.6 24.1 22z" fill="currentColor" opacity="0.15" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/></svg>',
    'rocket'    => '<svg width="64" height="64" viewBox="0 0 64 64" fill="none"><path d="M32 10c0 0-16 10-16 30l6 8h20l6-8c0-20-16-30-16-30z" fill="currentColor" opacity="0.15" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><circle cx="32" cy="30" r="5" stroke="currentColor" stroke-width="2.5"/><path d="M26 48l-4 8M38 48l4 8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>',
];
$iconSvg = $iconMap[$icon] ?? $iconMap['checkmark'];
if ($icon === 'custom' && $customIcon) {
    $iconSvg = '<img src="' . $customIcon . '" alt="" width="64" height="64" style="object-fit:contain;">';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $heading ?> - <?= $formTitle ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($formSettings['favicon'] ?? '/public/images/favicon.png') ?>">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            <?php if ($bgType === 'color'): ?>
            background: <?= $bgColor ?>;
            <?php else: ?>
            background: linear-gradient(135deg, #1a1a2e 0%, <?= $brandColor ?>33 50%, #1a1a2e 100%);
            <?php endif; ?>
            color: #fff;
            -webkit-font-smoothing: antialiased;
        }

        .ty-container {
            text-align: center;
            padding: 2rem;
            max-width: 540px;
            width: 100%;
            animation: tyFadeIn 0.8s ease forwards;
        }

        .ty-icon {
            color: <?= $brandColor ?>;
            margin-bottom: 2rem;
            display: inline-block;
        }

        .ty-heading {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        .ty-message {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }

        .ty-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 14px 32px;
            background: <?= $brandColor ?>;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .ty-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(<?php
                echo hexdec(substr($brandColor, 1, 2)) . ', '
                   . hexdec(substr($brandColor, 3, 2)) . ', '
                   . hexdec(substr($brandColor, 5, 2));
            ?>, 0.4);
            color: #fff;
        }

        .ty-redirect-notice {
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.4);
        }

        .ty-redirect-notice span {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Confetti-style decorative dots */
        .ty-decoration {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .ty-decoration span {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            opacity: 0;
            animation: tyParticle 2s ease-out forwards;
        }

        .ty-container { position: relative; z-index: 1; }

        @keyframes tyFadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes tyCircle {
            from { stroke-dashoffset: 188.5; }
            to { stroke-dashoffset: 0; }
        }

        @keyframes tyCheck {
            to { stroke-dashoffset: 0; }
        }

        @keyframes tyParticle {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-200px) scale(0); }
        }

        @media (max-width: 640px) {
            .ty-heading { font-size: 1.875rem; }
            .ty-message { font-size: 1rem; }
            .ty-container { padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <!-- Decorative particles -->
    <div class="ty-decoration" id="tyDecoration"></div>

    <div class="ty-container">
        <!-- Icon -->
        <div class="ty-icon">
            <?= $iconSvg ?>
        </div>

        <!-- Heading -->
        <h1 class="ty-heading"><?= $heading ?></h1>

        <!-- Message -->
        <p class="ty-message"><?= $message ?></p>

        <!-- Button -->
        <?php if ($showButton): ?>
        <a href="<?= $buttonUrl ?>" class="ty-btn">
            <?= $buttonText ?>
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <?php endif; ?>

        <!-- Auto redirect countdown -->
        <?php if ($autoRedirect): ?>
        <p class="ty-redirect-notice">
            Redirecionando em <span id="countdown"><?= $autoRedirectSecs ?></span> segundos...
        </p>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        // Generate decorative particles
        var decoration = document.getElementById('tyDecoration');
        var colors = ['<?= $brandColor ?>', '#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe'];
        for (var i = 0; i < 30; i++) {
            var span = document.createElement('span');
            span.style.left = Math.random() * 100 + '%';
            span.style.top = (50 + Math.random() * 50) + '%';
            span.style.background = colors[Math.floor(Math.random() * colors.length)];
            span.style.width = (4 + Math.random() * 8) + 'px';
            span.style.height = span.style.width;
            span.style.animationDelay = (Math.random() * 0.8) + 's';
            span.style.animationDuration = (1.5 + Math.random() * 1.5) + 's';
            decoration.appendChild(span);
        }

        <?php if ($autoRedirect): ?>
        // Auto redirect countdown
        var seconds = <?= $autoRedirectSecs ?>;
        var countdownEl = document.getElementById('countdown');
        var timer = setInterval(function() {
            seconds--;
            if (countdownEl) countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = <?= json_encode($autoRedirectUrl) ?>;
            }
        }, 1000);
        <?php endif; ?>
    })();
    </script>

</body>
</html>
