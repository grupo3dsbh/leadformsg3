<?php
/**
 * LeadForm SaaS - Password-Protected Form Page
 *
 * Full-screen page with a password input to unlock access to a protected form.
 *
 * Variables available via extract():
 *   $form         - The form record array (optional)
 *   $formSettings - Merged form display settings (optional)
 *   $error        - Error message if password was incorrect (optional)
 *   $formSlug     - The form slug for the POST target (optional)
 */

$form         = $form ?? [];
$formSettings = $formSettings ?? [];
$formTitle    = htmlspecialchars($form['title'] ?? 'Formulario Protegido');
$formSlug     = $formSlug ?? ($form['slug'] ?? '');
$brandColor   = htmlspecialchars($formSettings['brand_color'] ?? '#4F46E5');
$faviconUrl   = htmlspecialchars($formSettings['favicon'] ?? '/public/images/favicon.png');
$error        = $error ?? '';
$hint         = htmlspecialchars($formSettings['password_hint'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $formTitle ?> - Acesso Protegido</title>
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
            background: linear-gradient(135deg, #1a1a2e 0%, <?= $brandColor ?>22 50%, #1a1a2e 100%);
            color: #fff;
            -webkit-font-smoothing: antialiased;
        }

        .pw-container {
            text-align: center;
            padding: 2rem;
            max-width: 440px;
            width: 100%;
            animation: fadeInUp 0.6s ease forwards;
        }

        .pw-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 2rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .pw-heading {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.03em;
        }

        .pw-subtext {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .pw-form {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            backdrop-filter: blur(12px);
        }

        .pw-input-group {
            position: relative;
            margin-bottom: 1rem;
        }

        .pw-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            pointer-events: none;
        }

        .pw-input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: all 0.25s ease;
        }

        .pw-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .pw-input:focus {
            border-color: <?= $brandColor ?>;
            box-shadow: 0 0 0 3px <?= $brandColor ?>33;
            background: rgba(255, 255, 255, 0.1);
        }

        .pw-input.error {
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .pw-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            padding: 4px;
            font-size: 18px;
            transition: color 0.2s;
        }

        .pw-toggle:hover {
            color: rgba(255, 255, 255, 0.6);
        }

        .pw-error {
            font-size: 0.8125rem;
            color: #EF4444;
            text-align: left;
            margin-bottom: 1rem;
            padding-left: 2px;
        }

        .pw-hint {
            font-size: 0.8125rem;
            color: rgba(255, 255, 255, 0.35);
            text-align: left;
            margin-bottom: 1rem;
            padding-left: 2px;
        }

        .pw-btn {
            width: 100%;
            padding: 14px;
            background: <?= $brandColor ?>;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .pw-btn:hover {
            filter: brightness(1.1);
            box-shadow: 0 6px 20px <?= $brandColor ?>66;
        }

        .pw-btn:active {
            transform: scale(0.98);
        }

        .pw-home {
            display: block;
            margin-top: 2rem;
            font-size: 0.8125rem;
            color: rgba(255, 255, 255, 0.25);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .pw-home:hover {
            color: rgba(255, 255, 255, 0.5);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 640px) {
            .pw-heading { font-size: 1.5rem; }
            .pw-form { padding: 1.5rem; }
            .pw-container { padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="pw-container">
        <!-- Lock Icon -->
        <div class="pw-icon">
            <svg width="72" height="72" viewBox="0 0 72 72" fill="none">
                <rect x="20" y="34" width="32" height="24" rx="4" stroke="currentColor" stroke-width="2.5"/>
                <path d="M26 34v-8a10 10 0 0120 0v8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="36" cy="46" r="3.5" fill="currentColor" opacity="0.5"/>
                <path d="M36 49v4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>
            </svg>
        </div>

        <!-- Heading -->
        <h1 class="pw-heading"><?= $formTitle ?></h1>
        <p class="pw-subtext">Este formulario e protegido por senha. Insira a senha para continuar.</p>

        <!-- Password Form -->
        <form class="pw-form" method="POST" action="/f/<?= htmlspecialchars($formSlug) ?>/unlock" autocomplete="off">
            <div class="pw-input-group">
                <span class="pw-input-icon">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><rect x="4" y="8" width="10" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M6 8V6a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <input
                    type="password"
                    name="password"
                    class="pw-input <?= $error ? 'error' : '' ?>"
                    placeholder="Digite a senha"
                    required
                    autofocus
                    autocomplete="off"
                    id="pwInput"
                >
                <button type="button" class="pw-toggle" id="pwToggle" aria-label="Mostrar senha" title="Mostrar/ocultar senha">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" id="eyeOpen"><path d="M1.5 9s3-5.5 7.5-5.5S16.5 9 16.5 9s-3 5.5-7.5 5.5S1.5 9 1.5 9z" stroke="currentColor" stroke-width="1.5"/><circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5"/></svg>
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" id="eyeClosed" style="display:none;"><path d="M1.5 9s3-5.5 7.5-5.5S16.5 9 16.5 9s-3 5.5-7.5 5.5S1.5 9 1.5 9z" stroke="currentColor" stroke-width="1.5"/><circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M3 3l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </button>
            </div>

            <?php if ($error): ?>
            <div class="pw-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($hint): ?>
            <div class="pw-hint">Dica: <?= $hint ?></div>
            <?php endif; ?>

            <button type="submit" class="pw-btn">Desbloquear</button>
        </form>

        <a href="/" class="pw-home">Voltar ao inicio</a>
    </div>

    <script>
    (function() {
        var toggle = document.getElementById('pwToggle');
        var input = document.getElementById('pwInput');
        var eyeOpen = document.getElementById('eyeOpen');
        var eyeClosed = document.getElementById('eyeClosed');

        toggle.addEventListener('click', function() {
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            eyeOpen.style.display = isPassword ? 'none' : 'block';
            eyeClosed.style.display = isPassword ? 'block' : 'none';
            input.focus();
        });

        // Allow Enter key to submit
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                input.closest('form').submit();
            }
        });
    })();
    </script>

</body>
</html>
