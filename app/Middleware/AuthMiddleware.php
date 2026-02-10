<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Authentication Middleware
 *
 * Verifies that the current request belongs to an authenticated user by
 * checking the session for stored user data. If the user is not logged in,
 * the request is redirected to the login page. On success the user record
 * and tenant ID are made available to downstream middleware and controllers
 * via the global $_REQUEST superglobal.
 */
class AuthMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param \Closure $next The next handler in the middleware pipeline.
     * @return mixed
     */
    public function handle(\Closure $next): mixed
    {
        if (!$this->isAuthenticated()) {
            return $this->redirectToLogin();
        }

        // Hydrate request context so controllers can access the user.
        $this->setRequestUser();

        return $next();
    }

    // ------------------------------------------------------------------
    // Authentication helpers
    // ------------------------------------------------------------------

    /**
     * Determine whether the current session contains a valid authenticated user.
     */
    protected function isAuthenticated(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        // We require both user_id and user to be present.
        if (empty($_SESSION['user_id']) || empty($_SESSION['user'])) {
            return false;
        }

        // Optionally verify the session has not been idle too long.
        $maxIdleSeconds = (int) ($_ENV['SESSION_IDLE_TIMEOUT'] ?? 3600);

        if (isset($_SESSION['_last_activity'])) {
            if ((time() - (int) $_SESSION['_last_activity']) > $maxIdleSeconds) {
                $this->destroySession();
                return false;
            }
        }

        // Refresh the last-activity timestamp.
        $_SESSION['_last_activity'] = time();

        return true;
    }

    /**
     * Return the authenticated user array stored in the session.
     */
    protected function getSessionUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Store user data and tenant context in the request so that controllers
     * and other middleware can access them without touching $_SESSION directly.
     */
    protected function setRequestUser(): void
    {
        $user = $this->getSessionUser();

        if ($user === null) {
            return;
        }

        // Make user accessible globally through a lightweight container.
        $_REQUEST['__auth_user']  = $user;
        $_REQUEST['__auth_id']    = (int) ($user['id'] ?? 0);
        $_REQUEST['__tenant_id']  = (int) ($user['tenant_id'] ?? 0);

        // Also store in a global that helpers like auth() can read.
        $GLOBALS['__auth_user'] = $user;
        $GLOBALS['__tenant_id'] = (int) ($user['tenant_id'] ?? 0);
    }

    // ------------------------------------------------------------------
    // Session management
    // ------------------------------------------------------------------

    /**
     * Completely destroy the current session.
     */
    protected function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
    }

    // ------------------------------------------------------------------
    // Redirect
    // ------------------------------------------------------------------

    /**
     * Redirect an unauthenticated user to the login page.
     *
     * The originally-requested URI is preserved in the session so that the
     * user can be sent back after a successful login.
     */
    protected function redirectToLogin(): never
    {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remember where the user wanted to go.
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['url.intended'] = $currentUri;
        }

        header('Location: /login', true, 302);
        exit;
    }
}
