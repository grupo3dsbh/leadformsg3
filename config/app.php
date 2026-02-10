<?php
/**
 * Application Configuration
 */

// Load .env file
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Helper function
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Application settings
define('APP_NAME', env('APP_NAME', 'LeadForm SaaS'));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true');
define('APP_KEY', env('APP_KEY', 'default-key-change-me-in-production'));
define('APP_LOCALE', env('APP_LOCALE', 'pt_BR'));

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', APP_ENV === 'production' ? '1' : '0');
ini_set('session.use_strict_mode', '1');
