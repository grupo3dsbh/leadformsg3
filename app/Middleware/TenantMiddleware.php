<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Tenant Context Middleware
 *
 * Sets the current tenant context from the authenticated user's session so
 * that all downstream queries, file operations and logic are automatically
 * scoped to the correct tenant. The resolved tenant record is stored in a
 * global variable accessible through the tenant() helper.
 *
 * This middleware expects AuthMiddleware (or ApiMiddleware) to have already
 * run so that __tenant_id is available in the request globals.
 */
class TenantMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param \Closure $next The next handler in the middleware pipeline.
     * @return mixed
     */
    public function handle(\Closure $next): mixed
    {
        $tenantId = $this->resolveTenantId();

        if ($tenantId === 0) {
            return $this->noTenantResponse();
        }

        $tenant = $this->loadTenant($tenantId);

        if ($tenant === null) {
            return $this->noTenantResponse();
        }

        // Verify the tenant account is active.
        if (!$this->isTenantActive($tenant)) {
            return $this->suspendedTenantResponse($tenant);
        }

        // Make the full tenant record available globally.
        $this->setTenantGlobals($tenant);

        return $next();
    }

    // ------------------------------------------------------------------
    // Tenant resolution
    // ------------------------------------------------------------------

    /**
     * Resolve the tenant ID from the request context.
     *
     * Priority: explicit request global > session user > subdomain.
     */
    protected function resolveTenantId(): int
    {
        // Set by AuthMiddleware or ApiMiddleware.
        if (!empty($GLOBALS['__tenant_id'])) {
            return (int) $GLOBALS['__tenant_id'];
        }

        if (!empty($_REQUEST['__tenant_id'])) {
            return (int) $_REQUEST['__tenant_id'];
        }

        // Fall back to session user.
        if (!empty($_SESSION['user']['tenant_id'])) {
            return (int) $_SESSION['user']['tenant_id'];
        }

        // Optional: resolve from subdomain.
        $tenantId = $this->resolveFromSubdomain();
        if ($tenantId > 0) {
            return $tenantId;
        }

        return 0;
    }

    /**
     * Attempt to resolve a tenant from the request subdomain.
     *
     * e.g. acme.leadform.app => slug "acme" => tenants.slug lookup.
     */
    protected function resolveFromSubdomain(): int
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if ($host === '') {
            return 0;
        }

        $appDomain = $_ENV['APP_DOMAIN'] ?? '';

        if ($appDomain === '' || !str_ends_with($host, $appDomain)) {
            return 0;
        }

        $subdomain = str_replace(".{$appDomain}", '', $host);

        if ($subdomain === '' || $subdomain === $host || $subdomain === 'www') {
            return 0;
        }

        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT id FROM tenants WHERE slug = :slug AND status = :status LIMIT 1');
            $stmt->execute(['slug' => $subdomain, 'status' => 'active']);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row ? (int) $row['id'] : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    // ------------------------------------------------------------------
    // Tenant loading and validation
    // ------------------------------------------------------------------

    /**
     * Load the full tenant record from the database.
     */
    protected function loadTenant(int $tenantId): ?array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $tenantId]);
            $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $tenant ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Determine whether the tenant account is currently active.
     */
    protected function isTenantActive(array $tenant): bool
    {
        $status = $tenant['status'] ?? 'inactive';

        // Allow active and trialing tenants.
        return in_array($status, ['active', 'trialing'], true);
    }

    // ------------------------------------------------------------------
    // Global context
    // ------------------------------------------------------------------

    /**
     * Store the tenant record in globals so it is accessible everywhere.
     */
    protected function setTenantGlobals(array $tenant): void
    {
        $GLOBALS['__tenant']    = $tenant;
        $GLOBALS['__tenant_id'] = (int) $tenant['id'];

        $_REQUEST['__tenant']    = $tenant;
        $_REQUEST['__tenant_id'] = (int) $tenant['id'];
    }

    // ------------------------------------------------------------------
    // Error responses
    // ------------------------------------------------------------------

    /**
     * Handle the case where no tenant could be resolved.
     */
    protected function noTenantResponse(): never
    {
        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');

        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => true,
                'message' => 'Tenant context could not be established.',
                'code'    => 401,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Web request: redirect to login.
        header('Location: /login', true, 302);
        exit;
    }

    /**
     * Handle the case where the tenant exists but is suspended or inactive.
     */
    protected function suspendedTenantResponse(array $tenant): never
    {
        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');

        $status = $tenant['status'] ?? 'inactive';

        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => true,
                'message' => "Your account is currently {$status}. Please contact support.",
                'code'    => 403,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Web request: show a friendly suspension notice.
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_flash']['error'] = "Your account is currently {$status}. Please contact support.";
        }

        header('Location: /account/suspended', true, 302);
        exit;
    }
}
