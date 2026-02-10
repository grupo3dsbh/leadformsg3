<?php

declare(strict_types=1);

namespace Core;

/**
 * CSRF Protection
 *
 * Generates and validates per-session CSRF tokens to protect state-
 * changing requests (POST, PUT, PATCH, DELETE) against Cross-Site
 * Request Forgery attacks.
 *
 * Usage in forms:
 *
 *   <form method="POST" action="/submit">
 *       <?= \Core\CSRF::field() ?>
 *       ...
 *   </form>
 *
 * Validation (typically inside middleware):
 *
 *   if (!\Core\CSRF::verify()) {
 *       abort(403);
 *   }
 *
 * For AJAX/SPA, read the token from a <meta> tag or cookie and send
 * it in the X-CSRF-TOKEN header:
 *
 *   <meta name="csrf-token" content="<?= \Core\CSRF::token() ?>">
 */
class CSRF
{
    /** Session key where tokens are stored. */
    private const SESSION_KEY = '_csrf_tokens';

    /** Name of the POST field / header. */
    private const FIELD_NAME = '_csrf_token';

    /** Header name (X-CSRF-TOKEN). */
    private const HEADER_NAME = 'X-CSRF-TOKEN';

    /** Maximum number of tokens to keep per session (sliding window). */
    private const MAX_TOKENS = 10;

    /** Token lifetime in seconds (1 hour). */
    private const TOKEN_TTL = 3600;

    // ------------------------------------------------------------------
    // Token generation
    // ------------------------------------------------------------------

    /**
     * Generate a new CSRF token and store it in the session.
     * If a valid token already exists for this session, return it
     * instead of generating a new one (avoids breaking browser back
     * button or multiple tabs).
     */
    public static function token(): string
    {
        self::ensureSession();

        // Return the most recent non-expired token if one exists.
        $tokens = $_SESSION[self::SESSION_KEY] ?? [];

        if (!empty($tokens)) {
            $latest = end($tokens);
            if ($latest['expires'] > time()) {
                return $latest['token'];
            }
        }

        return self::regenerate();
    }

    /**
     * Force-generate a new token (useful after login / sensitive actions).
     */
    public static function regenerate(): string
    {
        self::ensureSession();

        $token = bin2hex(random_bytes(32));
        $entry = [
            'token'   => $token,
            'expires' => time() + self::TOKEN_TTL,
        ];

        $_SESSION[self::SESSION_KEY][] = $entry;

        // Prune old tokens beyond the sliding window.
        self::prune();

        return $token;
    }

    // ------------------------------------------------------------------
    // HTML helpers
    // ------------------------------------------------------------------

    /**
     * Return a hidden input field containing the CSRF token.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . $token . '">';
    }

    /**
     * Return a <meta> tag (for AJAX/SPA usage).
     */
    public static function meta(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<meta name="csrf-token" content="' . $token . '">';
    }

    // ------------------------------------------------------------------
    // Verification
    // ------------------------------------------------------------------

    /**
     * Verify the CSRF token from the current request.
     *
     * Checks (in order):
     *   1. POST body (_csrf_token field)
     *   2. X-CSRF-TOKEN header
     *
     * Returns true if a valid, non-expired token is found.
     */
    public static function verify(): bool
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Safe methods do not need CSRF protection.
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $submitted = self::getSubmittedToken();

        if ($submitted === null || $submitted === '') {
            return false;
        }

        return self::validateToken($submitted);
    }

    /**
     * Verify or throw a ForbiddenException.
     *
     * @throws \Core\Exceptions\ForbiddenException
     */
    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            throw new \Core\Exceptions\ForbiddenException(
                'CSRF token mismatch. Please refresh the page and try again.'
            );
        }
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Extract the submitted CSRF token from the request.
     */
    private static function getSubmittedToken(): ?string
    {
        // 1. POST field.
        if (!empty($_POST[self::FIELD_NAME])) {
            return $_POST[self::FIELD_NAME];
        }

        // 2. X-CSRF-TOKEN header.
        $headerKey = 'HTTP_' . str_replace('-', '_', strtoupper(self::HEADER_NAME));
        if (!empty($_SERVER[$headerKey])) {
            return $_SERVER[$headerKey];
        }

        return null;
    }

    /**
     * Check the submitted token against all stored tokens.
     * Uses hash_equals for timing-safe comparison.
     */
    private static function validateToken(string $submitted): bool
    {
        self::ensureSession();

        $tokens = $_SESSION[self::SESSION_KEY] ?? [];

        foreach ($tokens as $index => $entry) {
            // Skip expired tokens.
            if ($entry['expires'] <= time()) {
                unset($_SESSION[self::SESSION_KEY][$index]);
                continue;
            }

            if (hash_equals($entry['token'], $submitted)) {
                // Token is valid. We do NOT remove it immediately so that
                // the same form can be re-submitted (e.g. validation errors
                // redirect back). Tokens expire naturally or get pruned.
                return true;
            }
        }

        // Reindex after potential removals.
        $_SESSION[self::SESSION_KEY] = array_values(
            $_SESSION[self::SESSION_KEY] ?? []
        );

        return false;
    }

    /**
     * Remove expired tokens and trim to MAX_TOKENS.
     */
    private static function prune(): void
    {
        $tokens = $_SESSION[self::SESSION_KEY] ?? [];

        // Remove expired.
        $tokens = array_filter(
            $tokens,
            fn (array $entry) => $entry['expires'] > time(),
        );

        // Keep only the most recent MAX_TOKENS.
        if (count($tokens) > self::MAX_TOKENS) {
            $tokens = array_slice($tokens, -self::MAX_TOKENS);
        }

        $_SESSION[self::SESSION_KEY] = array_values($tokens);
    }

    /**
     * Make sure a session is active.
     */
    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
