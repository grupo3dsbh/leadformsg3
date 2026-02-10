<?php

declare(strict_types=1);

namespace Core;

/**
 * Authentication Service
 *
 * Handles:
 *   - Session-based authentication for web requests
 *   - JWT token authentication for API requests
 *   - TOTP-based Two-Factor Authentication (2FA)
 *   - Password hashing and verification (bcrypt via password_hash)
 *   - Password reset token generation
 *   - Role / permission checks
 */
class Auth
{
    // ------------------------------------------------------------------
    // Constants
    // ------------------------------------------------------------------

    /** JWT algorithm (HMAC-SHA256). */
    private const JWT_ALGO = 'HS256';

    /** Default JWT lifetime in seconds (1 hour). */
    private const JWT_TTL = 3600;

    /** TOTP settings. */
    private const TOTP_PERIOD   = 30;   // seconds
    private const TOTP_DIGITS   = 6;
    private const TOTP_WINDOW   = 1;    // allow +/- 1 period

    /** Session keys. */
    private const SESSION_USER_KEY = '_auth_user';
    private const SESSION_2FA_KEY  = '_auth_2fa_pending';

    // ==================================================================
    // Session-based web authentication
    // ==================================================================

    /**
     * Attempt to log a user in with email + password.
     * Returns true on success, false on failure.
     *
     * The $userRecord must be an associative array (or null) returned by
     * your own user lookup (e.g. User::where('email', $email)->first()).
     */
    public static function attempt(string $email, string $password): bool
    {
        $db = Database::getInstance();

        $user = $db->table('users')
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();

        if ($user === false) {
            return false;
        }

        if (!self::verifyPassword($password, $user['password'])) {
            return false;
        }

        // If 2FA is enabled, set a pending flag instead of logging in fully.
        if (!empty($user['two_factor_secret'])) {
            $_SESSION[self::SESSION_2FA_KEY] = $user['id'];
            return true; // caller should check needs2FA() and redirect.
        }

        self::loginUser($user);

        return true;
    }

    /**
     * Manually log a user in (skip password verification).
     */
    public static function login(array $user): void
    {
        self::loginUser($user);
    }

    /**
     * Log the current user out.
     */
    public static function logout(): void
    {
        unset(
            $_SESSION[self::SESSION_USER_KEY],
            $_SESSION[self::SESSION_2FA_KEY],
        );

        session_regenerate_id(true);
    }

    /**
     * Get the currently authenticated user (from session).
     */
    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_USER_KEY] ?? null;
    }

    /**
     * Get the current user's ID.
     */
    public static function id(): ?int
    {
        $user = self::user();
        return $user !== null ? (int) $user['id'] : null;
    }

    /**
     * Check whether a user is authenticated.
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Check whether a user is a guest (not authenticated).
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * Whether a 2FA verification is pending for the current session.
     */
    public static function needs2FA(): bool
    {
        return isset($_SESSION[self::SESSION_2FA_KEY]);
    }

    /**
     * Check if the current user has the given role.
     */
    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return $user !== null && ($user['role'] ?? '') === $role;
    }

    /**
     * Check if the current user is a super-admin.
     */
    public static function isSuperAdmin(): bool
    {
        return self::hasRole('superadmin');
    }

    /**
     * Check if the current user is a client/admin of their own account.
     */
    public static function isClient(): bool
    {
        return self::hasRole('client') || self::hasRole('admin');
    }

    // ------------------------------------------------------------------
    // Internal: persist user into session
    // ------------------------------------------------------------------

    private static function loginUser(array $user): void
    {
        // Never store the password hash in the session.
        unset($user['password'], $user['two_factor_secret']);

        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_KEY] = $user;
        $_SESSION['_created'] = time();
    }

    // ==================================================================
    // Password hashing
    // ==================================================================

    /**
     * Hash a plain-text password.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a plain-text password against a hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check whether a hash needs rehashing (e.g. cost changed).
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // ==================================================================
    // Password reset tokens
    // ==================================================================

    /**
     * Generate a cryptographically secure password-reset token.
     */
    public static function generateResetToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash a reset token for safe storage.
     */
    public static function hashResetToken(string $token): string
    {
        return hash('sha256', $token);
    }

    // ==================================================================
    // JWT authentication (for API routes)
    // ==================================================================

    /**
     * Generate a JWT for the given payload (user data).
     */
    public static function generateJWT(array $payload, ?int $ttl = null): string
    {
        $secret = self::jwtSecret();
        $ttl    = $ttl ?? self::JWT_TTL;

        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => self::JWT_ALGO,
        ]));

        $now     = time();
        $payload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => bin2hex(random_bytes(16)),
        ]);

        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payloadEncoded}", $secret, true)
        );

        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    /**
     * Validate and decode a JWT. Returns the payload array on success
     * or null if the token is invalid / expired.
     */
    public static function validateJWT(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $secret          = self::jwtSecret();
        $expectedSig     = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $secret, true)
        );

        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }

        $decoded = json_decode(self::base64UrlDecode($payload), true);

        if (!is_array($decoded)) {
            return null;
        }

        // Check expiration.
        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            return null;
        }

        return $decoded;
    }

    /**
     * Extract a bearer token from the Authorization header.
     */
    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
                  ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                  ?? '';

        if (preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Authenticate via JWT (for API middleware). Returns the decoded
     * payload or null.
     */
    public static function authenticateAPI(): ?array
    {
        $token = self::bearerToken();

        if ($token === null) {
            return null;
        }

        return self::validateJWT($token);
    }

    // ------------------------------------------------------------------
    // JWT helpers
    // ------------------------------------------------------------------

    private static function jwtSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '';

        if ($secret === '') {
            throw new \RuntimeException(
                'JWT_SECRET environment variable is not set.'
            );
        }

        return $secret;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }

    // ==================================================================
    // Two-Factor Authentication (TOTP - RFC 6238)
    // ==================================================================

    /**
     * Generate a new TOTP secret (base32 encoded, 160-bit).
     */
    public static function generate2FASecret(int $length = 20): string
    {
        $bytes   = random_bytes($length);
        $base32  = self::base32Encode($bytes);

        return substr($base32, 0, 32); // 160 bits = 32 base32 chars
    }

    /**
     * Generate the provisioning URI for authenticator apps.
     *
     * otpauth://totp/LeadForm:user@example.com?secret=...&issuer=LeadForm&digits=6&period=30
     */
    public static function get2FAProvisioningUri(string $secret, string $email, string $issuer = 'LeadForm'): string
    {
        $label   = rawurlencode("{$issuer}:{$email}");
        $params  = http_build_query([
            'secret'  => $secret,
            'issuer'  => $issuer,
            'digits'  => self::TOTP_DIGITS,
            'period'  => self::TOTP_PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Verify a TOTP code against the secret, allowing a configurable window.
     */
    public static function verify2FA(string $secret, string $code): bool
    {
        $code = str_pad($code, self::TOTP_DIGITS, '0', STR_PAD_LEFT);

        for ($offset = -self::TOTP_WINDOW; $offset <= self::TOTP_WINDOW; $offset++) {
            $expected = self::generateTOTP($secret, time(), $offset);

            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Complete 2FA verification during login: verify the code, then
     * fully log the user in.
     */
    public static function complete2FA(string $code): bool
    {
        $userId = $_SESSION[self::SESSION_2FA_KEY] ?? null;

        if ($userId === null) {
            return false;
        }

        $db   = Database::getInstance();
        $user = $db->table('users')->where('id', $userId)->first();

        if ($user === false || empty($user['two_factor_secret'])) {
            return false;
        }

        if (!self::verify2FA($user['two_factor_secret'], $code)) {
            return false;
        }

        unset($_SESSION[self::SESSION_2FA_KEY]);
        self::loginUser($user);

        return true;
    }

    /**
     * Generate backup codes for 2FA recovery.
     *
     * @return list<string> Eight 8-character alphanumeric codes.
     */
    public static function generateBackupCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
        }

        return $codes;
    }

    // ------------------------------------------------------------------
    // TOTP internals (RFC 6238 / RFC 4226)
    // ------------------------------------------------------------------

    /**
     * Generate a TOTP code for the given secret and time.
     */
    private static function generateTOTP(string $base32Secret, int $timestamp, int $periodOffset = 0): string
    {
        $key       = self::base32Decode($base32Secret);
        $counter   = intdiv($timestamp, self::TOTP_PERIOD) + $periodOffset;
        $counterBin = pack('J', $counter); // unsigned 64-bit big-endian

        $hash = hash_hmac('sha1', $counterBin, $key, true);

        // Dynamic truncation (RFC 4226 section 5.4).
        $offset = ord($hash[19]) & 0x0F;
        $binary = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
             (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binary % (10 ** self::TOTP_DIGITS);

        return str_pad((string) $otp, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------
    // Base32 encoding / decoding (RFC 4648)
    // ------------------------------------------------------------------

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private static function base32Encode(string $data): string
    {
        $binary  = '';
        $encoded = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binary, 5);

        foreach ($chunks as $chunk) {
            $chunk    = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $index    = bindec($chunk);
            $encoded .= self::BASE32_ALPHABET[$index];
        }

        return $encoded;
    }

    private static function base32Decode(string $encoded): string
    {
        $encoded = strtoupper($encoded);
        $encoded = rtrim($encoded, '=');
        $binary  = '';

        foreach (str_split($encoded) as $char) {
            $index = strpos(self::BASE32_ALPHABET, $char);
            if ($index === false) {
                continue;
            }
            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes  = str_split($binary, 8);
        $output = '';

        foreach ($bytes as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $output .= chr((int) bindec($byte));
        }

        return $output;
    }
}
