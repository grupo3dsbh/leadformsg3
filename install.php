<?php
/**
 * LeadForm SaaS - Instalador Web
 *
 * Wizard de instalação em 5 etapas:
 *   1. Verificação de requisitos do sistema
 *   2. Configuração do banco de dados
 *   3. Criação das tabelas e dados iniciais
 *   4. Configuração do administrador
 *   5. Finalização
 *
 * Após a instalação, este arquivo cria um arquivo .installed como lock.
 * Acesse novamente /install.php para ver a mensagem de já instalado.
 */

define('ROOT_PATH', __DIR__);
define('INSTALL_LOCK', ROOT_PATH . '/storage/.installed');

// Se já foi instalado, bloqueia acesso
if (file_exists(INSTALL_LOCK)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>LeadForm - Já Instalado</title>
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family:'Inter',system-ui,sans-serif; background:#f8fafc; display:flex; align-items:center; justify-content:center; min-height:100vh; }
            .box { background:#fff; border-radius:16px; padding:48px; max-width:480px; text-align:center; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
            .icon { font-size:48px; margin-bottom:16px; }
            h1 { font-size:24px; color:#1f2937; margin-bottom:8px; }
            p { color:#6b7280; margin-bottom:24px; line-height:1.6; }
            a { display:inline-block; padding:12px 32px; background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border-radius:8px; text-decoration:none; font-weight:600; }
            a:hover { opacity:0.9; }
            .warn { font-size:12px; color:#9ca3af; margin-top:16px; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="icon">&#9989;</div>
            <h1>LeadForm ja esta instalado</h1>
            <p>O sistema ja foi instalado anteriormente. Se deseja reinstalar, remova o arquivo <code>storage/.installed</code> e acesse esta pagina novamente.</p>
            <a href="/">Ir para o site</a>
            <p class="warn">Por seguranca, recomendamos remover o arquivo install.php apos a instalacao.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Iniciar sessão para manter estado entre etapas
session_start();

// Determinar etapa atual
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$step = max(1, min(5, $step));

// Processar POST de cada etapa
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Apenas verificação - avança se tudo OK
            $checks = checkRequirements();
            $allPassed = true;
            foreach ($checks as $c) {
                if (!$c['ok']) { $allPassed = false; break; }
            }
            if ($allPassed) {
                header('Location: install.php?step=2');
                exit;
            } else {
                $errors[] = 'Corrija os requisitos acima antes de continuar.';
            }
            break;

        case 2:
            // Testar conexão com banco
            $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
            $dbPort = trim($_POST['db_port'] ?? '3306');
            $dbName = trim($_POST['db_name'] ?? 'leadform_saas');
            $dbUser = trim($_POST['db_user'] ?? 'root');
            $dbPass = $_POST['db_pass'] ?? '';

            // Salvar na sessão
            $_SESSION['install_db'] = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass');

            // Testar conexão
            try {
                $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Tentar criar o banco se não existir
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");

                header('Location: install.php?step=3');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Erro na conexao: ' . $e->getMessage();
            }
            break;

        case 3:
            // Executar schema e seeds
            $db = $_SESSION['install_db'] ?? null;
            if (!$db) {
                header('Location: install.php?step=2');
                exit;
            }

            try {
                $dsn = "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['dbUser'], $db['dbPass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                // Executar schema
                $schema = file_get_contents(ROOT_PATH . '/database/schema.sql');
                $pdo->exec($schema);

                // Executar seeds
                $seeds = file_get_contents(ROOT_PATH . '/database/seeds.sql');
                $pdo->exec($seeds);

                $_SESSION['install_db_done'] = true;

                header('Location: install.php?step=4');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Erro ao executar SQL: ' . $e->getMessage();
            }
            break;

        case 4:
            // Configurar admin
            $db = $_SESSION['install_db'] ?? null;
            if (!$db) {
                header('Location: install.php?step=2');
                exit;
            }

            $adminName  = trim($_POST['admin_name'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass  = $_POST['admin_pass'] ?? '';
            $adminPass2 = $_POST['admin_pass2'] ?? '';

            $appName    = trim($_POST['app_name'] ?? 'LeadForm SaaS');
            $appUrl     = trim($_POST['app_url'] ?? 'http://localhost');
            $appLocale  = trim($_POST['app_locale'] ?? 'pt_BR');

            // Validações
            if (empty($adminName)) $errors[] = 'Nome do administrador e obrigatorio.';
            if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail invalido.';
            if (strlen($adminPass) < 6) $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
            if ($adminPass !== $adminPass2) $errors[] = 'As senhas nao conferem.';

            if (empty($errors)) {
                try {
                    $dsn = "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db['dbUser'], $db['dbPass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);

                    // Atualizar super admin
                    $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 10]);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = 1");
                    $stmt->execute([$adminName, $adminEmail, $hash]);

                    // Se não atualizou nenhuma linha (user id=1 não existe), insere
                    if ($stmt->rowCount() === 0) {
                        $stmt = $pdo->prepare("INSERT INTO users (tenant_id, name, email, password, role, two_factor_enabled, email_verified_at, status, locale, timezone, created_at, updated_at) VALUES (NULL, ?, ?, ?, 'super_admin', 0, NOW(), 'active', ?, 'America/Sao_Paulo', NOW(), NOW())");
                        $stmt->execute([$adminName, $adminEmail, $hash, $appLocale]);
                    }

                    // Gerar .env
                    $appKey = bin2hex(random_bytes(16));
                    $jwtSecret = bin2hex(random_bytes(16));

                    $envContent = "# Application\n";
                    $envContent .= "APP_NAME=\"{$appName}\"\n";
                    $envContent .= "APP_URL={$appUrl}\n";
                    $envContent .= "APP_ENV=production\n";
                    $envContent .= "APP_DEBUG=false\n";
                    $envContent .= "APP_KEY={$appKey}\n";
                    $envContent .= "APP_LOCALE={$appLocale}\n";
                    $envContent .= "\n# Database\n";
                    $envContent .= "DB_HOST={$db['dbHost']}\n";
                    $envContent .= "DB_PORT={$db['dbPort']}\n";
                    $envContent .= "DB_NAME={$db['dbName']}\n";
                    $envContent .= "DB_USER={$db['dbUser']}\n";
                    $envContent .= "DB_PASS={$db['dbPass']}\n";
                    $envContent .= "\n# Mail\n";
                    $envContent .= "MAIL_HOST=smtp.mailtrap.io\n";
                    $envContent .= "MAIL_PORT=587\n";
                    $envContent .= "MAIL_USER=\n";
                    $envContent .= "MAIL_PASS=\n";
                    $envContent .= "MAIL_FROM=noreply@leadform.io\n";
                    $envContent .= "MAIL_FROM_NAME=\"{$appName}\"\n";
                    $envContent .= "\n# Storage\n";
                    $envContent .= "UPLOAD_MAX_SIZE=10485760\n";
                    $envContent .= "UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv,zip\n";
                    $envContent .= "\n# AI Configuration\n";
                    $envContent .= "AI_PROVIDER=openai\n";
                    $envContent .= "AI_API_KEY=\n";
                    $envContent .= "AI_MODEL=gpt-4\n";
                    $envContent .= "\n# reCAPTCHA\n";
                    $envContent .= "RECAPTCHA_SITE_KEY=\n";
                    $envContent .= "RECAPTCHA_SECRET_KEY=\n";
                    $envContent .= "\n# Google\n";
                    $envContent .= "GOOGLE_ANALYTICS_ID=\n";
                    $envContent .= "GOOGLE_TAG_MANAGER_ID=\n";
                    $envContent .= "GOOGLE_MAPS_API_KEY=\n";
                    $envContent .= "\n# Facebook\n";
                    $envContent .= "FACEBOOK_PIXEL_ID=\n";
                    $envContent .= "\n# Stripe\n";
                    $envContent .= "STRIPE_PUBLIC_KEY=\n";
                    $envContent .= "STRIPE_SECRET_KEY=\n";
                    $envContent .= "STRIPE_WEBHOOK_SECRET=\n";
                    $envContent .= "\n# PayPal\n";
                    $envContent .= "PAYPAL_CLIENT_ID=\n";
                    $envContent .= "PAYPAL_SECRET=\n";
                    $envContent .= "PAYPAL_MODE=sandbox\n";
                    $envContent .= "\n# JWT\n";
                    $envContent .= "JWT_SECRET={$jwtSecret}\n";
                    $envContent .= "JWT_EXPIRY=86400\n";

                    file_put_contents(ROOT_PATH . '/.env', $envContent);

                    // Criar lock de instalação
                    file_put_contents(INSTALL_LOCK, json_encode([
                        'installed_at' => date('Y-m-d H:i:s'),
                        'version' => '1.0.0',
                        'admin_email' => $adminEmail,
                    ]));

                    $_SESSION['install_admin_email'] = $adminEmail;
                    $_SESSION['install_app_url'] = $appUrl;

                    header('Location: install.php?step=5');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Erro ao configurar: ' . $e->getMessage();
                }
            }
            break;
    }
}

/**
 * Verifica requisitos do sistema
 */
function checkRequirements(): array
{
    $checks = [];

    // PHP Version
    $checks[] = [
        'name' => 'PHP 8.1 ou superior',
        'detail' => 'Versao atual: PHP ' . PHP_VERSION,
        'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
    ];

    // PDO MySQL
    $checks[] = [
        'name' => 'Extensao PDO MySQL',
        'detail' => 'Necessaria para conexao com o banco de dados',
        'ok' => extension_loaded('pdo_mysql'),
    ];

    // mbstring
    $checks[] = [
        'name' => 'Extensao mbstring',
        'detail' => 'Necessaria para manipulacao de strings UTF-8',
        'ok' => extension_loaded('mbstring'),
    ];

    // json
    $checks[] = [
        'name' => 'Extensao JSON',
        'detail' => 'Necessaria para codificar/decodificar dados',
        'ok' => extension_loaded('json'),
    ];

    // openssl
    $checks[] = [
        'name' => 'Extensao OpenSSL',
        'detail' => 'Necessaria para criptografia e tokens seguros',
        'ok' => extension_loaded('openssl'),
    ];

    // fileinfo
    $checks[] = [
        'name' => 'Extensao Fileinfo',
        'detail' => 'Necessaria para validacao de upload de arquivos',
        'ok' => extension_loaded('fileinfo'),
    ];

    // session
    $checks[] = [
        'name' => 'Extensao Session',
        'detail' => 'Necessaria para sessoes de usuario',
        'ok' => extension_loaded('session'),
    ];

    // cURL
    $checks[] = [
        'name' => 'Extensao cURL',
        'detail' => 'Necessaria para integracoes e webhooks',
        'ok' => extension_loaded('curl'),
    ];

    // GD or Imagick
    $checks[] = [
        'name' => 'Extensao GD ou Imagick',
        'detail' => 'Necessaria para manipulacao de imagens',
        'ok' => extension_loaded('gd') || extension_loaded('imagick'),
    ];

    // mod_rewrite (check via .htaccess existence)
    $checks[] = [
        'name' => 'Apache mod_rewrite ou equivalente',
        'detail' => 'Necessario para URLs amigaveis (verificado pela existencia do .htaccess)',
        'ok' => file_exists(ROOT_PATH . '/.htaccess'),
    ];

    // Writable directories
    $writableDirs = ['storage/logs', 'storage/cache', 'public/uploads'];
    foreach ($writableDirs as $dir) {
        $fullPath = ROOT_PATH . '/' . $dir;
        $checks[] = [
            'name' => "Diretorio gravavel: {$dir}/",
            'detail' => is_writable($fullPath) ? 'OK - Permissao de escrita' : 'Sem permissao de escrita - execute: chmod 775 ' . $dir,
            'ok' => is_dir($fullPath) && is_writable($fullPath),
        ];
    }

    // Root directory writable (for .env)
    $checks[] = [
        'name' => 'Diretorio raiz gravavel (para .env)',
        'detail' => is_writable(ROOT_PATH) ? 'OK' : 'Necessario para criar o arquivo .env',
        'ok' => is_writable(ROOT_PATH),
    ];

    // Database files exist
    $checks[] = [
        'name' => 'Arquivo schema.sql presente',
        'detail' => 'database/schema.sql',
        'ok' => file_exists(ROOT_PATH . '/database/schema.sql'),
    ];

    $checks[] = [
        'name' => 'Arquivo seeds.sql presente',
        'detail' => 'database/seeds.sql',
        'ok' => file_exists(ROOT_PATH . '/database/seeds.sql'),
    ];

    return $checks;
}

$requirements = ($step === 1) ? checkRequirements() : [];

// Dados da sessão para preencher campos
$dbData = $_SESSION['install_db'] ?? [
    'dbHost' => '127.0.0.1',
    'dbPort' => '3306',
    'dbName' => 'leadform_saas',
    'dbUser' => 'root',
    'dbPass' => '',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeadForm - Instalador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #818CF8;
            --secondary: #7C3AED;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --radius: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top bar */
        .installer-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 24px 0;
            text-align: center;
            color: #fff;
        }
        .installer-header .logo {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .installer-header .logo span { color: rgba(255,255,255,0.7); font-weight: 400; }
        .installer-header .subtitle {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 4px;
        }

        /* Steps indicator */
        .steps-bar {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 0;
        }
        .steps-container {
            max-width: 700px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            position: relative;
        }
        .steps-container::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 60px;
            right: 60px;
            height: 3px;
            background: var(--gray-200);
            z-index: 0;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            z-index: 1;
            position: relative;
        }
        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            border: 3px solid var(--gray-200);
            background: #fff;
            color: var(--gray-400);
            transition: all 0.3s ease;
        }
        .step-item.active .step-circle {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }
        .step-item.done .step-circle {
            border-color: var(--success);
            background: var(--success);
            color: #fff;
        }
        .step-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .step-item.active .step-label { color: var(--primary); }
        .step-item.done .step-label { color: var(--success); }

        /* Main content */
        .installer-body {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 32px 20px;
        }
        .installer-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            width: 100%;
            max-width: 640px;
            padding: 40px;
        }
        .installer-card h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--gray-900);
        }
        .installer-card .desc {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-group label .required {
            color: var(--danger);
        }
        .form-group small {
            display: block;
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 4px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--gray-800);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            outline: none;
            background: #fff;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .form-input::placeholder { color: var(--gray-300); }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4);
        }
        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        .btn-secondary:hover { background: var(--gray-200); }
        .btn-success {
            background: linear-gradient(135deg, #059669, #10B981);
            color: #fff;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-100);
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-danger {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        .alert-success {
            background: #F0FDF4;
            color: #166534;
            border: 1px solid #BBF7D0;
        }
        .alert-warning {
            background: #FFFBEB;
            color: #92400E;
            border: 1px solid #FDE68A;
        }
        .alert-info {
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }

        /* Requirements list */
        .req-list { list-style: none; }
        .req-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
        }
        .req-item:last-child { border-bottom: none; }
        .req-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .req-ok .req-icon {
            background: #D1FAE5;
            color: #059669;
        }
        .req-fail .req-icon {
            background: #FEE2E2;
            color: #DC2626;
        }
        .req-name { font-weight: 600; color: var(--gray-800); }
        .req-detail { font-size: 12px; color: var(--gray-400); margin-top: 2px; }
        .req-fail .req-name { color: var(--danger); }

        /* Step 3: Progress */
        .progress-area {
            text-align: center;
            padding: 20px 0;
        }
        .progress-bar-wrap {
            background: var(--gray-100);
            border-radius: 8px;
            height: 12px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 8px;
            transition: width 0.3s ease;
        }
        .progress-status {
            font-size: 14px;
            color: var(--gray-500);
        }

        /* Step 5: Success */
        .success-area {
            text-align: center;
            padding: 20px 0;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #059669, #10B981);
            color: #fff;
            font-size: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        }
        .info-box {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .info-box h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            border-bottom: 1px solid var(--gray-100);
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: var(--gray-500); }
        .info-row .value { font-weight: 600; color: var(--gray-800); }

        /* Loading spinner */
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer */
        .installer-footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: var(--gray-400);
        }

        /* Responsive */
        @media (max-width: 640px) {
            .installer-card { padding: 24px; }
            .form-row { grid-template-columns: 1fr; }
            .step-label { display: none; }
            .steps-container::before { left: 40px; right: 40px; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="installer-header">
        <div class="logo">Lead<span>Form</span></div>
        <div class="subtitle">Assistente de Instalacao</div>
    </div>

    <!-- Steps -->
    <div class="steps-bar">
        <div class="steps-container">
            <?php
            $steps = [
                1 => 'Requisitos',
                2 => 'Banco de Dados',
                3 => 'Instalar',
                4 => 'Administrador',
                5 => 'Concluido',
            ];
            foreach ($steps as $num => $label):
                $class = '';
                if ($num < $step) $class = 'done';
                elseif ($num === $step) $class = 'active';
            ?>
            <div class="step-item <?= $class ?>">
                <div class="step-circle">
                    <?php if ($num < $step): ?>&#10003;<?php else: ?><?= $num ?><?php endif; ?>
                </div>
                <div class="step-label"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Body -->
    <div class="installer-body">
        <div class="installer-card">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- STEP 1: REQUIREMENTS -->
                <h2>Verificacao de Requisitos</h2>
                <p class="desc">Verificando se o servidor atende aos requisitos minimos para executar o LeadForm.</p>

                <ul class="req-list">
                    <?php
                    $allOk = true;
                    foreach ($requirements as $req):
                        if (!$req['ok']) $allOk = false;
                    ?>
                    <li class="req-item <?= $req['ok'] ? 'req-ok' : 'req-fail' ?>">
                        <div class="req-icon"><?= $req['ok'] ? '&#10003;' : '&#10007;' ?></div>
                        <div>
                            <div class="req-name"><?= htmlspecialchars($req['name']) ?></div>
                            <div class="req-detail"><?= htmlspecialchars($req['detail']) ?></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!$allOk): ?>
                    <div class="alert alert-warning" style="margin-top:20px;">
                        Alguns requisitos nao foram atendidos. Corrija os itens em vermelho e recarregue a pagina.
                    </div>
                <?php endif; ?>

                <form method="POST" action="install.php?step=1">
                    <div class="btn-group">
                        <span></span>
                        <button type="submit" class="btn <?= $allOk ? 'btn-primary' : 'btn-secondary' ?>" <?= $allOk ? '' : 'disabled' ?>>
                            Continuar &#8594;
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 2): ?>
                <!-- STEP 2: DATABASE -->
                <h2>Configuracao do Banco de Dados</h2>
                <p class="desc">Informe os dados de conexao com o MySQL. O banco sera criado automaticamente se nao existir.</p>

                <form method="POST" action="install.php?step=2">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Host <span class="required">*</span></label>
                            <input type="text" name="db_host" class="form-input" value="<?= htmlspecialchars($dbData['dbHost']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Porta</label>
                            <input type="text" name="db_port" class="form-input" value="<?= htmlspecialchars($dbData['dbPort']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nome do Banco <span class="required">*</span></label>
                        <input type="text" name="db_name" class="form-input" value="<?= htmlspecialchars($dbData['dbName']) ?>" required>
                        <small>Sera criado automaticamente se nao existir.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Usuario <span class="required">*</span></label>
                            <input type="text" name="db_user" class="form-input" value="<?= htmlspecialchars($dbData['dbUser']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="db_pass" class="form-input" value="<?= htmlspecialchars($dbData['dbPass']) ?>">
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="install.php?step=1" class="btn btn-secondary">&#8592; Voltar</a>
                        <button type="submit" class="btn btn-primary">Testar Conexao e Continuar &#8594;</button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <!-- STEP 3: INSTALL DATABASE -->
                <h2>Instalacao do Banco de Dados</h2>
                <p class="desc">As tabelas e dados iniciais serao criados no banco de dados.</p>

                <div class="alert alert-info">
                    Serao criadas 35 tabelas com estrutura completa, incluindo planos padrao, permissoes, configuracoes do sistema, temas e traducoes (pt-BR e en).
                </div>

                <div id="installProgress" style="display:none;">
                    <div class="progress-area">
                        <div class="spinner"></div>
                        <div class="progress-status">Criando tabelas e inserindo dados...</div>
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="install.php?step=3" id="installForm">
                    <div class="btn-group">
                        <a href="install.php?step=2" class="btn btn-secondary">&#8592; Voltar</a>
                        <button type="submit" class="btn btn-primary" id="installBtn" onclick="startInstall()">
                            Instalar Banco de Dados &#8594;
                        </button>
                    </div>
                </form>

                <script>
                function startInstall() {
                    document.getElementById('installProgress').style.display = 'block';
                    document.getElementById('installBtn').disabled = true;
                    document.getElementById('installBtn').textContent = 'Instalando...';
                    var bar = document.getElementById('progressBar');
                    var w = 0;
                    var timer = setInterval(function() {
                        w += Math.random() * 15;
                        if (w > 90) w = 90;
                        bar.style.width = w + '%';
                    }, 300);
                    // O form envia normalmente e redireciona
                    setTimeout(function() { clearInterval(timer); }, 10000);
                }
                </script>

            <?php elseif ($step === 4): ?>
                <!-- STEP 4: ADMIN CONFIG -->
                <h2>Configuracao do Administrador</h2>
                <p class="desc">Configure a conta do super administrador e as informacoes basicas do sistema.</p>

                <form method="POST" action="install.php?step=4">
                    <div style="margin-bottom:28px;">
                        <h3 style="font-size:15px; font-weight:700; color:var(--gray-700); margin-bottom:16px; padding-bottom:8px; border-bottom:2px solid var(--gray-100);">
                            Informacoes do Sistema
                        </h3>

                        <div class="form-group">
                            <label>Nome do Sistema</label>
                            <input type="text" name="app_name" class="form-input" value="<?= htmlspecialchars($_POST['app_name'] ?? 'LeadForm SaaS') ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>URL do Sistema <span class="required">*</span></label>
                                <input type="url" name="app_url" class="form-input" value="<?= htmlspecialchars($_POST['app_url'] ?? 'http://localhost') ?>" required>
                                <small>Ex: https://app.leadform.com.br</small>
                            </div>
                            <div class="form-group">
                                <label>Idioma</label>
                                <select name="app_locale" class="form-input">
                                    <option value="pt_BR" <?= ($_POST['app_locale'] ?? 'pt_BR') === 'pt_BR' ? 'selected' : '' ?>>Portugues (Brasil)</option>
                                    <option value="en" <?= ($_POST['app_locale'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 style="font-size:15px; font-weight:700; color:var(--gray-700); margin-bottom:16px; padding-bottom:8px; border-bottom:2px solid var(--gray-100);">
                            Conta do Super Administrador
                        </h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome Completo <span class="required">*</span></label>
                                <input type="text" name="admin_name" class="form-input" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>E-mail <span class="required">*</span></label>
                                <input type="email" name="admin_email" class="form-input" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Senha <span class="required">*</span></label>
                                <input type="password" name="admin_pass" class="form-input" minlength="6" required>
                                <small>Minimo 6 caracteres</small>
                            </div>
                            <div class="form-group">
                                <label>Confirmar Senha <span class="required">*</span></label>
                                <input type="password" name="admin_pass2" class="form-input" minlength="6" required>
                            </div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="install.php?step=3" class="btn btn-secondary">&#8592; Voltar</a>
                        <button type="submit" class="btn btn-primary">Finalizar Instalacao &#8594;</button>
                    </div>
                </form>

            <?php elseif ($step === 5): ?>
                <!-- STEP 5: COMPLETE -->
                <div class="success-area">
                    <div class="success-icon">&#10003;</div>
                    <h2 style="margin-bottom: 8px;">Instalacao Concluida!</h2>
                    <p class="desc">O LeadForm foi instalado com sucesso e esta pronto para uso.</p>

                    <div class="info-box">
                        <h4>Dados de Acesso</h4>
                        <div class="info-row">
                            <span class="label">URL do Sistema</span>
                            <span class="value"><?= htmlspecialchars($_SESSION['install_app_url'] ?? 'http://localhost') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Painel Admin</span>
                            <span class="value"><?= htmlspecialchars(($_SESSION['install_app_url'] ?? 'http://localhost') . '/admin/dashboard') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">E-mail do Admin</span>
                            <span class="value"><?= htmlspecialchars($_SESSION['install_admin_email'] ?? '') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Senha</span>
                            <span class="value">(a que voce definiu)</span>
                        </div>
                    </div>

                    <div class="alert alert-warning" style="text-align:left;">
                        <strong>Importante:</strong> Por seguranca, recomendamos fortemente que voce remova ou renomeie o arquivo <code>install.php</code> do servidor.
                    </div>

                    <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <a href="/" class="btn btn-primary">Ir para o Site</a>
                        <a href="/admin/dashboard" class="btn btn-success">Acessar o Admin</a>
                        <a href="/login" class="btn btn-secondary">Fazer Login</a>
                    </div>
                </div>

                <?php
                // Limpar sessão de instalação
                unset($_SESSION['install_db'], $_SESSION['install_db_done'], $_SESSION['install_admin_email'], $_SESSION['install_app_url']);
                ?>

            <?php endif; ?>

        </div>
    </div>

    <!-- Footer -->
    <div class="installer-footer">
        LeadForm SaaS v1.0.0 &bull; Instalador
    </div>

</body>
</html>
