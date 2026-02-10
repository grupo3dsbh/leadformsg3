<?php

declare(strict_types=1);

/**
 * Global Helper Functions
 *
 * A collection of utility functions available throughout the application.
 * These are loaded early in the bootstrap process (index.php) and provide
 * convenient shortcuts for views, redirects, authentication, formatting
 * and security operations.
 */

// ==================================================================
// VIEW & RESPONSE
// ==================================================================

/**
 * Render a view file with the given data.
 *
 * View files live under the /views directory. Dot-notation is converted
 * to directory separators (e.g., "auth.login" => "views/auth/login.php").
 *
 * @param string $name The view name (slash or dot notation).
 * @param array  $data Variables to extract into the view scope.
 * @return string The rendered HTML output.
 */
function view(string $name, array $data = []): string
{
    $name = str_replace('.', '/', $name);
    $path = ROOT_PATH . '/views/' . $name . '.php';

    if (!file_exists($path)) {
        throw new RuntimeException("View not found: {$name} ({$path})");
    }

    // Extract data so the view can use $variable directly.
    extract($data, EXTR_SKIP);

    ob_start();
    include $path;
    return ob_get_clean() ?: '';
}

/**
 * Send a redirect response and terminate execution.
 *
 * @param string $url  The target URL.
 * @param int    $code The HTTP status code (default 302).
 */
function redirect(string $url, int $code = 302): never
{
    header("Location: {$url}", true, $code);
    exit;
}

/**
 * Retrieve old form input from the previous request (stored in flash).
 *
 * @param string      $key     The input field name.
 * @param string|null $default Default value if the key is not present.
 * @return string|null
 */
function old(string $key, ?string $default = null): ?string
{
    $old = $_SESSION['_flash']['old'] ?? [];

    $value = $old[$key] ?? $default;

    return $value !== null ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : $default;
}

// ==================================================================
// CSRF PROTECTION
// ==================================================================

/**
 * Generate or retrieve the current CSRF token.
 *
 * A new token is generated per session and reused until the session
 * is destroyed or regenerated.
 *
 * @return string The CSRF token.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Generate a hidden HTML input field containing the CSRF token.
 *
 * @return string The HTML <input> element.
 */
function csrf_field(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="_token" value="' . e($token) . '">';
}

// ==================================================================
// URL & ASSET HELPERS
// ==================================================================

/**
 * Generate a full URL to a static asset.
 *
 * Appends a cache-busting query string in non-development environments
 * based on the file's last modification time.
 *
 * @param string $path The asset path relative to /public.
 * @return string The full asset URL.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $baseUrl = rtrim(defined('APP_URL') ? APP_URL : ($_ENV['APP_URL'] ?? ''), '/');

    $fullPath = ROOT_PATH . '/public/' . $path;
    $version  = '';

    if (file_exists($fullPath) && !dev_mode()) {
        $version = '?v=' . filemtime($fullPath);
    }

    return "{$baseUrl}/{$path}{$version}";
}

/**
 * Generate a full URL for the given path.
 *
 * @param string $path The path (e.g., "/dashboard").
 * @return string The full URL.
 */
function url(string $path = ''): string
{
    $baseUrl = rtrim(defined('APP_URL') ? APP_URL : ($_ENV['APP_URL'] ?? ''), '/');
    $path    = ltrim($path, '/');

    return $path !== '' ? "{$baseUrl}/{$path}" : $baseUrl;
}

// ==================================================================
// AUTHENTICATION & TENANT
// ==================================================================

/**
 * Get the currently authenticated user array, or null if not logged in.
 *
 * @return array|null The user record.
 */
function auth(): ?array
{
    return $GLOBALS['__auth_user'] ?? $_SESSION['user'] ?? null;
}

/**
 * Get the current tenant record, or null if not resolved.
 *
 * @return array|null The tenant record.
 */
function tenant(): ?array
{
    return $GLOBALS['__tenant'] ?? $_REQUEST['__tenant'] ?? null;
}

// ==================================================================
// CONFIGURATION
// ==================================================================

/**
 * Read a configuration value using dot-notation.
 *
 * Delegates to the App singleton when available, otherwise performs
 * a direct file-based lookup.
 *
 * @param string $key     Dot-notation key (e.g., "app.name").
 * @param mixed  $default Default value if the key doesn't exist.
 * @return mixed
 */
function config(string $key, mixed $default = null): mixed
{
    // Prefer the App instance if booted.
    try {
        $app = \Core\App::getInstance();
        return $app->config($key, $default);
    } catch (\Throwable) {
        // Fall back to manual lookup.
    }

    static $cache = [];

    $segments = explode('.', $key);
    $file     = array_shift($segments);

    if (!isset($cache[$file])) {
        $configPath = ROOT_PATH . "/config/{$file}.php";
        $cache[$file] = file_exists($configPath) ? require $configPath : [];
    }

    $value = $cache[$file];

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

// ==================================================================
// TRANSLATION
// ==================================================================

/**
 * Translate a string by its key.
 *
 * Language files are stored in /lang/{locale}/{group}.php and return
 * associative arrays. Keys use dot-notation: "messages.welcome".
 *
 * @param string      $key     The translation key.
 * @param string|null $default Fallback if the key is not found.
 * @param array       $replace Placeholder replacements (e.g., [':name' => 'John']).
 * @return string
 */
function __(string $key, ?string $default = null, array $replace = []): string
{
    static $translations = [];

    $locale = defined('APP_LOCALE') ? APP_LOCALE : ($_ENV['APP_LOCALE'] ?? 'pt_BR');

    $segments = explode('.', $key);
    $group    = array_shift($segments);
    $item     = implode('.', $segments);

    $cacheKey = "{$locale}.{$group}";

    if (!isset($translations[$cacheKey])) {
        $filePath = ROOT_PATH . "/lang/{$locale}/{$group}.php";
        $translations[$cacheKey] = file_exists($filePath) ? require $filePath : [];
    }

    // Walk the nested array.
    $value = $translations[$cacheKey];
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default ?? $key;
        }
        $value = $value[$segment];
    }

    if (!is_string($value)) {
        return $default ?? $key;
    }

    // Apply replacements.
    foreach ($replace as $placeholder => $replacement) {
        $value = str_replace($placeholder, (string) $replacement, $value);
    }

    return $value;
}

// ==================================================================
// ESCAPING & OUTPUT
// ==================================================================

/**
 * Escape a string for safe HTML output.
 *
 * @param string|null $string The raw string.
 * @return string The escaped string.
 */
function e(?string $string): string
{
    if ($string === null) {
        return '';
    }

    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Send a JSON response with the given HTTP status code and terminate.
 *
 * @param mixed $data The data to encode.
 * @param int   $code The HTTP status code.
 */
function json_response(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ==================================================================
// NAVIGATION
// ==================================================================

/**
 * Check if the current request URI matches or starts with the given path.
 *
 * Useful for highlighting active navigation links.
 *
 * @param string $path   The path to check against.
 * @param bool   $exact  If true, requires an exact match; otherwise, prefix match.
 * @return bool
 */
function is_active(string $path, bool $exact = false): bool
{
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '/';

    if ($exact) {
        return rtrim($current, '/') === rtrim($path, '/');
    }

    return str_starts_with($current, $path);
}

// ==================================================================
// FORMATTING
// ==================================================================

/**
 * Format a date/datetime string.
 *
 * @param string|null $date   The date string (any strtotime-compatible format).
 * @param string      $format The desired output format (default: d/m/Y H:i).
 * @return string The formatted date, or an empty string on failure.
 */
function format_date(?string $date, string $format = 'd/m/Y H:i'): string
{
    if ($date === null || $date === '') {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
}

/**
 * Format a byte count into a human-readable string.
 *
 * @param int $bytes     The number of bytes.
 * @param int $precision Decimal precision (default 2).
 * @return string The formatted string (e.g., "1.50 MB").
 */
function format_bytes(int $bytes, int $precision = 2): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $power = (int) floor(log($bytes, 1024));
    $power = min($power, count($units) - 1);

    return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
}

/**
 * Apply a phone mask to a Brazilian phone number.
 *
 * Supports both 10-digit (landline) and 11-digit (mobile) numbers.
 *
 * @param string $phone The raw phone digits.
 * @return string The masked phone (e.g., "(11) 98765-4321").
 */
function mask_phone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);

    return match (strlen($digits)) {
        11      => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4)),
        10      => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4)),
        default => $phone,
    };
}

// ==================================================================
// BRAZILIAN DOCUMENT VALIDATION
// ==================================================================

/**
 * Validate a Brazilian CPF (Cadastro de Pessoas Fisicas) number.
 *
 * @param string $cpf The CPF string (digits only or formatted).
 * @return bool True if the CPF is valid.
 */
function validate_cpf(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    // Reject known invalid sequences (all same digit).
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Validate check digits.
    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) {
            $sum += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digit = ((10 * $sum) % 11) % 10;
        if ((int) $cpf[$t] !== $digit) {
            return false;
        }
    }

    return true;
}

/**
 * Validate a Brazilian CNPJ (Cadastro Nacional da Pessoa Juridica) number.
 *
 * @param string $cnpj The CNPJ string (digits only or formatted).
 * @return bool True if the CNPJ is valid.
 */
function validate_cnpj(string $cnpj): bool
{
    $cnpj = preg_replace('/\D/', '', $cnpj);

    if (strlen($cnpj) !== 14) {
        return false;
    }

    if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }

    $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    // First check digit.
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int) $cnpj[$i] * $weights1[$i];
    }
    $remainder = $sum % 11;
    $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

    if ((int) $cnpj[12] !== $digit1) {
        return false;
    }

    // Second check digit.
    $sum = 0;
    for ($i = 0; $i < 13; $i++) {
        $sum += (int) $cnpj[$i] * $weights2[$i];
    }
    $remainder = $sum % 11;
    $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

    return (int) $cnpj[13] === $digit2;
}

// ==================================================================
// SECURITY & TOKENS
// ==================================================================

/**
 * Generate a cryptographically secure random token.
 *
 * @param int $length The desired token length in characters (hex output = 2x bytes).
 * @return string The hex-encoded token.
 */
function generate_token(int $length = 64): string
{
    $bytes = max(1, intdiv($length, 2));
    return bin2hex(random_bytes($bytes));
}

// ==================================================================
// FLASH MESSAGES
// ==================================================================

/**
 * Set a flash message that will be available on the next request only.
 *
 * @param string $key   The flash key (e.g., "success", "error", "info").
 * @param mixed  $value The flash value.
 */
function flash(string $key, mixed $value): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION['_flash'][$key] = $value;
}

/**
 * Retrieve and consume a flash message.
 *
 * The value is removed from the session after being read so it is
 * only displayed once.
 *
 * @param string     $key     The flash key.
 * @param mixed|null $default Default value if the key is not set.
 * @return mixed
 */
function get_flash(string $key, mixed $default = null): mixed
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return $default;
    }

    $value = $_SESSION['_flash'][$key] ?? $default;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

// ==================================================================
// ENVIRONMENT
// ==================================================================

/**
 * Check if the application is running in development mode.
 *
 * @return bool True if APP_ENV is "development", "local" or APP_DEBUG is true.
 */
function dev_mode(): bool
{
    $env = defined('APP_ENV') ? APP_ENV : ($_ENV['APP_ENV'] ?? 'production');

    if (in_array($env, ['development', 'local', 'dev'], true)) {
        return true;
    }

    $debug = defined('APP_DEBUG') ? APP_DEBUG : (($_ENV['APP_DEBUG'] ?? 'false') === 'true');

    return (bool) $debug;
}
