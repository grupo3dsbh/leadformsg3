<?php

declare(strict_types=1);

namespace Core;

/**
 * Main Application Class
 *
 * Bootstraps the application: starts sessions, loads configuration,
 * registers routes, and dispatches the incoming HTTP request through
 * the router. Uncaught exceptions are handled gracefully so end-users
 * never see raw stack traces in production.
 */
class App
{
    private static ?App $instance = null;
    private Router $router;
    private array $config = [];

    // ----------------------------------------------------------------
    // Lifecycle
    // ----------------------------------------------------------------

    public function __construct()
    {
        $this->bootstrap();
        static::$instance = $this;
    }

    /**
     * Singleton accessor.
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    // ----------------------------------------------------------------
    // Bootstrap helpers
    // ----------------------------------------------------------------

    private function bootstrap(): void
    {
        $this->loadEnvironment();
        $this->startSession();
        $this->initRouter();
        $this->loadRoutes();
    }

    /**
     * Load environment variables from a .env file (simple key=value parser).
     * If the file does not exist we silently continue so that the app can
     * rely on real environment variables (e.g. in Docker).
     */
    private function loadEnvironment(): void
    {
        $envPath = $this->basePath('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Start a secure PHP session if one is not already active.
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                     || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly'  => true,
            'samesite'  => 'Lax',
        ]);

        session_start();

        // Regenerate the session ID periodically to mitigate fixation.
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    /**
     * Create the Router instance.
     */
    private function initRouter(): void
    {
        $this->router = new Router();
    }

    /**
     * Load route definitions from config/routes.php.
     * The file receives the $router variable so it can register routes.
     */
    private function loadRoutes(): void
    {
        $routesFile = $this->basePath('config/routes.php');

        if (!file_exists($routesFile)) {
            throw new \RuntimeException("Routes file not found: {$routesFile}");
        }

        $router = $this->router;
        require $routesFile;
    }

    // ----------------------------------------------------------------
    // Request dispatch
    // ----------------------------------------------------------------

    /**
     * Dispatch the current HTTP request and send the response.
     */
    public function run(): void
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $uri    = rawurldecode($uri);

            $response = $this->router->dispatch($method, $uri);
            $this->sendResponse($response);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Send whatever the router/controller returned back to the client.
     */
    private function sendResponse(mixed $response): void
    {
        if ($response instanceof Response) {
            $response->send();
            return;
        }

        if (is_array($response)) {
            (new Response())
                ->setStatusCode(200)
                ->json($response)
                ->send();
            return;
        }

        if (is_string($response)) {
            echo $response;
            return;
        }

        // Null or void returns are perfectly fine (controller already echoed).
    }

    /**
     * Graceful exception handler.
     */
    private function handleException(\Throwable $e): void
    {
        $code = match (true) {
            $e instanceof \Core\Exceptions\NotFoundException       => 404,
            $e instanceof \Core\Exceptions\ForbiddenException      => 403,
            $e instanceof \Core\Exceptions\UnauthorizedException   => 401,
            $e instanceof \Core\Exceptions\ValidationException     => 422,
            default                                                 => 500,
        };

        http_response_code($code);

        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '',
            '/api/'
        );

        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => true,
                'message' => $e->getMessage(),
                'code'    => $code,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $debug = (bool) ($_ENV['APP_DEBUG'] ?? false);

        if ($debug) {
            echo '<h1>Error ' . $code . '</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            // In production, show a friendly error page when available.
            $errorView = $this->basePath("views/errors/{$code}.php");
            if (file_exists($errorView)) {
                include $errorView;
            } else {
                echo "<h1>Error {$code}</h1><p>Something went wrong.</p>";
            }
        }
    }

    // ----------------------------------------------------------------
    // Accessors / Helpers
    // ----------------------------------------------------------------

    /**
     * Return the Router instance (useful for testing or manual route registration).
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Resolve an absolute path relative to the project root.
     */
    public function basePath(string $path = ''): string
    {
        $base = dirname(__DIR__);
        return $path !== '' ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }

    /**
     * Read a config value (dot-notation supported).
     */
    public function config(string $key, mixed $default = null): mixed
    {
        // Lazy-load config files from config/ directory.
        $segments = explode('.', $key);
        $file     = array_shift($segments);

        if (!isset($this->config[$file])) {
            $configFile = $this->basePath("config/{$file}.php");
            $this->config[$file] = file_exists($configFile)
                ? require $configFile
                : [];
        }

        $value = $this->config[$file];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Quick access to an environment variable with an optional default.
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }
}
