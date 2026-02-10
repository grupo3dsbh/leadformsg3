<?php

declare(strict_types=1);

namespace Core;

/**
 * HTTP Response Helper
 *
 * Encapsulates the response status code, headers, and body so that
 * controllers can build responses fluently and the App can send them
 * in a single place.
 *
 * Usage:
 *
 *   return (new Response())
 *       ->setStatusCode(200)
 *       ->json(['ok' => true]);
 *
 *   return Response::make('Hello world', 200);
 *   return Response::jsonResponse(['data' => $items]);
 *   return Response::redirectTo('/dashboard');
 */
class Response
{
    private int $statusCode = 200;

    /** @var array<string, string> */
    private array $headers = [];

    private string $body = '';

    private bool $sent = false;

    // ------------------------------------------------------------------
    // Static factories
    // ------------------------------------------------------------------

    /**
     * Create a simple text/html response.
     */
    public static function make(string $body = '', int $statusCode = 200, array $headers = []): static
    {
        $response = new static();
        $response->setStatusCode($statusCode);
        $response->setBody($body);

        foreach ($headers as $key => $value) {
            $response->setHeader($key, $value);
        }

        return $response;
    }

    /**
     * Create a JSON response.
     */
    public static function jsonResponse(mixed $data, int $statusCode = 200): static
    {
        return (new static())
            ->setStatusCode($statusCode)
            ->json($data);
    }

    /**
     * Create a redirect response.
     */
    public static function redirectTo(string $url, int $statusCode = 302): static
    {
        return (new static())
            ->setStatusCode($statusCode)
            ->redirect($url);
    }

    /**
     * Create a "204 No Content" response.
     */
    public static function noContent(): static
    {
        return (new static())->setStatusCode(204);
    }

    /**
     * Create a file download response.
     */
    public static function download(string $filePath, ?string $filename = null, string $contentType = 'application/octet-stream'): static
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $filename = $filename ?? basename($filePath);

        $response = new static();
        $response->setStatusCode(200);
        $response->setHeader('Content-Type', $contentType);
        $response->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->setHeader('Content-Length', (string) filesize($filePath));
        $response->setHeader('Cache-Control', 'no-cache, must-revalidate');
        $response->setBody(file_get_contents($filePath));

        return $response;
    }

    // ------------------------------------------------------------------
    // Fluent setters
    // ------------------------------------------------------------------

    /**
     * Set the HTTP status code.
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set a response header.
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers at once.
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * Set the raw response body.
     */
    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Encode data as JSON and set appropriate headers.
     */
    public function json(mixed $data): static
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this;
    }

    /**
     * Set redirect headers.
     */
    public function redirect(string $url): static
    {
        $this->setHeader('Location', $url);

        if ($this->statusCode < 300 || $this->statusCode >= 400) {
            $this->statusCode = 302;
        }

        return $this;
    }

    /**
     * Set a cookie via a Set-Cookie header.
     */
    public function withCookie(
        string $name,
        string $value,
        int    $maxAge   = 0,
        string $path     = '/',
        string $domain   = '',
        bool   $secure   = true,
        bool   $httpOnly = true,
        string $sameSite = 'Lax',
    ): static {
        $cookie = "{$name}=" . rawurlencode($value);
        $cookie .= "; Path={$path}";

        if ($maxAge > 0) {
            $cookie .= "; Max-Age={$maxAge}";
            $cookie .= '; Expires=' . gmdate('D, d M Y H:i:s T', time() + $maxAge);
        }

        if ($domain !== '') {
            $cookie .= "; Domain={$domain}";
        }

        if ($secure) {
            $cookie .= '; Secure';
        }

        if ($httpOnly) {
            $cookie .= '; HttpOnly';
        }

        $cookie .= "; SameSite={$sameSite}";

        // Allow multiple Set-Cookie headers.
        $this->headers['Set-Cookie-' . $name] = $cookie;

        return $this;
    }

    /**
     * Add cache-control headers.
     */
    public function cache(int $seconds): static
    {
        $this->setHeader('Cache-Control', "public, max-age={$seconds}");
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s T', time() + $seconds));

        return $this;
    }

    /**
     * Mark the response as non-cacheable.
     */
    public function noCache(): static
    {
        $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Expires', '0');

        return $this;
    }

    /**
     * Add CORS headers.
     */
    public function cors(
        string $origin  = '*',
        string $methods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        string $headers = 'Content-Type, Authorization, X-Requested-With',
        int    $maxAge  = 86400,
    ): static {
        $this->setHeader('Access-Control-Allow-Origin', $origin);
        $this->setHeader('Access-Control-Allow-Methods', $methods);
        $this->setHeader('Access-Control-Allow-Headers', $headers);
        $this->setHeader('Access-Control-Max-Age', (string) $maxAge);

        return $this;
    }

    // ------------------------------------------------------------------
    // Getters
    // ------------------------------------------------------------------

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    // ------------------------------------------------------------------
    // Send
    // ------------------------------------------------------------------

    /**
     * Send the response to the client (status line, headers, body).
     * Calling send() more than once is a no-op.
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;

        // Status code.
        http_response_code($this->statusCode);

        // Headers.
        foreach ($this->headers as $name => $value) {
            // Handle multiple Set-Cookie headers.
            if (str_starts_with($name, 'Set-Cookie-')) {
                header("Set-Cookie: {$value}", false);
            } else {
                header("{$name}: {$value}");
            }
        }

        // Body (skip for 204 / 304).
        if (!in_array($this->statusCode, [204, 304], true)) {
            echo $this->body;
        }
    }
}
