<?php

declare(strict_types=1);

namespace Core;

/**
 * Base Middleware
 *
 * All middleware classes must extend this abstract class and implement
 * the handle() method. The $next closure represents the next step in
 * the pipeline (either the next middleware or the route handler).
 *
 * A middleware can:
 *   - Run code before the handler  (pre-processing)
 *   - Run code after  the handler  (post-processing)
 *   - Short-circuit the pipeline   (e.g. redirect, return error)
 *
 * Example:
 *
 *   class AuthMiddleware extends Middleware
 *   {
 *       public function handle(\Closure $next): mixed
 *       {
 *           if (!Auth::check()) {
 *               return Response::redirectTo('/login');
 *           }
 *           return $next();
 *       }
 *   }
 */
abstract class Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param \Closure $next  Call this to pass control to the next layer.
     * @return mixed          The response (a Response object, string, array, etc.).
     */
    abstract public function handle(\Closure $next): mixed;

    // ------------------------------------------------------------------
    // Convenience helpers available to all middleware
    // ------------------------------------------------------------------

    /**
     * Abort with a JSON error response.
     */
    protected function jsonError(string $message, int $code = 403): Response
    {
        return (new Response())
            ->setStatusCode($code)
            ->json([
                'error'   => true,
                'message' => $message,
                'code'    => $code,
            ]);
    }

    /**
     * Redirect the user to a given URL.
     */
    protected function redirect(string $url, int $code = 302): Response
    {
        return Response::redirectTo($url, $code);
    }

    /**
     * Check whether the current request expects a JSON response
     * (API or XHR).
     */
    protected function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '';

        return str_contains($accept, 'application/json')
            || strtolower($xhr) === 'xmlhttprequest'
            || str_starts_with($uri, '/api/');
    }

    /**
     * Get the value of a request header.
     */
    protected function header(string $name): ?string
    {
        // PHP normalises headers to HTTP_UPPER_CASE in $_SERVER.
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $_SERVER[$key] ?? null;
    }

    /**
     * Get the client IP address, respecting common proxy headers.
     */
    protected function clientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }
}
