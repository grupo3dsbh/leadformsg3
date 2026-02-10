<?php

declare(strict_types=1);

namespace Core;

/**
 * Base Controller
 *
 * Every application controller extends this class to gain access to
 * view rendering, JSON responses, redirects, input helpers, and flash
 * message support.
 */
abstract class Controller
{
    /**
     * Base path for view files (resolved once).
     */
    private static ?string $viewBasePath = null;

    public function __construct()
    {
        // Allow child controllers to call parent::__construct()
    }

    // ------------------------------------------------------------------
    // Database helper
    // ------------------------------------------------------------------

    /**
     * Get the Database singleton instance.
     */
    protected function db(): Database
    {
        return Database::getInstance();
    }

    // ------------------------------------------------------------------
    // View rendering
    // ------------------------------------------------------------------

    /**
     * Render a view file and return the output as a string.
     *
     * View names use dot-notation: 'admin.dashboard' resolves to
     * views/admin/dashboard.php.
     *
     * @param string $view   Dot-notated view name.
     * @param array  $data   Variables to extract into the view scope.
     * @param string|null $layout  Optional layout wrapper (dot-notated).
     */
    protected function view(string $view, array $data = [], ?string $layout = null): string
    {
        $viewFile = $this->resolveViewPath($view);

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$viewFile}");
        }

        // Render the view content.
        $content = $this->renderFile($viewFile, $data);

        // If a layout is specified, wrap the content.
        if ($layout !== null) {
            $layoutFile = $this->resolveViewPath($layout);

            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout not found: {$layoutFile}");
            }

            $data['content'] = $content;
            $content = $this->renderFile($layoutFile, $data);
        }

        return $content;
    }

    /**
     * Render and immediately echo a view.
     */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        echo $this->view($view, $data, $layout);
    }

    /**
     * Include a view file, extracting $data into its scope, and return
     * captured output.
     */
    private function renderFile(string $filePath, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();

        try {
            include $filePath;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Convert dot-notated view name to an absolute file path.
     */
    private function resolveViewPath(string $name): string
    {
        if (self::$viewBasePath === null) {
            self::$viewBasePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';
        }

        $relative = str_replace('.', DIRECTORY_SEPARATOR, $name) . '.php';

        return self::$viewBasePath . DIRECTORY_SEPARATOR . $relative;
    }

    // ------------------------------------------------------------------
    // JSON responses
    // ------------------------------------------------------------------

    /**
     * Return a Response configured for JSON output.
     */
    protected function json(mixed $data, int $statusCode = 200, array $headers = []): string
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Shorthand for a successful JSON payload.
     */
    protected function jsonSuccess(mixed $data = null, string $message = 'OK', int $code = 200): string
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Shorthand for an error JSON payload.
     */
    protected function jsonError(string $message = 'Error', int $code = 400, mixed $errors = null): string
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return $this->json($payload, $code);
    }

    // ------------------------------------------------------------------
    // Redirects
    // ------------------------------------------------------------------

    /**
     * Redirect to a given URL, optionally with flash session data.
     */
    protected function redirect(string $url, array $flash = []): string
    {
        // Store flash data in session for next request
        if (!empty($flash)) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['_flash'] = array_merge($_SESSION['_flash'] ?? [], $flash);
        }

        // Send redirect headers and exit immediately
        http_response_code(302);
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect back to the previous page (uses the Referer header).
     */
    protected function back(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        return $this->redirect($referer);
    }

    /**
     * Redirect to a named route (delegates to the Router).
     */
    protected function redirectToRoute(string $name, array $params = []): Response
    {
        $url = App::getInstance()->getRouter()->url($name, $params);

        return $this->redirect($url);
    }

    // ------------------------------------------------------------------
    // Input helpers
    // ------------------------------------------------------------------

    /**
     * Retrieve a value from the request (GET or POST).
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Retrieve all input as an associative array.
     */
    protected function allInput(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Retrieve only the specified keys from input.
     *
     * @param list<string> $keys
     */
    protected function only(array $keys): array
    {
        $all = $this->allInput();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Read the raw request body (useful for JSON API payloads).
     */
    protected function rawBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Decode a JSON request body into an associative array.
     */
    protected function jsonBody(): array
    {
        $body = $this->rawBody();

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Retrieve an uploaded file ($_FILES entry).
     */
    protected function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    // ------------------------------------------------------------------
    // Flash messages (session-based)
    // ------------------------------------------------------------------

    /**
     * Store a flash message for the next request.
     */
    protected function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Retrieve (and remove) a flash message.
     */
    protected function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    /**
     * Flash a success message.
     */
    protected function flashSuccess(string $message): void
    {
        $this->flash('success', $message);
    }

    /**
     * Flash an error message.
     */
    protected function flashError(string $message): void
    {
        $this->flash('error', $message);
    }

    // ------------------------------------------------------------------
    // Validation shorthand
    // ------------------------------------------------------------------

    /**
     * Validate input data against the given rules.
     *
     * Returns an empty array on success, or an array of error messages on failure.
     * All controllers call this as: $errors = $this->validate($data, $rules)
     *
     * @param array $data   Input data to validate (typically $_POST).
     * @param array $rules  Validation rules (field => 'rule|rule:param').
     * @return array Empty on success, error messages grouped by field on failure.
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = new Validator($data, $rules);

        if (!$validator->passes()) {
            return $validator->errors();
        }

        return [];
    }

    // ------------------------------------------------------------------
    // Auth helpers
    // ------------------------------------------------------------------

    /**
     * Return the currently authenticated user (or null).
     */
    protected function user(): ?array
    {
        return Auth::user();
    }

    /**
     * Return the current user's ID (or null).
     */
    protected function userId(): ?int
    {
        $user = $this->user();
        return $user ? (int) $user['id'] : null;
    }

    /**
     * Check whether a user is logged in.
     */
    protected function isAuthenticated(): bool
    {
        return Auth::check();
    }

    // ------------------------------------------------------------------
    // Misc helpers
    // ------------------------------------------------------------------

    /**
     * Abort the request with the given HTTP status code and message.
     */
    protected function abort(int $code = 404, string $message = ''): never
    {
        $exceptionClass = match ($code) {
            401     => \Core\Exceptions\UnauthorizedException::class,
            403     => \Core\Exceptions\ForbiddenException::class,
            404     => \Core\Exceptions\NotFoundException::class,
            422     => \Core\Exceptions\ValidationException::class,
            default => \RuntimeException::class,
        };

        throw new $exceptionClass($message ?: "HTTP {$code}");
    }
}
