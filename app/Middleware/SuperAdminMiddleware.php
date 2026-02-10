<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Super Admin Middleware
 *
 * Ensures the authenticated user has the "super_admin" role. This middleware
 * must run after AuthMiddleware so that the session user is already verified.
 * If the user is not a super admin they are redirected to the regular
 * dashboard instead of being shown a 403 error (friendlier UX).
 */
class SuperAdminMiddleware
{
    /**
     * Handle the incoming request.
     *
     * The AuthMiddleware check is performed first -- if the user is not logged
     * in at all they get redirected to /login. If they are logged in but lack
     * the super_admin role they are sent to /dashboard.
     *
     * @param \Closure $next The next handler in the middleware pipeline.
     * @return mixed
     */
    public function handle(\Closure $next): mixed
    {
        // ---- Step 1: Ensure the user is authenticated ----
        if (!$this->isAuthenticated()) {
            return $this->redirectTo('/login');
        }

        // ---- Step 2: Verify super_admin role ----
        $user = $this->getSessionUser();

        if ($user === null || !$this->isSuperAdmin($user)) {
            return $this->redirectTo('/dashboard');
        }

        // Propagate user context for downstream consumers.
        $this->setRequestUser($user);

        return $next();
    }

    // ------------------------------------------------------------------
    // Authentication helpers (mirror AuthMiddleware logic)
    // ------------------------------------------------------------------

    /**
     * Check whether the session contains a valid authenticated user.
     */
    protected function isAuthenticated(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        return !empty($_SESSION['user_id']) && !empty($_SESSION['user']);
    }

    /**
     * Return the user array from the session.
     */
    protected function getSessionUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    // ------------------------------------------------------------------
    // Role verification
    // ------------------------------------------------------------------

    /**
     * Determine if the given user record has the super_admin role.
     */
    protected function isSuperAdmin(array $user): bool
    {
        return ($user['role'] ?? '') === 'super_admin';
    }

    // ------------------------------------------------------------------
    // Request context
    // ------------------------------------------------------------------

    /**
     * Make user and tenant data available to controllers and helpers.
     */
    protected function setRequestUser(array $user): void
    {
        $_REQUEST['__auth_user'] = $user;
        $_REQUEST['__auth_id']   = (int) ($user['id'] ?? 0);
        $_REQUEST['__tenant_id'] = (int) ($user['tenant_id'] ?? 0);

        $GLOBALS['__auth_user'] = $user;
        $GLOBALS['__tenant_id'] = (int) ($user['tenant_id'] ?? 0);
    }

    // ------------------------------------------------------------------
    // Redirect
    // ------------------------------------------------------------------

    /**
     * Redirect to the given URL.
     */
    protected function redirectTo(string $url): never
    {
        header("Location: {$url}", true, 302);
        exit;
    }
}
