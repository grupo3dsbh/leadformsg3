<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * API Authentication Middleware
 *
 * Validates incoming API requests by inspecting the Authorization header for
 * a Bearer token. The token can be either:
 *
 *  1. A JWT (JSON Web Token) signed with the application key.
 *  2. A simple API key stored against a tenant in the database.
 *
 * On success the tenant context is established for the request. On failure
 * a 401 JSON response is returned immediately.
 */
class ApiMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param \Closure $next The next handler in the middleware pipeline.
     * @return mixed
     */
    public function handle(\Closure $next): mixed
    {
        $token = $this->extractBearerToken();

        if ($token === null) {
            return $this->unauthorized('Authorization token is required.');
        }

        // Attempt JWT validation first, fall back to API key lookup.
        $payload = $this->validateJwt($token);

        if ($payload !== null) {
            $this->setTenantContext($payload);
            return $next();
        }

        // Fall back to database API-key validation.
        $tenant = $this->validateApiKey($token);

        if ($tenant !== null) {
            $this->setTenantContextFromRecord($tenant);
            return $next();
        }

        return $this->unauthorized('Invalid or expired token.');
    }

    // ------------------------------------------------------------------
    // Token extraction
    // ------------------------------------------------------------------

    /**
     * Extract the Bearer token from the Authorization header.
     *
     * Supports the standard "Authorization: Bearer <token>" format as well
     * as an "X-API-Key" header as a convenience for integrations.
     */
    protected function extractBearerToken(): ?string
    {
        // Standard Authorization header.
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: X-API-Key header.
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

        if ($apiKey !== null && $apiKey !== '') {
            return trim($apiKey);
        }

        return null;
    }

    // ------------------------------------------------------------------
    // JWT validation
    // ------------------------------------------------------------------

    /**
     * Validate a JWT token and return its decoded payload on success.
     *
     * This implements a minimal HS256 JWT decoder. For production use
     * consider replacing with firebase/php-jwt or equivalent library.
     *
     * @param string $token The raw JWT string.
     * @return array|null The decoded payload array or null on failure.
     */
    protected function validateJwt(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $secret = $_ENV['APP_KEY'] ?? ($_ENV['JWT_SECRET'] ?? 'default-key-change-me-in-production');

        // Verify HMAC-SHA256 signature.
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true)
        );

        if (!hash_equals($expectedSignature, $signatureB64)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (!is_array($payload)) {
            return null;
        }

        // Check expiration.
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        // Check "not before" claim.
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return null;
        }

        return $payload;
    }

    // ------------------------------------------------------------------
    // API key validation
    // ------------------------------------------------------------------

    /**
     * Look up the token as a plain API key in the tenants or api_keys table.
     *
     * @param string $token The raw API key.
     * @return array|null The tenant record or null.
     */
    protected function validateApiKey(string $token): ?array
    {
        try {
            $db = \Core\Database::getInstance();

            // First check a dedicated api_keys table.
            $stmt = $db->prepare(
                'SELECT t.* FROM api_keys ak
                 JOIN tenants t ON t.id = ak.tenant_id
                 WHERE ak.token = :token
                   AND ak.is_active = 1
                   AND (ak.expires_at IS NULL OR ak.expires_at > NOW())
                 LIMIT 1'
            );
            $stmt->execute(['token' => hash('sha256', $token)]);
            $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($tenant) {
                return $tenant;
            }

            // Fall back to tenant.api_key column.
            $stmt = $db->prepare(
                'SELECT * FROM tenants WHERE api_key = :key AND status = :status LIMIT 1'
            );
            $stmt->execute([
                'key'    => hash('sha256', $token),
                'status' => 'active',
            ]);

            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Tenant context
    // ------------------------------------------------------------------

    /**
     * Set the tenant context from a decoded JWT payload.
     */
    protected function setTenantContext(array $payload): void
    {
        $tenantId = (int) ($payload['tenant_id'] ?? $payload['tid'] ?? 0);
        $userId   = (int) ($payload['user_id'] ?? $payload['sub'] ?? 0);

        $_REQUEST['__tenant_id'] = $tenantId;
        $_REQUEST['__auth_id']   = $userId;
        $_REQUEST['__api_auth']  = true;

        $GLOBALS['__tenant_id'] = $tenantId;
        $GLOBALS['__auth_id']   = $userId;
        $GLOBALS['__api_auth']  = true;
    }

    /**
     * Set the tenant context from a database tenant record.
     */
    protected function setTenantContextFromRecord(array $tenant): void
    {
        $tenantId = (int) ($tenant['id'] ?? 0);

        $_REQUEST['__tenant_id'] = $tenantId;
        $_REQUEST['__auth_id']   = 0; // API-key auth has no specific user.
        $_REQUEST['__api_auth']  = true;

        $GLOBALS['__tenant_id'] = $tenantId;
        $GLOBALS['__auth_id']   = 0;
        $GLOBALS['__api_auth']  = true;
    }

    // ------------------------------------------------------------------
    // Error responses
    // ------------------------------------------------------------------

    /**
     * Return a 401 Unauthorized JSON response and halt execution.
     */
    protected function unauthorized(string $message = 'Unauthorized'): never
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error'   => true,
            'message' => $message,
            'code'    => 401,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ------------------------------------------------------------------
    // Base64-URL helpers
    // ------------------------------------------------------------------

    /**
     * Encode data to Base64-URL (no padding).
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode a Base64-URL string.
     */
    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
