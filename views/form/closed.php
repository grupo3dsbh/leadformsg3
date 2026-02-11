<?php
/**
 * LeadForm SaaS - Closed Form Page
 *
 * Displayed when a form is not published, closed, or access is denied.
 *
 * Variables available via extract():
 *   $title   - Page title
 *   $message - Message to display
 *   $form    - The form record array (optional)
 */

$title   = htmlspecialchars($title ?? 'Formulario Indisponivel');
$message = htmlspecialchars($message ?? 'Este formulario nao esta aceitando respostas no momento.');
$form    = $form ?? [];
$formTitle = htmlspecialchars($form['title'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $title ?><?= $formTitle ? ' - ' . $formTitle : '' ?></title>

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
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #fff;
            -webkit-font-smoothing: antialiased;
        }

        .closed-container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            animation: fadeInUp 0.7s ease forwards;
        }

        .closed-icon {
            color: rgba(255, 255, 255, 0.35);
            margin-bottom: 2rem;
            display: inline-block;
        }

        .closed-heading {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
            color: rgba(255, 255, 255, 0.9);
        }

        .closed-text {
            font-size: 1.0625rem;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .closed-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 100px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .closed-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #6B7280;
        }

        .closed-home {
            display: block;
            margin-top: 2.5rem;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .closed-home:hover {
            color: rgba(255, 255, 255, 0.6);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 640px) {
            .closed-heading { font-size: 1.625rem; }
            .closed-text { font-size: 0.9375rem; }
        }
    </style>
</head>
<body>

    <div class="closed-container">
        <div class="closed-icon">
            <svg width="72" height="72" viewBox="0 0 72 72" fill="none">
                <circle cx="36" cy="36" r="33" stroke="currentColor" stroke-width="2.5" opacity="0.2"/>
                <rect x="22" y="32" width="28" height="22" rx="4" stroke="currentColor" stroke-width="2.5" opacity="0.6"/>
                <path d="M28 32v-6a8 8 0 1116 0v6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="36" cy="43" r="3" fill="currentColor" opacity="0.6"/>
            </svg>
        </div>

        <h1 class="closed-heading"><?= $title ?></h1>

        <p class="closed-text"><?= $message ?></p>

        <div class="closed-badge">
            <span class="dot"></span>
            Fechado
        </div>

        <a href="/" class="closed-home">Voltar ao inicio</a>
    </div>

</body>
</html>
