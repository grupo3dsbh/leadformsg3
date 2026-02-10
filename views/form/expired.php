<?php
/**
 * LeadForm SaaS - Expired / Locked Form Page
 *
 * Full-screen message displayed when a form is expired, paused, or locked.
 *
 * Variables available via extract():
 *   $form         - The form record array (optional)
 *   $formSettings - Merged form display settings (optional)
 *   $reason       - Reason string: 'expired', 'paused', 'limit_reached', 'locked' (optional)
 *   $message      - Custom message override (optional)
 */

$form         = $form ?? [];
$formSettings = $formSettings ?? [];
$reason       = $reason ?? 'expired';
$formTitle    = htmlspecialchars($form['title'] ?? 'Formulario');
$brandColor   = htmlspecialchars($formSettings['brand_color'] ?? '#4F46E5');
$faviconUrl   = htmlspecialchars($formSettings['favicon'] ?? '/public/images/favicon.png');

// Determine heading and message based on reason
$reasonData = [
    'expired' => [
        'heading' => 'Formulario Expirado',
        'text'    => 'Este formulario nao esta mais aceitando respostas. O periodo de coleta foi encerrado.',
        'icon'    => '<svg width="72" height="72" viewBox="0 0 72 72" fill="none"><circle cx="36" cy="36" r="33" stroke="currentColor" stroke-width="2.5" opacity="0.2"/><path d="M36 20v18l12 6" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ],
    'paused' => [
        'heading' => 'Formulario Pausado',
        'text'    => 'Este formulario esta temporariamente pausado. Por favor, tente novamente mais tarde.',
        'icon'    => '<svg width="72" height="72" viewBox="0 0 72 72" fill="none"><circle cx="36" cy="36" r="33" stroke="currentColor" stroke-width="2.5" opacity="0.2"/><rect x="26" y="24" width="6" height="24" rx="2" fill="currentColor" opacity="0.6"/><rect x="40" y="24" width="6" height="24" rx="2" fill="currentColor" opacity="0.6"/></svg>',
    ],
    'limit_reached' => [
        'heading' => 'Limite Atingido',
        'text'    => 'Este formulario atingiu o numero maximo de respostas permitidas.',
        'icon'    => '<svg width="72" height="72" viewBox="0 0 72 72" fill="none"><circle cx="36" cy="36" r="33" stroke="currentColor" stroke-width="2.5" opacity="0.2"/><path d="M28 28l16 16M44 28L28 44" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
    ],
    'locked' => [
        'heading' => 'Formulario Bloqueado',
        'text'    => 'Este formulario esta bloqueado e nao pode receber novas respostas no momento.',
        'icon'    => '<svg width="72" height="72" viewBox="0 0 72 72" fill="none"><rect x="22" y="32" width="28" height="22" rx="4" stroke="currentColor" stroke-width="2.5" opacity="0.6"/><path d="M28 32v-6a8 8 0 1116 0v6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><circle cx="36" cy="43" r="3" fill="currentColor" opacity="0.6"/></svg>',
    ],
];

$data      = $reasonData[$reason] ?? $reasonData['expired'];
$heading   = htmlspecialchars($message ?? $data['heading']);
$text      = isset($message) ? '' : $data['text'];
$iconSvg   = $data['icon'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $heading ?> - <?= $formTitle ?></title>
    <link rel="icon" type="image/png" href="<?= $faviconUrl ?>">

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

        .expired-container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            animation: fadeInUp 0.7s ease forwards;
        }

        .expired-icon {
            color: rgba(255, 255, 255, 0.35);
            margin-bottom: 2rem;
            display: inline-block;
        }

        .expired-heading {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
            color: rgba(255, 255, 255, 0.9);
        }

        .expired-text {
            font-size: 1.0625rem;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .expired-badge {
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

        .expired-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: <?php
                echo $reason === 'paused' ? '#F59E0B' : ($reason === 'locked' ? '#EF4444' : '#6B7280');
            ?>;
        }

        .expired-home {
            display: block;
            margin-top: 2.5rem;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .expired-home:hover {
            color: rgba(255, 255, 255, 0.6);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 640px) {
            .expired-heading { font-size: 1.625rem; }
            .expired-text { font-size: 0.9375rem; }
        }
    </style>
</head>
<body>

    <div class="expired-container">
        <!-- Icon -->
        <div class="expired-icon">
            <?= $iconSvg ?>
        </div>

        <!-- Heading -->
        <h1 class="expired-heading"><?= $heading ?></h1>

        <!-- Description -->
        <?php if ($text): ?>
        <p class="expired-text"><?= htmlspecialchars($text) ?></p>
        <?php endif; ?>

        <!-- Status badge -->
        <div class="expired-badge">
            <span class="dot"></span>
            <?= htmlspecialchars(ucfirst($reason === 'limit_reached' ? 'limite atingido' : $reason)) ?>
        </div>

        <!-- Link back -->
        <a href="/" class="expired-home">Voltar ao inicio</a>
    </div>

</body>
</html>
